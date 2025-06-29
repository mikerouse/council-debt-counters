# Supplemental AGENTS.md

Read the AGENTS.md file in the parent directory to understand the history of this app and where it started. If you are reading this AGENTS.md file it is the dominant file. 

## 🎯 Purpose

This Laravel PHP app is the new app that replaces the WordPress plugin that was the original version of this app. The purpose of the app is to provide a visually compelling way to present local government finance data for councils and combined authorities. Eventually, this will be extended to include NHS Trusts, Police Forces and other public sector organisations that produce balance sheets and comprehensive statements of income and expenditure. The aim is to become the UK's most reliable database for local government and public sector finance data in a format that can be shared, consumed and examined. Importantly, the data must be comparable via an e-commerce like experience, allowing the public to compare the finances of two or more local councils for instance. 

The app will likely run on data that has been input by activists and supporters based on transcription and cross-keying from published PDFs. Over time, the data may come from better sources. Ultimately the aim is to provide real-time data. 

The emphasis for this app is 'visually compelling', which means animated and striking ways of showing the data. It also means ways of describing the data in ways humans can more easily understand. For instance, if a council is spending millions of pounds on a certain area we could express that in helper/muted text as the number of Ford Focus cars that could be purchased for the same amount, or other such quirky facts. For example, if the council's debt were to be represented by pound coins how many olympic-sized swimming pools would be filled. 

The aim is to provide a fast, responsive and fun app for people to play around with an interrogate. It should make local government finance data fun, accessible, readable, understandable and above all relatable. 

## 🔧 Architectural Principles

This plugin is built around the following key principles, which all contributors must follow:

### ✅ **A**ccessibility
- Ensure that all UI elements are accessible (ARIA tags, high contrast modes, tab focus).
- Counters do not need to work with JS disabled - a message can be shown asking the user to turn on JavaScript. 
- Do not assume a high level of coding knowledge - ***always*** provide comments around your code to explain what it is doing and why.

### ✅ **G**eneral Purpose & Portable
- The app should be built using Laravel with the intention of deploying a native smartphone app later - that means API endpoints for everything in addition to any website work.
- All data (e.g. per-council debt figures) must be configurable via the admin interface. We aim to provide as much configuration via backend screens as possible. 
- Avoid hardcoded URLs, inline SQL, or site-specific hacks.
- Ensure that as many actions as possible are logged to an accessible troubleshooting tool
- Think *holistically* about the system - don't just update a single PHP page or function - look at where the function is being called from and see if any changes need to be made there too. 

### ✅ **E**xtensibility
- All counter types (e.g. debt, tax per household, interest) should use a modular approach - allowing new types of counters to be added later
- Developers should be able to register new counter types with minimal boilerplate.

### ✅ **N**ot reinventing the wheel
- Follow standards and best practices
- Don't be afraid to use frameworks and plugins
- Don't make hard work for future developers

### ✅ **T**heming & Style
- All styling should be implemented using a modern system like Bootstrap.
- Animations must be **smooth digit transitions** (not whole-number jumps).
- Allow theme overrides via class names and filter hooks where appropriate.
- Allow users to choose fonts and styles for counters they create.
- When creating things like form fields ensure that descriptions and helper text is provided for the user. 

### ✅ **S**eparation of Concerns
- Follow **object-oriented design** with a clear directory and file structure:

## 🙌 Contributions

We welcome pull requests that:
- Improve performance or accessibility
- Extend shortcode types or admin UI functionality
- Fix bugs or enhance code clarity

Please respect the above design patterns, submit pull requests with clear commit messages, and include inline comments wherever possible.

## 📜 Licensing

This app will be released under an appropriate attribution licence later.

## 🧠 Author Note

This plugin is designed to empower transparency and democratic accountability. It must not be used to harass individuals, spread misinformation, or promote hate. If you build upon this plugin, we ask that you uphold the same civic values.