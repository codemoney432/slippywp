# US Zip Code Database Setup

This document explains how to set up the local US zip code database for fast zip code lookups.

## Overview

The local zip code database replaces slow Nominatim API calls with instant database lookups. This provides:
- **Instant results** (milliseconds instead of 9+ seconds)
- **No rate limits** (unlimited queries)
- **Reliable** (no external API dependencies)
- **Cost-free** (no API fees)

## Database Migration

1. **Run the migration SQL file:**
   ```bash
   # Replace {TABLE_PREFIX} with your actual table prefix (e.g., slippy_)
   mysql -u your_username -p your_database < api/migrations/add_zip_codes_table.sql
   ```
   
   Or manually execute the SQL in your database management tool, replacing `{TABLE_PREFIX}` with your table prefix (e.g., `slippy_`).

## Importing Zip Code Data

You need to populate the `zip_codes` table with US zip code data. Here are several options:

### Option 1: Free CSV Data Sources

1. **SimpleMaps US Zip Codes Database** (Free, ~42,000 zip codes)
   - Download: https://simplemaps.com/data/us-zips
   - Format: CSV with zip, city, state, lat, lng, etc.
   - Import script: See `import_zip_codes.php` below

2. **GeoNames US Postal Codes** (Free, comprehensive)
   - Download: http://download.geonames.org/export/zip/US.zip
   - Format: Tab-separated with zip, city, state, lat, lng, etc.
   - Requires parsing the TSV format

### Option 2: Create Import Script

Create a PHP script to import CSV data:

```php
<?php
// api/import_zip_codes.php
require_once '../config/database.php';

$conn = getDBConnection();
$table_name = DB_TABLE_PREFIX . 'zip_codes';

// Read CSV file (adjust path and format as needed)
$csvFile = 'uszips.csv'; // Your CSV file path
$handle = fopen($csvFile, 'r');

if ($handle === false) {
    die("Could not open CSV file\n");
}

// Skip header row if present
$header = fgetcsv($handle);

$stmt = $conn->prepare("INSERT INTO {$table_name} (zip_code, latitude, longitude, city, state, state_name, county) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE latitude=VALUES(latitude), longitude=VALUES(longitude), city=VALUES(city), state=VALUES(state), state_name=VALUES(state_name), county=VALUES(county)");

$imported = 0;
$errors = 0;

while (($data = fgetcsv($handle)) !== false) {
    // Adjust column indices based on your CSV format
    // Example format: zip, city, state, lat, lng, state_name, county
    $zip = str_pad($data[0], 5, '0', STR_PAD_LEFT); // Ensure 5 digits
    $city = $data[1] ?? null;
    $state = $data[2] ?? null;
    $lat = floatval($data[3] ?? 0);
    $lng = floatval($data[4] ?? 0);
    $stateName = $data[5] ?? null;
    $county = $data[6] ?? null;
    
    if (strlen($zip) === 5 && is_numeric($zip) && $lat != 0 && $lng != 0) {
        $stmt->bind_param("sddssss", $zip, $lat, $lng, $city, $state, $stateName, $county);
        if ($stmt->execute()) {
            $imported++;
        } else {
            $errors++;
            echo "Error importing zip {$zip}: " . $stmt->error . "\n";
        }
    }
}

echo "Import complete: {$imported} zip codes imported, {$errors} errors\n";

fclose($handle);
$stmt->close();
$conn->close();
?>
```

### Option 3: Manual SQL Import

If you have a SQL dump or can convert your data to SQL:

```sql
INSERT INTO `slippy_zip_codes` (zip_code, latitude, longitude, city, state, state_name, county) VALUES
('98121', 47.6150099, -122.3459347, 'Seattle', 'WA', 'Washington', 'King County'),
('10001', 40.7506, -73.9972, 'New York', 'NY', 'New York', 'New York County'),
-- ... more zip codes
ON DUPLICATE KEY UPDATE latitude=VALUES(latitude), longitude=VALUES(longitude);
```

## Testing

After importing data, test the API endpoint:

```bash
# Test with a known zip code
curl "http://localhost/slippywp/api/get_zip_code.php?zip=98121"
```

Expected response:
```json
{
  "success": true,
  "zip_code": "98121",
  "latitude": 47.6150099,
  "longitude": -122.3459347,
  "city": "Seattle",
  "state": "WA",
  "state_name": "Washington",
  "county": "King County",
  "display_name": "Seattle, WA, 98121"
}
```

## Maintenance

- **Update frequency**: Zip codes rarely change, but you may want to update annually
- **Missing zip codes**: The system will automatically fall back to Nominatim for zip codes not in the database
- **Performance**: Database lookups are typically < 10ms vs 9+ seconds for Nominatim

## Notes

- The database stores only 5-digit zip codes (ZIP+4 format is normalized to 5 digits)
- The `ON DUPLICATE KEY UPDATE` clause in the import script allows re-running imports safely
- The API endpoint falls back to Nominatim if a zip code isn't found locally

