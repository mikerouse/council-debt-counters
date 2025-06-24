# Migration and Historical Data

Version 0.2.6 introduces financial year support throughout the plugin. Any documents that existed before this release are automatically assigned to the current financial year. Editors should review each council and update the year if a statement relates to an earlier period.

When uploading a Statement of Accounts you now choose the financial year from a dropdown list. The dropdown defaults to the current financial year (April to March) but you can select any previous year as far back as ten years. This choice controls which year the figures belong to and allows multiple statements to be stored per council.

Shortcodes accept an optional `year` attribute so historical figures can be displayed. For example:

```
[council_counter id="123" year="2023/24"]
[cdc_leaderboard type="highest_debt" year="2022/23"]
```

If `year` is omitted the counters use the current financial year.

## Usage Tips

* Keep older statements uploaded so editors can switch between years when reviewing data.
* Counters on council pages indicate which financial year is being shown so visitors know the context.
* When migrating existing content ensure the correct year is set on each document to keep historical totals accurate.
