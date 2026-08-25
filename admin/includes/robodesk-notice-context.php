<?php
// phpcs:disable WordPress.DB.SlowDBQuery
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Assign notices to pages, products, CPTs and taxonomy terms so the
 * front-end chat widget can show a context-aware notices dropdown.
 *
 * Post meta (on any supported post type):  _robodesk_related_notices  => array of notice IDs
 * Term meta (on any supported taxonomy):  _robodesk_related_notices  => array of notice IDs
 */

/**
 * Post types that can carry a "Notices" assignment meta box.
 */
function robodesk_get_notice_assignable_post_types()
{
    static $types = null;
    if (null !== $types) {
        return $types;
    }

    $excluded = array(
        'notice',
        'robodesk_faq',
        'robodesk_ticket',
        'attachment',
        'nav_menu_item',
        'revision',
    );

    $types = array();
    $all = get_post_types(array('public' => true), 'objects');
    foreach ($all as $pt) {
        if (in_array($pt->name, $excluded, true) || empty($pt->show_ui)) {
            continue;
        }
        $types[$pt->name] = $pt->labels->singular_name;
    }
    return $types;
}

/**
 * Taxonomies that can carry a "Notices" assignment field.
 */
function robodesk_get_notice_assignable_taxonomies()
{
    static $taxes = null;
    if (null !== $taxes) {
        return $taxes;
    }

    $excluded = array('robodesk_ticket_origin', 'robodesk_ticket_priority', 'robodesk_faq_category', 'nav_menu', 'link_category', 'post_format');

    $taxes = array();
    $all = get_taxonomies(array('public' => true), 'objects');
    foreach ($all as $tax) {
        if (in_array($tax->name, $excluded, true) || empty($tax->show_ui)) {
            continue;
        }
        $taxes[$tax->name] = $tax->labels->singular_name;
    }
    return $taxes;
}

/**
 * All active notices, keyed by ID -> title.
 */
function robodesk_get_active_notices_list()
{
    static $notices = null;
    if (null !== $notices) {
        return $notices;
    }

    $notices = array();
    $query = get_posts(array(
        'post_type'      => 'notice',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'meta_query'     => array(
            array(
                'key'   => '_robodesk_notice_active',
                'value' => 'true',
                'compare' => '=',
            ),
        ),
    ));

    foreach ($query as $notice) {
        $notices[$notice->ID] = $notice->post_title;
    }
    return $notices;
}

/**
 * Map notices to the taxonomy sources that assign them to a given post:
 * notice_id => array of "Taxonomy label: term name" strings.
 */
function robodesk_get_post_term_notice_sources($post_id)
{
    $sources = array();

    foreach (array_keys(robodesk_get_notice_assignable_taxonomies()) as $tax) {
        $term_ids = wp_get_post_terms($post_id, $tax, array('fields' => 'ids'));
        if (is_wp_error($term_ids) || empty($term_ids)) {
            continue;
        }

        $taxonomy = get_taxonomy($tax);
        $tax_label = $taxonomy && isset($taxonomy->labels->singular_name)
            ? $taxonomy->labels->singular_name
            : $tax;

        foreach ($term_ids as $term_id) {
            $term = get_term($term_id, $tax);
            $term_name = ($term && !is_wp_error($term)) ? $term->name : sprintf('#%d', $term_id);
            $assigned = array_map('intval', (array) get_term_meta($term_id, '_robodesk_related_notices', true));

            foreach ($assigned as $notice_id) {
                if (empty($notice_id)) {
                    continue;
                }
                if (!isset($sources[$notice_id])) {
                    $sources[$notice_id] = array();
                }
                $sources[$notice_id][$tax . ':' . $term_id] = sprintf('%s: %s', $tax_label, $term_name);
            }
        }
    }

    return $sources;
}

