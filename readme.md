=== Metamint Helpdesk ===
Contributors: codepixelzmedia, utsavsinghrathour
Tags: support, tickets, helpdesk, customer-support, faq
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A support ticket and FAQ system for WordPress.

== Description ==

Metamint Helpdesk is a simple and efficient support plugin for WordPress that enables users to submit support tickets, track their status, and communicate with the support team directly from the WordPress dashboard.

= Features =

* Support tickets with statuses, priorities, and categories
* Frontend support dashboard and chat widget
* FAQ and notice board
* Email-to-ticket via IMAP
* Customer conversations and notifications

== External Services ==

This plugin connects to the following external service:

Gravatar — Avatar images are requested from the Gravatar service (https://www.gravatar.com/). When a user or comment author has no locally stored avatar, a hash of their email address is sent to Gravatar to retrieve a profile image. Data sent: a hashed email address. Service URL: https://www.gravatar.com/ | Privacy policy: https://automattic.com/privacy/ | Terms of service: https://automattic.com/tos/

No other external services are contacted. Emojis in the message composer are rendered using the visitor's native system emoji font, so no emoji images are loaded from a remote CDN.

== Source Code & Build Tools ==

The frontend assets in public/build/ are generated from the source files in public/src/ using webpack. The human-readable source code for these build artifacts is publicly available at:

https://github.com/utsavsinghrathour/metamint-helpdesk

Build configuration: public/webpack.config.js

== Security Configuration ==

The plugin binds its secrets to WordPress's predefined keys from `wp-config.php`:

* `AUTH_KEY` / `SECURE_AUTH_KEY` — Used to encrypt customer credentials stored
  in the `wp_mmhd_credentials` table and to sign JWT tokens issued by the
  `/mmhd/v1/login` and `/mmhd/v1/signup` REST endpoints.

If you want to override these with plugin-specific secrets, define these
constants in `wp-config.php`:

* `MMHD_JWT_SECRET` — Secret used to sign JWT tokens issued by the
  `/mmhd/v1/login` and `/mmhd/v1/signup` REST endpoints.
* `MMHD_ENCRYPTION_KEY` — Secret used to encrypt customer credentials
  stored in the `wp_mmhd_credentials` table.

Example:

```php
define( 'MMHD_JWT_SECRET', 'your-long-random-string-here' );
define( 'MMHD_ENCRYPTION_KEY', 'another-long-random-string-here' );
```

If neither the plugin-specific constants nor `AUTH_KEY` are defined, the
plugin falls back to `wp_hash()`, which is derived from WordPress's auth
keys and salts.

Important: if you rotate `AUTH_KEY` / `SECURE_AUTH_KEY` in `wp-config.php`,
previously encrypted credentials and issued JWT tokens will no longer be
decryptable / verifiable, since they are bound to those keys.

== Installation ==

1. Upload the plugin files to /wp-content/plugins/metamint-helpdesk
2. Activate the plugin through the WordPress Plugins screen
3. Start managing support tickets from the dashboard

== Frequently Asked Questions ==

= Where is the source code? =

The complete source code, including the sources used to build the frontend bundles, is available at https://github.com/utsavsinghrathour/metamint-helpdesk

== Changelog ==

= 2.1.1 =
* Added the `[mmhd_faq]` shortcode with accordion and Q&A modes, optional category filtering, and FAQPage structured data.
* Added a copyable shortcode to the FAQ category admin screen.
* Moved remaining inline admin JavaScript/CSS into enqueued asset files.
* Removed the admin-wide email stylesheet; email styles are inlined only into transactional emails.
* Added a depth limit to MIME parsing to prevent resource exhaustion from deeply nested messages.
* Kept dashboard forms and buttons intact while escaping dashboard output.
* Fixed version consistency across the plugin header, constant, and readme.

= 1.5.8 =
* Fixed plugin activation on clean WordPress installations (support page creation now runs on init).
* Added Requires PHP and Requires at least headers; improved dbDelta table definitions.
* Added nonce and capability checks to comment status changes, profile updates, taxonomy ordering, and ticket creation.
* Prefixed remaining global function names.
* Documented the public source repository and build tooling in the readme.
* Removed the external Google Fonts request; the widget now uses the system font stack.
* Removed the invalid Domain Path header and fixed an i18n translator comment.

= 1.5.7 =
* Rebranded to Metamint Helpdesk (slug mmhd)
* IMAP save fix: removed duplicate imap_close()
* Text domain changed to metamint-helpdesk

== License ==

This plugin is licensed under the GPLv2 or later.
