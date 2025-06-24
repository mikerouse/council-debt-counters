# AGENTS.md

## 🎯 Purpose

This plugin assimilates into a WordPress-powered website and acts as the means to extend the functions of the website to deliver an app-like experience. Eventually, the plugin will be evolved into a standalone PHP app that does not depend on WordPress. The purpose of the app is to provide a visually compelling way to communicate local government finance data (e.g. council debt) for individual UK local authorities. It is designed for **activists, campaigners, journalists, councillors, and civic tech developers** who want to embed or use live, meaningful financial data in a user-friendly and visually compelling way. We will, eventually, provide an API for the same data to be available for things like mechanical counters that can call a particular API endpoint to make a particular request for a particular counter and get a simple number in reply. 

Overall, the visitors will now be clear about what a counter is showing them. When this started as a campaign tool, it was intended to be a visual illustration, but now that it is turning into a transparency tool we need to be more accurate. The counters will therefore run on the basis of the financial year for which we have figures. When a visitor sees a council's front-end page it will state the year's figures we are using (e.g. 2023/24) and the counters will spin up (count up) based on what the debt/spending/interest *was* at this point in that year (where 'this' means DateTime(now) and where 'that' means the financial year being displayed). For example and context only, we could say for instance that this council had spent £203,814,637.33 on 25/06/2025 at 00:26:48 and continue the counter until the end of the year, when it will start again. 

## 🔧 Architectural Principles

This plugin is built around the following key principles, which all contributors must follow:

### ✅ **A**ccessibility
- Ensure that all UI elements are accessible (ARIA tags, high contrast modes, tab focus).
- Counters do not need to work with JS disabled - a message can be shown asking the user to turn on JavaScript. 
- Do not assume a high level of coding knowledge - ***always*** provide comments around your code to explain what it is doing and why.

### ✅ **G**eneral Purpose & Portable
- Plugin only needs to work on this WordPress install for now, with a view to migrating to a PHP standalone app later (potentially based on the Symfony framework)
- All data (e.g. per-council debt figures) must be configurable via the WordPress admin interface. We aim to provide as much configuration via backend screens as possible. 
- Avoid hardcoded URLs, inline SQL, or site-specific hacks.
- Ensure that as many actions as possible are logged to the Troubleshooting tool
- Think *holistically* about the system - don't just update a single PHP page or function - look at where the function is being called from and see if any changes need to be made there too. 

### ✅ **E**xtensibility
- All counter types (e.g. debt, tax per household, interest) should use a modular shortcode handler structure.
- Developers should be able to register new counter types with minimal boilerplate.

### ✅ **N**ative WordPress Compliance
- Follow WordPress Plugin Handbook guidelines: 
  - Use WordPress Settings API
  - Use `wp_enqueue_script` and `wp_enqueue_style` properly
  - Namespace functions, classes, and options to avoid conflicts
  - Prepare for translation (`__()`, `_e()`, etc.)

### ✅ **T**heming & Style
- All styling should be implemented using **Bootstrap 5**, ideally using Bootstrap utility classes where possible.
- Animations must be **smooth digit transitions** (not whole-number jumps).
- Allow theme overrides via class names and filter hooks where appropriate.
- Allow users to choose fonts and styles for counters they create.
- When creating things like form fields ensure that descriptions and helper text is provided for the user. 

### ✅ **S**eparation of Concerns
- Follow **object-oriented design** with a clear directory and file structure:

- /includes/
- - class-counter-manager.php
- - class-shortcode-renderer.php
- - class-settings-page.php
- - class-data-loader.php
- /admin/
- views/
- js/
- css/
- /public/
- - js/
- - css/

## 📦 Plugin Compatibility Targets

- WordPress 5.8+
- PHP 7.4+ (ideally PHP 8+ tested)
- Compatible with Elementor, Classic Editor, and Full Site Editing
- Zero dependency on jQuery (optional polyfill support only)

## 🙌 Contributions

We welcome pull requests that:
- Improve performance or accessibility
- Extend shortcode types or admin UI functionality
- Fix bugs or enhance code clarity

Please respect the above design patterns, submit pull requests with clear commit messages, and include inline comments where appropriate.

## 📜 Licensing

This plugin will be released under an appropriate attribution licence later.

## 🧠 Author Note

This plugin is designed to empower transparency and democratic accountability. It must not be used to harass individuals, spread misinformation, or promote hate. If you build upon this plugin, we ask that you uphold the same civic values.