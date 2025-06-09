# Council Debt Counters

This WordPress plugin provides animated counters to display UK council debt figures. Counters can be embedded using a shortcode and configured through the WordPress admin interface.

The plugin registers a custom **Council** post type where you can store detailed information about each local authority. The post type is hidden from the regular WordPress menu so users cannot manually add councils from the Posts screen. It remains visible in ACF location rules and can be managed from the plugin's own **Debt Counters → Councils** submenu. The free version allows up to **two councils**; enter a valid license key on the settings page to create more.

This plugin depends on the **Advanced Custom Fields** (ACF) plugin. Please install and activate ACF before using Council Debt Counters.

Councils can be added, edited, and deleted from the **Debt Counters → Councils** page which uses a clean Bootstrap design. The ACF field groups assigned to the `council` post type are displayed on this screen so you can capture all relevant information before uploading finance documents.

Currently the plugin includes an admin page with instructions for uploading starting debt figures via CSV. Additional functionality such as data uploading and counter rendering will be added in future versions.

## Installation
1. Copy the plugin folder to your `wp-content/plugins` directory.
2. Activate **Council Debt Counters** in the WordPress admin.
3. Ensure the Advanced Custom Fields plugin is installed and active.
4. Visit **Debt Counters** in the admin menu to enter your license key and start adding councils.
