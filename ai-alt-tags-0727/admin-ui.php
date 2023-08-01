<?php
// Check if WordPress is running, if not, exit the script
if (!defined('ABSPATH')) {
  exit;
}

// Initialize a variable to check if settings have been updated
$settings_updated = false;

// Check if the nonce is set and valid, then update the options in the database
if (isset($_POST['ai_alt_tag_options_nonce']) && wp_verify_nonce($_POST['ai_alt_tag_options_nonce'], 'ai_alt_tag_options_nonce')) {
  // Update the API key
  update_option('ai_alt_tags_api_key', sanitize_text_field($_POST['api_key']));
  
  // Update the API version
  $api_version_value = isset($_POST['api_version']) ? $_POST['api_version'] : $_POST['api_version_hidden'];
  update_option('ai_alt_tags_api_version', $api_version_value);
  
  // Update the language
  if (isset($_POST['custom_language'])) {
    update_option('ai_alt_tags_language', sanitize_text_field($_POST['custom_language']));
  } else {
    update_option('ai_alt_tags_language', $_POST['language']);
  }
  
  // Update the post editing option
  update_option('ai_alt_tags_post_editing', isset($_POST['post_editing']) ? '1' : '0');
  
  // Update the batch processing option
  update_option('ai_alt_tags_batch_processing', $_POST['batch_processing']);
  
  // Update the specific version option
  update_option('ai_alt_tags_specific_version', $_POST['specific_version'] ?? '');
  
  // Set the settings updated flag to true
  $settings_updated = true;
}

// Get the options from the database
$api_key = get_option('ai_alt_tags_api_key', '7d777b1e7c934f22a6d4ff662d8fd865');
$api_version = get_option('ai_alt_tags_api_version', 'v4.0');
$post_editing = get_option('ai_alt_tags_post_editing', '1');
$batch_processing = get_option('ai_alt_tags_batch_processing', 'default');
$specific_version = get_option('ai_alt_tags_specific_version');
$language = get_option('ai_alt_tags_language', 'en');

// Check if the settings are set
$hasSettings = !empty($api_key) && !empty($api_version);

// Initialize variables for image analysis
$totalImages = 0;
$imagesMissingAltText = 0;

// If settings are set, get all posts and analyze the images
if ($hasSettings) {
  $posts = get_posts([
    'post_type' => 'post',
    'posts_per_page' => -1,
  ]);

  // Loop through each post
  foreach ($posts as $post) {
    $dom_doc = new DOMDocument();
    if (!empty($post->post_content)) {
      @$dom_doc->loadHTML($post->post_content, LIBXML_HTML_NODEFDTD);
    }
    $image_tags = $dom_doc->getElementsByTagName('img');
    $totalImages += $image_tags->length;

    // Loop through each image tag
    foreach ($image_tags as $image_tag) {
      $alt = $image_tag->getAttribute('alt');
      if (empty($alt)) {
        $imagesMissingAltText++;
      }
    }
  }
}

// Start of HTML output
?>

