<?php
$site_name = isset($site_name) ? (string) $site_name : get_bloginfo('name');
$site_url = isset($site_url) ? (string) $site_url : get_home_url();
$year = isset($year) ? (string) $year : date_i18n('Y');
$email_subject = isset($email_subject) ? (string) $email_subject : '';
$email_content = isset($email_content) ? (string) $email_content : '';
$logo_url = isset($logo_url) ? (string) $logo_url : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title><?php echo esc_html($email_subject ?: $site_name); ?></title>
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
                            <?php if ($logo_url !== ''): ?>
                                <a href="<?php echo esc_url($site_url); ?>" target="_blank" rel="noopener noreferrer">
                                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>" />
                                </a>
                            <?php else: ?>
                                <h1 class="subject"><?php echo esc_html($site_name); ?></h1>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <?php if ($email_subject !== ''): ?>
                        <tr>
                            <td style="padding: 24px 30px 0;">
                                <h2 class="subject"><?php echo esc_html($email_subject); ?></h2>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <td class="content">
                            <?php echo $email_content; ?>
                        </td>
                    </tr>

                    <tr>
                        <td class="footer">
                            <div>
                                <a href="<?php echo esc_url($site_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($site_name); ?></a>
                            </div>
                            <div>&copy; <?php echo esc_html($year); ?> <?php echo esc_html($site_name); ?>. All rights reserved.</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>