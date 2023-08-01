<?php
/*
Plugin Name: AI Alt Tags 0727
Description: Automatically generate alt text for images with Artificial Intelligence (AI) for better accessibility and SEO. Batch process all posts, with WP CLI integration for faster execution.
Author: BoardingAI
Version: 1.0.0
*/

// Load the plugin's text domain for internationalization
function ai_alt_tags_load_textdomain() {
    load_plugin_textdomain( 'ai-alt-tags', false, 'ai-alt-tags-0727/languages/' );
}

// Set the locale based on the language option set in the plugin's settings
function ai_alt_tags_set_locale( $locale ) {
    $language = get_option( 'ai_alt_tags_language', 'en' );

    switch ( $language ) {
        case 'fr':
            return 'fr_FR';
        case 'de':
            return 'de_DE';
        case 'id':
            return 'id_ID';
        case 'ja':
            return 'ja_JP';
        case 'ko':
            return 'ko_KR';
        case 'pt-BR':
            return 'pt_BR';
        case 'es':
            return 'es_ES';
        case 'en':
        default:
            return $locale;
    }
}

// Add filters and actions for setting the locale and loading the text domain
add_filter( 'locale', 'ai_alt_tags_set_locale' );
add_action( 'plugins_loaded', 'ai_alt_tags_load_textdomain' );

// Add the plugin's page to the admin menu
add_action('admin_menu', 'ai_alt_tags_add_admin_menu');
function ai_alt_tags_add_admin_menu() {
    add_submenu_page('tools.php', 'AI Alt Tags', 'AI Alt Tags', 'manage_options', 'ai-alt-tags', 'ai_alt_tags__admin_page');
}

// Include the admin UI file when the plugin's page is accessed
function ai_alt_tags__admin_page ()
{
    require 'admin-ui.php';
}

// Function to generate a unique user ID based on the site URL
function ai_alt_tags__generate_user_id()
{
    // Get the site URL
    $site_url = get_site_url();

    // Parse the site URL to get the host
    $parsed_url = parse_url($site_url);
    $host = $parsed_url['host'];

    // Split the host into parts
    $host_parts = explode('.', $host);

    // Get the base name of the site
    $base_name = $host_parts[0];

    // Sanitize the base name
    $base_name = sanitize_title($base_name);

    // Return the base name
    return $base_name;
}

// Include the queue-system.php file to access the queue functions
require_once plugin_dir_path(__FILE__) . 'queue-system.php';

