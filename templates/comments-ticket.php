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
                    static $mmhd_previous_author_id = null;
                    static $mmhd_previous_is_sent = null;
                    static $mmhd_previous_is_bot = null;

                    $mmhd_current_user = wp_get_current_user();
                    $mmhd_current_user_id = (int) get_current_user_id();
                    $mmhd_current_user_email = $mmhd_current_user && !empty($mmhd_current_user->user_email) ? strtolower((string) $mmhd_current_user->user_email) : '';
                    $mmhd_comment_email = !empty($comment->comment_author_email) ? strtolower((string) $comment->comment_author_email) : '';
                    $mmhd_comment_author_type = (string) get_comment_meta($comment->comment_ID, 'author_type', true);
                    $mmhd_comment_source = (string) get_comment_meta($comment->comment_ID, 'comment_source', true);
                    $mmhd_ticket_customer_user_id = function_exists('mmhd_get_ticket_customer_user_id') ? (int) mmhd_get_ticket_customer_user_id($comment->comment_post_ID) : 0;

                    $mmhd_is_bot = function_exists('mmhd_is_bot_comment') ? mmhd_is_bot_comment($comment->comment_ID) : false;

                    // Determine if this comment was sent by the current (customer) user.
                    // Email replies from customers (comment_source = 'email', author_type = 'customer')
                    // must also be treated as "sent" so they appear on the right side, matching
                    // the customer's chat replies.
                    $mmhd_is_email_reply_from_customer = (
                        $mmhd_comment_source === 'email' &&
                        $mmhd_comment_author_type === 'customer' &&
                        (
                            $mmhd_ticket_customer_user_id === $mmhd_current_user_id ||
                            ($mmhd_current_user_email !== '' && $mmhd_comment_email === $mmhd_current_user_email)
                        )
                    );

                    $mmhd_is_sent = !$mmhd_is_bot && (
                        (int) $comment->user_id === $mmhd_current_user_id ||
                        ($mmhd_current_user_email !== '' && $mmhd_comment_email === $mmhd_current_user_email) ||
                        ($mmhd_comment_author_type === 'customer' && $mmhd_ticket_customer_user_id === $mmhd_current_user_id) ||
                        $mmhd_is_email_reply_from_customer
                    );
                    $mmhd_sender_key = (int) $comment->user_id > 0
                        ? 'user:' . (int) $comment->user_id
                        : 'email:' . $mmhd_comment_email . ':' . $mmhd_comment_author_type;
                    $mmhd_is_same_sender = $mmhd_previous_author_id !== null
                        && $mmhd_previous_author_id === $mmhd_sender_key
                        && $mmhd_previous_is_sent === $mmhd_is_sent
                        && $mmhd_previous_is_bot === $mmhd_is_bot;

                    $mmhd_item_classes = array(
                        $mmhd_is_sent ? 'mmhd-comment-sent' : 'mmhd-comment-received',
                    );

                    if ($mmhd_is_same_sender) {
                        $mmhd_item_classes[] = 'mmhd-comment-same-sender';
                    }
            ?>
                <li <?php comment_class($mmhd_item_classes); ?> id="comment-<?php comment_ID(); ?>">
                    <div class="comment-body">
                        <?php
                        $mmhd_comment_avatar = function_exists('mmhd_get_ticket_comment_avatar_url')
                            ? mmhd_get_ticket_comment_avatar_url($comment, 50)
                            : get_avatar_url($comment, array('size' => 50));
                        $mmhd_comment_author = function_exists('mmhd_get_ticket_comment_author_name')
                            ? mmhd_get_ticket_comment_author_name($comment)
                            : get_comment_author();
                        ?>
                        <div class="comment-author vcard">
                            <?php if (!$mmhd_is_same_sender) : ?>
                                <img alt="<?php echo esc_attr($mmhd_comment_author); ?>"
                                    src="<?php echo esc_url($mmhd_comment_avatar); ?>" class="avatar avatar-50 photo"
                                    height="50" width="50" />
                            <?php endif; ?>
                        </div>
                        <div class="mmhd-comment-bubble">
                            <?php if (!$mmhd_is_same_sender) : ?>
                                <div class="comment-meta">
                                    <cite><?php echo esc_html($mmhd_is_sent ? 'You' : $mmhd_comment_author); ?></cite>
                                </div>
                            <?php endif; ?>
                            <div class="comment-content">
                                <?php comment_text(); ?>
                            </div>
                            <div class="comment-metadata">
                                <?php
                                $mmhd_comment_utc = function_exists('mmhd_comment_utc_iso') ? mmhd_comment_utc_iso($comment) : get_comment_date('Y-m-d H:i:s');
                                printf(
                                    '<time class="mmhd-local-time" datetime="%1$s" data-utc="%1$s" data-format="datetime">%2$s</time>',
                                    esc_attr($mmhd_comment_utc),
                                    esc_html(get_comment_date('Y-m-d H:i:s'))
                                );
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
                    $mmhd_previous_author_id = $mmhd_sender_key;
                    $mmhd_previous_is_sent = $mmhd_is_sent;
                    $mmhd_previous_is_bot = $mmhd_is_bot;
                }
            ));
            ?>
        </ol>
    <?php endif; ?>
    <?php if (comments_open()) : ?>
        <?php
        add_filter('comment_form_submit_field', function ($submit_field) {
            $nonce = wp_nonce_field('mmhd_reply_action', 'mmhd_reply_nonce', true, false);
            return $submit_field . $nonce;
        });
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
