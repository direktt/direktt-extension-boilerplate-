=== Direktt Extension Boilerplate ===
Contributors: direkttwp
Tags: mobile app, customer care, messaging, push notifications, mobile integration
Requires at least: 5.4
Tested up to: 6.8
Stable tag: 1.0
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Minimal boilerplate plugin for building Direktt Extensions. Adds a sample settings tab under Direktt > Settings and a sample profile tool in the Direktt user profile interface.

== Description ==

This plugin is a starting point for developers who want to build custom Direktt Extensions on top of the Direktt WordPress plugin.

It demonstrates how to:

* Depend on the Direktt core plugin and auto‑deactivate if it’s missing.
* Add a custom settings panel under **Direktt > Settings** in wp‑admin.
* Add a custom profile tool/tab inside the Direktt user profile UI (used by channel admins in the Direktt mobile app).
* Enqueue front‑end JavaScript only when a Direktt user is present.

You can clone this plugin, rename it, and extend it with your own business logic (loyalty, ticketing, custom services, automations, etc.).

Note: This plugin is a boilerplate and does not add end‑user features on its own.

== Features ==

* **Direktt dependency check**
  * Deactivates itself and shows an admin notice if the Direktt plugin is not active.
  * Adds an inline warning row in the Plugins list.

* **Custom Direktt Settings tab**
  * Registers a new tab under **Direktt > Settings**.
  * Renders placeholder content as an example.
  * Demonstrates how to attach page‑specific CSS and JS.

* **Custom Direktt Profile tool**
  * Registers a new tool in the Direktt user profile (visible to admins in the app).
  * Renders placeholder content as an example.
  * Demonstrates how to attach tool‑specific CSS and JS.
  * Supports optional restriction by Direktt user categories/tags.

* **Public script enqueue example**
  * Hooks into Direktt’s public script loading.
  * Enqueues a sample front‑end script only when there is an authenticated Direktt user.

== Requirements ==

* WordPress 6.0 or higher
* PHP 7.4 or higher
* Direktt WordPress Plugin (must be installed and active)
* A connected Direktt channel for full functionality

== Installation ==

1. Install and activate the **Direktt** WordPress plugin and connect it to your Direktt channel.
2. Upload or copy the **Direktt Extension Boilerplate** into `/wp-content/plugins/`, or install it via **Plugins > Add New > Upload Plugin**.
3. Activate the boilerplate plugin from the **Plugins** screen.
4. If Direktt is not active, this plugin will automatically deactivate itself and show an error notice.

== Usage ==

After activation:

* In **wp‑admin**, go to **Direktt > Settings**:
  * You will see a new tab labeled “Extension Boilerplate Settings” with placeholder content.
  * Use this as a template for your own extension settings UI.

* In the **Direktt user profile UI** (accessed from the Direktt mobile app in Admin mode):
  * When opening a user profile, you will see a new tool labeled “Direktt Extension Tool”.
  * This shows placeholder content and demonstrates how a custom profile tool is integrated.

You are expected to replace the placeholder content and extend the plugin with your own logic (options, tools, services).

== Developer Notes ==

This boilerplate illustrates:

* How to hook into:
  * `direktt_setup_settings_pages` to register extra settings panels.
  * `direktt_setup_profile_tools` to register profile tools/tabs.
  * `direktt_enqueue_public_scripts` to load public assets only for Direktt users.
* How to define:
  * Settings page metadata (ID, label, callback, assets).
  * Profile tool metadata (ID, label, callback, categories/tags, priority, assets).

Use it as a reference for structuring your own Direktt Extensions.

== Frequently Asked Questions ==

= Does this plugin change anything for my site visitors? =

Not by itself. It is a developer boilerplate and only adds placeholder UI components for demonstration.

= Do I need the Direktt plugin installed? =

Yes. The Direktt WordPress plugin must be installed and active. If it is not, this boilerplate will automatically deactivate itself.

= Can I rename and ship this plugin? =

Yes. You can clone and adapt it for your own Direktt Extensions. Update the plugin headers, text domain, and replace the placeholder callbacks with your implementation.

== Changelog ==

= 1.0.0 =
* Initial release of the Direktt Extension Boilerplate:
  * Direktt dependency check.
  * Sample settings tab under Direktt > Settings.
  * Sample profile tool in the Direktt user profile.
  * Sample public script enqueue for Direktt users.