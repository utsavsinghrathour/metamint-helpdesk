<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (post_password_required()) {
    return;
}
?>

<div id="comments" class="comments-area">
    <?php if (have_comments()) : ?>
    <ol class="comment-list">
        <?php
            wp_list_comments(array(
                'style' => 'ol',
                'type' => 'ticket_replies',
                'callback' => function ($comment, $args, $depth) {
                    static $robodesk_previous_author_id = null;
                    static $robodesk_previous_is_sent = null;
                    static $robodesk_previous_is_bot = null;

                    $robodesk_is_bot = function_exists('robodesk_is_bot_comment') ? robodesk_is_bot_comment($comment->comment_ID) : false;
                    $robodesk_is_sent = !$robodesk_is_bot && (int) $comment->user_id === (int) get_current_user_id();
                    $robodesk_is_same_sender = $robodesk_previous_author_id !== null
                        && (int) $robodesk_previous_author_id === (int) $comment->user_id
                        && $robodesk_previous_is_sent === $robodesk_is_sent
                        && $robodesk_previous_is_bot === $robodesk_is_bot;

                    $robodesk_item_classes = array(
                        $robodesk_is_sent ? 'robodesk-comment-sent' : 'robodesk-comment-received',
                    );

                    if ($robodesk_is_same_sender) {
                        $robodesk_item_classes[] = 'robodesk-comment-same-sender';
                    }
            ?>
        <li <?php comment_class($robodesk_item_classes); ?> id="comment-<?php comment_ID(); ?>">
            <div class="comment-body">
                <?php
                        $robodesk_comment_avatar = function_exists('robodesk_get_ticket_comment_avatar_url')
                            ? robodesk_get_ticket_comment_avatar_url($comment, 50)
                            : get_avatar_url($comment, array('size' => 50));
                        $robodesk_comment_author = function_exists('robodesk_get_ticket_comment_author_name')
                            ? robodesk_get_ticket_comment_author_name($comment)
                            : get_comment_author();
                        ?>
                <div class="comment-author vcard">
                    <?php if (!$robodesk_is_same_sender) : ?>
                    <img alt="<?php echo esc_attr($robodesk_comment_author); ?>"
                        src="<?php echo esc_url($robodesk_comment_avatar); ?>" class="avatar avatar-50 photo"
                        height="50" width="50" />
                    <?php endif; ?>
                </div>
                <div class="robodesk-comment-bubble">
                    <?php if (!$robodesk_is_same_sender) : ?>
                    <div class="comment-meta">
                        <cite><?php echo esc_html($robodesk_is_sent ? 'You' : $robodesk_comment_author); ?></cite>
                    </div>
                    <?php endif; ?>
                    <div class="comment-content">
                        <?php comment_text(); ?>
                    </div>
                    <div class="comment-metadata">
                        <?php
                                printf('<time>%s</time>', esc_html(get_comment_date('Y-m-d H:i:s')));
                                ?>
                    </div>
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
                    $robodesk_previous_author_id = (int) $comment->user_id;
                    $robodesk_previous_is_sent = $robodesk_is_sent;
                    $robodesk_previous_is_bot = $robodesk_is_bot;
                }
            ));
            ?>
    </ol>
    <?php endif; ?>
    <?php if (comments_open()) : ?>
    <?php
        comment_form(array(
            'title_reply' => '',
            'label_submit' => 'Add Reply',
            'comment_notes_before' => '',
            'comment_notes_after' => '',
            'id_form' => 'commentform',
            'id_submit' => 'submit',
            'class_submit' => 'submit',
            'submit_field' => '<p class="form-submit">%1$s %2$s</p>'

        ));
        ?>

    <?php endif; ?>


</div>