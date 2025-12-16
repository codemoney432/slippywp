# Database Migration: Add Intersection Column

This migration adds an `intersection` column to the `reports` table to store intersection/address information, eliminating the need to fetch it from the Nominatim API on every page load.

## Steps to Apply Migration

### 1. Add the Column to Database

Run the SQL script to add the `intersection` column:

```sql
ALTER TABLE `{YOUR_TABLE_PREFIX}reports` 
ADD COLUMN `intersection` VARCHAR(255) NULL DEFAULT NULL AFTER `submitter_name`;
```

**Important:** Replace `{YOUR_TABLE_PREFIX}` with your actual table prefix (e.g., `slippy_`).

You can run this via:
- phpMyAdmin
- MySQL command line
- Your database management tool

### 2. (Optional) Backfill Existing Reports

If you have existing reports without intersection data, you can run the backfill script:

```bash
php api/migrations/backfill_intersections.php
```

**Note:** This script respects Nominatim's rate limit of 1 request per second, so it will take approximately 1.1 seconds per report. For 100 reports, this would take about 2 minutes.

**Warning:** Only run this if you have existing reports. New reports will automatically have intersection data populated when created.

## What Changed

1. **New reports**: Intersection is now fetched and stored when a report is created (via `create_report.php`)
2. **Existing reports**: Intersection is included in the API response from `get_reports.php` (will be `null` for old reports)
3. **Frontend**: `app.js` now uses the stored intersection data instead of making separate API calls
4. **Performance**: Page loads are much faster since intersections are loaded from the database instead of making slow API calls

## Benefits

- ✅ Faster page loads (no waiting for Nominatim API calls)
- ✅ Reduced API calls to Nominatim (respects rate limits better)
- ✅ Better user experience (intersections appear immediately)
- ✅ More reliable (no dependency on external API availability for displaying reports)

## Notes

- The `get_intersection.php` API endpoint still exists and can be used for manual lookups or backfilling
- Old reports without intersection data will show "Location unavailable" until backfilled
- New reports will automatically have intersection data populated


