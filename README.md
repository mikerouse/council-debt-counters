# Council Debt Counters

This WordPress plugin provides animated counters to display UK council debt figures. Counters can be embedded using a shortcode and configured through the WordPress admin interface.

The plugin registers a custom **Council** post type where you can store detailed information about each local authority. The post type is hidden from the regular WordPress menu so users cannot manually add councils from the Posts screen. Councils are managed from the plugin's own **Debt Counters → Councils** submenu. The free version allows up to **two councils**; enter a valid license key on the settings page to create more.

Version 0.2 replaces the dependency on Advanced Custom Fields with a built‑in custom field system. You can create your own fields (text, number or monetary) from **Debt Counters → Custom Fields** and capture the values for each council. Monetary fields display a £ symbol and store values to two decimal places.

The plugin will automatically create the necessary database tables on activation or if they are missing after an update.

If you provide an OpenAI API key you can let the plugin attempt to pull key figures from uploaded Statement of Accounts documents. Select the desired model under **Debt Counters → Settings**; this option is ignored unless an API key has been entered on the **Licences & Addons** page. Models like **o3**, **o4-mini** and **gpt‑4o** are available alongside gpt‑3.5 and gpt‑4. The AI’s suggestions are shown in the admin area so you can review and confirm them before the values are stored for that council. Large PDFs are automatically split into smaller chunks so each OpenAI request stays within the model’s token limits. Requests are throttled to each model’s tokens-per-minute allowance (for example 200k TPM for o4-mini and 30k TPM for gpt‑4o). The progress overlay now displays how many tokens were used and shows a countdown bar so you know how long to wait. Requests allow up to 60 seconds by default; filter `cdc_openai_timeout` to change this.
If the accounts indicate figures are in thousands of pounds (e.g. using "£000s" headings) the AI multiplies numbers by 1,000 so stored values are the full amounts.

By default each council includes standard fields such as **Council Name**, **Council Type**, **Population**, **Households**, **Current Liabilities**, **Long-Term Liabilities**, **PFI or Finance Lease Liabilities**, **Interest Paid on Debt**, and a **Financial Data Source URL** so visitors can view the statement used. These mandatory fields cannot be removed, though you may edit their labels. A **Total Debt** field is calculated automatically from the others and is visible as a read-only value. Additional custom fields &ndash; including a **Status Message** and **Status Message Type** &ndash; can be added, edited or removed from the admin screen and you can change whether they are required as well as their field type (text, number or monetary).

Councils can be added, edited, and deleted from the **Debt Counters → Councils** page which uses a clean Bootstrap design. All custom fields are displayed on this screen so you can capture relevant information before uploading finance documents.

Currently the plugin includes an admin page with instructions for uploading starting debt figures via CSV. Additional functionality such as data uploading and counter rendering will be added in future versions.

The **Troubleshooting** submenu lets you view error logs and choose how much JavaScript debugging information appears in the browser console. Available levels are **Verbose**, **Standard**, and **Quiet**. Counters now log more details when set to Verbose, which can help diagnose issues with animation or data loading. Counters only initialise once when they become visible thanks to an `IntersectionObserver`, preventing duplicate animations if page builders reinsert the HTML.

You can also pick a Google Font and weight for the counters on the Settings page. The font defaults to **Oswald** with a weight of **600**, but you can select other styles to match your theme.

## Shortcodes

Use `[council_counter]` to display figures for a specific council. For overall figures across every council in the database you can use the following shortcodes:

- `[total_debt_counter]`
- `[total_spending_counter]`
- `[total_deficit_counter]`
- `[total_interest_counter]`
- `[total_revenue_counter]`
- `[total_custom_counter type="reserves|spending|income|deficit|interest|consultancy"]`

These counters animate just like the per‑council versions but sum the selected field for all councils.

The total debt counter also displays how many councils are included in the calculation.

### Leaderboards

Display ranked tables or lists of councils with `[cdc_leaderboard]`. Example:

```
[cdc_leaderboard type="debt_per_resident" limit="5" format="table" link="1"]
```

Available `type` options include `highest_debt`, `debt_per_resident`,
`debt_to_reserves_ratio`, `biggest_deficit`, `lowest_reserves`,
`highest_spending_per_resident` and `highest_interest_paid`. The `limit`
parameter controls how many rows are shown, `format` can be `table` or `list`,
and setting `link="1"` adds a View details link for each council.

### Social sharing

Add `[cdc_share_buttons]` on a single council page to show buttons for sharing
that council’s stats on X, WhatsApp or Facebook. The shortcode automatically
uses the selected council’s name, relevant figure and permalink so each share
promotes that specific page rather than the site in general. When used outside
of a council post you can pass an `id` (or `council_id`) attribute to specify
which council’s data should be used.

### Status messages

Use `[council_status]` to display any status message you have recorded for a council. The shortcode falls back to the post status (Draft or Under Review) if no custom message is set and outputs a Bootstrap alert with the chosen style.


## Installation
1. Copy the plugin folder to your `wp-content/plugins` directory.
2. Activate **Council Debt Counters** in the WordPress admin.
3. Visit **Debt Counters** in the admin menu to enter your license key and start adding councils.

## Asset loading and CDNs

Bootstrap 5 and CountUp.js are bundled locally inside the plugin's `public/` directory. These files are loaded by default to avoid external requests. If you prefer to use a CDN instead, hook into the `cdc_use_cdn` filter:

```php
add_filter( 'cdc_use_cdn', '__return_true' );
```

## Uninstalling
Deleting the plugin from the Plugins screen removes all data it created. This includes the custom `council` posts, uploaded documents, custom database tables and stored options like the licence key or OpenAI API key.

## Legal notice

When displaying council data you must comply with the **Copyright, Designs and Patents Act 1988**, **Section 11A of the Freedom of Information Act 2001**, and the **Re-Use of Public Sector Information Regulations 2005**. Data must not be shown in a misleading context or used for commercial gain, including behind paywalls. Always attribute the data source to the relevant council whenever it is displayed, including when output via shortcodes.
