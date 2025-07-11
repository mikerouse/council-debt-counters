# Council Debt Counters

**Council Debt/Finance Counters** is the backend engine that powers the UK’s unofficial local authority debt (and finance) statistics on our website. Currently this is mikerouse.co.uk the founding developer, but will migrate to a more appropriate website in due course. 

The backend engine, developed as a WordPress plugin, gives site editors and administrators a simple interface to maintain, review and publish animated debt counters for every council along with other information, statistics and facts. The plugin sits within a WordPress instance, allowing greater use of the site later to publish articles and other materials relating to councils as the service grows. 

---

## Key Features

- **Centralised Council Management**  
  Store and edit each council’s data in one place. Fields include:
  - Council name, type and region  
  - Population & households  
  - Liabilities (current, long-term, PFI/leases)  
  - Interest paid on debt  
  - Finance source URL  
  - Custom fields, status messages, “Not available” toggles for when data can't be found  

- **Field-by-Field AI Assistance**  
  – **Ask AI** buttons on each field to fetch figures from PDF statements (OpenAI models).  
  – Review suggested values and sources before saving.  
  – “Ask AI for All” to batch-process every counter field at once.  

- **Approval Workflow & Audit Log**  
  – Visitors can submit updated figures for review.  
  – Admins see side-by-side “Existing vs Submitted” values.  
  – Field-level acceptance or rejection, with actions recorded in a `moderation.log`.

- **Animated Front-end Counters**  
  – Counters animate when visible, powered by CountUp.js.  
  – Shortcodes drive per-council or site-wide totals (debt, spending, deficit, interest, revenue).  
  – Leaderboards: top/bottom councils by any metric.  

- **Custom Fields & Tabs**  
  – Create and configure your own text, number or monetary fields.  
  – Mark individual fields or entire tabs as “N/A” via Bootstrap switches.  
  – Total debt is calculated automatically from component values.

- **Document Management**  
  – Upload or link PDF “Statement of Accounts” for each council.  
  – AI extraction can pull figures directly from uploaded documents.  
  – Store multiple years/types and manage them in one place.

---

## For Site Editors & Administrators

1. **Council List**  
   Navigate to **Debt Counters → Councils**.  
   - Filter by status (Active, Draft, Under Review).  
   - Bulk-repair or mark as “Published as N/A.”

2. **Edit a Council**  
   - Update core fields or toggle “No Accounts Published.”  
   - Upload new account statements or point to external URLs.  
   - Use **Ask AI** for individual fields or **Ask AI for All**.  
   - Save your changes; optionally submit for moderation review.

3. **Power Editor**
   - Go to **Debt Counters → Power Editor**.
   - Quickly edit figures for councils marked “Under Review.”
   - Inline-edit population, liabilities (including PFI/leases), spending,
   deficit, income, interest and whether the council has closed in a
   spreadsheet-like table.
   - Select a year once to apply it to all edits.
   - A spinner in the top-right shows when your changes are saving.
   - Updating any debt figure automatically recalculates Total Debt and
     clears its N/A flag.
   - Ticking **Closed** adds a status message that the council no longer exists,
     marks it active and removes the row.
   - Use **Confirm** to mark a council active and hide it from your list once
     you are done editing.
   - The header and column headings stay visible as you scroll.

4. **Moderation Review**
   - Go to **Debt Counters → Submissions**.  
   - Click **Review** on a pending submission.  
   - Compare existing vs submitted values, choose per field, then **Save**.  
   - All actions are logged to `moderation.log` for audit.

5. **Publishing Shortcodes**

   Embed counters anywhere on the live site using: ` [council_counter id="123"] [total_debt_counter] [cdc_leaderboard type="debt_per_resident" limit="5"] `

   See the “Shortcodes” section below for full usage.

6. **Troubleshooting & Logs**
- **Debt Counters → Troubleshooting** to view AI and error logs.  
- Adjust JavaScript debug levels (Verbose, Standard, Quiet).  
- Inspect token-usage and progress overlays when AI runs.

---

## Available Shortcodes

  - `[council_counter id="…"]` – Animated per-council figures that count from zero to the annual total over fifteen seconds.
  - `[total_debt_counter year="YYYY/YY"]`, `[total_spending_counter]`, `[total_deficit_counter]`, `[total_interest_counter]`, `[total_revenue_counter]` – Site-wide totals. Defaults use the year set in **Debt Counters → Settings**. These counters count up from zero to the total figure over fifteen seconds.
  - `[total_custom_counter type="reserves|income|consultancy"]` – Any custom metric.
  - `[cdc_leaderboard type="highest_debt|debt_per_resident|lowest_reserves" limit="…"]` – Ranked lists or tables with a year selector.
  - Deficit counters switch to **Surplus** when the figure is negative, displaying the value as a positive.

---

## Deployment Notes

This plugin is tightly integrated into our site’s theme and admin UI. It is **not** intended for general WordPress distribution. Our team controls updates, security and licensing centrally. If you need to run it elsewhere, you can extract the plugin folder and see how you get on, but support is only provided for our official instance.

Bootstrap can be loaded from a CDN via **Debt Counters → Settings → Load assets from CDN**. Font Awesome icons are always served via our kit.

### Front‑end Financial Year Selector

When viewing a single council page, a drop‑down will appear above the content allowing visitors to choose which financial year to display. Selecting a year fetches the updated HTML via AJAX and re-renders all counters. Ensure your theme outputs `the_content()` so the selector can be injected automatically.

---

*If you’re part of our content team and have questions about any feature, please reach out to the development team or review the Troubleshooting logs
