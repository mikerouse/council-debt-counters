# Council Debt Counters

**Council Debt Counters** is the backend engine that powers the UK’s official local‐authority debt statistics on our site.  
It gives site editors and administrators a simple interface to maintain, review and publish animated debt counters for every council.

---

## Key Features

- **Centralised Council Management**  
  Store and edit each council’s data in one place. Fields include:
  - Council name, type and region  
  - Population & households  
  - Liabilities (current, long-term, PFI/leases)  
  - Interest paid on debt  
  - Finance source URL  
  - Custom fields, status messages, “Not available” toggles  

- **Field-by-Field AI Assistance**  
  – **Ask AI** buttons on each field to fetch figures from PDF statements (OpenAI models).  
  – Review suggested values and sources before saving.  
  – “Ask AI for All” to batch-process every counter field at once.  

- **Approval Workflow & Audit Log**  
  – Data editors can submit updated figures for review.  
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

3. **Moderation Review**  
   - Go to **Debt Counters → Submissions**.  
   - Click **Review** on a pending submission.  
   - Compare existing vs submitted values, choose per field, then **Save**.  
   - All actions are logged to `moderation.log` for audit.

4. **Publishing Shortcodes**  
   Embed counters anywhere on the live site using: 
   ` [council_counter id="123"] [total_debt_counter] [cdc_leaderboard type="debt_per_resident" limit="5"] `
   See the “Shortcodes” section below for full usage.

5. **Troubleshooting & Logs**  
- **Debt Counters → Troubleshooting** to view AI and error logs.  
- Adjust JavaScript debug levels (Verbose, Standard, Quiet).  
- Inspect token-usage and progress overlays when AI runs.

---

## Available Shortcodes

- `[council_counter id="…"]` – Animated per-council figures.  
- `[total_debt_counter]`, `[total_spending_counter]`, `[total_deficit_counter]`, `[total_interest_counter]`, `[total_revenue_counter]` – Site-wide totals.  
- `[total_custom_counter type="reserves|income|consultancy"]` – Any custom metric.  
- `[cdc_leaderboard type="highest_debt|debt_per_resident|lowest_reserves" limit="…"]` – Ranked lists or tables.  

---

## Configuration & Settings

- **Licences & Addons**  
  Enter your OpenAI API key and choose models (gpt-3.5, gpt-4, o4-mini, gpt-4o, etc.).  
- **General Settings**  
  Enable stacks, set currency format, pick fonts (default: Oswald 600).  
- **Counters**  
  Toggle which metrics appear and adjust animation timing.  
- **AI & Debug**  
  Control request throttling, timeouts (`cdc_openai_timeout` filter), and logging verbosity.  

---

## Deployment Notes

This plugin is tightly integrated into our site’s theme and admin UI. It is **not** intended for general WordPress distribution. Our team controls updates, security and licensing centrally. If you need to run it elsewhere, you can extract the plugin folder and see how you get on, but support is only provided for our official instance.

---

*If you’re part of our content team and have questions about any feature, please reach out to the development team or review the Troubleshooting logs