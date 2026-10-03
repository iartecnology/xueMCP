=== MCP Listeo Connector ===
Contributors: antigravity
Tags: mcp, listeo, dokan, ai, directory, marketplace
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later

Bridge between Model Context Protocol (MCP) and Listeo/Dokan ecosystem.

== Description ==

This plugin exposes WordPress, Listeo, and Dokan functionalities as MCP Abilities. It allows AI agents and models to interact with your directory, marketplace, and core content securely.

Features:
* Listeo Integration: Listings, Bookings Lifecycle, Health Checks/Auditing, User Dashboard.
* Standards Support: OpenAPI 3.0 (/schema) and JSON-RPC 2.0 (/rpc).
* Admin System Prompts: Copy-ready System Prompt for Claude, ChatGPT, Cursor, and Antigravity.
* Dokan Integration: Vendor Stores, Products.
* WordPress Core: Posts and Pages management.
* Admin Settings: Enable/Disable specific abilities.
* Security: RBAC (Role-Based Access Control) and Sanitization.

== Installation ==

1. Upload the `mcp-listeo-connector` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure enabled abilities in the 'MCP Connector' menu.

== Frequently Asked Questions ==

= Does it work with any theme? =
Specifically designed for Listeo theme, but standard WP Core functions work on any theme.

= Is it secure? =
Yes, it uses internal WordPress capability checks for every request.

== Changelog ==

= 1.2.0 =
* Added System Prompt tab with one-click clipboard copy for AI agents.
* Added listeo/audit-listings ability for Health Check scanning.
* Added bookings management (listeo/list-bookings, listeo/update-booking-status, listeo/create-booking).
* Added JSON-RPC 2.0 endpoint (/rpc) and OpenAPI 3.0 schema (/schema).
* Added plugin version indicator in admin dashboard.

= 1.0.0 =
* Initial release with Listeo and Dokan support.
