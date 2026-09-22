<?php

// // Function to handle webhook requests in WordPress
// function mmhd_ticket_messaging_handler() {
   
//     if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//         wp_send_json_error(["error" => "Method Not Allowed"], 405);
//     }

//     $input = file_get_contents("php://input");
//     $data = json_decode($input, true);


//     if (!isset($data['user_id']) || !isset($data['message'])) {
//         wp_send_json_error(["error" => "Missing required parameters"], 400);
//     }

//     $user_id = intval($data['user_id']);
//     $message = sanitize_text_field($data['message']);


//     $log_entry = date('Y-m-d H:i:s') . " - User: $user_id, Message: $message" . PHP_EOL;
//     file_put_contents(WP_CONTENT_DIR . "/webhook_log.txt", $log_entry, FILE_APPEND);

//     wp_send_json_success(["message" => "Webhook received successfully"]);
// }

// // Register a custom endpoint in WordPress
// function register_mmhd_ticket_messaging_endpoint() {
//     add_rewrite_rule('mmhd-ticket-messaging/?$', 'index.php?mmhd_ticket_messaging=1', 'top');
// }
// add_action('init', 'register_mmhd_ticket_messaging_endpoint');

// // Add query var
// function add_mmhd_ticket_messaging_query_var($vars) {
//     $vars[] = 'mmhd_ticket_messaging';
//     return $vars;
// }
// add_filter('query_vars', 'add_mmhd_ticket_messaging_query_var');

// // Capture requests to the custom webhook
// function mmhd_ticket_messaging_template_redirect() {
//     if (get_query_var('mmhd_ticket_messaging') == 1) {
//         mmhd_ticket_messaging_handler();
//         exit;
//     }
// }
// add_action('template_redirect', 'mmhd_ticket_messaging_template_redirect');

