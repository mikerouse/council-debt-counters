# AGENTS.md

## 🎯 Purpose

This plugin provides shortcode-driven **animated counters** that visualise public finance data (e.g. council debt) for individual UK local authorities. It is designed for **activists, campaigners, journalists, councillors, and civic tech developers** who want to embed live, meaningful financial data into WordPress websites in a user-friendly and visually compelling way.

## 🔧 Architectural Principles

This plugin is built around the following key principles, which all contributors must follow:

### ✅ **A**ccessibility
- Ensure that all UI elements are accessible (ARIA tags, high contrast modes, tab focus).
- Counters should work with JS disabled (fallback static value).

### ✅ **G**eneral Purpose & Portable
- Plugin should work on any standard WordPress install without dependency on bespoke themes or frameworks.
- All data (e.g. per-council debt figures) must be configurable via the WordPress admin interface.
- Avoid hardcoded URLs, inline SQL, or site-specific hacks.

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


## 🧩 Features Planned

- `[council_counter]` shortcode (with arguments like `type=debt`, `council="Redditch"`, etc.)
- Admin panel for:
- Uploading CSV of base figures
- Overriding individual council data manually
- Setting animation preferences (tick speed, rounding, digit format)
- Smooth JS animation using `requestAnimationFrame` or lightweight libraries
- Optional charts using Chart.js (or similar) for trends
- Bootstrap UI components (cards, tooltips, badges, tables) for presenting supplementary data

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

This plugin will be released under the **GNU General Public License v2 (or later)** to remain compatible with WordPress.org requirements.


## 🧠 Author Note

This plugin is designed to empower transparency and democratic accountability. It must not be used to harass individuals, spread misinformation, or promote hate. If you build upon this plugin, we ask that you uphold the same civic values.