<?php
// Check if WordPress is running, if not, exit the script
if (!defined('ABSPATH')) {
    exit;
}

// Include the index.php file to access the necessary functions and variables
require_once plugin_dir_path(__FILE__) . 'index.php';

// Define the base URL for the REST API
define('AI_QUEUE_HANDLER_API_BASE_URL', 'https://boardingai.wpengine.com/?rest_route=/boardingai/v1/queue');

// Function to log errors
function ai_alt_tags__log_error($message) {
    if (WP_DEBUG === true){
        if (is_array($message) || is_object($message)){
            error_log(print_r($message, true));
        } else {
            error_log($message);
        }
    }
}

// Function to check the status of the queue
function ai_alt_tags__check_queue_status($user_id)
{
    // Send a POST request to the BoardingAI API to check the queue status
    $response = wp_remote_post(AI_QUEUE_HANDLER_API_BASE_URL, array(
        'body' => array(
            'action' => 'check_queue_status',
            'user_id' => $user_id,
        ),
    ));

    // Check if the request is successful
    if (is_wp_error($response)) {
        ai_alt_tags__log_error($response->get_error_message());
        return false;
    }

    if (wp_remote_retrieve_response_code($response) !== 200 && wp_remote_retrieve_response_code($response) !== 204) {
        ai_alt_tags__log_error('Unexpected response code: ' . wp_remote_retrieve_response_code($response));
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    // Check if the response is successful
    if (wp_remote_retrieve_response_code($response) === 200 && isset($body['queue_status']) && isset($body['position'])) {
        return array(
            'queue_status' => $body['queue_status'],
            'position' => $body['position'],
        );
    }

    // Handle the case when the queue is empty
    if (wp_remote_retrieve_response_code($response) === 204) {
        return array(
            'queue_status' => 'empty',
            'position' => 0,
        );
    }

    ai_alt_tags__log_error('Unexpected response body: ' . wp_remote_retrieve_body($response));
    return false;
}

// Function to add user to the queue
function ai_alt_tags__add_to_queue($user_id)
{
    // Send a POST request to the BoardingAI API to add the user to the queue
    $response = wp_remote_post(AI_QUEUE_HANDLER_API_BASE_URL, array(
        'body' => array(
            'action' => 'add_to_queue',
            'user_id' => $user_id,
        ),
    ));

    // Check if the request is successful
    if (is_wp_error($response)) {
        ai_alt_tags__log_error($response->get_error_message());
        return false;
    }

    if (wp_remote_retrieve_response_code($response) !== 200) {
        ai_alt_tags__log_error('Unexpected response code: ' . wp_remote_retrieve_response_code($response));
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    // Check if the response is successful
    if (isset($body['position'])) {
        return $body['position'];
    }

    ai_alt_tags__log_error('Unexpected response body: ' . wp_remote_retrieve_body($response));
    return false;
}

// Function to remove user from the queue
function ai_alt_tags__remove_from_queue($user_id)
{
    // Send a POST request to the BoardingAI API to remove the user from the queue
    $response = wp_remote_post(AI_QUEUE_HANDLER_API_BASE_URL, array(
        'body' => array(
            'action' => 'remove_from_queue',
            'user_id' => $user_id,
        ),
    ));

    // Check if the request is successful
    if (is_wp_error($response)) {
        ai_alt_tags__log_error($response->get_error_message());
        return false;
    }

    if (wp_remote_retrieve_response_code($response) !== 200) {
        ai_alt_tags__log_error('Unexpected response code: ' . wp_remote_retrieve_response_code($response));
        return false;
    }

    return true;
}

// Generate a unique user ID for the current installation
$user_id = ai_alt_tags__generate_user_id();