/**
 * Render the shared notice picker: searchable multi-select dropdown plus a
 * list of the currently selected notices below it. When editing a post, the
 * optional $sources map annotates notices that are already shown because of
 * taxonomy terms assigned to this item.
 */
function robodesk_render_notice_assignment_picker($notices, $saved, $sources = array())
{
    $saved = array_map('intval', (array) $saved);
    $sources = (array) $sources;
    $inherited = array();
    ?>
    <div class="robodesk-notice-picker">
        <input
            type="search"
            class="robodesk-notice-search"
            placeholder="<?php esc_attr_e('Search notices…', 'robodesk'); ?>"
            style="width:100%;margin-bottom:6px;"
        />
        <select
            name="robodesk_related_notices[]"
            multiple="multiple"
            class="robodesk-notice-select"
            style="width:100%;min-height:120px;"
        >
            <?php foreach ($notices as $id => $title) : ?>
                <?php
                $id = (int) $id;
                $source_text = '';
                if (!empty($sources[$id])) {
                    $source_text = ' — (' . implode(', ', array_unique(array_values($sources[$id]))) . ')';
                }
                if ($source_text && !in_array($id, $saved, true)) {
                    $inherited[$id] = $title . $source_text;
                }
                ?>
                <option value="<?php echo (int) $id; ?>" <?php selected(in_array($id, $saved, true), true); ?>>
                    <?php echo esc_html($title . $source_text); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e('Hold Ctrl (Cmd on Mac) to select multiple notices.', 'robodesk'); ?></p>
        <?php if (!empty($inherited)) : ?>
            <p class="description" style="margin-bottom:4px;font-weight:600;">
                <?php esc_html_e('Also shown because of taxonomy:', 'robodesk'); ?>
            </p>
            <ul class="robodesk-notice-inherited" style="margin:0;padding-left:16px;">
                <?php foreach ($inherited as $label) : ?>
                    <li style="font-size:12px;color:#646970;"><?php echo esc_html($label); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <div class="robodesk-notice-selected"></div>
    </div>
    <?php
    robodesk_print_notice_picker_assets();
}

/**
 * Print the picker JS + CSS once per page.
 */
