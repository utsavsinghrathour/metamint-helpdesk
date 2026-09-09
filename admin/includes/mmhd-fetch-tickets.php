<?php
// phpcs:disable WordPress.DB.SlowDBQuery
// mmhd-email-to-ticket
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Function to display the email to ticket
function mmhd_fetch_emails_callback()
{
    echo '<h1>Email To Ticket</h1>';
    echo '<p>Click the button below to fetch Tickets</p>';
    echo '<form method="post" action="">';
    wp_nonce_field('mmhd_fetch_emails_action', 'mmhd_fetch_emails_nonce'); // Add nonce field for security
    echo '<input type="submit" name="fetch_emails" value="Fetch Tickets" class="button button-primary">';
    echo '</form>';
    // Check if the fetch emails button is clicked and nonce is valid
    if (isset($_POST['fetch_emails']) && check_admin_referer('mmhd_fetch_emails_action', 'mmhd_fetch_emails_nonce')) {
        mmhd_fetch_emails();
    }

    echo '<table class="wp-list-table widefat fixed striped  ">';
    echo '<tr><th>From</th><th>Subject</th><th>Date</th><th>Message</th></tr>';

    $posts = get_posts(
        [
            'post_type' => 'mmhd_ticket',
            'meta_key' => 'email_message_id',
            'numberposts' => -1 // Get all tickets with email_message_id meta
        ]
    );
    foreach ($posts as $post) {
        echo '<tr>';
        echo '<td>' . esc_html(get_post_meta($post->ID, 'email_from', true)) . '</td>';
        echo '<td>' . esc_html($post->post_title) . '</td>';
        echo '<td>' . esc_html($post->post_date) . '</td>';
        echo '<td>' . esc_html($post->post_content) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

// Find the ticket a reply belongs to by matching a message ID across
// ticket post meta AND reply comment meta (replies are stored as comments).
function mmhd_find_ticket_by_email_message_id($message_id)
{
    $message_id = trim((string) $message_id);
    if ($message_id === '') {
        return 0;
    }

    $ticket_query = new WP_Query([
        'meta_key' => 'email_message_id',
        'meta_value' => $message_id,
        'post_type' => 'mmhd_ticket',
        'posts_per_page' => 1,
        'fields' => 'ids',
    ]);
    if (!empty($ticket_query->posts)) {
        return (int) $ticket_query->posts[0];
    }

    $matching_comments = get_comments([
        'meta_key' => 'email_message_id',
        'meta_value' => $message_id,
        'number' => 1,
    ]);
    if (!empty($matching_comments)) {
        return (int) $matching_comments[0]->comment_post_ID;
    }

    return 0;
}

// Find the ticket a reply belongs to: first match message IDs across ticket
// post meta AND reply comment meta, then fall back to sender + normalized
// subject (covers replies to agent emails whose Message-ID is never stored).
function mmhd_find_ticket_by_reply_context($reference_ids, $useremail = '', $subject = '', $date = '')
{
    $reference_ids = is_array($reference_ids) ? $reference_ids : [];

    foreach ($reference_ids as $ref_id) {
        $matched_ticket_id = mmhd_find_ticket_by_email_message_id($ref_id);
        if ($matched_ticket_id) {
            mmhd_log('Matched reply by message ID to ticket ID: ' . $matched_ticket_id);
            return $matched_ticket_id;
        }
    }

    if (!empty($useremail) && $subject !== '') {
        $normalized_subject = strtolower(trim((string) preg_replace('/^\s*(re|fwd|fw|aw)\s*:\s*/i', '', $subject)));
        if ($normalized_subject !== '') {
            $date_args = [];
            if ($date !== '') {
                $date_args = [
                    [
                        'before'    => $date,
                        'inclusive' => false,
                    ],
                ];
            }
            $thread_query = new WP_Query([
                'post_type'      => 'mmhd_ticket',
                'post_status'    => 'publish',
                'posts_per_page' => 5,
                'fields'         => 'ids',
                'meta_query'     => [
                    [
                        'key'   => '_mmhd_customer_email',
                        'value' => $useremail,
                        'compare' => '=',
                    ],
                ],
                'date_query' => $date_args,
                'orderby'    => 'date',
                'order'      => 'DESC',
            ]);
            foreach ($thread_query->posts as $thread_post_id) {
                $ticket_subject = strtolower(trim((string) preg_replace('/^\s*(re|fwd|fw|aw)\s*:\s*/i', '', get_the_title($thread_post_id))));
                if ($ticket_subject === $normalized_subject) {
                    mmhd_log('Matched reply by sender + subject to ticket ID: ' . $thread_post_id);
                    return (int) $thread_post_id;
                }
            }
        }
    }

return 0;
}

// Detect whether a decoded email body is HTML or plain text.
function mmhd_email_body_is_html($message, $is_html = null)
{
    if ($is_html !== null) {
        return (bool) $is_html;
    }

    if (empty($message)) {
        return false;
    }

    $trimmed = ltrim($message);

    $is_html = preg_match('/^<!doctype\s+html/i', $trimmed) === 1
        || stripos($trimmed, '<html') === 0
        || (strpos($message, '<') !== false
            && preg_match('/<(\s*(?:html|body|div|p|br|span|table|b|strong|em|i|u|s|h[1-6]|ul|ol|li|a)\s|<\/(?:html|body|div|p|br|span|table|b|strong|em|i|u|s|h[1-6]|ul|ol|li|a)>)/i', $message) === 1);

    return $is_html;
}

// Walk the MIME structure and pull out the decoded text/plain and text/html
// bodies (handles multipart/alternative, nested multipart and attachments).
function mmhd_fetch_email_text_parts($email, $email_number, $structure)
{
    $result = ['html' => '', 'plain' => ''];

    $decode = function ($body, $encoding) {
        if ($body === false || $body === null) {
            return '';
        }

        // Safety net: some senders/gateways mislabel a base64 body (e.g. as
        // 7bit). If the body is pure base64 and decodes to readable text, use
        // the decoded content so raw base64 is never stored as the message.
        $decode_if_base64 = function ($raw) {
            if (!is_string($raw)) {
                return $raw;
            }
            $compact = preg_replace('/\s+/', '', $raw);
            if (strlen($compact) < 40 || !preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $compact)) {
                return $raw;
            }
            $decoded = base64_decode($raw, true);
            if ($decoded === false
                || $decoded === ''
                || strlen($decoded) < 30
                || preg_match('//u', $decoded) !== 1
                || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $decoded) === 1
                || (strpos($decoded, ' ') === false && strpos($decoded, '<') === false)) {
                return $raw;
            }
            return $decoded;
        };

        if ((int) $encoding === 3) {
            $decoded = base64_decode($body, true);
            // Strict mode rejects any non-alphabet byte; fall back to lenient
            // decoding so malformed payloads still yield usable content.
            return $decoded === false ? base64_decode($body) : $decoded;
        }
        if ((int) $encoding === 4) {
            return quoted_printable_decode($body);
        }
        return $decode_if_base64($body);
    };

    $get_charset = function ($part) {
        foreach (($part->parameters ?? []) as $param) {
            if (strtolower((string) ($param->attribute ?? '')) === 'charset') {
                return (string) $param->value;
            }
        }
        return '';
    };

    $to_utf8 = function ($body, $charset) {
        if ($charset !== '' && strtoupper($charset) !== 'UTF-8' && strtoupper($charset) !== 'US-ASCII' && strtoupper($charset) !== 'ASCII') {
            if (function_exists('mb_convert_encoding')) {
                $converted = @mb_convert_encoding($body, 'UTF-8', $charset);
                if ($converted !== false) {
                    return $converted;
                }
            }
        }
        return $body;
    };

    $fetch_first = function (array $sections) use ($email, $email_number) {
        foreach ($sections as $candidate) {
            $body = imap_fetchbody($email, $email_number, $candidate);
            if ($body !== false && $body !== null && $body !== '') {
                return $body;
            }
        }
        return '';
    };

    $walk = function ($struct, $section = '', $inside_rfc822 = false) use (&$walk, $email, $email_number, $decode, $get_charset, $to_utf8, $fetch_first, &$result) {
        $struct_type = (int) ($struct->type ?? 0);

        // Descend into multipart (type 1) containers and message/rfc822
        // (type 2) attached/forwarded messages. Section numbering follows
        // c-client: multipart containers are transparent, so an embedded
        // message's parts are addressed as 2.1, 2.2 ... (not 2.1.1).
        if (($struct_type === 1 || $struct_type === 2) && isset($struct->parts) && is_array($struct->parts)) {
            foreach ($struct->parts as $part_index => $part) {
                if ($struct_type === 2 && (int) ($part->type ?? 0) === 1) {
                    $walk($part, $section); // embedded multipart body: transparent
                    continue;
                }
                $child_section = $section === '' ? (string) ($part_index + 1) : $section . '.' . ($part_index + 1);
                $walk($part, $child_section, $struct_type === 2);
            }
            return;
        }

        if ($struct_type !== 0) {
            return; // Only textual leaf parts (type 0).
        }

        $subtype = strtoupper((string) ($struct->subtype ?? ''));
        $primary = $section !== '' ? $section : '1';

        // c-client also exposes the body of a simple embedded message as
        // N.TEXT; try that (and the bare part) if the numbered section is empty.
        $candidates = [$primary];
        if ($inside_rfc822 && preg_match('/(\.\d+)$/', $primary, $m)) {
            $prefix = substr($primary, 0, -strlen($m[1]));
            $candidates[] = $prefix . '.TEXT';
            $candidates[] = $prefix;
        }

        if ($subtype === 'PLAIN' && $result['plain'] === '') {
            $body = $decode($fetch_first($candidates), $struct->encoding ?? 0);
            $result['plain'] = $to_utf8($body, $get_charset($struct));
        } elseif ($subtype === 'HTML' && $result['html'] === '') {
            $body = $decode($fetch_first($candidates), $struct->encoding ?? 0);
            $result['html'] = $to_utf8($body, $get_charset($struct));
        }
    };

    $walk($structure);

    return $result;
}

