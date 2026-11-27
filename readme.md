=== Metamint Helpdesk ===
Contributors: codepixelzmedia, utsavsinghrathour
Tags: support, tickets, helpdesk, customer-support, faq, live chat, knowledge base
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Self-hosted support tickets, live chat, and a knowledge base for WordPress — with no per-agent fees.

== Description ==

**Metamint Helpdesk** turns your WordPress dashboard into a fast, self-hosted customer support hub, live chat widget, and ticket management system—with **no per-agent fees**, no external SaaS lock-in, and complete ownership of your customer data.

Built for agencies, WooCommerce stores, SaaS founders, and service businesses, Metamint Helpdesk gives your support team everything needed to organize, assign, and resolve customer inquiries directly inside WordPress.

### Why Choose Metamint Helpdesk?

* **100% Self-Hosted Data Ownership:** Every ticket, live chat transcript, internal note, and customer record stays inside your own WordPress database.
* **No Per-Agent Pricing:** Add unlimited support agents and departments without paying monthly per-seat SaaS taxes.
* **Lightweight & Modular:** Engineered with clean, modern WordPress standards so your frontend stays fast and your admin dashboard remains clutter-free.
* **Works Out of the Box:** Install, configure your support portal page, and start receiving tickets and live chats in minutes.

### Core Features (Included Free)

* **Unified Support Ticket Inbox:** Filter, search, prioritize, and assign tickets by status (Open, Pending, Resolved, Closed), priority, department, or agent.
* **Frontend Customer Ticket Portal:** Allow logged-in users or guests to submit new tickets, upload attachments, and track reply history via a clean shortcode.
* **Real-Time Live Chat Widget:** Embed a customizable live chat widget on your site so visitors can start conversations immediately and seamlessly convert chats into trackable tickets.
* **Agent Collision Detection & Internal Notes:** Leave private internal notes for teammates and prevent duplicate replies when multiple agents view the same ticket.
* **Department & Agent Routing:** Organize inquiries across Sales, Billing, and Technical Support teams with role-based permissions.
* **Custom Ticket Fields & Priorities:** Capture order numbers, URLs, product categories, or custom metadata right at ticket submission.
* **Built-In Knowledge Base & Docs:** Publish searchable self-service help articles and FAQs to reduce repetitive support tickets.
* **Email Notifications:** Automated email alerts for new tickets, agent replies, and status updates using your site’s existing WordPress mail setup.

### Optional Pro Addons (Available Separately)