function robodesk_print_notice_picker_assets()
{
    static $printed = false;
    if ($printed) {
        return;
    }
    $printed = true;
    ?>
    <style>
        .robodesk-notice-selected { display:flex; flex-wrap:wrap; gap:4px; margin-top:6px; }
        .robodesk-notice-chip {
            display:inline-flex; align-items:center; gap:4px;
            background:#f0f0f1; border:1px solid #dcdcde; border-radius:3px;
            padding:2px 6px; font-size:12px;
        }
        .robodesk-notice-chip-remove {
            background:none; border:none; cursor:pointer; padding:0 2px;
            color:#787c82; font-size:14px; line-height:1;
        }
        .robodesk-notice-chip-remove:hover { color:#d63638; }
    </style>
    <script>
    (function () {
        if (window.__robodeskNoticePickerInit) {
            return;
        }
        window.__robodeskNoticePickerInit = true;

        function initPicker(root) {
            var select = root.querySelector('select[name="robodesk_related_notices[]"]');
            var search = root.querySelector('.robodesk-notice-search');
            var selectedBox = root.querySelector('.robodesk-notice-selected');
            if (!select || !selectedBox) return;

            function renderChips() {
                selectedBox.innerHTML = '';
                var options = Array.prototype.slice.call(select.selectedOptions || []);
                options.forEach(function (option) {
                    var chip = document.createElement('span');
                    chip.className = 'robodesk-notice-chip';
                    chip.textContent = option.textContent;

                    var remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'robodesk-notice-chip-remove';
                    remove.setAttribute('aria-label', 'Remove');
                    remove.textContent = '×';
                    remove.addEventListener('click', function () {
                        option.selected = false;
                        renderChips();
                        select.dispatchEvent(new Event('change'));
                    });

                    chip.appendChild(remove);
                    selectedBox.appendChild(chip);
                });
            }

            select.addEventListener('change', renderChips);
            renderChips();

            if (search) {
                search.addEventListener('input', function () {
                    var query = search.value.toLowerCase();
                    var options = select.options;
                    for (var i = 0; i < options.length; i++) {
                        options[i].style.display =
                            options[i].textContent.toLowerCase().indexOf(query) !== -1 ? '' : 'none';
                    }
                });
            }
        }

        function initAll() {
            var roots = document.querySelectorAll('.robodesk-notice-picker');
            for (var i = 0; i < roots.length; i++) {
                initPicker(roots[i]);
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAll);
        } else {
            initAll();
        }
    })();
    </script>
    <?php
}

/**
 * Register the "Notices" meta box on all assignable post types.
 */
function robodesk_add_notice_assignment_meta_boxes()
{
    foreach (array_keys(robodesk_get_notice_assignable_post_types()) as $pt) {
        add_meta_box(
            'robodesk_notice_assignment',
            __('Notices', 'robodesk'),
            'robodesk_render_notice_assignment_meta_box',
            $pt,
            'side',
            'default'
        );
    }
}
add_action('add_meta_boxes', 'robodesk_add_notice_assignment_meta_boxes');

/**
 * Render the "Notices" meta box on a post edit screen.
 */
function robodesk_render_notice_assignment_meta_box($post)
{
    wp_nonce_field('robodesk_notice_assignment', 'robodesk_notice_assignment_nonce');

    $notices = robodesk_get_active_notices_list();
    $saved = (array) get_post_meta($post->ID, '_robodesk_related_notices', true);

    if (empty($notices)) {
        echo '<p>' . esc_html__('No active notices found. Create and activate a notice first.', 'robodesk') . '</p>';
        return;
    }

    echo '<p class="description" style="margin-top:0;">' . esc_html__('Show these notices in the chat widget on this item.', 'robodesk') . '</p>';
    robodesk_render_notice_assignment_picker($notices, $saved, robodesk_get_post_term_notice_sources($post->ID));
}

/**
 * Save the "Notices" assignment on post save.
 */
function robodesk_save_notice_assignment_meta($post_id)
{
    if (!isset($_POST['robodesk_notice_assignment_nonce'])) {
        return;
    }
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['robodesk_notice_assignment_nonce'])), 'robodesk_notice_assignment')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $pt = get_post_type($post_id);
    if (!array_key_exists($pt, robodesk_get_notice_assignable_post_types())) {
        return;
    }

    $notice_ids = array();
    if (isset($_POST['robodesk_related_notices']) && is_array($_POST['robodesk_related_notices'])) {
        $notice_ids = array_values(array_filter(array_map('absint', wp_unslash($_POST['robodesk_related_notices']))));
    }

    if (empty($notice_ids)) {
        delete_post_meta($post_id, '_robodesk_related_notices');
    } else {
        update_post_meta($post_id, '_robodesk_related_notices', $notice_ids);
    }
}
add_action('save_post', 'robodesk_save_notice_assignment_meta');

/**
 * Render the "Notices" field on a taxonomy term add/edit screen.
 */
