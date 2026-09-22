=== Metamint Helpdesk ===
Contributors: codepixelzmedia
Tags: support, tickets, helpdesk, customer-support
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.5.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A support ticket and FAQ system for WordPress.

== Description ==

Metamint Helpdesk is a simple and efficient support plugin for WordPress that enables users to submit support tickets, track their status, and communicate with the support team directly from the WordPress dashboard.

== External Services ==

This plugin uses the following external services:

**jsdelivr CDN** — Used by the frontend emoji picker to load emoji sprite images (PNG). Data sent: emoji image URLs requested. No personal data is transmitted. Service URL: https://www.jsdelivr.com/ | Privacy policy: https://www.jsdelivr.com/terms/privacy-policy-jsdelivr-net

== Source Code & Build Tools ==

The frontend assets in `public/build/` are generated from source files located in `public/src/` using webpack. The source code for these build artifacts is included in the plugin at `public/src/`. Build configuration: `public/webpack.config.js`. Source repository: https://github.com/utsavsinghrathour/robodesk.git (branch: rebrand/metamint-helpdesk) — the distribution ZIP excludes public/src/ and npm files

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/metamint-helpdesk`
2. Activate the plugin through the WordPress Plugins screen
3. Start managing support tickets from the dashboard

== Changelog ==

= 1.5.7 =
* Rebranded to Metamint Helpdesk (slug mmhd)
* IMAP save fix: removed duplicate imap_close()
* Text domain changed to metamint-helpdesk

== License ==

This plugin is licensed under the GPLv2 or later.