// Function to set the alt text for images in a post's content
function ai_alt_tags__set_post_content($pid, $batch_processing)
{
    // Global variable to prevent infinite loops when saving posts
    global $aiSaving;
    global $user_id;
    global $specific_version;
    global $api_version;

    // Generate a unique user ID for the current installation
    $user_id = ai_alt_tags__generate_user_id();

    // Check the queue status
    if (!ai_alt_tags__check_queue_status($user_id)) {
        // Add the user to the queue if the Azure API is in use
        ai_alt_tags__add_to_queue($user_id);
        return;
    }

    // If the function is called with an array of post IDs, call the function for each post ID
    if (is_array($pid)) {
        foreach ($pid as $single_pid) {
            ai_alt_tags__set_post_content($single_pid, $batch_processing);
        }
        return;
    }

    // If the function is called for a post that is currently being saved, return to prevent an infinite loop
    if ($aiSaving == $pid) {
        return;
    }

    // Get the API key and version from the plugin's settings
    $api_key = get_option('ai_alt_tags_api_key');
    $api_version = get_option('ai_alt_tags_api_version');
    $hasSettings = !empty($api_key) && !empty($api_version);

    // If the API key or version is not set, return false
    if (!$hasSettings) {
        return false;
    }

    // Get the alt text versions for the images in the post
    $imageSrcAltVersions = get_post_meta($pid, 'ai_alt_tags_alt_versions', true) ?: [];
    $imageSrcAltVersionsUpdates = [];

    // Get the post object
    $post = get_post($pid);

    // Create a new DOMDocument object
    $dom_doc = new DOMDocument();

    // Load the post's content into the DOMDocument object
    if (!empty($post->post_content)) {
        @$dom_doc->loadHTML($post->post_content, LIBXML_HTML_NODEFDTD);
    }

    // Get all the image tags in the post's content
    $image_tags = $dom_doc->getElementsByTagName('img');
    $changes = 0;

    // Loop through each image tag
    foreach ($image_tags as $index => $image_tag) {
        // Get the src and alt attributes of the image tag
        $src = $image_tag->getAttribute('src');
        $alt = $image_tag->getAttribute('alt');
        // Get the version of the alt text for the image
        $version = isset($imageSrcAltVersions[$src]) ? $imageSrcAltVersions[$src] : null;

        // Log the image data before processing
        error_log("DEBUG [Post ID: $pid] [Image $index] Image data before processing: " . json_encode(['src' => $src, 'alt' => $alt, 'version' => $version]));

        // If the batch processing option is set to 'default' and the image already has alt text or the alt text version matches the API version, skip this image
        if ($batch_processing === 'default' && (!empty($alt) || (!empty($version) && $version == $api_version))) {
            continue;
        } elseif ($batch_processing === 'overwrite_all') {
            // If the batch processing option is set to 'overwrite_all', do nothing and continue processing the image
        } elseif ($batch_processing === 'overwrite_specific' && $version !== $specific_version) {
            // If the batch processing option is set to 'overwrite_specific' and the alt text version does not match the specific version, skip this image
            continue;
        } elseif ($batch_processing === 'overwrite_empty' && (isset($alt) && $alt !== '')) {
            // If the batch processing option is set to 'overwrite_empty' and the image already has alt text, skip this image
            continue;
        }

        // Store the image data before processing
        $image_data_before = [
            'src' => $src,
            'alt' => $alt,
            'version' => $version
        ];

        // If the alt text for the image source has not been fetched yet, fetch it
        if (!isset($imgSrcs[$src])) {
            $imgSrcs[$src] = ai_alt_tags__get_alt($src);
        }

        // Store the image data after processing
        $image_data_after = [
            'src' => $src,
            'alt' => $imgSrcs[$src],
            'version' => $api_version
        ];

        // If the fetched alt text is not empty, set the alt attribute of the image tag to the fetched alt text, increment the changes counter, and log the generated alt text
        if (isset($imgSrcs[$src]) && !empty($imgSrcs[$src])) {
            $imageSrcAltVersionsUpdates[$src] = $api_version;
            $image_tag->setAttribute('alt', $imgSrcs[$src]);
            $changes++;

            error_log("INFO [Post ID: $pid] [Image $index] Generated alt text for image $src: " . $imgSrcs[$src]);
        }

        // Log the image data after processing
        error_log("DEBUG [Post ID: $pid] [Image $index] Image data after processing: " . json_encode($image_data_after));

        // If the alt text version for the image source has been updated, update it in the array of alt text versions
        if (isset($imageSrcAltVersionsUpdates[$src])) {
            $imageSrcAltVersionsUpdates[$src] = $api_version;
        }
    }

    // If any changes were made, update the post's content and the alt text versions for the images in the post
    if ($changes > 0) {
        $html = str_replace(['<html>', '</html>', '<body>', '</body>'], '', $dom_doc->saveHTML());
        wp_update_post([
            'ID' => $pid,
            'post_content' => $html
        ]);

        update_post_meta($pid, 'ai_alt_tags_alt_versions', $imageSrcAltVersionsUpdates);
    }

    // Log the number of images updated in the post
    error_log("INFO [Post ID: $pid] Finished processing post, updated $changes images.");

    // Reset the global variable to prevent infinite loops when saving posts
    $aiSaving = null;

    // Remove the user from the queue after using the Azure API
    ai_alt_tags__remove_from_queue($user_id);

    return $changes;
}

