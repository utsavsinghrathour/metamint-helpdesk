<?php
// Get filter parameters from URL
$search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$customer_filter = isset($_GET['customer']) ? sanitize_text_field($_GET['customer']) : '';
$agent_filter = isset($_GET['agent']) ? sanitize_text_field($_GET['agent']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';

// Get the current page number from URL parameter
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$posts_per_page = 10; // Number of tickets per page
$offset = ($current_page - 1) * $posts_per_page;

// Build the base query arguments
$args = array(
    'post_type' => 'robodesk_ticket',
    'post_status' => 'publish',
    'posts_per_page' => $posts_per_page,
    'offset' => $offset,
    'orderby' => 'modified',
    'order' => 'DESC'
);

// Initialize meta_query array
$meta_query = array();

// Add meta query for status filter
if ($status_filter === 'all') {
    // Show only open and in-progress tickets by default
    $meta_query[] = array(
        'relation' => 'OR',
        array(
            'key' => '_robodesk_ticket_status',
            'value' => 'open',
            'compare' => '='
        ),
        array(
            'key' => '_robodesk_ticket_status',
            'value' => 'in-progress',
            'compare' => '='
        )
    );
} else {
    // Filter for specific status
    $meta_query[] = array(
        'key' => '_robodesk_ticket_status',
        'value' => $status_filter,
        'compare' => '='
    );
}

// Add meta query for agent filter
if (!empty($agent_filter)) {
    $meta_query[] = array(
        'key' => 'ticket_assignee',
        'value' => $agent_filter,
        'compare' => 'LIKE'
    );
}

// Set the meta_query with AND relation if there are multiple conditions
if (!empty($meta_query)) {
    if (count($meta_query) > 1) {
        $meta_query['relation'] = 'AND';
    }
    $args['meta_query'] = $meta_query;
}

// Add search functionality - check if it's a ticket ID or general search
if (!empty($search_term)) {
    // Check if search term is numeric (potential ticket ID)
    if (is_numeric($search_term)) {
        $args['post__in'] = array(intval($search_term));
    } else {
        $args['s'] = $search_term;
    }
}

// Add author filter for customer search
if (!empty($customer_filter)) {
    // Search for users by display name or email
    $user_query = new WP_User_Query(array(
        'search' => '*' . $customer_filter . '*',
        'search_columns' => array('display_name', 'user_email', 'user_login'),
        'fields' => 'ID'
    ));

    $user_ids = $user_query->get_results();

    if (!empty($user_ids)) {
        $args['author__in'] = $user_ids;
    } else {
        // If no users found, set impossible condition to return no results
        $args['author__in'] = array(0);
    }
}

// Get total count with same filters for pagination
$total_args = $args;
$total_args['posts_per_page'] = -1;
$total_args['fields'] = 'ids';
unset($total_args['offset']);
$total_query = new WP_Query($total_args);
$total_posts = $total_query->found_posts;
$total_pages = ceil($total_posts / $posts_per_page);

// Main query with filters
$query = new WP_Query($args);
$tickets = []; // Initialize tickets array

if ($query->have_posts()) {
    while ($query->have_posts()) {
        $query->the_post();
        $ticket_id = get_the_ID();
        $ticket_status = get_post_meta($ticket_id, '_robodesk_ticket_status', true) ?: 'N/A';
        $ticket_origin = get_post_meta($ticket_id, 'robodesk_ticket_origin', true) ?: 'N/A';
        $priority_terms = get_the_terms($ticket_id, 'robodesk_ticket_priority');
        $author_id = (int) get_the_author_meta('ID');
        $author_image = function_exists('robodesk_get_avatar_url')
            ? robodesk_get_avatar_url($author_id, 32)
            : get_avatar_url($author_id, array('size' => 32));
        $ticket_priority = $priority_terms ? $priority_terms[0]->name : 'N/A';
        $updated_at = get_the_modified_date('Y-m-d H:i:s', $ticket_id);
        $created_at = get_the_date('Y-m-d H:i:s', $ticket_id);
        $ticket_terms = wp_get_post_terms($ticket_id, 'robodesk_ticket_category');
        $ticket_category = '';
        if (!is_wp_error($ticket_terms) && !empty($ticket_terms)) {
            $ticket_category = [
                'name' => $ticket_terms[0]->name,
                'id' => $ticket_terms[0]->term_id,
            ];
        }

        // Simulate replies field using comments
        $replies = get_comments(array('post_id' => $ticket_id));
        $replies_count = count($replies);
        if ($replies_count > 0) {
            $replies = array_map(function ($reply) {
                return [
                    'id' => $reply->comment_ID,
                    'author' => $reply->comment_author,
                    'content' => $reply->comment_content,
                    'date' => $reply->comment_date
                ];
            }, $replies);
        } else {
            $replies = [];
        }

        $tickets[] = [
            'id' => $ticket_id,
            'title' => get_the_title(),
            'customer' => get_the_author(),
            'email' => get_the_author_meta('email'),
            'author_id' => get_the_author_meta('ID'),
            'source' => $ticket_origin,
            'status' => $ticket_status,
            'priority' => $ticket_priority,
            'created' => $created_at,
            'author_image' => $author_image,
            'updated' => $updated_at,
            'category' => $ticket_category,
            'assignee' => get_post_meta($ticket_id, 'ticket_assignee', true) ?: 'N/A',
            'assignee_id' => (int) get_post_meta($ticket_id, 'ticket_assignee_id', true),
            'replies' => $replies_count ? $replies : [
                'id' => 0,
                'author' => 'N/A',
                'content' => 'No replies yet',
                'date' => $updated_at
            ],
            'replies_count' => $replies_count
        ];
    }
}
wp_reset_postdata();

// Function to generate pagination URL with current filters
function get_pagination_url($page)
{
    $current_url = admin_url('admin.php');
    $params = $_GET;
    $params['page'] = 'robodesk-dashboard';
    $params['tab'] = 'tickets';
    $params['paged'] = $page;
    unset($params['ticket']);
    return add_query_arg($params, $current_url);
}

// Function to build filter URL
function get_filter_url($new_params = array())
{
    $current_url = admin_url('admin.php');
    $params = $_GET;

    $params['page'] = 'robodesk-dashboard';
    $params['tab'] = 'tickets';

    // Remove paged parameter when filtering to start from page 1
    unset($params['paged']);
    unset($params['ticket']);

    // Merge new parameters
    $params = array_merge($params, $new_params);

    return add_query_arg($params, $current_url);
}

// Get users for assignee options
$users =  get_users([
    'role__in' => ['robodesk_support_staff', 'robodesk_support_manager', 'author', 'editor', 'administrator'],
    'fields'   => ['ID', 'display_name'],
]);

// Get users for autocomplete in customer/agent filters
$customer_filter_users = get_users([
    'fields' => ['display_name', 'user_email', 'user_login'],
    'number' => 500,
    'orderby' => 'display_name',
    'order' => 'ASC',
]);

$agent_filter_users = get_users([
    'role__in' => ['robodesk_support_staff', 'robodesk_support_manager', 'author', 'editor', 'administrator'],
    'fields' => ['display_name', 'user_email', 'user_login'],
    'number' => 500,
    'orderby' => 'display_name',
    'order' => 'ASC',
]);

// Get priority terms
$priority_terms = get_terms(array(
    'taxonomy' => 'robodesk_ticket_priority',
    'hide_empty' => false,
));
?>
<div id="tickets-page" class="page-content hidden">
    <!-- Search and Filter Bar -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <form method="GET" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                <!-- Preserve other GET parameters -->
                <!-- Keep the page parameter to stay on Conversations tab -->
                <input type="hidden" name="page" value="robodesk-dashboard">
                <input type="hidden" name="tab" value="tickets">
                <?php foreach ($_GET as $key => $value) : ?>
                    <?php if (!in_array($key, ['page', 'tab', 'search', 'customer', 'agent', 'status', 'paged'])) : ?>
                        <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
                    <?php endif; ?>
                <?php endforeach; ?>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Conversations</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text"
                            name="search"
                            value="<?php echo esc_attr($search_term); ?>"
                            placeholder="Search tickets..."
                            class="styled-input block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search by Customer</label>
                    <input type="text"
                        name="customer"
                        list="robodesk-customer-options"
                        value="<?php echo esc_attr($customer_filter); ?>"
                        placeholder="Customer name..."
                        class="styled-input block w-full px-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search by Agent</label>
                    <input type="text"
                        name="agent"
                        list="robodesk-agent-options"
                        value="<?php echo esc_attr($agent_filter); ?>"
                        placeholder="Agent name..."
                        class="styled-input block w-full px-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status Filter</label>
                    <select name="status" class="styled-select block w-full px-3 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md">
                        <option value="all" <?php selected($status_filter, 'all'); ?>> Active Conversations</option>
                        <option value="open" <?php selected($status_filter, 'open'); ?>>Open</option>
                        <option value="in-progress" <?php selected($status_filter, 'in-progress'); ?>>In Progress</option>
                        <option value="closed" <?php selected($status_filter, 'closed'); ?>>Closed</option>
                    </select>
                </div>

                <div class="flex space-x-2">
                    <button id="filter-button" type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-filter mr-2"></i>
                        Filter
                    </button>

                    <!-- Clear filters button -->
                    <?php if (!empty($search_term) || !empty($customer_filter) || !empty($agent_filter) || $status_filter !== 'all') : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=robodesk-dashboard&tab=tickets')); ?>"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-times mr-2"></i>
                            Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <datalist id="robodesk-customer-options">
                <?php foreach ($customer_filter_users as $customer_user) : ?>
                    <?php if (!empty($customer_user->display_name)) : ?>
                        <option value="<?php echo esc_attr($customer_user->display_name); ?>"><?php echo esc_html($customer_user->user_email); ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </datalist>

            <datalist id="robodesk-agent-options">
                <?php foreach ($agent_filter_users as $agent_user) : ?>
                    <?php if (!empty($agent_user->display_name)) : ?>
                        <option value="<?php echo esc_attr($agent_user->display_name); ?>"><?php echo esc_html($agent_user->user_email); ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </datalist>
        </div>
    </div>

    <!-- Filter Results Summary -->
    <?php if (!empty($search_term) || !empty($customer_filter) || !empty($agent_filter) || $status_filter !== 'all') : ?>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-filter text-blue-600 mr-2"></i>
                    <span class="text-sm font-medium text-blue-800">Active Filters:</span>
                    <div class="ml-2 flex flex-wrap gap-2">
                        <?php if (!empty($search_term)) : ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Search: "<?php echo esc_html($search_term); ?>"
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($customer_filter)) : ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Customer: "<?php echo esc_html($customer_filter); ?>"
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($agent_filter)) : ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Agent: "<?php echo esc_html($agent_filter); ?>"
                            </span>
                        <?php endif; ?>
                        <?php if ($status_filter !== 'all') : ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Status: <?php echo esc_html(ucfirst(str_replace('-', ' ', $status_filter))); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="text-sm text-blue-600"><?php echo $total_posts; ?> result<?php echo $total_posts !== 1 ? 's' : ''; ?> found</span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Bulk Actions Bar -->
    <?php if (!empty($tickets)) : ?>
        <div id="bulk-actions-bar" class="bg-white border border-gray-200 rounded-lg p-4 mb-4" style="display: none;">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <span id="selected-count" class="text-sm font-medium text-gray-900">0 items selected</span>
                    <div class="flex items-center space-x-2">
                        <select id="bulk-action-select" class="styled-select text-sm border-gray-300 rounded-md">
                            <option value="">Select Action</option>
                            <optgroup label="Status">
                                <option value="status-open">Set Status: Open</option>
                                <option value="status-in-progress">Set Status: In Progress</option>
                                <option value="status-closed">Set Status: Closed</option>
                            </optgroup>
                            <optgroup label="Priority">
                                <?php if (!empty($priority_terms)) : ?>
                                    <?php foreach ($priority_terms as $priority_term) : ?>
                                        <option value="priority-<?php echo esc_attr($priority_term->name); ?>">Set Priority: <?php echo esc_html($priority_term->name); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </optgroup>
                            <optgroup label="Assignee">
                                <?php foreach ($users as $user) : ?>
                                    <option value="assignee-<?php echo esc_attr($user->ID); ?>">Assign to: <?php echo esc_html($user->display_name); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Actions">
                                <option value="delete" class="text-red-600">Delete Selected</option>
                            </optgroup>
                        </select>
                        <button id="apply-bulk-action" type="button" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" disabled>
                            Apply
                        </button>
                    </div>
                </div>
                <button id="clear-selection" type="button" class="text-sm text-gray-500 hover:text-gray-700">
                    Clear Selection
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!----------------------------- Tickets Table ----------------------------->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <?php if (!empty($tickets)) : ?>
                        <th class="px-3 py-3 text-left">
                            <input type="checkbox" id="select-all-tickets" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </th>
                    <?php endif; ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conversation</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Updated</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200" id="tickets-table">
                <!-- JavaScript for tickets (now only for viewing, not filtering) -->
                <script>
                    const tickets = <?php echo wp_json_encode($tickets); ?>;
                    const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
                    const bulkActionNonce = '<?php echo wp_create_nonce('robodesk_bulk_action'); ?>';
                </script>
                <?php if (!empty($tickets)) : ?>
                    <?php foreach ($tickets as $ticket) : ?>
                        <tr class="table-row">
                            <td class="px-3 py-4 whitespace-nowrap">
                                <input type="checkbox" class="ticket-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    value="<?php echo esc_attr($ticket['id']); ?>"
                                    data-ticket-id="<?php echo esc_attr($ticket['id']); ?>">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap cursor-pointer" onclick="viewTicket(<?php echo esc_js($ticket['id']); ?>)">
                                <div class="text-sm font-medium text-gray-900">#<?php echo esc_html($ticket['id']); ?></div>
                                <div class="text-sm text-gray-500 truncate max-w-xs"><?php echo esc_html($ticket['title']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap cursor-pointer" onclick="viewTicket(<?php echo esc_js($ticket['id']); ?>)">
                                <div class="text-sm font-medium text-gray-900"><?php echo esc_html($ticket['customer']); ?></div>
                                <div class="text-sm text-gray-500 truncate max-w-xs"><?php echo esc_html($ticket['email']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap cursor-pointer" onclick="viewTicket(<?php echo esc_js($ticket['id']); ?>)">
                                <span class="status-badge <?php echo esc_attr(get_status_class($ticket['status'])); ?>">
                                    <?php echo esc_html(str_replace('-', ' ', $ticket['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap cursor-pointer" onclick="viewTicket(<?php echo esc_js($ticket['id']); ?>)">
                                <span class="status-badge" style="background-color:<?php echo esc_attr(get_priority_class($ticket['priority'])); ?>">
                                    <?php echo esc_html($ticket['priority']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap cursor-pointer" onclick="viewTicket(<?php echo esc_js($ticket['id']); ?>)">
                                <?php echo esc_html($ticket['assignee']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 cursor-pointer" onclick="viewTicket(<?php echo esc_js($ticket['id']); ?>)">
                                <?php echo esc_html(format_date($ticket['updated'])); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            <?php if (!empty($search_term) || !empty($customer_filter) || !empty($agent_filter) || $status_filter !== 'all') : ?>
                                No conversations found matching your filters. <a href="<?php echo esc_url(admin_url('admin.php?' . http_build_query(array_filter($_GET, function ($key) {
                                                                                            return !in_array($key, ['search', 'customer', 'agent', 'status', 'paged']);
                                                                                        }, ARRAY_FILTER_USE_KEY)))); ?>" class="text-blue-600 hover:text-blue-800">Clear filters</a> to see all conversations.
                            <?php else : ?>
                                No Conversations found.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Custom Pagination for Plugin Dashboard -->
    <?php if ($total_pages > 1) : ?>
        <div class="mt-4">
            <nav id="cpm-nav" class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <!-- Mobile pagination -->
                    <?php if ($current_page > 1) : ?>
                        <a href="<?php echo esc_url(get_pagination_url($current_page - 1)); ?>"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </a>
                    <?php else : ?>
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed">
                            Previous
                        </span>
                    <?php endif; ?>

                    <?php if ($current_page < $total_pages) : ?>
                        <a href="<?php echo esc_url(get_pagination_url($current_page + 1)); ?>"
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                        </a>
                    <?php else : ?>
                        <span class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed">
                            Next
                        </span>
                    <?php endif; ?>
                </div>

                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?php echo (($current_page - 1) * $posts_per_page) + 1; ?></span>
                            to <span class="font-medium"><?php echo min($current_page * $posts_per_page, $total_posts); ?></span>
                            of <span class="font-medium"><?php echo $total_posts; ?></span> results
                        </p>
                    </div>

                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <!-- Previous Page Link -->
                            <?php if ($current_page > 1) : ?>
                                <a href="<?php echo esc_url(get_pagination_url($current_page - 1)); ?>"
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Previous</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            <?php else : ?>
                                <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                    <span class="sr-only">Previous</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            <?php endif; ?>

                            <!-- Page Numbers -->
                            <?php
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);

                            // Show first page if we're not close to it
                            if ($start_page > 1) : ?>
                                <a href="<?php echo esc_url(get_pagination_url(1)); ?>"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    1
                                </a>
                                <?php if ($start_page > 2) : ?>
                                    <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start_page; $i <= $end_page; $i++) : ?>
                                <?php if ($i == $current_page) : ?>
                                    <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-blue-50 text-sm font-medium text-blue-600">
                                        <?php echo $i; ?>
                                    </span>
                                <?php else : ?>
                                    <a href="<?php echo esc_url(get_pagination_url($i)); ?>"
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <!-- Show last page if we're not close to it -->
                            <?php if ($end_page < $total_pages) : ?>
                                <?php if ($end_page < $total_pages - 1) : ?>
                                    <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                                <?php endif; ?>
                                <a href="<?php echo esc_url(get_pagination_url($total_pages)); ?>"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    <?php echo $total_pages; ?>
                                </a>
                            <?php endif; ?>

                            <!-- Next Page Link -->
                            <?php if ($current_page < $total_pages) : ?>
                                <a href="<?php echo esc_url(get_pagination_url($current_page + 1)); ?>"
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Next</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            <?php else : ?>
                                <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                    <span class="sr-only">Next</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </nav>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Intentionally no auto-submit search here.
        // Filtering only runs when user clicks the Filter button.
    });
</script>