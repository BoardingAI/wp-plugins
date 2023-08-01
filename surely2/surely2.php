<?php
/**
 * Plugin Name: *surely2
 * Description: Artificial intelligence content optimization plugin.
 * Version: 1.0
 * Author: BoardingArea
 * Author URI: Your Website
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue scripts and styles
function surely_enqueue_scripts($hook): void {
    if ('post.php' !== $hook && 'post-new.php' !== $hook) {
        return;
    }

    wp_enqueue_style('surely-styles', plugin_dir_url(__FILE__) . 'css/surely2.css');
    wp_enqueue_script('surely-scripts', plugin_dir_url(__FILE__) . 'js/surely2.js', array('jquery'), '1.0.0', true);
    wp_localize_script('surely-scripts', 'surely_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'system_messages' => array(
            'headline' => get_system_message('headline'),
            'keywords' => get_system_message('keywords'),
            'meta_description' => get_system_message('meta_description'),
            'introduction' => get_system_message('introduction'),
            'conclusion' => get_system_message('conclusion'),
            'image_alt_text' => get_system_message('image_alt_text'),
            'image_caption' => get_system_message('image_caption'),
            'call_to_action' => get_system_message('call_to_action'),
            'internal_links' => get_system_message('internal_links'),
            'external_links' => get_system_message('external_links'),
        )
    ));
}
add_action('admin_enqueue_scripts', 'surely_enqueue_scripts');

// Meta box creation
function surely_add_meta_box(): void {
    add_meta_box('surely-meta-box', '<div class="surely-logo-container"><img src="' . plugin_dir_url(__FILE__) . 'images/BoardingAI.jpg" alt="Company Logo" class="surely-logo" /></div>', 'surely_meta_box_callback', 'post', 'side', 'high');
}
add_action('add_meta_boxes', 'surely_add_meta_box');

// Meta box content
function surely_meta_box_callback(): void {
    // Add a nonce field for security
    wp_nonce_field('surely_nonce_action', 'surely_nonce');

    // Meta box HTML
    ?>
    <div id="surely-container">
        <p>Optimize your content with the help of *Surely, our AI assistant powered by BoardingAI. Improve various aspects of your blog posts for better reader engagement and search engine performance.</p>
        <label for="surely-aspect">Optimization Aspect:</label>
        <select id="surely-aspect">
            <option value="headline">Headline</option>
            <option value="keywords">Keywords</option>
            <option value="meta_description">Meta Description</option>
            <option value="introduction">Introduction</option>
            <option value="conclusion">Conclusion</option>
            <option value="image_alt_text">Image Alt Text</option>
            <option value="image_caption">Image Caption</option>
            <option value="call_to_action">Call to Action</option>
            <option value="internal_links">Internal Links</option>
            <option value="external_links">External Links</option>
        </select>
        <button id="surely-analyze" type="button">Analyze & Optimize Content</button>
        <div id="surely-loading" style="display:none;">Loading...</div>
        <label for="surely-response">Optimized Content:</label> <!-- Add this line -->
        <textarea id="surely-response" readonly></textarea>
        <button id="surely-insert" type="button">Insert Optimized Content</button>
    </div>
    <?php
}

// AJAX handler for GPT-4 API communication
function surely_ajax_handler(): void {
    check_ajax_referer('surely_nonce_action', 'security');

    $aspect = $_POST['aspect'];
    $post_data = $_POST['post_data'] ?? array();
    $post_title = $post_data['title'] ?? '';
    $post_content = $post_data['content'] ?? '';
    $previous_responses = isset($_POST['previous_responses']) ? (array) $_POST['previous_responses'] : array();

    // Set up the GPT-4 API request
    $api_key = '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';
    $api_url = 'https://api.openai.com/v1/chat/completions';
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    );

    $system_message_data = get_system_message($aspect);
    $system_message = $system_message_data["message"];
    $temperature = $system_message_data["temperature"];

    $messages = array(
        array('role' => 'system', 'content' => $system_message),
        array('role' => 'user', 'content' => "Headline: $post_title Blog Post: $post_content")
    );

    if ($previous_responses) {
        $messages = array_merge($messages, array_map(function ($response) {
            return array('role' => 'user', 'content' => $response);
        }, $previous_responses));
    }

    $data = array(
        'model' => 'gpt-3.5-turbo-16k-0613',
        'temperature' => $temperature,
        'messages' => $messages
    );

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        error_log('CURL Error: ' . $error_msg);
        echo json_encode(array('error' => array('message' => 'CURL Error: ' . $error_msg)));
    } else {
        // Log the raw response in the debug.log file
        error_log('Raw response: ' . $response);

        // Send the API response back to the JavaScript
        $response_data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo json_encode($response_data); // Replace wp_send_json with echo
        } else {
            echo json_encode(array('error' => array('message' => 'Invalid JSON response from the API.'))); // Replace wp_send_json_error with echo
        }
    }

    curl_close($ch);
    wp_die(); // Add wp_die() to terminate the AJAX request properly
}
add_action('wp_ajax_surely_optimize', 'surely_ajax_handler');

// AJAX handler for logging messages from JavaScript
function log_js_message(): void {
    check_ajax_referer('surely_nonce_action', 'security');
    $message = $_POST['message'] ?? '';
    if (!empty($message)) {
        error_log($message);
    }
    wp_die();
}
add_action('wp_ajax_log_js_message', 'log_js_message');

function get_system_message(string $aspect): array {
    $system_messages = array(
        'headline_score' => array("message" => "You are an AI BoardingArea headline analyzer and scorer.\n- Carefully analyze the provided headline and corresponding blog post.\n- Evaluate the headline based on the provided 'Headline Scoring Factors for AI Analysis' and their associated weights in %.\n- For each factor, provide actionable comments and analysis on what could be improved and describe your thought process on why you assigned it a given score. If the factor could be improved, provide suggestions and examples on how the factor could be improved. Do this before moving on to the next factor.\n- After completing the analysis of a factor, calculate the weighted score for that factor before moving on to the next one.\n- Each factor can be scored earning 0-5 points each, or N/A if the factor is not relevant.\n- Assign a score for each factor, taking into account the weight of each factor, and move on to the next one.\n- After assessing all factors, summarize your actionable comments into a coherent analysis.\n- Calculate the weighted total score and score percentage based on the points earned, the maximum possible points, and the weights of each factor.\n- Present the user with both the weighted total score and score percentage, along with your thorough overall analysis.\n\n'Headline Scoring Factors for AI Analysis':\n```\n1. Relevance and Clarity (score weight of 20%)\n   - Content representation\n   - User intent alignment\n   - Readability\n   - Timelessness\n   - Future update potential\n\n2. Emotional Appeal and Engagement (score weight of 20%)\n   - Emotional impact\n   - Sentiment\n   - Controversy or debate\n   - Open loops or teasers\n   - Encouragement of further exploration\n\n3. SEO, Social Media, and Virality (score weight of 40%)\n   - Keyword usage\n   - Platform-specific optimization\n   - Voice search optimization\n   - Shareability\n   - Elements that encourage sharing\n   - Exclusive or limited-time offers\n\n4. Audience Targeting and Brand Consistency (score weight of 10%)\n   - Target audience appeal\n   - Personalization\n   - Segmentation\n   - Branding\n   - Consistency\n   - Accuracy\n   - Expertise\n   - Trustworthiness\n\n5. Format, Structure, and Creativity (score weight of 5%)\n   - Questions or statements\n   - Content format indication\n   - Punctuation marks usage\n   - Originality\n   - Avoidance of clichés or overused phrases\n   - Headline length\n   - Use of numbers or data\n   - Power words or action verbs\n   - Mobile-friendliness\n\n6. Special Considerations for BoardingArea Content (score weight of 5%)\n   - Travel-related terms\n   - Discounts, deals, or rewards\n   - Specific travel destinations\n   - Travel experiences\n   - Localization\n```", "temperature" => 0),
        'headline_generate' => array("message" => "You are an AI BoardingArea headline generator. Generate a large list of potential headlines for the given content based on the provided Headline, Blog Post, and suggestions from the analysis.", "temperature" => 1),
        'headline_best' => array("message" => "You are an AI BoardingArea headline optimizer. Extract the best headline from the generated list and provide a final optimized headline.", "temperature" => 0.7),
        'keywords' => array("message" => "You are an AI BoardingArea keyword extractor. Extract the most relevant keywords from the given content.", "temperature" => 0),
        'keywords_refine' => array("message" => "You are an AI BoardingArea keyword refiner. Refine the extracted keywords and provide a single optimized keyword.", "temperature" => 0.7),
        'meta_description' => array("message" => "You are an AI BoardingArea meta description generator. Generate an optimized meta description for the given content.", "temperature" => 0),
        'introduction' => array("message" => "You are an AI BoardingArea introduction generator. Generate an engaging introduction for the given content.", "temperature" => 0),
        'introduction_refine' => array("message" => "You are an AI BoardingArea introduction refiner. Refine the generated introduction and provide a final optimized introduction.", "temperature" => 0.7),
        'conclusion' => array("message" => "You are an AI BoardingArea conclusion generator. Generate a compelling conclusion for the given content.", "temperature" => 0),
        'conclusion_refine' => array("message" => "You are an AI BoardingArea conclusion refiner. Refine the generated conclusion and provide a final optimized conclusion.", "temperature" => 0.7),
        'image_alt_text' => array("message" => "You are an AI BoardingArea image alt text generator. Generate descriptive and relevant alt text for the images in the given content.", "temperature" => 0),
        'image_caption' => array("message" => "You are an AI BoardingArea image caption generator. Generate engaging and relevant captions for the images in the given content.", "temperature" => 0),
        'call_to_action' => array("message" => "You are an AI BoardingArea call to action generator. Generate a persuasive call to action for the given content.", "temperature" => 0),
        'internal_links' => array("message" => "You are an AI BoardingArea internal link generator. Suggest relevant internal links to be added to the given content.", "temperature" => 0),
        'external_links' => array("message" => "You are an AI BoardingArea external link generator. Suggest relevant external links to be added to the given content.", "temperature" => 0),
    );

    return $system_messages[$aspect] ?? array("message" => "", "temperature" => 1);
}