// Clean a plain-text email body: strip quoted/forwarded thread content while
// preserving the signature, then convert to HTML for display.
function mmhd_clean_plain_email_message($message)
{
    if (empty($message)) {
        return '';
    }

    // 1. Cut the message at common plain-text reply / forward markers. The
    // message that precedes the marker is the new content, everything after it
    // is the quoted thread we no longer want (keeps replies independent).
    $split_patterns = array(
        // On Thu, 27 Aug 2026 at 12:00, Someone <x@y.com> wrote:
        '/[\r\n]+\s*On\s+(?:(?!\bOn\b).){1,300}?\s+wrote:\s*/i',
        // -----Original Message----- / ---Original Message---
        '/[\r\n]+\s*[-_=]{3,}\s*Original Message\s*[-_=]{3,}/i',
        // Outlook / webmail header block: From: ... Sent/Date/Subject/To: ...
        '/[\r\n]+\s*From:\s+[^\r\n]+[\r\n]+(?:\s*(?:To|Cc|Bcc|Sent|Date|Subject|Importance|MIME-Version):\s*[^\r\n]*[\r\n]*)+/i',
        // Apple Mail / Gmail "---------- Forwarded message ----------"
        '/[\r\n]+\s*[-_=]{3,}\s*Forwarded message\s*[-_=]{3,}/i',
        '/[\r\n]+\s*Begin forwarded message:\s*[\r\n]+/i',
    );
    foreach ($split_patterns as $pattern) {
        $parts = preg_split($pattern, $message, 2);
        if (count($parts) > 1) {
            $message = $parts[0];
        }
    }

    // 2. Remove lines starting with ">" (quoted text).
    $message = preg_replace('/^>.*$/m', '', $message);

    // 3. Remove a single "On ... wrote:" line that may have survived at the end.
    $message = preg_replace('/[\r\n]+\s*On\s+(?:(?!\bOn\b).){1,300}?\s+wrote:\s*$/i', '', $message);

    // 4. Trim trailing divider lines (dashes/underscores/equals) — these often
    // separate the message from the quoted block. A signature below the divider
    // is kept because the message ends with the signature text, not the divider.
    $message = preg_replace('/[\r\n]+\s*[-_=]{6,}\s*$/', '', $message);

    // 5. Collapse excessive whitespace.
    $message = preg_replace('/\h+/', ' ', $message);
    $message = preg_replace('/\v+/', "\n", $message);

    $message = trim($message);
    if ($message === '') {
        return '';
    }

    // 6. Convert newlines to <br> so line breaks are preserved, linkify URLs,
    // then sanitize so the stored content is safe for HTML rendering.
    $message = nl2br($message);
    $message = make_clickable($message);
    $message = wp_kses_post($message);

    return trim($message);
}