function robodesk_render_notice_assignment_term_field($term = null)
{
    $notices = robodesk_get_active_notices_list();
    $saved = array();
    if ($term && is_object($term) && !is_wp_error($term)) {
        $saved = (array) get_term_meta($term->term_id, '_robodesk_related_notices', true);
    }
    ?>
    <div class="form-field robodesk-notice-assignment-field">
        <label for="robodesk_related_notices"><?php esc_html_e('Notices', 'robodesk'); ?></label>
        <?php if (empty($notices)) : ?>
            <p><?php esc_html_e('No active notices found. Create and activate a notice first.', 'robodesk'); ?></p>
        <?php else : ?>
            <p class="description" style="margin-top:0;"><?php esc_html_e('Show these notices in the chat widget when content in this term is viewed.', 'robodesk'); ?></p>
            <?php robodesk_render_notice_assignment_picker($notices, $saved); ?>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Register the term field rendering + saving for every assignable taxonomy.
 */
function robodesk_init_notice_assignment_term_fields()
{
    foreach (array_keys(robodesk_get_notice_assignable_taxonomies()) as $tax) {
        // The add-form hook passes the taxonomy name (string), not a term.
        add_action($tax . '_add_form_fields', function ($taxonomy) {
            robodesk_render_notice_assignment_term_field();
        }, 10, 1);

        add_action($tax . '_edit_form_fields', function ($term) {
            if (!is_object($term) || is_wp_error($term)) {
                return;
            }
            ?>
            <tr class="form-field">
                <th scope="row"><?php esc_html_e('Notices', 'robodesk'); ?></th>
                <td>
                    <?php robodesk_render_notice_assignment_term_field($term); ?>
                </td>
            </tr>
            <?php
        }, 10, 1);

        add_action('created_' . $tax, 'robodesk_save_notice_assignment_term_meta', 10, 2);
        add_action('edited_' . $tax, 'robodesk_save_notice_assignment_term_meta', 10, 2);
    }
}
add_action('admin_init', 'robodesk_init_notice_assignment_term_fields');

/**
 * Save the "Notices" assignment on term save.
 */
function robodesk_save_notice_assignment_term_meta($term_id, $taxonomy = '')
{
    if (!array_key_exists($taxonomy, robodesk_get_notice_assignable_taxonomies())) {
        return;
    }
    if (!current_user_can('edit_term', $term_id)) {
        return;
    }

    $notice_ids = array();
    // phpcs:disable WordPress.Security.NonceVerification.Missing -- WP core verifies the term-edit nonce (update-tag_{taxonomy}) in wp-admin/term.php before this hook fires.
    if (isset($_POST['robodesk_related_notices']) && is_array($_POST['robodesk_related_notices'])) {
        $notice_ids = array_values(array_filter(array_map('absint', wp_unslash($_POST['robodesk_related_notices']))));
    }
    // phpcs:enable WordPress.Security.NonceVerification.Missing

    if (empty($notice_ids)) {
        delete_term_meta($term_id, '_robodesk_related_notices');
    } else {
        update_term_meta($term_id, '_robodesk_related_notices', $notice_ids);
    }
}

/**
 * Resolve notices for the current page context.
 *
 * Combines notices assigned directly to the post with notices assigned to
 * the post's taxonomy terms, deduped by notice ID.
 *
 * @param int   $post_id  Post ID being viewed (0 on taxonomy archives).
 * @param array $term_ids Term IDs the current post belongs to.
 * @return array Resolved notices.
 */
function robodesk_resolve_context_notices($post_id = 0, $term_ids = array())
{
    $notices_by_id = array();

    // Directly assigned notices (post meta stores an array of notice IDs).
    if ($post_id > 0) {
        $assigned = (array) get_post_meta($post_id, '_robodesk_related_notices', true);
        $assigned = array_values(array_filter(array_map('absint', $assigned)));

        if (!empty($assigned)) {
            $direct = get_posts(array(
                'post_type'      => 'notice',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'post__in'       => $assigned,
                'meta_query'     => array(
                    array(
                        'key'   => '_robodesk_notice_active',
                        'value' => 'true',
                        'compare' => '=',
                    ),
                ),
            ));
            foreach ($direct as $n) {
                $notices_by_id[$n->ID] = $n;
            }
        }
    }

    // Notices assigned to the post's taxonomy terms.
    $term_ids = array_values(array_filter(array_map('absint', $term_ids)));
    if (!empty($term_ids)) {
        $term_notice_ids = array();
        foreach ($term_ids as $term_id) {
            $meta = (array) get_term_meta($term_id, '_robodesk_related_notices', true);
            foreach ($meta as $nid) {
                $term_notice_ids[(int) $nid] = (int) $nid;
            }
        }
        $term_notice_ids = array_values(array_filter($term_notice_ids));

        if (!empty($term_notice_ids)) {
            $from_terms = get_posts(array(
                'post_type'      => 'notice',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'post__in'       => $term_notice_ids,
                'meta_query'     => array(
                    array(
                        'key'   => '_robodesk_notice_active',
                        'value' => 'true',
                        'compare' => '=',
                    ),
                ),
            ));
            foreach ($from_terms as $n) {
                $notices_by_id[$n->ID] = $n;
            }
        }
    }

    $notices = array_values($notices_by_id);
    usort($notices, function ($a, $b) {
        return strtotime($b->post_date) - strtotime($a->post_date);
    });

    return $notices;
}

/**
 * Resolve the current page context for the chat widget notices dropdown.
 *
 * On a singular page/product/CPT this returns the post ID plus the term IDs
 * of every assignable taxonomy the post belongs to. On a taxonomy archive it
 * returns the term IDs only.
 */
function robodesk_get_current_notice_context()
{
    $context = array(
        'post_id'   => 0,
        'post_type' => '',
        'term_ids'  => array(),
    );

    $queried = get_queried_object();

    if ($queried instanceof WP_Post) {
        $context['post_id']   = (int) $queried->ID;
        $context['post_type'] = $queried->post_type;

        foreach (array_keys(robodesk_get_notice_assignable_taxonomies()) as $tax) {
            $terms = get_the_terms($queried->ID, $tax);
            if ($terms && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $context['term_ids'][] = (int) $term->term_id;
                }
            }
        }
    } elseif ($queried instanceof WP_Term) {
        $context['term_ids'] = array((int) $queried->term_id);
    }

    $context['term_ids'] = array_values(array_unique(array_filter($context['term_ids'])));

    return $context;
}