// Function to get the alt text for an image from the Microsoft Vision API
function ai_alt_tags__get_alt($src)
{
    // Declare the variable as global inside the function
    global $api_version;

    // Generate a unique user ID for the current installation
    $user_id = ai_alt_tags__generate_user_id();

    // Check the queue status
    if (!ai_alt_tags__check_queue_status($user_id)) {
        // Add the user to the queue if the Azure API is in use
        ai_alt_tags__add_to_queue($user_id);

        // Return false as the user is not at the front of the queue
        return false;
    }

    // Get the API key and language from the plugin's settings
    $api_key = get_option('ai_alt_tags_api_key');
    $language = get_option('ai_alt_tags_language', 'en');
    $hasSettings = !empty($api_key) && !empty($api_version);

    // If the API key or version is not set, return false
    if (!$hasSettings) {
        // Remove the user from the queue before returning
        ai_alt_tags__remove_from_queue($user_id);

        return false;
    }

    // If the image source is a relative URL, prepend the home URL to it
    if (strpos($src, '://') === 0) {
        $src = home_url($src);
    }

    // Determine if the 'Tags' visual feature should be used based on the language
    $additional_languages = ['de', 'fr', 'ko', 'id', 'pt-BR'];
    $use_tags_visual_feature = in_array($language, $additional_languages);

    // Set the request URL based on the API version
    if ($api_version === 'v3.2') {
        $visual_features = $use_tags_visual_feature ? 'Tags' : 'Description';
        $request_url = 'https://auto-alt-text.cognitiveservices.azure.com/vision/v3.2/analyze?visualFeatures=' . $visual_features . '&language=' . $language . '&model-version=latest';
    } else {
        $request_url = 'https://auto-alt-text.cognitiveservices.azure.com/computervision/imageanalysis:analyze?api-version=2023-02-01-preview&features=caption&language=' . $language . '&gender-neutral-caption=False';
    }

    // Send a POST request to the Microsoft Vision API
    $response = wp_remote_post($request_url, array(
        'headers' => [
            'Content-Type' => 'application/json',
            'Ocp-Apim-Subscription-Key' => $api_key,
        ],
        'body' => json_encode(['url' => $src]),
    ));

    // If the request resulted in an error, log the error message and return false
    if (is_wp_error($response)) {
        error_log("Error calling Microsoft Vision API: " . $response->get_error_message());

        // Remove the user from the queue before returning
        ai_alt_tags__remove_from_queue($user_id);

        return false;
    }

    // Decode the response body
    $result = json_decode(wp_remote_retrieve_body($response), true);

    // If the response contains an error, log the error message and return a localized error message
    if (isset($result['error'])) {
        $error_code = $result['error']['code'];
        switch ($error_code) {
            case 'InvalidRequest':
                error_log("Invalid request error from Microsoft Vision API: " . $result['error']['message']);
                return __('Invalid request error occurred.', 'ai-alt-tags');
            case 'InvalidImageUrl':
                error_log("Invalid image URL error from Microsoft Vision API: " . $result['error']['message']);
                return __('Invalid image URL error occurred.', 'ai-alt-tags');
            case 'InvalidImageSize':
                error_log("Invalid image size error from Microsoft Vision API: " . $result['error']['message']);
                return __('Invalid image size error occurred.', 'ai-alt-tags');
            case 'NotSupportedVisualFeature':
                error_log("Not supported visual feature error from Microsoft Vision API: " . $result['error']['message']);
                return __('Not supported visual feature error occurred.', 'ai-alt-tags');
            case 'InvalidImageFormat':
                error_log("Invalid image format error from Microsoft Vision API: " . $result['error']['message']);
                return __('Invalid image format error occurred.', 'ai-alt-tags');
            case 'NotSupportedImage':
                error_log("Unsupported image error from Microsoft Vision API: " . $result['error']['message']);
                return __('Unsupported image error occurred.', 'ai-alt-tags');
            case 'NotSupportedLanguage':
                error_log("Unsupported language error from Microsoft Vision API: " . $result['error']['message']);
                return __('Unsupported language error occurred.', 'ai-alt-tags');
            case 'BadArgument':
                error_log("Bad argument error from Microsoft Vision API: " . $result['error']['message']);
                return __('Bad argument error occurred.', 'ai-alt-tags');
            case 'Timeout':
                error_log("Timeout error from Microsoft Vision API: " . $result['error']['message']);
                return __('Timeout error occurred.', 'ai-alt-tags');
            case 'InternalServerError':
                error_log("Internal server error from Microsoft Vision API: " . $result['error']['message']);
                return __('Internal server error occurred.', 'ai-alt-tags');
            default:
                error_log("Unknown error from Microsoft Vision API: " . $result['error']['message']);
                return __('An unknown error occurred.', 'ai-alt-tags');
        }
    }

    // If the API version is 'v3.2', get the alt text from the 'tags' or 'description.captions[0].text' key in the response
    if ($api_version === 'v3.2') {
        if ($use_tags_visual_feature) {
            if (isset($result['tags']) && is_array($result['tags'])) {
                $tags = array_map(function ($tag) {
                    return $tag['name'];
                }, $result['tags']);
                return implode(', ', $tags);
            } else {
                error_log("Error: Microsoft Vision API v3.2 response does not contain the expected 'tags' key.");
                error_log("API response: " . json_encode($result));
                error_log('Image Source: ' . $src);
                return false;
            }
        } else {
            if (isset($result['description']['captions'][0]['text'])) {
                return $result['description']['captions'][0]['text'];
            } else {
                error_log("Error: Microsoft Vision API v3.2 response does not contain the expected 'description.captions[0].text' key.");
                error_log("API response: " . json_encode($result));
                error_log('Image Source: ' . $src);
                return false;
            }
        }
    } else {
        // If the API version is not 'v3.2', get the alt text from the 'captionResult' key in the response
        if (isset($result['captionResult']['text'])) {
            return $result['captionResult']['text'];
        } else {
            error_log("Error: Microsoft Vision API v4.0 response does not contain the expected 'captionResult' key.");
            error_log("API response: " . json_encode($result));
            error_log('Image Source: ' . $src);
            return false;
        }
    }

    // After the API call and processing is done, remove the user from the queue
    ai_alt_tags__remove_from_queue($user_id);
}

