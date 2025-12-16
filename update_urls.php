<?php
/**
 * Quick script to update WordPress URLs in the database
 * Run this once via browser or command line to update URLs from staging to localhost
 * 
 * Usage: Visit https://localhost/slippy-wp/update_urls.php in your browser
 *        Or run: php update_urls.php
 */

// Load WordPress
require_once('wp-load.php');

// Old staging URL
$old_url = 'https://slippycheckcom.bigscoots-staging.com';

// New localhost URL
$new_url = 'https://localhost/slippy-wp';

// If you're using http instead of https, change it:
// $new_url = 'http://localhost/slippy-wp';

global $wpdb;

// Update siteurl and home options
$wpdb->query($wpdb->prepare(
    "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = 'siteurl'",
    $new_url
));

$wpdb->query($wpdb->prepare(
    "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = 'home'",
    $new_url
));

// Update any serialized URLs in post content and meta
$wpdb->query($wpdb->prepare(
    "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s)",
    $old_url,
    $new_url
));

$wpdb->query($wpdb->prepare(
    "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, %s, %s)",
    $old_url,
    $new_url
));

echo "URLs updated successfully!\n";
echo "Old URL: {$old_url}\n";
echo "New URL: {$new_url}\n";
echo "\n";
echo "You can now delete this file for security.\n";
?>