// Clean an HTML email body with a DOM parser: keep text formatting (bold,
// italic, lists, links, headings), drop scripts/styles/images and the quoted
// thread (blockquotes, gmail/outlook/yahoo quote containers), keep signature.
function mmhd_clean_html_email_message($html)
{
    if (empty($html)) {
        return '';
    }

    $has_dom = class_exists('DOMDocument');

    if (!$has_dom) {
        // Fallback: regex-based thread stripping, then plain-text cleaning.
        $html_split_patterns = array(
            '/<blockquote[\s>]/i',
            '/<div[^>]*class=["\'][^"\']*gmail_quote["\'][^>]*>/i',
            '/<div[^>]*class=["\'][^"\']*gmail_extra["\'][^>]*>/i',
            '/<div[^>]*class=["\'][^"\']*email-quote["\'][^>]*>/i',
            '/<div[^>]*class=["\'][^"\']*yahoo_quoted["\'][^>]*>/i',
            '/<div[^>]*class=["\'][^"\']*originalMessage["\'][^>]*>/i',
            '/<hr[^>]*id=["\'](?:stopSpelling|divRplyFwdMsg)["\'][^>]*>/i',
        );
        foreach ($html_split_patterns as $pattern) {
            $parts = preg_split($pattern, $html, 2);
            if (count($parts) > 1) {
                $html = $parts[0];
            }
        }

        return wp_kses_post(trim(nl2br(wp_strip_all_tags($html))));
    }

    // Wrap the HTML in a proper document so DOMDocument preserves all content.
    $html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>';

    $previous_internal_errors = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $loaded = $dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();
    libxml_use_internal_errors($previous_internal_errors);

    if (!$loaded) {
        return wp_kses_post(trim(nl2br(wp_strip_all_tags($html))));
    }

    $xpath = new DOMXPath($dom);

    // Drop non-content elements entirely.
    $drop_selector = '//head | //style | //script | //title | //meta | //link | //noscript | //iframe | //object | //embed | //form | //input | //button | //select | //textarea | //svg | //canvas | //img';
    foreach ($xpath->query($drop_selector) as $node) {
        if ($node->parentNode) {
            $node->parentNode->removeChild($node);
        }
    }

    // Remove quoted / forwarded thread containers (keeps each reply independent).
    // Gmail/Outlook append random hashes to classes, so match class substrings.
    $quote_selector = '//blockquote'
        . ' | //*[contains(@class, "gmail_quote")]'
        . ' | //*[contains(@class, "gmail_extra")]'
        . ' | //*[contains(@class, "yahoo_quoted")]'
        . ' | //*[contains(@class, "email-quote")]'
        . ' | //*[contains(@class, "originalMessage")]'
        . ' | //*[contains(@class, "apple-mail-inner-quote")]';
    foreach ($xpath->query($quote_selector) as $node) {
        if ($node->parentNode) {
            $node->parentNode->removeChild($node);
        }
    }

    // Outlook: everything below the "stopSpelling"/"divRplyFwdMsg" rule is the
    // forwarded/replied thread — remove the rule and all following siblings.
    $outlook_rules = $xpath->query('//hr[contains(@id, "stopSpelling")] | //div[contains(@id, "divRplyFwdMsg")] | //hr[contains(@id, "divRplyFwdMsg")] | //hr[contains(@class, "stopSpelling")]');
    foreach ($outlook_rules as $node) {
        $parent = $node->parentNode;
        if (!$parent) {
            continue;
        }
        while ($node->nextSibling) {
            $parent->removeChild($node->nextSibling);
        }
        $parent->removeChild($node);
    }

    // Strip inline layout styles; semantic tags keep the formatting.
    foreach ($xpath->query('//*[@style]') as $node) {
        $node->removeAttribute('style');
    }
    foreach ($xpath->query('//*[@class]') as $node) {
        $node->removeAttribute('class');
    }
    foreach ($xpath->query('//*[@id]') as $node) {
        $node->removeAttribute('id');
    }

    // Neutralise dangerous link protocols; open in a new tab.
    foreach ($xpath->query('//a') as $node) {
        $href = trim((string) $node->getAttribute('href'));
        if (preg_match('/^\s*(?:javascript|vbscript|data):/i', $href)) {
            $node->removeAttribute('href');
        } else {
            $node->setAttribute('target', '_blank');
            $node->setAttribute('rel', 'noopener nofollow');
        }
    }

    // Remove stray "On ... wrote:" header text nodes that sit outside a
    // blockquote (e.g. Outlook HTML replies).
    $text_nodes = $xpath->query('//text()');
    foreach ($text_nodes as $text_node) {
        $text = $text_node->nodeValue;
        if (preg_match('/^\s*On\s+(?:(?!\bOn\b).){1,300}?\s+wrote:\s*$/i', trim($text))) {
            $text_node->nodeValue = '';
        }
    }

    // Serialize just the body children (keeps <p>/<br>/lists formatting).
    $body = $dom->getElementsByTagName('body')->item(0);
    if ($body) {
        $inner = '';
        foreach ($body->childNodes as $child) {
            $inner .= $dom->saveHTML($child);
        }
        $html_out = $inner;
    } else {
        $html_out = $dom->saveHTML();
    }

    $html_out = wp_kses_post($html_out);
    $html_out = preg_replace('/[\r\n\t]+/', ' ', $html_out);
    $html_out = preg_replace('/[ \t]{2,}/', ' ', $html_out);
    $html_out = preg_replace('/>\s+</', '><', $html_out);

    return trim($html_out);
}