// Function to check if an image meets the requirements for the Microsoft Vision API
function ai_alt_tags__check_image_requirements($src, $api_version)
{
    // Get the upload directory
    $upload_dir = wp_upload_dir();
    // Get the path to the image file
    $image_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $src);

    // Get the image details
    $image_info = wp_getimagesize($image_path);
    if ($image_info === false) {
        return __('Unable to get image details', 'ai-alt-tags');
    }

    // Get the image dimensions, type, and file size
    list($width, $height, $type) = $image_info;
    $filesize = wp_filesize($image_path);

    // Map the image type constant to a string
    $image_types = [
        IMAGETYPE_GIF => 'GIF',
        IMAGETYPE_JPEG => 'JPEG',
        IMAGETYPE_PNG => 'PNG',
        IMAGETYPE_SWF => 'SWF',
        IMAGETYPE_PSD => 'PSD',
        IMAGETYPE_BMP => 'BMP',
        IMAGETYPE_TIFF_II => 'TIFF_II',
        IMAGETYPE_TIFF_MM => 'TIFF_MM',
        IMAGETYPE_JPC => 'JPC',
        IMAGETYPE_JP2 => 'JP2',
        IMAGETYPE_JPX => 'JPX',
        IMAGETYPE_JB2 => 'JB2',
        IMAGETYPE_SWC => 'SWC',
        IMAGETYPE_IFF => 'IFF',
        IMAGETYPE_WBMP => 'WBMP',
        IMAGETYPE_XBM => 'XBM',
        IMAGETYPE_ICO => 'ICO',
        IMAGETYPE_WEBP => 'WEBP',
    ];

    $image_type = isset($image_types[$type]) ? $image_types[$type] : 'Unknown';

    // Log the image details
    error_log("Image source: $src, width: $width, height: $height, type: $image_type, file size: $filesize bytes");

    // Set the maximum file size and valid image types based on the API version
    $max_filesize = $api_version === 'v4.0' ? 20 * 1024 * 1024 : 4 * 1024 * 1024;
    $valid_types = $api_version === 'v4.0' ? [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_BMP, IMAGETYPE_WEBP, IMAGETYPE_ICO, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM] : [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_BMP];

    // If the image file size exceeds the maximum allowed size, return an error message
    if ($filesize > $max_filesize) {
        return __('Image filesize exceeds the maximum allowed size', 'ai-alt-tags');
    }

    // If the image dimensions are outside the allowed range, return an error message
    if ($width < 50 || $height < 50 || $width > 16000 || $height > 16000) {
        return __('Image dimensions are outside the allowed range', 'ai-alt-tags');
    }

    // If the image type is not supported, return an error message
    if (!in_array($type, $valid_types)) {
        return __('Image type is not supported', 'ai-alt-tags');
    }

    return '';
}

