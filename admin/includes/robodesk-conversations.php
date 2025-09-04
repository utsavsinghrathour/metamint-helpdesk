<div id="tickets-page" class="page-content hidden">
    <!-- Search and Filter Bar -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                <!-- Preserve other GET parameters -->
                <?php foreach ($_GET as $key => $value) : ?>
                    <?php if (!in_array($key, ['search', 'customer', 'agent', 'status', 'paged'])) : ?>
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
                        value="<?php echo esc_attr($customer_filter); ?>"
                        placeholder="Customer name..."
                        class="styled-input block w-full px-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search by Agent</label>
                    <input type="text"
                        name="agent"
                        value="<?php echo esc_attr($agent_filter); ?>"
                        placeholder="Agent name..."
                        class="styled-input block w-full px-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status Filter</label>
                    <select name="status" class="styled-select block w-full px-3 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md">
                        <option value="all" <?php selected($status_filter, 'all'); ?>>All Status</option>
                        <option value="open" <?php selected($status_filter, 'open'); ?>>Open</option>
                        <option value="in-progress" <?php selected($status_filter, 'in-progress'); ?>>In Progress</option>
                        <option value="closed" <?php selected($status_filter, 'closed'); ?>>Closed</option>
                    </select>
                </div>

                <div class="flex space-x-2">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-filter mr-2"></i>
                        Filter
                    </button>

                    <!-- Clear filters button -->
                    <?php if (!empty($search_term) || !empty($customer_filter) || !empty($agent_filter) || $status_filter !== 'all') : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?' . http_build_query(array_filter($_GET, function ($key) {
                                        return !in_array($key, ['search', 'customer', 'agent', 'status', 'paged']);
                                    }, ARRAY_FILTER_USE_KEY)))); ?>"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-times mr-2"></i>
                            Clear
                        </a>
                    <?php endif; ?>


                </div>
            </form>
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

    <!----------------------------- Tickets Table ----------------------------->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
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
                </script>
                <?php if (!empty($tickets)) : ?>
                    <?php foreach ($tickets as $ticket) : ?>
                        <tr class="table-row cursor-pointer" onclick="viewTicket(<?php echo esc_js($ticket['id']); ?>)">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">#<?php echo esc_html($ticket['id']); ?></div>
                                <div class="text-sm text-gray-500 truncate max-w-xs"><?php echo esc_html($ticket['title']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo esc_html($ticket['customer']); ?></div>
                                <div class="text-sm text-gray-500 truncate max-w-xs"><?php echo esc_html($ticket['email']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="status-badge <?php echo esc_attr(get_status_class($ticket['status'])); ?>">
                                    <?php echo esc_html(str_replace('-', ' ', $ticket['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="status-badge" style="background-color:<?php echo esc_attr(get_priority_class($ticket['priority'])); ?>">
                                    <?php echo esc_html($ticket['priority']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo esc_html($ticket['assignee']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo esc_html(format_date($ticket['updated'])); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
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