// Basic email cleaning function: preserves HTML formatting and signatures,
// strips quoted/forwarded thread content so each reply stays independent.
function mmhd_clean_email_message($message, $is_html = null)
{
    if (empty($message)) {
        return '';
    }

    if (mmhd_email_body_is_html($message, $is_html)) {
        return mmhd_clean_html_email_message($message);
    }

    return mmhd_clean_plain_email_message($message);
}

function mmhd_fetch_emails()
{
    mmhd_log('Starting email fetch process');

    // Check if the IMAP PHP extension is available
    if (!function_exists('imap_open')) {
        $imap_error = 'The IMAP PHP extension is not enabled on this server. Email fetching is unavailable.';
        mmhd_log($imap_error);
        return 'Error: ' . $imap_error;
    }

    // Verify the custom post type exists
    if (!post_type_exists('mmhd_ticket')) {
        mmhd_log('Custom post type "mmhd_ticket" does not exist!');
        return 'Error: Custom post type "mmhd_ticket" does not exist!';
    }

    // Check if required taxonomies exist
    if (!taxonomy_exists('mmhd_ticket_origin')) {
        mmhd_log('Taxonomy "mmhd_ticket_origin" does not exist!');
        return 'Error: Taxonomy "mmhd_ticket_origin" does not exist!';
    }

    if (!taxonomy_exists('mmhd_ticket_priority')) {
        mmhd_log('Taxonomy "mmhd_ticket_priority" does not exist!');
        return 'Error: Taxonomy "mmhd_ticket_priority" does not exist!';
    }

    // Get last fetched date

    $last_fetched_date = get_option('mmhd_last_fetched_date', '2021-01-01 00:00:00'); // Default to a past date if not set( as we are also using the ticket id to retrieve the last mail or reply    )
    mmhd_log('Last fetched date: ' . $last_fetched_date);
    mmhd_log('Metamint Helpdesk: Last email fetched date/time: ' . $last_fetched_date);

    $last_fetched_gmt = get_gmt_from_date($last_fetched_date, 'Y-m-d H:i:s');
    $since_date = gmdate('d-M-Y H:i:s', strtotime($last_fetched_gmt));
    mmhd_log('IMAP since date format: ' . $since_date);




    // // Retrieve IMAP settings from options
    $settings = get_option('mmhd_imap_settings', array(
        'hostname' => '',
        'port'     => 993,
        'username' => '',
        'password' => '',
    ));

    $auth_type = !empty($settings['auth']) ? 'ssl' : 'tls';
    $hostname = $settings['hostname'];
    $port = $settings['port'];
    $username = $settings['username'];
    $password = $settings['password'];

    mmhd_log('Attempting IMAP connection: ' . $hostname . ':' . $port);

    $email = mmhd_imap_connect($hostname, $port, $auth_type, $username, $password);
    if (!$email) {
        $error_message = 'Cannot connect to IMAP: ' . imap_last_error();
        mmhd_log($error_message);
        return 'Error: ' . $error_message;
    }

    mmhd_log('Successfully connected to IMAP server');
    // Check if the mailbox is empty
    try {
        // Fetch emails received after last fetched date
        $search_criteria = 'SINCE "' . $since_date . '"';
        mmhd_log('Search criteria: ' . $search_criteria);

        $emails = imap_search($email, $search_criteria);

        if (!$emails) {
            $notice = 'No new emails found since ' . $since_date;
            mmhd_log($notice);
            return $notice; // ✅ Return this message, don't echo
        }

        mmhd_log('Found ' . count($emails) . ' emails to process');

        // Sort emails oldest to newest
        sort($emails);

        // Pre-fetch existing message IDs to prevent duplicates (ticket posts + reply comments)
        $existing_message_ids = get_posts([
            'post_type' => 'mmhd_ticket',
            'meta_key' => 'email_message_id',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'email_message_id',
                    'compare' => 'EXISTS',
                ],
            ],
        ]);
        $existing_message_ids = array_map(function ($post_id) {
            return get_post_meta($post_id, 'email_message_id', true);
        }, $existing_message_ids);

        // Replies are stored as comments, so their message IDs must be deduped too
        $reply_message_id_comments = get_comments([
            'meta_key' => 'email_message_id',
            'number' => 0,
        ]);
        foreach ($reply_message_id_comments as $reply_message_id_comment) {
            $existing_message_ids[] = get_comment_meta($reply_message_id_comment->comment_ID, 'email_message_id', true);
        }

        $existing_message_ids = array_values(array_filter(array_unique($existing_message_ids)));

        $processed_count = 0;
        $skipped_count = 0;
        $reply_count = 0;
        $error_count = 0;

        foreach ($emails as $email_number) {
            try {
            $headerInfo = imap_headerinfo($email, $email_number);
            if (!$headerInfo) {
                mmhd_log('Failed to get header info for email #' . $email_number);
                $error_count++;
                continue;
            }

            $raw_headers = imap_fetchheader($email, $email_number) ?: '';
            $email_source_context = mmhd_collect_email_source_context($raw_headers);

            $message_id = $headerInfo->message_id ?? '';
            if (empty($message_id)) {
                mmhd_log('Email #' . $email_number . ' has no message ID, skipping');
                $skipped_count++;
                continue;
            }

            $date_utc = gmdate("Y-m-d H:i:s", strtotime($headerInfo->date ?? 'now'));
            $date = get_date_from_gmt($date_utc, 'Y-m-d H:i:s');

            // Skip emails older than or equal to last fetched date silently —
            // the IMAP SINCE search is day-granular so it re-reads the whole day
            // every run; those are already handled, not a skip worth reporting.
            if (strtotime($date) <= strtotime($last_fetched_date)) {
                continue;
            }

            mmhd_log('--- Processing email #' . $email_number . ' ---');
            mmhd_log('Email date: ' . $date . ', Last fetched: ' . $last_fetched_date);

            // Check for duplicate tickets
            if (in_array($message_id, $existing_message_ids)) {
                mmhd_log('Skipping email #' . $email_number . ' as a ticket already exists with message ID: ' . $message_id);
                $skipped_count++;
                continue;
            }

            // if email is from Mail Delivery System of email Mailer-Daemon@antispam.mailspamprotection.com  skip it
            if (stripos($headerInfo->fromaddress ?? '', 'Mailer-Daemon') !== false || stripos($headerInfo->fromaddress ?? '', 'Mail Delivery System') !== false) {
                mmhd_log('Skipping email #' . $email_number . ' as it is from Mail Delivery System');
                $skipped_count++;
                continue;
            }

            $from = $headerInfo->fromaddress ?? 'Unknown Sender';
            //Vr Rv <vriasuhn@gmail.com> remove the email address
            $from = preg_replace('/<.*?>/', '', $from);
            $from = trim($from);
            $useremail = '';
            if (!empty($headerInfo->from[0]->mailbox) && !empty($headerInfo->from[0]->host)) {
                $useremail = sanitize_email($headerInfo->from[0]->mailbox . '@' . $headerInfo->from[0]->host);
            }
            if ($useremail === '' && preg_match('/<([^>]+)>/', (string) ($headerInfo->fromaddress ?? ''), $from_email_match)) {
                $useremail = sanitize_email($from_email_match[1]);
            }
            // only showing the name part 
            $subject_raw = $headerInfo->subject ?? 'No Subject';
            if (function_exists('imap_mime_header_decode') && strpos($subject_raw, '=?') !== false) {
                $subject = '';
                foreach (imap_mime_header_decode($subject_raw) as $subject_part) {
                    $subject .= $subject_part->text;
                }
                $subject = trim($subject);
            } else {
                $subject = $subject_raw;
            }
            mmhd_log('Email from: ' . $from . ', Subject: ' . $subject);

            // Fetch email body with robust MIME handling and transfer encoding decoding.
            // Prefer the HTML part when present (preserves rich formatting), fall back
            // to plain text.
            $structure = imap_fetchstructure($email, $email_number);
            $body_parts = mmhd_fetch_email_text_parts($email, $email_number, $structure);

            $is_html_body = false;
            if (!empty($body_parts['html'])) {
                $message = $body_parts['html'];
                $is_html_body = true;
            } elseif (!empty($body_parts['plain'])) {
                $message = $body_parts['plain'];
            } else {
                $message = '';
            }

            if (empty($message)) {
                mmhd_log('Could not retrieve body for email #' . $email_number);
                $error_count++;
                continue;
            }

            $message = mmhd_clean_email_message($message, $is_html_body);

            // Handle replies for parent tickets
            $in_reply_to = $headerInfo->in_reply_to ?? '';
            $references = $headerInfo->references ?? '';
            $original_post_id = null;

            if (!empty($in_reply_to) || !empty($references)) {
                mmhd_log('Email #' . $email_number . ' appears to be a reply');
                $reference_ids = !empty($references) ? preg_split('/[\s,]+/', trim($references)) : [$in_reply_to];
                $original_post_id = mmhd_find_ticket_by_reply_context($reference_ids, $useremail, $subject, $date);

                if ($original_post_id) {
                    // Prevent duplicate comments
                    $existing_comments = get_comments([
                        'post_id' => $original_post_id,
                        'meta_key' => 'email_message_id',
                        'meta_value' => $message_id,
                    ]);

                    $is_duplicate = false;
                    foreach ($existing_comments as $comment) {
                        if (trim($comment->comment_content) == trim($message)) {
                            $is_duplicate = true;
                            mmhd_log('Skipping duplicate comment for ticket ID: ' . $original_post_id);
                            break;
                        }
                    }

                    if (!$is_duplicate) {
                        mmhd_log('Adding comment to ticket ID: ' . $original_post_id);
                        $comment_user_id = 0;
                        if ($useremail !== '') {
                            $reply_user = get_user_by('email', $useremail);
                            if ($reply_user && !empty($reply_user->ID)) {
                                $comment_user_id = (int) $reply_user->ID;
                            }
                        }
                        if ($comment_user_id <= 0 && function_exists('mmhd_get_ticket_customer_user_id')) {
                            $comment_user_id = (int) mmhd_get_ticket_customer_user_id($original_post_id);
                        }

                        $comment_id = wp_insert_comment([
                            'comment_post_ID' => $original_post_id,
                            'comment_author' => $from,
                            'comment_author_email' => $useremail,
                            'user_id' => $comment_user_id,
                            'comment_content' => $message,
                            'comment_date' => $date,
                            'comment_approved' => 1,
                            'comment_type' => 'ticket_replies',
                        ]);

                        if (!is_wp_error($comment_id)) {
                            add_comment_meta($comment_id, 'email_message_id', $message_id, true);
                            // update_comment_meta: mmhd_comment_creation_on_insert (wp_insert_comment hook)
                            // writes comment_source/author_type first; we must override with 'email'
                            update_comment_meta($comment_id, 'comment_source', 'email');
                            update_comment_meta($comment_id, 'author_type', 'customer');
                            add_comment_meta($comment_id, '_mmhd_source_ip', $email_source_context['ip'], true);
                            add_comment_meta($comment_id, '_mmhd_source_country', $email_source_context['country'], true);
                            add_comment_meta($comment_id, '_mmhd_source_browser', $email_source_context['browser'], true);
                            mmhd_store_ticket_source_meta($original_post_id, $email_source_context, $comment_id);
                            $existing_message_ids[] = $message_id;
                            mmhd_log('Successfully added comment ID: ' . $comment_id);
                            $reply_count++;
                        } else {
                            mmhd_log('Failed to add comment: ' . $comment_id->get_error_message());
                            $error_count++;
                        }
                    } else {
                        $skipped_count++;
                    }

                    // Update last fetched date
                    update_option('mmhd_last_fetched_date', $date); // Update last fetched date to current email date
                    mmhd_log('Metamint Helpdesk: Updated last fetched email date/time to: ' . $date);
                    continue; // Skip creating a new ticket for replies
                } else {
                    mmhd_log('Could not find original ticket for reply, creating new ticket instead');
                }
            }

            // Create new ticket
            mmhd_log('Creating new ticket from email: ' . $useremail);

            // Check if email should be filtered before creating ticket
            if (!apply_filters('mmhd_before_create_ticket_from_email', true, [
                'from' => $useremail,
                'subject' => $subject,
                'message' => $message
            ])) {
                mmhd_log('Email from ' . $useremail . ' was filtered by exclusion rules');
                $skipped_count++;
                continue;
            }

            $user = get_user_by('email', $useremail);
            if (!$user) {
                mmhd_log('User not found, creating new user with email: ' . $useremail);

                // Check if role exists
                $roles = wp_roles();
                $role = isset($roles->roles['mmhd_support_customer']) ? 'mmhd_support_customer' : 'subscriber';

                // Ensure unique user_login
                $base_login = explode('@', $useremail)[0];
                $user_login = $base_login;
                $counter = 1;
                while (username_exists($user_login)) {
                    $user_login = $base_login . '_' . $counter++;
                }

                $user_id = wp_insert_user([
                    'user_login'   => $user_login,
                    'user_email'   => $useremail,
                    'display_name' => $from,
                    'user_pass'    => wp_generate_password(),
                    'role'         => $role,
                ], true);
                // send notification to the new user
                if (!mmhd_are_emails_suppressed()) {
                    wp_send_new_user_notifications($user_id, 'user');
                }


                if (is_wp_error($user_id)) {
                    mmhd_log('Failed to create user: ' . $user_id->get_error_message());
                    $error_count++;
                    continue;
                } else {
                    mmhd_log('Created new user with ID: ' . $user_id);
                }
            } else {
                $user_id = $user->ID;
                mmhd_log('Using existing user with ID: ' . $user_id);
            }

            // Ensure taxonomy terms exist

            $term_email = term_exists('Email', 'mmhd_ticket_origin');
            $term_priority = term_exists('Medium', 'mmhd_ticket_priority');

            if (!$term_email) {
                mmhd_log('Creating "Email" term in mmhd_ticket_origin taxonomy');
                $term_email = wp_insert_term('Email', 'mmhd_ticket_origin');
            }

            if (!$term_priority) {
                mmhd_log('Creating "Medium" term in mmhd_ticket_priority taxonomy');
                $term_priority = wp_insert_term('Medium', 'mmhd_ticket_priority');
            }

            if (is_wp_error($term_email) || is_wp_error($term_priority)) {
                mmhd_log('Cannot proceed without required terms');
                $error_count++;
                continue;
            }

            $term_email_id = is_array($term_email) ? $term_email['term_id'] : $term_email;
            $term_priority_id = is_array($term_priority) ? $term_priority['term_id'] : $term_priority;

            // Create new ticket post
            mmhd_log('Inserting new ticket post');
            $post_id = wp_insert_post([
                'post_title'   => $subject,
                'post_content' => $message,
                'post_date'    => $date,
                'post_status'  => 'publish',
                'post_author'  => $user_id,
                'post_type'    => 'mmhd_ticket',
                // add post meta of assignee

            ]);

            if (!is_wp_error($post_id)) {
                // Save the dedup key immediately so a failure later in this block
                // can never leave an orphan ticket that gets re-created on next fetch
                add_post_meta($post_id, 'email_message_id', $message_id, true);
                $existing_message_ids[] = $message_id;

                // Assign the ticket to a support staff member
                $assignee = auto_assign_ticket_to_support_staff(true);

                // If an assignee was found, add it as post meta
                if ($assignee) {
                    add_post_meta($post_id, 'ticket_assignee', $assignee);
                    mmhd_store_ticket_assignee_id($post_id, $assignee);
                } else {
                    // Optional: Handle case where no assignee was found (e.g., log or set default)
                    mmhd_log('Metamint Helpdesk: Failed to assign ticket ID ' . $post_id . ' - no support staff available.');
                }
            } else {
                // Handle error if ticket creation failed
                mmhd_log('Metamint Helpdesk: Failed to create ticket: ' . $post_id->get_error_message());
            }

            //if post is created successfully add a comment with email message
            if ($post_id && !is_wp_error($post_id)) {
                mmhd_log('Successfully created ticket post with ID: ' . $post_id);
                // Add a comment with the email message
                $comment_id = wp_insert_comment([
                    'comment_post_ID' => $post_id,
                    'comment_author' => $from,
                    'comment_author_email' => $useremail,
                    'user_id' => $user_id,
                    'comment_content' => $message,
                    'comment_date' => $date,
                    'comment_approved' => 1,
                    'comment_type' => 'ticket_replies',
                    'is_mmhd_note'     => true, // Custom comment type for ticket replies
                ]);
                if (!is_wp_error($comment_id)) {
                    add_comment_meta($comment_id, 'email_message_id', $message_id, true);
                    // update_comment_meta: mmhd_comment_creation_on_insert (wp_insert_comment hook)
                    // writes comment_source/author_type first; we must override with 'email'
                    update_comment_meta($comment_id, 'comment_source', 'email');
                    update_comment_meta($comment_id, 'author_type', 'customer');
                    add_comment_meta($comment_id, 'mmhd_suppress_notification', 1, true);
                    mmhd_log('Added comment ID: ' . $comment_id . ' to ticket ID: ' . $post_id);
                } else {
                    mmhd_log('Failed to add comment: ' . $comment_id->get_error_message());
                }
            } else {
                mmhd_log('Failed to create ticket post: ' . (is_wp_error($post_id) ? $post_id->get_error_message() : 'Unknown error'));
            }

            if (is_wp_error($post_id)) {
                mmhd_log('Failed to create ticket: ' . $post_id->get_error_message());
                $error_count++;
                continue;
            }

            mmhd_log('Created new ticket with ID: ' . $post_id);



            if ($term_priority_id) {
                $result = wp_set_post_terms($post_id, [$term_priority_id], 'mmhd_ticket_priority', false);
                if (is_wp_error($result)) {
                    mmhd_log('Failed to set priority term: ' . $result->get_error_message());
                }
            }

            // Update post meta
            update_post_meta($post_id, 'mmhd_ticket_origin', 'email');
            update_post_meta($post_id, '_mmhd_ticket_status', 'open');
            update_post_meta($post_id, '_mmhd_customer_email', $useremail);
            add_post_meta($post_id, 'email_from', $from);
            mmhd_store_ticket_source_meta($post_id, $email_source_context);

            mmhd_notify_ticket_created($post_id, 'email');

            do_action('mmhd_after_ticket_created', $post_id, [], $user_id);

            $processed_count++;

            // Update last fetched date
            update_option('mmhd_last_fetched_date', $date);
            mmhd_log('Metamint Helpdesk: Updated last fetched email date/time to: ' . $date);
            } catch (Throwable $e) {
                mmhd_log('Metamint Helpdesk: Error processing email #' . $email_number . ': ' . $e->getMessage());
                $error_count++;
            }
        }

        // Display summary
        $summary = "Email processing complete. Processed: $processed_count, Replies: $reply_count, Errors: $error_count";

        mmhd_log($summary);
        mmhd_log('========================= END OF FETCH =========================');

        return $summary; // ✅ Return it

    } finally {
        // Ensure IMAP connection is closed
        if ($email) {
            imap_close($email);
        }
    }
}


