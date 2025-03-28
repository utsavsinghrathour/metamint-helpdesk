<?php
if (post_password_required()) {
    return;
}
?>

<div id="comments" class="comments-area">
    <?php if (have_comments()) : ?>
        <h3>Replies</h3>
        <ol class="comment-list">
            <?php
            wp_list_comments(array(
                'style' => 'ol',
                'type' => 'ticket_replies',
                'callback' => function($comment, $args, $depth) {
                    ?>
                    <li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
                        <div class="comment-body">
                            <div class="comment-meta">
                                <div class="comment-author vcard">
                                    <?php echo get_avatar($comment, 50); // Add avatar here ?>
                                    <?php printf('<cite>%s</cite>', get_comment_author_link()); ?>
                                </div>
                                <div class="comment-metadata">
                                    <?php
                                    printf('<time>%s</time>', get_comment_date('Y-m-d H:i:s'));
                                    echo ' (' . human_time_diff(get_comment_time('U'), current_time('timestamp')) . ')';
                                    ?>
                                </div>
                            </div>
                            <div class="comment-content">
                                <?php comment_text(); ?>
                            </div>
                            <!-- <div class="reply"> -->
                                <?php
                                // comment_reply_link(array_merge($args, array(
                                //     'depth' => $depth,
                                //     'max_depth' => $args['max_depth'],
                                //     'add_below' => 'comment'
                                // )));
                                ?>
                            <!-- </div> -->
                        </div>
                    </li>
                    <?php
                }
            ));
            ?>
        </ol>
    <?php endif; ?>

    <?php if (comments_open()) : ?>
        <div id="respond" class="comment-respond">
            <?php
            comment_form(array(
                'title_reply' => 'Leave a Reply',
                'comment_notes_before' => '',
                'comment_notes_after' => '',
                'id_form' => 'commentform',
                'id_submit' => 'submit',
                'class_submit' => 'submit',
                'submit_field' => '<p class="form-submit">%1$s %2$s</p>',
                'action' => '' 
            ));
            ?>
        </div>
    <?php endif; ?>
</div>