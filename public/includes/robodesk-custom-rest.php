<?php

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

// Defining JWT secret keys
if (!defined('JWT_AUTH_SECRET_KEY')) {
    define('JWT_AUTH_SECRET_KEY', 'robodesk-secret');
}

/**
 * Generate JWT token for a given WordPress user.
 *
 * @param WP_User|int $user WP_User object or user ID.
 * @param int $expiration_in_days Number of days the token will be valid (default: 7).
 * @return string|WP_Error JWT token string on success, WP_Error on failure.
 */
function robodesk_generate_token($user, $expiration_in_days = 7) {
    if (is_numeric($user)) {
        $user = get_user_by('id', $user);
    }

    if (!($user instanceof WP_User)) {
        return new WP_Error('invalid_user', 'Invalid user object.');
    }

    // Generate or retrieve the secret key
    $secret_key = defined('JWT_AUTH_SECRET_KEY')
        ? JWT_AUTH_SECRET_KEY
        : wp_generate_password(64, true, true);

    // Set issued and expiration timestamps
    $issued_at = time();
    $expire = $issued_at + (DAY_IN_SECONDS * $expiration_in_days);

    // Token payload
    $token = array(
        'iss'  => get_site_url(),
        'iat'  => $issued_at,
        'exp'  => $expire,
        'data' => array(
            'user_id'    => $user->ID,
            'user_email' => $user->user_email,
        ),
    );

    try {
        $jwt = JWT::encode($token, $secret_key, 'HS256');
        return $jwt;
    } catch (Exception $e) {
        return new WP_Error('jwt_generation_failed', 'Failed to generate JWT: ' . $e->getMessage());
    }
}



/* 
The Hook to allow  headers to the response to allow Cross-Origin Resource Sharing (CORS) for the REST API requests. CORS is a security feature implemented by webbrowsers to restrict web pages from making requests to a different domain than the one that served
the original page. 
*/
add_filter('rest_pre_serve_request', function ($value) {
    header('Access-Control-Allow-Origin: ' . esc_url_raw(site_url()));
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400');
    return $value;
});


/* Public Rest Api registration hooks */

add_action('rest_api_init', callback: function () {

    register_rest_route('robodesk/v1', '/login', array(
        'methods' => 'POST',
        'callback' => 'secure_login_endpoint',
        'permission_callback' => '__return_true', // Public access for login
    ));

    register_rest_route('custom/v1', '/protected', array(
        'methods' => 'GET',
        'callback' => 'protected_endpoint',
        'permission_callback' => 'robodesk_validate_token',
    ));

    // Robodesk Registration Rest Api Registration
    register_rest_route('robodesk/v1', '/signup', array(
        'methods' => 'POST',
        'callback' => 'robodesk_user_signup',
        'permission_callback' => '__return_true',
    ));


});

function secure_login_endpoint(WP_REST_Request $request)
{

    
    $emailAddr = sanitize_email($request->get_param('email')); 
    $password = $request->get_param('password');         

    // Input validation
    if (empty($emailAddr) || empty($password)) {
        return new WP_Error('missing_credentials', 'Username and password are required.', array('status' => 400));
    }

    // Check if username is email
    if (is_email($emailAddr)) {
        $user = get_user_by('email', $emailAddr);
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

function robodesk_validate_token(WP_REST_Request $request)
{
    $auth_header = $request->get_header(key: 'Authorization');

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

function protected_endpoint(WP_REST_Request $request)
{
    $user = wp_get_current_user();
    return array(
        'success' => true,
        'message' => 'Access granted to protected endpoint.',
        'user_id' => $user->ID,
        'user_email' => $user->user_email,
    );
}

// User Signup/registration Rest Api callback function
// function robodesk_user_signup(WP_REST_Request $request)
// {
//     $params = $request->get_json_params();
//     $username = sanitize_user($params['username']);
//     $email = sanitize_email($params['email']);
//     $password = $params['password'];
               

//     if (empty($username) || empty($email) || empty($password) ) {
//         return new WP_Error(
//                 'invalid_data',
//                 __('Missing required fields','robodesk'),
//                 array('status' => 400));
//     }

//     if (username_exists($username) || email_exists($email)) {
//         return new WP_Error(
//             'user_exists',
//             __('Username or email already exists', 'robodesk'),
//             array('status' => 409)
//         );
//     }

//     // Create user
//     $user_id = wp_create_user($username, $password, $email);
//     if (is_wp_error($user_id)) {
//         return new WP_Error(
//             'user_creation_failed', 
//             $user_id->get_error_message(), 
//             array('status' => 500)
//         );
//     }

//     // Generate JWT token
//     $token = robodesk_generate_token($user_id);
    
//     if (is_wp_error($token)) {
//         return new WP_Error(
//             'token_failed', 
//             __('Failed to generate token','robodesk'), 
//             array('status' => 500)
//         );
//     }

//     return new WP_REST_Response(array(
//         'message' => __('User created successfully','robodesk'),
//         'token' => $token,
//         'user_id' => $user_id,
//     ), 201);
// }

function robodesk_user_signup(WP_REST_Request $request)
{
    $params = $request->get_json_params();
    $email = sanitize_email($params['email']);

    if (empty($email)) {
        return new WP_Error(
            'invalid_data',
            __('Missing email field', 'robodesk'),
            array('status' => 400)
        );
    }

    if (email_exists($email)) {
        return new WP_Error(
            'user_exists',
            __('Email already exists', 'robodesk'),
            array('status' => 409)
        );
    }

    // Generate a random strong password
    $password = wp_generate_password(12, true, true); // 12 characters, include special characters

    // Prepare user data for wp_insert_user
    $user_data = array(
        'user_login' => $email, // Use email as username
        'user_email' => $email,
        'user_pass' => $password,
        'role' => 'subscriber', // Default role, adjust as needed
    );

    // Create user with wp_insert_user
    $user_id = wp_insert_user($user_data);

    if (is_wp_error($user_id)) {
        return new WP_Error(
            'user_creation_failed',
            $user_id->get_error_message(),
            array('status' => 500)
        );
    }

    // Send default WordPress user notification email
    wp_new_user_notification($user_id, null, 'both'); // Sends email to user and admin

    // Generate JWT token
    $token = robodesk_generate_token($user_id);

    if (is_wp_error($token)) {
        return new WP_Error(
            'token_failed',
            __('Failed to generate token', 'robodesk'),
            array('status' => 500)
        );
    }

    return new WP_REST_Response(array(
        'message' => __('User created successfully', 'robodesk'),
        'token' => $token,
        'user_id' => $user_id,
    ), 201);
}






// Secure the secret key