// Function to display the IMAP settings page
function mmhd_imap_settings_callback()
{
    if (!current_user_can('mmhd_access_dashboard')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'mmhd'));
    }

    // Handle form submission for saving settings
    if (isset($_POST['mmhd_imap_save'])) {
        check_admin_referer('mmhd_imap_settings_action', 'mmhd_imap_settings_nonce');

        $hostname = isset($_POST['mmhd_imap_hostname']) ? sanitize_text_field(wp_unslash($_POST['mmhd_imap_hostname'])) : '';
        $port     = isset($_POST['mmhd_imap_port']) ? intval(wp_unslash($_POST['mmhd_imap_port'])) : 143;
        $username = isset($_POST['mmhd_imap_username']) ? sanitize_email(wp_unslash($_POST['mmhd_imap_username'])) : '';
        $password = isset($_POST['mmhd_imap_password']) ? sanitize_text_field(wp_unslash($_POST['mmhd_imap_password'])) : '';

        $inbox_name = isset($_POST['mmhd_imap_inbox_name']) ? sanitize_text_field(wp_unslash($_POST['mmhd_imap_inbox_name'])) : '';

        $settings = array(
            'hostname' => $hostname,
            'port'     => $port,
            'username' => $username,
            'password' => $password,
            'inbox_name' => $inbox_name,
        );

        update_option('mmhd_imap_settings', $settings);

        echo '<div class="updated"><p>IMAP settings saved successfully.</p></div>';
    }

    // Handle connection test
    if (isset($_POST['mmhd_imap_test'])) {
        check_admin_referer('mmhd_imap_settings_action', 'mmhd_imap_settings_nonce');

        $hostname_val = isset($_POST['mmhd_imap_hostname']) ? sanitize_text_field(wp_unslash($_POST['mmhd_imap_hostname'])) : '';
        $port_val     = isset($_POST['mmhd_imap_port']) ? intval(wp_unslash($_POST['mmhd_imap_port'])) : 143;
        $username     = isset($_POST['mmhd_imap_username']) ? sanitize_email(wp_unslash($_POST['mmhd_imap_username'])) : '';
        $password     = isset($_POST['mmhd_imap_password']) ? sanitize_text_field(wp_unslash($_POST['mmhd_imap_password'])) : '';

        $conn = mmhd_imap_connect($hostname_val, $port_val, 'ssl', $username, $password);
        if ($conn) {
            echo '<div class="updated"><p>IMAP connection successful!</p></div>';
            imap_close($conn);
        } else {
            echo '<div class="error"><p>' .
                esc_html__('IMAP connection failed: ', 'mmhd') .
                esc_html(imap_last_error()) .
                '</p></div>';
        }
    }

    // Load saved settings or use provided defaults
    $settings = get_option('mmhd_imap_settings', array(
        'hostname' => 'email.example.com', // Default hostname
        'port'     => 993,
        'username' => 'password',
        'password' => '', // Avoid hardcoding password
        'inbox_name' => '',
    ));

    $hostname   = $settings['hostname'];
    $port       = $settings['port'];
    $username   = $settings['username'];
    $password   = $settings['password'];
    $inbox_name = isset($settings['inbox_name']) ? $settings['inbox_name'] : '';
