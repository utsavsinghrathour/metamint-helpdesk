<?php 

// add_action('rest_api_init', function () {
//     register_rest_route('custom/v1', '/login', array(
//         'methods' => 'POST',
//         'callback' => 'custom_login_endpoint',
//         'permission_callback' => '__return_true', // Allow public access to the endpoint
//     ));
// });

// function custom_login_endpoint(WP_REST_Request $request) {
//     $username = sanitize_text_field($request->get_param('username'));
//     $password = $request->get_param('password');

//     if (empty($username) || empty($password)) {
//         return new WP_Error('missing_credentials', 'Username and password are required.', array('status' => 400));
//     }
    
//     if (is_email($username)) {
//         $user = get_user_by('email', $username);
//         if (!$user) {
//             return new WP_Error('invalid_email', 'No user found with this email.', array('status' => 404));
//         }
//         $username = $user->user_login;
//     } 

//     // Authenticate user
//     $creds = array(
//         'user_login' => $username,
//         'user_password' => $password,
//         'remember' => true,
//     );

//     $user = wp_signon($creds, is_ssl());

//     if (is_wp_error($user)) {
//         return new WP_Error('invalid_credentials', 'Invalid username or password.', array('status' => 401));
//     }

//     // Set authentication cookies
//     wp_set_auth_cookie($user->ID, true, is_ssl());

//     // Return user details
//     return array(
//         'success' => true,
//         'user_id' => $user->ID,
//         'user_email' => $user->user_email,
//         'user_display_name' => $user->display_name,
//         'message' => 'Login successful.',
//     );
// }


// add_filter('rest_pre_serve_request', function ($value) {
//     header('Access-Control-Allow-Origin:'.site_url()); 
//     header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
//     header('Access-Control-Allow-Credentials: true');
//     header('Access-Control-Allow-Headers: Content-Type');
//     return $value;
// });


// require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php'; // Ensure Firebase JWT is included
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/login', array(
        'methods' => 'POST',
        'callback' => 'secure_login_endpoint',
        'permission_callback' => '__return_true', // Public access for login
    ));

    register_rest_route('custom/v1', '/protected', array(
        'methods' => 'GET',
        'callback' => 'protected_endpoint',
        'permission_callback' => 'validate_jwt_token',
    ));
});

function secure_login_endpoint(WP_REST_Request $request) {
    $username = sanitize_text_field($request->get_param('username'));
    $password = $request->get_param('password');

    // Input validation
    if (empty($username) || empty($password)) {
        return new WP_Error('missing_credentials', 'Username and password are required.', array('status' => 400));
    }

    // Check if username is email
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
        'remember' => false,
    );

    $user = wp_authenticate($creds['user_login'], $creds['user_password']);

    if (is_wp_error($user)) {
        return new WP_Error('invalid_credentials', 'Invalid username or password.', array('status' => 401));
    }

    // Generate JWT
    $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : wp_generate_password(64, true, true);
    $issued_at = time();
    $expire = $issued_at + (DAY_IN_SECONDS * 7); // Token valid for 7 days

    $token = array(
        'iss' => get_site_url(),
        'iat' => $issued_at,
        'exp' => $expire,
        'data' => array(
            'user_id' => $user->ID,
            'user_email' => $user->user_email,
        ),
    );

    try {
        $jwt = JWT::encode($token, $secret_key, 'HS256');
        return array(
            'success' => true,
            'token' => $jwt,
            'user_id' => $user->ID,
            'user_email' => $user->user_email,
            'user_display_name' => $user->display_name,
            'expires_at' => $expire,
        );
    } catch (Exception $e) {
        return new WP_Error('jwt_error', 'Error generating token: ' . $e->getMessage(), array('status' => 500));
    }
}

function validate_jwt_token(WP_REST_Request $request) {
    $auth_header = $request->get_header('Authorization');
    
    if (!$auth_header) {
        return new WP_Error('no_token', 'Authorization header missing.', array('status' => 401));
    }

    // Extract token from "Bearer <token>"
    $token = str_replace('Bearer ', '', trim($auth_header));
    $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : wp_generate_password(64, true, true);

    try {
        $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
        
        // Verify issuer
        if ($decoded->iss !== get_site_url()) {
            return new WP_Error('invalid_token', 'Invalid token issuer.', array('status' => 401));
        }

        // Verify expiration
        if ($decoded->exp < time()) {
            return new WP_Error('expired_token', 'Token has expired.', array('status' => 401));
        }

        // Verify user exists
        $user = get_user_by('id', $decoded->data->user_id);
        if (!$user) {
            return new WP_Error('invalid_user', 'User not found.', array('status' => 401));
        }

        // Set current user for the request
        wp_set_current_user($user->ID);
        return true;

    } catch (Exception $e) {
        return new WP_Error('invalid_token', 'Invalid token: ' . $e->getMessage(), array('status' => 401));
    }
}

function protected_endpoint(WP_REST_Request $request) {
    $user = wp_get_current_user();
    return array(
        'success' => true,
        'message' => 'Access granted to protected endpoint.',
        'user_id' => $user->ID,
        'user_email' => $user->user_email,
    );
}

add_filter('rest_pre_serve_request', function ($value) {
    header('Access-Control-Allow-Origin: ' . esc_url_raw(site_url()));
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400');
    return $value;
});



// Secure the secret key
if (!defined('JWT_AUTH_SECRET_KEY')) {
    define('JWT_AUTH_SECRET_KEY', 'robodesk-secret');
}
