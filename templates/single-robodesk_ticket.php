<?php

/**
 * Template for displaying single robodesk_ticket posts
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header(); ?>

<div class="robodesk-container">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                    <div class="entry-meta">
                        <span class="ticket-status">Status: <?php echo esc_html(get_post_meta(get_the_ID(), '_robodesk_ticket_status', true)); ?></span> |
                        <span class="ticket-priority">Priority: <?php
                                                                $robodesk_priority_terms = wp_get_object_terms(get_the_ID(), 'robodesk_ticket_priority');
                                                                $robodesk_priority = !empty($robodesk_priority_terms) && !is_wp_error($robodesk_priority_terms) ? $robodesk_priority_terms[0]->name : 'N/A';
                                                                echo esc_html($robodesk_priority);
                                                                ?></span> |
                        <span class="ticket-origin">Origin: <?php
                                                            $robodesk_origin_terms = wp_get_object_terms(get_the_ID(), 'robodesk_ticket_origin');
                                                            $robodesk_origin = !empty($robodesk_origin_terms) && !is_wp_error($robodesk_origin_terms) ? $robodesk_origin_terms[0]->name : 'N/A';
                                                            echo esc_html($robodesk_origin);
                                                            ?></span> |
                        <span class="ticket-date">Date: <?php the_date(); ?></span>
                    </div>
                </header>

                <div class="entry-content">
                    <?php the_content(); ?>
                </div>

                <?php robodesk_display_notes(get_the_ID()); ?>
                <?php echo do_shortcode('[robodesk_note_form ticket_id="' . get_the_ID() . '"]'); ?>

                <div id="robodesk-credentials-modal" style="display: none;">
                    <div class="robodesk-modal-content">
                        <span id="robodesk-close-modal">&times;</span>
                        <div id="robodesk-credentials-list">
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
                                        <td><?php echo esc_html(get_post_meta(get_the_ID(), '_robodesk_credential_url', true)); ?></td>
                                        <td><?php echo esc_html(get_post_meta(get_the_ID(), '_robodesk_credential_username', true)); ?></td>
                                        <td><?php echo esc_html(get_post_meta(get_the_ID(), '_robodesk_credential_password', true)); ?></td>
                                        <td><?php echo esc_html(get_post_meta(get_the_ID(), '_robodesk_credential_notes', true)); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="robodesk-credentials-form">
                            <h2>Add Credentials</h2>
                            <label for="credential_url">URL:</label><br>
                            <input type="text" id="credential_url"><br><br>

                            <label for="credential_username">Username:</label><br>
                            <input type="text" id="credential_username"><br><br>

                            <label for="credential_password">Password:</label><br>
                            <input type="password" id="credential_password"><br><br>

                            <label for="credential_notes">Notes:</label><br>
                            <textarea id="credential_notes"></textarea><br><br>

                            <button id="robodesk-save-credential">Save Credential</button>
                        </div>
                    </div>
                </div>
                <button id="robodesk-add-credentials-btn">Add Credentials</button>
                <button id="robodesk-show-credentials-btn">Show Credentials</button>

            </article>

    <?php endwhile;
    endif; ?>
</div>

<?php get_footer(); ?>