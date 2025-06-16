<?php 

add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/login', array(
        'methods' => 'POST',
        'callback' => 'custom_login_endpoint',
        'permission_callback' => '__return_true', // Allow public access to the endpoint
    ));
});


function custom_login_endpoint(WP_REST_Request $request) {
    $username = sanitize_text_field($request->get_param('username'));
    $password = $request->get_param('password');

    if (empty($username) || empty($password)) {
        return new WP_Error('missing_credentials', 'Username and password are required.', array('status' => 400));
    }
    
    if (is_email($username)) {
        $user = get_user_by('email', $username);
        if (!$user) {
            return new WP_Error('invalid_email', 'No user found with this email.', array('status' => 404));
        }
        $username = $user->user_login;
    } 

    // Authenticate user
    $creds = array(
        'user_login' => $username,
        'user_password' => $password,
        'remember' => true,
    );

    $user = wp_signon($creds, is_ssl());

    if (is_wp_error($user)) {
        return new WP_Error('invalid_credentials', 'Invalid username or password.', array('status' => 401));
    }

    // Set authentication cookies
    wp_set_auth_cookie($user->ID, true, is_ssl());

    // Return user details
    return array(
        'success' => true,
        'user_id' => $user->ID,
        'user_email' => $user->user_email,
        'user_display_name' => $user->display_name,
        'message' => 'Login successful.',
    );
}


add_filter('rest_pre_serve_request', function ($value) {
    header('Access-Control-Allow-Origin:'.site_url()); 
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type');
    return $value;
});