// AJAX callback function to set alt tags for images in posts
function post_ajax__set_img_alt_tags_ai()
{
    // Check if the post IDs are set in the AJAX request
    if (isset($_POST['pid'])) {
        $post_ids = $_POST['pid'];
        // Filter and sanitize the post IDs
        $valid_post_ids = array_filter($post_ids, 'is_numeric');
        $valid_post_ids = array_map('intval', $valid_post_ids);
        $valid_post_ids = array_unique($valid_post_ids);

        // Get the batch processing option from the plugin's settings
        $batch_processing = get_option('ai_alt_tags_batch_processing', 'default');

        // Generate a unique user ID for the current installation
        $user_id = ai_alt_tags__generate_user_id();

        // Add the user to the queue
        ai_alt_tags__add_to_queue($user_id);

        // Process each valid post ID
        foreach ($valid_post_ids as $post_id) {
            // Check the queue status
            if (ai_alt_tags__check_queue_status($user_id)) {
                ai_alt_tags__set_post_content($post_id, $batch_processing);
                // Remove the user from the queue after using the Azure API
                ai_alt_tags__remove_from_queue($user_id);
            }
        }

        // Return the number of posts processed as a JSON response
        return wp_send_json(count($valid_post_ids));
    }

    // Return an error response if the post IDs are not set
    return wp_send_json_error();
}

// Function to set image alt tags when a post is saved
function post_saved__set_img_alt_tags_ai($pid)
{
    // Check if the post is a revision, if it is, return and do nothing
    if (wp_is_post_revision($pid)) {
        return;
    }

    // Get the option for AI alt tags post editing, default is '1' (enabled)
    $post_editing = get_option('ai_alt_tags_post_editing', '1');
    // If post editing is enabled
    if ($post_editing == '1') {
        // Get the option for AI alt tags batch processing, default is 'default'
        $batch_processing = get_option('ai_alt_tags_batch_processing', 'default');
        // Call the function to set alt tags in the post content
        ai_alt_tags__set_post_content($pid, $batch_processing);
    }
}

// Action hook for the AJAX callback function
add_action( 'wp_ajax_ai_alt_tag', 'post_ajax__set_img_alt_tags_ai');

// Action hook to set alt tags for images when a post is saved
add_action('save_post', 'post_saved__set_img_alt_tags_ai');