/**
 * REST: GET /wp-json/robodesk/v1/context-notices?post_id=123&term_ids=1,2,3
 */
function robodesk_register_context_notices_route()
{
    register_rest_route('robodesk/v1', '/context-notices', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'robodesk_rest_get_context_notices',
        'permission_callback' => '__return_true',
        'args'                => array(
            'post_id'  => array(
                'required'          => false,
                'sanitize_callback' => 'absint',
                'validate_callback' => function ($value) {
                    return is_numeric($value);
                },
            ),
            'term_ids' => array(
                'required'          => false,
                'sanitize_callback' => 'robodesk_sanitize_comma_separated_ids',
            ),
        ),
    ));
}
add_action('rest_api_init', 'robodesk_register_context_notices_route');

function robodesk_sanitize_comma_separated_ids($value)
{
    $ids = explode(',', is_array($value) ? implode(',', $value) : (string) $value);
    return array_values(array_filter(array_map('absint', $ids)));
}

function robodesk_rest_get_context_notices($request)
{
    $post_id = isset($request['post_id']) ? absint($request['post_id']) : 0;
    $term_ids = isset($request['term_ids']) ? array_map('absint', (array) $request['term_ids']) : array();

    $notices = robodesk_resolve_context_notices($post_id, $term_ids);

    $data = array_map(function ($n) {
        return array(
            'id'              => $n->ID,
            'title'           => array('rendered' => $n->post_title),
            // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Intentionally applying WP core content filters.
            'excerpt'         => array('rendered' => apply_filters('the_excerpt', $n->post_excerpt)),
            'content'         => array('rendered' => apply_filters('the_content', $n->post_content)),
            // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            'notice_action'   => get_post_meta($n->ID, '_robodesk_notice_action', true) ?: 'open',
            'initial_message' => (string) get_post_meta($n->ID, '_robodesk_notice_initial_message', true),
        );
    }, $notices);

    return rest_ensure_response($data);
}
