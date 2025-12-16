<?php
/**
 * Import US Zip Codes from CSV file
 * 
 * Usage: php api/import_zip_codes.php path/to/zip-codes.csv
 * 
 * CSV Format Expected (adjust column indices in code if different):
 * zip, city, state, latitude, longitude, state_name, county
 * 
 * Example sources:
 * - SimpleMaps: https://simplemaps.com/data/us-zips
 * - GeoNames: http://download.geonames.org/export/zip/US.zip
 */

require_once '../config/database.php';

$table_prefix = DB_TABLE_PREFIX;
$table_name = $table_prefix . 'zip_codes';

// Read CSV file (adjust path and format as needed)
$csvFile = 'uszips.csv'; // Your CSV file path


$conn = getDBConnection();
if (!$conn) {
    echo "Error: Database connection failed\n";
    exit(1);
}

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE '{$table_name}'");
if ($result->num_rows === 0) {
    echo "Error: Table '{$table_name}' does not exist. Please run the migration first.\n";
    echo "See: api/migrations/add_zip_codes_table.sql\n";
    $conn->close();
    exit(1);
}

// Prepare insert statement with ON DUPLICATE KEY UPDATE
$stmt = $conn->prepare("INSERT INTO {$table_name} (zip_code, latitude, longitude, city, state, state_name, county) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE latitude=VALUES(latitude), longitude=VALUES(longitude), city=VALUES(city), state=VALUES(state), state_name=VALUES(state_name), county=VALUES(county)");

if (!$stmt) {
    echo "Error preparing statement: " . $conn->error . "\n";
    $conn->close();
    exit(1);
}

// Open CSV file
$handle = fopen($csvFile, 'r');
if ($handle === false) {
    echo "Error: Could not open CSV file: {$csvFile}\n";
    $stmt->close();
    $conn->close();
    exit(1);
}

// Read header row (skip it)
$header = fgetcsv($handle);
echo "CSV Header: " . implode(', ', $header) . "\n";
echo "Starting import...\n\n";

$imported = 0;
$errors = 0;
$skipped = 0;
$startTime = microtime(true);

// Process each row
while (($data = fgetcsv($handle)) !== false) {
    // CSV Format: zip, lat, lng, city, state_id, state_name, zcta, parent_zcta, population, density, county_fips, county_name, ...
    // Map to our database columns:
    $zip = isset($data[0]) ? trim($data[0]) : '';
    $lat = isset($data[1]) ? floatval($data[1]) : 0;
    $lng = isset($data[2]) ? floatval($data[2]) : 0;
    $city = isset($data[3]) ? trim($data[3]) : null;
    $state = isset($data[4]) ? trim($data[4]) : null; // state_id
    $stateName = isset($data[5]) ? trim($data[5]) : null; // state_name
    $county = isset($data[11]) ? trim($data[11]) : null; // county_name
    
    // Normalize zip code to 5 digits
    $zip = preg_replace('/[^0-9]/', '', $zip);
    $zip = substr($zip, 0, 5);
    $zip = str_pad($zip, 5, '0', STR_PAD_LEFT);
    
    // Validate zip code
    if (strlen($zip) !== 5 || !ctype_digit($zip)) {
        $skipped++;
        continue;
    }
    
    // Validate coordinates
    if ($lat == 0 || $lng == 0 || abs($lat) > 90 || abs($lng) > 180) {
        $skipped++;
        continue;
    }
    
    // Insert or update
    $stmt->bind_param("sddssss", $zip, $lat, $lng, $city, $state, $stateName, $county);
    if ($stmt->execute()) {
        $imported++;
        if ($imported % 1000 === 0) {
            echo "Imported {$imported} zip codes...\n";
        }
    } else {
        $errors++;
        if ($errors <= 10) { // Only show first 10 errors
            echo "Error importing zip {$zip}: " . $stmt->error . "\n";
        }
    }
}

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

echo "\n";
echo "========================================\n";
echo "Import Complete!\n";
echo "========================================\n";
echo "Imported: {$imported} zip codes\n";
echo "Errors: {$errors}\n";
echo "Skipped: {$skipped}\n";
echo "Duration: {$duration} seconds\n";
echo "========================================\n";

fclose($handle);
$stmt->close();
$conn->close();