// WP CLI command for batch processing all posts
if (class_exists('WP_CLI')) {
    class aiAltTagCli extends WP_CLI_Command {
        public function batch() {
            // Generate a unique user ID for the current installation
            $user_id = ai_alt_tags__generate_user_id();

            // Check the queue status
            if (ai_alt_tags__check_queue_status($user_id)) {
                // Get the batch processing option and specific version from the plugin's settings
                $batch_processing = get_option('ai_alt_tags_batch_processing', 'default');
                $specific_version = get_option('ai_alt_tags_specific_version');

                // Get all the posts
                $posts = get_posts( [
                    'post_type'      => 'post',
                    'fields'         => 'ids',
                    'posts_per_page' => -1
                ] );

                // Get the total number of posts
                $total_posts = wp_count_posts()->publish;
                $total_images = 0;
                error_log("INFO Total posts to be processed: $total_posts");

                error_log("INFO BoardingAI Alt Tags Initiated");

                // Get the API key, version, post editing option, and language from the plugin's settings
                $api_key = get_option('ai_alt_tags_api_key');
                $api_version = get_option('ai_alt_tags_api_version');
                $post_editing = get_option('ai_alt_tags_post_editing', '1');
                $language = get_option('ai_alt_tags_language', 'en');

                error_log("DEBUG Current settings: API Key=$api_key, API Version=$api_version, Post Editing=$post_editing, Batch Processing=$batch_processing, Specific Version=$specific_version, Language=$language");

                error_log("INFO Processing alt tags for all posts...");

                // Create a progress bar for the batch processing
                $progress = \WP_CLI\Utils\make_progress_bar('Setting alt tags in ' . $total_posts . ' post\'s content...', $total_posts);

                // Loop through each post
                foreach ($posts as $pid) {
                    // Get the post object
                    $post = get_post($pid);
                    // Create a new DOMDocument object
                    $dom_doc = new DOMDocument();
                    // Load the post's content into the DOMDocument object
                    if (!empty($post->post_content)) {
                        @$dom_doc->loadHTML($post->post_content, LIBXML_HTML_NODEFDTD);
                    }
                    // Get all the image tags in the post's content
                    $image_tags = $dom_doc->getElementsByTagName('img');
                    // Count the total number of images
                    $total_images += $image_tags->length;
                    // Set the alt tags for the images in the post
                    ai_alt_tags__set_post_content($pid, $batch_processing);
                    // Increment the progress bar
                    $progress->tick();
                }

                // Finish the progress bar
                $progress->finish();

                // Log the total number of images processed
                error_log("INFO Total images to be processed: $total_images");

                // Remove the user from the queue after using the Azure API
                ai_alt_tags__remove_from_queue($user_id);
            } else {
                // Add the user to the queue if the Azure API is in use
                ai_alt_tags__add_to_queue($user_id);
            }
        }
    }

    // Register the WP CLI command
    if (class_exists('WP_CLI_Command')) {
        WP_CLI::add_command('ai_alt', 'aiAltTagCli');
    }
}

// Activation hook to set the Azure language to the site language
register_activation_hook(__FILE__, 'set_azure_language_to_site_language');
function set_azure_language_to_site_language() {
    $locale = get_locale();
    $azure_language = map_locale_to_azure_language($locale);
    update_option('ai_alt_tags_language', $azure_language);
}

// Action hook to update the Azure language when the site language is updated
add_action('update_option_WPLANG', 'update_azure_language_when_site_language_is_updated', 10, 2);
function update_azure_language_when_site_language_is_updated($old_value, $value) {
    $azure_language = map_locale_to_azure_language($value);
    update_option('ai_alt_tags_language', $azure_language);
}

// Function to map the site locale to the Azure language
function map_locale_to_azure_language($locale) {
    switch ($locale) {
        case 'fr_FR':
            return 'fr';
        case 'de_DE':
            return 'de';
        case 'id_ID':
            return 'id';
        case 'ja_JP':
            return 'ja';
        case 'ko_KR':
            return 'ko';
        case 'pt_BR':
            return 'pt-BR';
        case 'es_ES':
            return 'es';
        case 'en_US':
        default:
            return 'en';
    }
}

// Enqueue the admin script for the plugin's page
function ai_alt_tags_enqueue_scripts($hook) {
    if ('tools_page_ai-alt-tags' !== $hook) {
        return;
    }

    // Enqueue the script
    wp_enqueue_script('ai_alt_tags_admin_script', plugins_url('admin-script.js', __FILE__), array('jquery'), '1.0', true);

    // Include the queue-system.php file
    require_once plugin_dir_path(__FILE__) . 'queue-system.php';

    // Generate a unique user ID for the current installation
    $user_id = ai_alt_tags__generate_user_id();

    // Pass the user ID to the JavaScript code
    wp_localize_script('ai_alt_tags_admin_script', 'aiAltTags', array(
        'userId' => $user_id,
    ));
}
add_action('admin_enqueue_scripts', 'ai_alt_tags_enqueue_scripts');