Need omnichannel messaging, AI automation, or eCommerce integrations? Extend the core plugin with modular Pro Addons from [Metamint Apps](https://metamintapps.com/metamint-helpdesk):

* **AI Bot & AI-Assisted Replies:** Connect your own OpenAI or Google Gemini API key to draft responses and answer common questions using your Knowledge Base.
* **WooCommerce Integration:** View customer order history, subscription status, and lifetime value directly inside the ticket sidebar.
* **Slack, Discord & WhatsApp Integrations:** Receive instant ticket alerts and reply to customer conversations from your team chat tools.
* **Email Piping (IMAP/SMTP) & Multi-Inbox:** Automatically convert incoming support emails into tickets across multiple support addresses.
* **Canned Replies, Ratings & Lead Reports:** Speed up responses with saved templates, collect CSAT ratings, and track team resolution metrics.
* **Zapier & Webhook Automation:** Connect helpdesk events to thousands of external workflows.

### Privacy & External Services Disclosure

Metamint Helpdesk is designed around local data storage. By default, the core plugin does **not** transmit customer tickets, chat logs, or visitor data to Metamint Apps servers. Optional Pro Addons contact third-party services only when you enable them and provide your own API credentials.

== External Services ==

This plugin connects to the following external service:

Gravatar — Avatar images are requested from the Gravatar service (https://www.gravatar.com/). When a user or comment author has no locally stored avatar, a hash of their email address is sent to Gravatar to retrieve a profile image. Data sent: a hashed email address. Service URL: https://www.gravatar.com/ | Privacy policy: https://automattic.com/privacy/ | Terms of service: https://automattic.com/tos/

No other external services are contacted by the core plugin. Emojis in the message composer are rendered using the visitor's native system emoji font, so no emoji images are loaded from a remote CDN.

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

1. Log in to your WordPress dashboard and navigate to **Plugins > Add New**.
2. Search for **Metamint Helpdesk**, click **Install Now**, and then click **Activate**.
3. Follow the instructions in the Setup Wizard.

== Frequently Asked Questions ==

= Is Metamint Helpdesk completely free to use? =

Yes. The core plugin includes unlimited tickets, unlimited support agents, live chat, a frontend customer portal, internal notes, and a built-in knowledge base at no cost. Optional Pro Addons are available if you need integrations like WooCommerce, Slack, WhatsApp, or AI-assisted replies. Metamint Helpdesk does not charge per addon: one fee gives you access to all 12 addons and future ones.

= Where is my customer and ticket data stored? =

All tickets, chat messages, attachments, and customer details are stored locally inside your own WordPress database and uploads directory. Metamint Apps LLC does not store or read your support tickets.

= Does it work with WooCommerce and any WordPress theme? =

Yes. Metamint Helpdesk is compatible with all standard WordPress block themes and classic themes, including Kadence, Astra, GeneratePress, and Hello Elementor, and works seamlessly alongside WooCommerce.

= Can customers submit tickets without creating a WordPress account? =

Yes. You can allow guest ticket submissions using an email verification workflow, or restrict the portal to registered WordPress users, configurable in **Helpdesk > Settings**.

= Do I need to pay per support agent? =

No. Unlike hosted SaaS helpdesks, Metamint Helpdesk lets you assign as many WordPress users as support agents as your team requires without per-seat charges.

= How do I connect AI replies, Slack, or WhatsApp? =

Those integrations are provided as optional Pro Addons. Once enabled, you enter your own API keys (such as OpenAI, Gemini, Slack, or WhatsApp Business API) directly inside your WordPress settings with encrypted credential storage.

== Changelog ==

= 2.2.0 =
* Added a ticket transfer feature to the admin ticket detail page, allowing conversations to be reassigned to an existing user or a new account.
* Added a searchable user dropdown and refined validation to the ticket transfer form.
* Added license status banners to the setup wizard addon screen for active, unlicensed, and not-installed states.
* Fixed ticket, note, and chat reply scrolling to target their containers instead of the whole page.
* Limited the "Do not send email on import" option to import-related emails only; regular ticket, reply, and password-reset emails are no longer suppressed.

= 2.1.0 =
* Added the `[mmhd_faq]` shortcode with accordion and Q&A modes, optional category filtering, and FAQPage structured data.
* Added a copyable shortcode to the FAQ category admin screen.
* Added optional department-based auto-assignment that routes unassigned conversations to the least-busy agent in the matching department.
* Included administrator, author, and editor roles in department auto-assignment.
* Showed all non-closed tickets in Active Conversations and excluded closed tickets from Unassigned Conversations.
* Added plugin text-domain loading and a translation template.
* Bound credential encryption and JWT signing to WordPress authentication keys, with a legacy decryption fallback.
* Resolved ticket webhook authors from a user ID, email, or login to a numeric user ID.
* Loaded Dashicons on the frontend so dashboard icons and credential vault buttons render correctly.
* Fixed dashboard JavaScript errors and script initialization.
* Added a "Powered by Metamint Apps" credit to the dashboard Quick Actions.
* Polished dashboard select controls, quick stats colors, and the default theme color.
* Polished setup wizard navigation, role overview, addon grid, and review prompt timing.
* Added a MIME parsing depth limit to prevent resource exhaustion from deeply nested messages.
* Kept dashboard forms and buttons intact while escaping dashboard output.

= 2.0.0 =
* Added the setup wizard with welcome, setup, addons, and finish steps.
* Added a live chat widget preview and FAQ shortcode documentation to the wizard.
* Preserved wizard completion status across plugin deactivation and reactivation.
* Added explicit accordion and Q&A mode classes to the FAQ shortcode and fixed its CSS loading.
* Improved the conversation details, conversation tabs, and mail settings design.
* Addressed WordPress.org plugin review findings, including dashboard tab rendering, author type detection, emoji picker, nonce synchronization, and FAQ shortcode copying.
* Declared compatibility with WordPress 6.6.
* Version 2.0 release.

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
