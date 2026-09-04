<?php

/**
 * Template for displaying single mmhd_ticket posts
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header(); ?>

<div class="mmhd-container">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                    <div class="entry-meta">
                        <span class="ticket-status">Status: <?php echo esc_html(get_post_meta(get_the_ID(), '_mmhd_ticket_status', true)); ?></span> |
                        <span class="ticket-priority">Priority: <?php
                                                                $mmhd_priority_terms = wp_get_object_terms(get_the_ID(), 'mmhd_ticket_priority');
                                                                $mmhd_priority = !empty($mmhd_priority_terms) && !is_wp_error($mmhd_priority_terms) ? $mmhd_priority_terms[0]->name : 'N/A';
                                                                echo esc_html($mmhd_priority);
                                                                ?></span> |
                        <span class="ticket-origin">Origin: <?php
                                                            $mmhd_origin_terms = wp_get_object_terms(get_the_ID(), 'mmhd_ticket_origin');
                                                            $mmhd_origin = !empty($mmhd_origin_terms) && !is_wp_error($mmhd_origin_terms) ? $mmhd_origin_terms[0]->name : 'N/A';
                                                            echo esc_html($mmhd_origin);
                                                            ?></span> |
                        <?php $mmhd_ticket_utc = function_exists('mmhd_post_utc_iso') ? mmhd_post_utc_iso(get_post()) : get_the_date('Y-m-d H:i:s'); ?>
                        <span class="ticket-date">Date: <time class="mmhd-local-time"
                                datetime="<?php echo esc_attr($mmhd_ticket_utc); ?>"
                                data-utc="<?php echo esc_attr($mmhd_ticket_utc); ?>"
                                data-format="date"><?php echo esc_html(get_the_date()); ?></time></span>
                    </div>
                </header>

                <div class="entry-content">
                    <?php the_content(); ?>
                </div>

                <?php mmhd_display_notes(get_the_ID()); ?>
                <?php echo do_shortcode('[mmhd_note_form ticket_id="' . get_the_ID() . '"]'); ?>

                <div id="mmhd-credentials-modal" style="display: none;">
                    <div class="mmhd-modal-content">
                        <span id="mmhd-close-modal">&times;</span>
                        <div id="mmhd-credentials-list">
                            <h2>Credentials</h2>
                            <table>
                                <thead>
                                    <tr>
                                        <th>URL</th>
                                        <th>Username</th>
                                        <th>Password</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?php echo esc_html(get_post_meta(get_the_ID(), '_mmhd_credential_url', true)); ?></td>
                                        <td><?php echo esc_html(get_post_meta(get_the_ID(), '_mmhd_credential_username', true)); ?></td>
                                        <td><?php echo esc_html(get_post_meta(get_the_ID(), '_mmhd_credential_password', true)); ?></td>
                                        <td><?php echo esc_html(get_post_meta(get_the_ID(), '_mmhd_credential_notes', true)); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="mmhd-credentials-form">
                            <h2>Add Credentials</h2>
                            <label for="credential_url">URL:</label><br>
                            <input type="text" id="credential_url"><br><br>

                            <label for="credential_username">Username:</label><br>
                            <input type="text" id="credential_username"><br><br>

                            <label for="credential_password">Password:</label><br>
                            <input type="password" id="credential_password"><br><br>

                            <label for="credential_notes">Notes:</label><br>
                            <textarea id="credential_notes"></textarea><br><br>

                            <button id="mmhd-save-credential">Save Credential</button>
                        </div>
                    </div>
                </div>
                <button id="mmhd-add-credentials-btn">Add Credentials</button>
                <button id="mmhd-show-credentials-btn">Show Credentials</button>

            </article>

    <?php endwhile;
    endif; ?>
</div>

<?php get_footer(); ?>
