<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$robodesk_site_name = isset($robodesk_site_name) ? (string) $robodesk_site_name : get_bloginfo('name');
$robodesk_site_url = isset($robodesk_site_url) ? (string) $robodesk_site_url : get_home_url();
$robodesk_year = isset($robodesk_year) ? (string) $robodesk_year : date_i18n('Y');
$robodesk_email_subject = isset($robodesk_email_subject) ? (string) $robodesk_email_subject : '';
$robodesk_email_content = isset($robodesk_email_content) ? (string) $robodesk_email_content : '';
$robodesk_logo_url = isset($robodesk_logo_url) ? (string) $robodesk_logo_url : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title><?php echo esc_html($robodesk_email_subject ?: $robodesk_site_name); ?></title>
    <style type="text/css">
        body {
            margin: 0;
            padding: 0;
            background-color: #f1f1f1;
            font-family: Arial, sans-serif;
            color: #212529;
        }

        table {
            border-collapse: collapse;
            border-spacing: 0;
        }

        .wrapper {
            width: 100%;
            background-color: #f1f1f1;
            padding: 24px 0;
        }

        .container {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 14px;
            overflow: hidden;
        }

        .header {
            padding: 24px 28px;
            border-bottom: 1px solid #e8ecf1;
            text-align: center;
        }

        .header img {
            max-width: 170px;
            width: 100%;
            height: auto;
        }

        .subject {
            margin: 0;
            padding: 0;
            font-size: 22px;
            line-height: 1.4;
            color: #2c2c2c;
            font-weight: 700;
        }

        .content {
            padding: 30px;
            font-size: 16px;
            line-height: 1.6;
            color: #212529;
        }

        .content a {
            color: #ff0000;
        }

        .content p {
            margin: 0 0 14px;
        }

        .footer {
            background-color: #1a1a1a;
            text-align: center;
            padding: 18px 24px;
        }

        .footer,
        .footer a {
            color: #ffffff;
            font-size: 12px;
            line-height: 1.5;
            text-decoration: none;
        }

        @media only screen and (max-width: 680px) {
            .wrapper {
                padding: 12px;
            }

            .content {
                padding: 20px;
            }

            .subject {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
    <table role="presentation" class="wrapper" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center">
                <table role="presentation" class="container" width="650" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td class="header">
                            <?php if ($robodesk_logo_url !== ''): ?>
                                <a href="<?php echo esc_url($robodesk_site_url); ?>" target="_blank" rel="noopener noreferrer">
                                    <img src="<?php echo esc_url($robodesk_logo_url); ?>" alt="<?php echo esc_attr($robodesk_site_name); ?>" />
                                </a>
                            <?php else: ?>
                                <h1 class="subject"><?php echo esc_html($robodesk_site_name); ?></h1>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <?php if ($robodesk_email_subject !== ''): ?>
                        <tr>
                            <td style="padding: 24px 30px 0;">
                                <h2 class="subject"><?php echo esc_html($robodesk_email_subject); ?></h2>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <td class="content">
                            <?php echo wp_kses_post($robodesk_email_content); ?>
                        </td>
                    </tr>

                    <tr>
                        <td class="footer">
                            <div>
                                <a href="<?php echo esc_url($robodesk_site_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($robodesk_site_name); ?></a>
                            </div>
                            <div>&copy; <?php echo esc_html($robodesk_year); ?> <?php echo esc_html($robodesk_site_name); ?>. All rights reserved.</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>