<!-- Container for the admin UI -->
<div class="container" style="width: calc(100% - 2rem);">
  <!-- Title of the page -->
  <h1><?php _e('AI Alt Tags', 'ai-alt-tags'); ?></h1>
  
  <!-- Description of the plugin -->
  <p><?php _e('AI Alt Tags is an advanced feature integrated into the BoardingPack plugin that enhances website accessibility and SEO by automatically generating image alt tags using Microsoft Azure\'s Computer Vision API. Streamlining the process of adding descriptive alt tags, this feature supports batch processing of all posts and offers WP CLI integration for faster execution, ensuring content is accessible and optimized for search engines while intelligently analyzing images to generate accurate, meaningful alt tags that contribute to your website\'s overall performance.', 'ai-alt-tags'); ?></p>
  
  <!-- Display a success message if settings have been updated -->
  <?php if ($settings_updated) : ?>
  <div id="setting-error-settings_updated" class="notice notice-success settings-error is-dismissible" style="margin: 1rem 0;"> 
    <p><strong><?php _e('Settings saved.', 'ai-alt-tags'); ?></strong></p>
    <button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e('Dismiss this notice.', 'ai-alt-tags'); ?></span></button>
  </div>
  <?php endif; ?>
  
  <!-- Start of the form for the settings -->
  <hr>
  <form method="POST">
    <!-- Azure Settings -->
    <h4 class="settings-expander">
      <?php _e('Azure Settings', 'ai-alt-tags'); ?>
    </h4>
    <div style="display: none;">
      <!-- API Key -->
      <p>
        <label><?php _e('API Key', 'ai-alt-tags'); ?></label><br>
        <input type="password" placeholder="<?php esc_attr_e('Azure API Key', 'ai-alt-tags'); ?>" name="api_key" value="<?php echo esc_attr($api_key); ?>" />
        <span class="tooltip">
          <i class="info-icon">i</i>
          <span class="tooltip-text"><?php _e('Enter the API Key provided by Azure for the Computer Vision service.', 'ai-alt-tags'); ?></span>
        </span>
      </p>
      
      <!-- Language -->
      <p>
        <label><?php _e('Language', 'ai-alt-tags'); ?></label><br>
        <select name="language">
          <option value="en" <?php selected($language, 'en'); ?>><?php _e('English', 'ai-alt-tags'); ?></option>
          <option value="fr" <?php selected($language, 'fr'); ?>><?php _e('French', 'ai-alt-tags'); ?></option>
          <option value="de" <?php selected($language, 'de'); ?>><?php _e('German', 'ai-alt-tags'); ?></option>
          <option value="id" <?php selected($language, 'id'); ?>><?php _e('Indonesian', 'ai-alt-tags'); ?></option>
          <option value="ja" <?php selected($language, 'ja'); ?>><?php _e('Japanese', 'ai-alt-tags'); ?></option>
          <option value="ko" <?php selected($language, 'ko'); ?>><?php _e('Korean', 'ai-alt-tags'); ?></option>
          <option value="pt-BR" <?php selected($language, 'pt-BR'); ?>><?php _e('Portuguese (Brazilian)', 'ai-alt-tags'); ?></option>
          <option value="es" <?php selected($language, 'es'); ?>><?php _e('Spanish', 'ai-alt-tags'); ?></option>
        </select>
        <span class="tooltip">
          <i class="info-icon">i</i>
          <span class="tooltip-text"><?php _e('Select the language for the generated alt text. Note that not all languages are supported by both API versions.', 'ai-alt-tags'); ?></span>
        </span>
      </p>
      
      <!-- API Version -->
      <p>
        <label><?php _e('API Version', 'ai-alt-tags'); ?></label><br>
        <select name="api_version">
          <option value="v3.2" <?php selected($api_version, 'v3.2'); ?>>v3.2</option>
          <option value="v4.0" <?php selected($api_version, 'v4.0'); ?>>v4.0</option>
        </select>
        <input type="hidden" name="api_version_hidden" value="<?php echo $api_version; ?>">
        <span class="tooltip">
          <i class="info-icon">i</i>
          <span class="tooltip-text"><?php _e('Select the API version to use for generating alt tags. Choose between v3.2 and v4.0.', 'ai-alt-tags'); ?></span>
        </span>
      </p>
    </div>

    <!-- Post Editing Settings -->
    <hr>
    <h4 class="settings-expander">
      <?php _e('Post Editing Settings', 'ai-alt-tags'); ?>
    </h4>
    <div style="display: none;">
      <p>
        <input type="checkbox" id="post_editing" name="post_editing" value="1" <?php checked($post_editing, '1'); ?>>
        <label for="post_editing"><?php _e('Automatically generate alt tags on post save (recommended)', 'ai-alt-tags'); ?></label>
        <span class="tooltip">
          <i class="info-icon">i</i>
          <span class="tooltip-text"><?php _e('When enabled, the feature will automatically add or update alt tags for images in a post when you save or update the post.', 'ai-alt-tags'); ?></span>
        </span>
      </p>
    </div>

    <!-- Batch Processing Settings -->
    <hr>
    <h4 class="settings-expander">
      <?php _e('Batch Processing Settings', 'ai-alt-tags'); ?>
    </h4>
    <div style="display: none;">
      <!-- Default Behavior -->
      <p>
        <input type="radio" id="batch_default" name="batch_processing" value="default" <?php checked($batch_processing, 'default'); ?>>
        <label for="batch_default"><?php _e('Default Behavior (recommended)', 'ai-alt-tags'); ?></label>
        <span class="tooltip">
          <i class="info-icon">i</i>
          <span class="tooltip-text"><?php _e('Adds alt tags to images that don\'t have them or have outdated ones. Existing alt tags remain unchanged.', 'ai-alt-tags'); ?></span>
        </span>
      </p>
      
      <!-- Overwrite all existing alt tags -->
      <p>
        <input type="radio" id="batch_overwrite_all" name="batch_processing" value="overwrite_all" <?php checked($batch_processing, 'overwrite_all'); ?>>
        <label for="batch_overwrite_all"><?php _e('Overwrite all existing alt tags', 'ai-alt-tags'); ?></label>
        <span class="tooltip">
          <i class="info-icon">i</i>
          <span class="tooltip-text"><?php _e('Generates new alt tags for all images during batch processing, replacing any existing alt tags.', 'ai-alt-tags'); ?></span>
        </span>
      </p>
      
      <!-- Overwrite alt tags from a specific version -->
      <p>
        <input type="radio" id="batch_overwrite_specific" name="batch_processing" value="overwrite_specific" <?php checked($batch_processing, 'overwrite_specific'); ?>>
        <label for="batch_overwrite_specific"><?php _e('Overwrite alt tags from a specific version', 'ai-alt-tags'); ?></label>
        <span class="tooltip">
          <i class="info-icon">i</i>
          <span class="tooltip-text"><?php _e('Updates alt tags only for images that were generated by a specific version of the API during batch processing.', 'ai-alt-tags'); ?></span>
        </span>
        <select name="specific_version" <?php echo $batch_processing !== 'overwrite_specific' ? 'disabled' : ''; ?>>
          <option value="v3.2" <?php selected($specific_version, 'v3.2'); ?>>v3.2</option>
          <option value="v4.0" <?php selected($specific_version, 'v4.0'); ?>>v4.0</option>
        </select>
      </p>
      
      <!-- Overwrite empty alt tags only -->
      <p>
        <input type="radio" id="batch_overwrite_empty" name="batch_processing" value="overwrite_empty" <?php checked($batch_processing, 'overwrite_empty'); ?>>
        <label for="batch_overwrite_empty"><?php _e('Overwrite empty alt tags only', 'ai-alt-tags'); ?></label>
        <span class="tooltip">
          <i class="info-icon">i</i>
          <span class="tooltip-text"><?php _e('Generates alt tags only for images that currently have empty alt tags during batch processing, regardless of their version.', 'ai-alt-tags'); ?></span>
        </span>
      </p>
    </div>

    <!-- Save Settings Button -->
    <hr>
    <?php wp_nonce_field( 'ai_alt_tag_options_nonce', 'ai_alt_tag_options_nonce' ); ?>
    <button type="submit" class="button button-primary save-settings-footer">
      <?php _e('Save Settings', 'ai-alt-tags'); ?>
    </button>
  </form>

  <!-- Batch Process All Posts Button -->
  <?php if (!empty($api_key) && !empty($api_version)) : ?>
  <hr>
  <div id="batch-ai-progress-wrapper">
    <div id="batch-ai-progress">
      <span class="animated-gradient"></span>
    </div>
    <p><strong><?php _e('DO NOT CLOSE THIS WINDOW.', 'ai-alt-tags'); ?></strong><br><?php _e('Processing alt tags', 'ai-alt-tags'); ?> <span class="progress-text"></span>% <?php _e('completed...', 'ai-alt-tags'); ?></p>
  </div>
  <p>
    <button id="batch-ai-alt" class="button button-primary">
      <?php _e('Batch Process All Posts', 'ai-alt-tags'); ?>
    </button>
    </p>

  <!-- Image Analysis Report -->
  <?php if ($hasSettings) : ?>
  <hr>
  <div id="image-analysis-wrapper">
    <h3><?php _e('Image Analysis Report', 'ai-alt-tags'); ?></h3>
    <div id="image-analysis-report">
      <p>
        <strong><?php _e('Total Images:', 'ai-alt-tags'); ?></strong> <span id="total-images"><?php echo $totalImages; ?></span>
      </p>
      <p>
        <strong><?php _e('Images missing alt text:', 'ai-alt-tags'); ?></strong> <span id="missing-alt-text"><?php echo $imagesMissingAltText; ?></span>
      </p>
    </div>
  </div>
  <?php endif; ?>

  <!-- Total Images Processed To Date Across Network -->
  <hr>
  <div id="total-images-processed-wrapper">
    <h3><?php _e('Total Images Processed To Date Across Network', 'ai-alt-tags'); ?></h3>
    <div id="total-images-processed">
      <p>
        <span id="total-images-processed-number"><?php echo $totalImagesProcessed; ?></span>
      </p>
    </div>
  </div>

  <!-- WP CLI Command for Pro Users -->
  <hr>
  <p><?php _e('Pro users: For faster batching, use the WP CLI command:', 'ai-alt-tags'); ?></p>
  <ul>
    <li><code>wp ai_alt batch</code></li>
  </ul>
  <?php endif; ?>

