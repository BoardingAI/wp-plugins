<?php
/*
Plugin Name: AI Queue Handler
Description: Handles queue requests for AI Alt Tags plugin.
Author: BoardingAI
Version: 1.0.0
*/

class AI_Queue_Handler extends WP_REST_Controller {

    public function __construct() {
        if (WP_DEBUG === true) {
            error_log('AI Queue Handler: AI_Queue_Handler class was instantiated.');
        }
    }

    public function register_routes() {
        if (WP_DEBUG === true) {
            error_log('AI Queue Handler: register_routes() method was called.');
        }

        register_rest_route('boardingai/v1', '/queue', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_queue_request'),
            'permission_callback' => '__return_true',
        ));
    }

    public function handle_queue_request($request)
    {
        if (WP_DEBUG === true) {
            error_log('AI Queue Handler: handle_queue_request() method was called.');
        }

        // Get the request data
        $request_data = $request->get_params();

        // Log the request details
        $this->log_queue_action("Request received from " . $_SERVER['REMOTE_ADDR'] . ". Method: " . $_SERVER['REQUEST_METHOD'] . ". URL: " . $_SERVER['REQUEST_URI'] . ". User Agent: " . $_SERVER['HTTP_USER_AGENT'] . ". Request Data: " . json_encode($request_data));

        try {
            // Start the timer for performance metrics
            $start_time = microtime(true);

            // Check if the request is to add a user to the queue
            if (isset($request_data['action']) && $request_data['action'] === 'add_to_queue') {
                // Get the user ID from the request data
                $user_id = $request_data['user_id'];

                // Add the user to the queue
                $this->add_to_queue($user_id);

                // Send a success response with the user's position in the queue
                $position = $this->get_user_position_in_queue($user_id);

                // Log the performance metrics
                $end_time = microtime(true);
                $this->log_queue_action("Time taken to add user $user_id to the queue and get position: " . ($end_time - $start_time) . " seconds.");

                return new WP_REST_Response(array('position' => $position), 200);
            }

            // Check if the request is to remove a user from the queue
            if (isset($request_data['action']) && $request_data['action'] === 'remove_from_queue') {
                // Get the user ID from the request data
                $user_id = $request_data['user_id'];

                // Remove the user from the queue
                $this->remove_from_queue($user_id);

                // Log the performance metrics
                $end_time = microtime(true);
                $this->log_queue_action("Time taken to remove user $user_id from the queue: " . ($end_time - $start_time) . " seconds.");

                // Send a success response
                return new WP_REST_Response(null, 200);
            }

            // Check the queue status for a user
            if (isset($request_data['action']) && $request_data['action'] === 'check_queue_status') {
                // Get the user ID from the request data
                $user_id = $request_data['user_id'];

                // Check the queue status for the user
                $queue_status = $this->check_queue_status($user_id);

                // Get the position of the user in the queue
                $position = $this->get_user_position_in_queue($user_id);

                // Log the performance metrics
                $end_time = microtime(true);
                $this->log_queue_action("Time taken to check queue status and position for user $user_id: " . ($end_time - $start_time) . " seconds.");

                // Handle the case when the queue is empty
                if ($queue_status === false && $position === 0) {
                    return new WP_REST_Response(null, 204);
                }

                // Send the queue status and position as the response
                return new WP_REST_Response(array('queue_status' => $queue_status, 'position' => $position), 200);
            }

            // Send an error response for unsupported requests
            return new WP_Error('invalid_request', 'Invalid request.', array('status' => 400));
        } catch (Exception $e) {
            // Log the error details
            $this->log_queue_action("Error occurred: " . $e->getMessage() . ". Stack trace: " . $e->getTraceAsString());
            return new WP_Error('server_error', 'An error occurred on the server.', array('status' => 500));
        }
    }

    // Function to add a user to the queue
    function add_to_queue($user_id)
    {
        try {
            // Get the path to the queue file
            $queue_file_path = plugin_dir_path(__FILE__) . 'queue.txt';

            // Append the user ID to the queue file
            file_put_contents($queue_file_path, "$user_id\n", FILE_APPEND);

            // Log the action
            $this->log_queue_action("User $user_id was added to the queue.");
        } catch (Exception $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }

    // Function to remove a user from the queue
    function remove_from_queue($user_id)
    {
        try {
            // Get the path to the queue file
            $queue_file_path = plugin_dir_path(__FILE__) . 'queue.txt';

            // Read the contents of the queue file
            $queue_contents = file_get_contents($queue_file_path);

            // Parse the queue contents into an array
            $queue_lines = explode("\n", $queue_contents);

            // Remove the current user from the queue
            foreach ($queue_lines as $key => $user) {
                if ($user === $user_id) {
                    unset($queue_lines[$key]);
                    break;
                }
            }

            // Update the queue file
            file_put_contents($queue_file_path, implode("\n", $queue_lines));

            // Log the action
            $this->log_queue_action("User $user_id was removed from the queue.");
        } catch (Exception $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }

    // Function to check the queue status for a user
    function check_queue_status($user_id)
    {
        try {
            // Get the path to the queue file
            $queue_file_path = plugin_dir_path(__FILE__) . 'queue.txt';

            // Check if the queue file is empty
            if (filesize($queue_file_path) === 0) {
                // Log the action
                $this->log_queue_action("Checked queue status for user $user_id. Queue is empty.");
                return false;
            }

            // Read the contents of the queue file
            $queue_contents = file_get_contents($queue_file_path);

            // Parse the queue contents into an array
            $queue_lines = explode("\n", $queue_contents);

            // Check if the user is in the queue
            foreach ($queue_lines as $user) {
                if ($user === $user_id) {
                    // Log the action
                    $this->log_queue_action("Checked queue status for user $user_id. User is in the queue.");
                    return true;
                }
            }

            // Log the action
            $this->log_queue_action("Checked queue status for user $user_id. User is not in the queue.");
            return false;
        } catch (Exception $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }

    // Function to get the position of a user in the queue
    function get_user_position_in_queue($user_id)
    {
        try {
            // Get the path to the queue file
            $queue_file_path = plugin_dir_path(__FILE__) . 'queue.txt';

            // Read the contents of the queue file
            $queue_contents = file_get_contents($queue_file_path);

            // Parse the queue contents into an array
            $queue_lines = explode("\n", $queue_contents);

            // Find the position of the user in the queue
            $position = 0;
            foreach ($queue_lines as $key => $user) {
                $position++;
                if ($user === $user_id) {
                    break;
                }
            }

            // Log the action
            $this->log_queue_action("Checked position for user $user_id. Position is $position.");

            // Return the position of the user in the queue
            return $position;
        } catch (Exception $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }

    // Function to log queue actions
    function log_queue_action($message)
    {
        // Get the path to the log file
        $log_file_path = plugin_dir_path(__FILE__) . 'queue.log';

        // Append the log entry to the log file
        file_put_contents($log_file_path, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
    }
}

add_action('rest_api_init', function () {
    $ai_queue_handler = new AI_Queue_Handler();
    $ai_queue_handler->register_routes();
    if (WP_DEBUG === true) {
        error_log('AI Queue Handler: AI_Queue_Handler class was instantiated and routes were registered.');
    }
});