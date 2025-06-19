# Council Debt Counters

This WordPress plugin provides animated counters to display UK council debt figures. Counters can be embedded using a shortcode and configured through the WordPress admin interface.

The plugin registers a custom **Council** post type where you can store detailed information about each local authority. The post type is hidden from the regular WordPress menu so users cannot manually add councils from the Posts screen. Councils are managed from the plugin's own **Debt Counters → Councils** submenu. The free version allows up to **two councils**; enter a valid license key on the settings page to create more.

Version 0.2 replaces the dependency on Advanced Custom Fields with a built‑in custom field system. You can create your own fields (text, number or monetary) from **Debt Counters → Custom Fields** and capture the values for each council. Monetary fields display a £ symbol and store values to two decimal places.

The plugin will automatically create the necessary database tables on activation or if they are missing after an update.

By default each council includes standard fields such as **Council Name**, **Council Type**, **Population**, **Households**, **Current Liabilities**, **Long-Term Liabilities**, **PFI or Finance Lease Liabilities**, **Interest Paid on Debt**, and **Minimum Revenue Provision (Debt Repayment)**. These mandatory fields cannot be removed, though you may edit their labels. A **Total Debt** field is calculated automatically from the others and is visible as a read-only value. Additional custom fields can be added, edited or removed from the admin screen and you can change whether they are required as well as their field type (text, number or monetary).

Councils can be added, edited, and deleted from the **Debt Counters → Councils** page which uses a clean Bootstrap design. All custom fields are displayed on this screen so you can capture relevant information before uploading finance documents.

Currently the plugin includes an admin page with instructions for uploading starting debt figures via CSV. Additional functionality such as data uploading and counter rendering will be added in future versions.

The **Troubleshooting** submenu lets you view error logs and choose how much JavaScript debugging information appears in the browser console. Available levels are **Verbose**, **Standard**, and **Quiet**.

## Installation
1. Copy the plugin folder to your `wp-content/plugins` directory.
2. Activate **Council Debt Counters** in the WordPress admin.
3. Visit **Debt Counters** in the admin menu to enter your license key and start adding councils.

## Legal notice

When displaying council data you must comply with the **Copyright, Designs and Patents Act 1988**, **Section 11A of the Freedom of Information Act 2001**, and the **Re-Use of Public Sector Information Regulations 2005**. Data must not be shown in a misleading context or used for commercial gain, including behind paywalls. Always attribute the data source to the relevant council whenever it is displayed, including when output via shortcodes.