?>

    <div class="wrap">
        <h1>Metamint Helpdesk IMAP Settings</h1>
        <form method="post" action="">
            <?php wp_nonce_field('mmhd_imap_settings_action', 'mmhd_imap_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="mmhd_imap_hostname">IMAP Hostname</label></th>
                    <td><input type="text" name="mmhd_imap_hostname" id="mmhd_imap_hostname" value="<?php echo esc_attr($hostname); ?>" class="regular-text">
                        <p class="description">Example: <code>imappro.zoho.com</code></p>
                        <p class="description">For DEV: <code>{imappro.zoho.com:{port}/imap/ssl}INBOX</code></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="mmhd_imap_port">IMAP Port</label></th>
                    <td><input type="number" name="mmhd_imap_port" id="mmhd_imap_port" value="<?php echo esc_attr($port); ?>" class="small-text">
                        <p class="description">Default: <code>993</code> for SSL</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="mmhd_imap_username">IMAP Username</label></th>
                    <td><input type="email" name="mmhd_imap_username" id="mmhd_imap_username" value="<?php echo esc_attr($username); ?>" class="regular-text">
                        <p class="description">Your email address , from where email will be fetched</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="mmhd_imap_password">IMAP Password</label></th>
                    <td><input type="password" name="mmhd_imap_password" id="mmhd_imap_password" value="<?php echo esc_attr($password); ?>" class="regular-text">
                        <p class="description"> Password</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="mmhd_imap_inbox_name">Inbox Name</label></th>
                    <td><input type="text" name="mmhd_imap_inbox_name" id="mmhd_imap_inbox_name" value="<?php echo esc_attr($inbox_name); ?>" class="regular-text" placeholder="e.g. Sales, Support, General">
                        <p class="description">A name to identify this inbox in the dashboard.</p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="mmhd_imap_save" class="button button-primary" value="Save Settings">
                <input type="submit" name="mmhd_imap_test" class="button button-secondary" value="Test Connection">
            </p>
        </form>
    </div>

<?php
}


// now the email ticket via comment is working

// Disable default WordPress notifications
add_filter('notify_post_author', '__return_false');
add_filter('comment_notification_recipients', '__return_empty_array');
add_filter('comment_moderation_recipients', '__return_empty_array');
add_filter('send_password_change_email', '__return_false');
remove_action('after_password_reset', 'wp_password_change_notification');

// Define fallback logging function
if (!function_exists('mmhd_log')) {
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    function mmhd_log($message)
    {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log("[Metamint Helpdesk] $message");
    }
}