<!-- Custom CSS for the admin UI -->
<style>
/* Hide the progress wrapper by default */
#batch-ai-progress-wrapper {
  display: none;
}

/* Style the progress bar */
#batch-ai-progress {
  width: 100%;
  height: 40px;
  position: relative;
  border: 2px solid #ccc;
  border-radius: 1rem;
  overflow: hidden;
  margin: 1rem 0;
}

/* Style the progress bar fill */
#batch-ai-progress span {
  position: absolute;
  height: 100%;
  width: 0;
  transition: width 0.2s ease-in-out;
}

/* Style the animated gradient for the progress bar */
.animated-gradient {
  background: repeating-linear-gradient(to right, #2271b1 0%, #5aa4df 50%, #2271b1 100%);
  width: 100%;
  background-size: 200% auto;
  background-position: 0 100%;
  animation: gradient 2s infinite;
  animation-fill-mode: forwards;
  animation-timing-function: linear;
}

/* Style the settings expander */
.settings-expander {
  cursor: pointer;
}

/* Add a '+' after the settings expander */
.settings-expander::after {
  content: '+';
  display: inline-block;
}

/* Change the '+' to a '-' when the settings expander is expanded */
.settings-expander.expanded::after {
  content: '-';
}

/* Define the animation for the gradient */
@keyframes gradient { 
  0%   { background-position: 0 0; }
  100% { background-position: -200% 0; }
}

/* Style the tooltip container */
.tooltip {
  position: relative;
  display: inline-block;
}

/* Style the info icon */
.info-icon {
  color: #2271b1;
  font-style: normal;
  font-size: 14px;
  cursor: pointer;
  margin-left: 5px;
  padding: 2px;
  border: 1px solid #2271b1;
  border-radius: 50%;
  width: 16px;
  height: 16px;
  line-height: 14px;
  text-align: center;
  display: inline-block;
}

/* Style the tooltip text */
.tooltip-text {
  visibility: hidden;
  width: 250px;
  background-color: #555;
  color: #fff;
  text-align: center;
  padding: 5px;
  border-radius: 6px;
  position: absolute;
  z-index: 1;
  bottom: 125%;
  left: 50%;
  margin-left: -125px;
  opacity: 0;
  transition: opacity 0.3s;
}

/* Show the tooltip text on hover */
.tooltip:hover .tooltip-text {
  visibility: visible;
  opacity: 1;
}

/* Style the image analysis wrapper */
#image-analysis-wrapper {
  margin-top: 20px;
  margin-bottom: 20px;
}

/* Style the image analysis report */
#image-analysis-report {
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 4px;
  background-color: #f5f5f5;
}

/* Style the paragraphs in the image analysis report */
#image-analysis-report p {
  margin: 10px 0;
  text-align: center;
}

/* Style the strong tags in the image analysis report */
#image-analysis-report strong {
  font-weight: bold;
}

/* Style the total images and missing alt text counts */
#total-images,
#missing-alt-text {
  font-size: large;
  color: #2271b1;
  font-weight: bold;
}

/* Show the finished progress wrapper */
#batch-ai-progress-wrapper.finished {
  display: block;
  text-align: center;
  margin-top: 20px;
}

/* Style the success message in the finished progress wrapper */
#batch-ai-progress-wrapper.finished p.success {
  color: #46b450;
}

/* Style the error message in the finished progress wrapper */
#batch-ai-progress-wrapper.finished p.error {
  color: #dc3232;
}
</style>

<!-- Localize the script for AJAX and REST API calls -->
<?php
wp_localize_script('ai_alt_tags_admin_script', 'ai_alt_tags', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'rest_url' => esc_url_raw(add_query_arg(array("rest_route" => "/wp/v2/posts"), home_url())),
    'api_version' => $api_version,
));
?>