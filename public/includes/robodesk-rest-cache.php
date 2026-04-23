<?php
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
class Robodesk_rest_cache
{
    private $prefix = 'robodesk_rest_cache';          // Prefix for cache keys
    private $default_timeout = 86400;            // Default cache timeout (1 day)
    private $cached_routes = array();            // Routes to cache

    /**
     * Generate a unique cache key based on the request route and parameters.
     *
     * @param WP_REST_Request $request The REST API request object.
     * @return string The cache key.
     */
    public function get_cache_key($request)
    {
        $route = $request->get_route();
        $route_key = str_replace('/', '_', $route);
        $params = $request->get_params();
        return $this->prefix . $route_key . '_' . md5(serialize($params));
    }

    /**
     * Enable caching for specified existing WordPress REST API routes.
     *
     * @param array $routes An array of routes to cache (e.g., ['/wp/v2/posts']).
     */
    public function enable_core_routes_caching($routes)
    {
        $this->cached_routes = $routes;
        add_filter('rest_pre_dispatch', array($this, 'check_cache_before_dispatch'), 10, 3);
        add_filter('rest_post_dispatch', array($this, 'cache_response_after_dispatch'), 10, 3);
    }

    /**
     * Check for a cached response before dispatching the request.
     *
     * @param mixed $result The default result.
     * @param WP_REST_Server $server The REST server instance.
     * @param WP_REST_Request $request The request object.
     * @return mixed Cached response or original result.
     */
    public function check_cache_before_dispatch($result, $server, $request)
    {
        if ($request->get_method() !== 'GET') {
            return $result;
        }

        $route = $request->get_route();
        foreach ($this->cached_routes as $cached_route) {
            if (strpos($route, $cached_route) === 0) {
                $key = $this->get_cache_key($request);
                $cached = get_transient($key);
                if ($cached !== false) {
                    $response = new WP_REST_Response($cached, 200);
                    $response->header('X-Cache', 'HIT');
                    return $response;
                }
                break;
            }
        }
        return $result;
    }

    /**
     * Cache the response after it’s generated.
     *
     * @param WP_REST_Response $response The response object.
     * @param WP_REST_Server $server The REST server instance.
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response The original response.
     */
    public function cache_response_after_dispatch($response, $server, $request)
    {
        if ($request->get_method() === 'GET' && $response->get_status() === 200) {
            $route = $request->get_route();
            foreach ($this->cached_routes as $cached_route) {
                if (strpos($route, $cached_route) === 0) {
                    $key = $this->get_cache_key($request);
                    $data = $response->get_data();
                    set_transient($key, $data, $this->default_timeout);
                    break;
                }
            }
        }
        return $response;
    }

    /**
     * Flush caches for a specific route.
     *
     * @param string $route The REST API route to flush.
     */
    public function flush_caches_by_route($route)
    {
        global $wpdb;
        $route_key = str_replace('/', '_', $route);
        $prefix = '_transient_' . $this->prefix . $route_key . '_';
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $prefix . '%'));
    }

    /**
     * Register a hook to flush specific routes when triggered.
     *
     * @param string $hook The WordPress hook to listen for.
     * @param array $routes The routes to flush when the hook is triggered.
     */
    public function register_flush_hook($hook, $routes)
    {
        add_action($hook, function () use ($routes) {
            foreach ($routes as $route) {
                $this->flush_caches_by_route($route);
            }
        });
    }
}
