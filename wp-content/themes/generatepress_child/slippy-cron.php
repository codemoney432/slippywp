<?php
/**
 * Slippy Intersection Backfill Cron Job
 * 
 * This cron job runs every 5 minutes to backfill intersection data
 * for reports that failed to get intersections during creation.
 * 
 * @package GeneratePress Child
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Register the cron schedules
 * Must be registered early so WordPress recognizes them when rescheduling
 */
function slippy_add_cron_schedule($schedules) {
    if (!is_array($schedules)) {
        $schedules = array();
    }
    $schedules['slippy_every_5min'] = array(
        'interval' => 300, // 5 minutes in seconds
        'display' => __('Every 5 Minutes', 'generatepress')
    );
    $schedules['slippy_every_2min'] = array(
        'interval' => 120, // 2 minutes in seconds
        'display' => __('Every 2 Minutes', 'generatepress')
    );
    return $schedules;
}
// Register early - before WordPress tries to reschedule events
add_filter('cron_schedules', 'slippy_add_cron_schedule', 10, 1);

/**
 * Schedule the cron events on theme activation and ensure they're scheduled
 */
function slippy_schedule_cron_jobs() {
    // Ensure schedules are registered before scheduling
    $schedules = wp_get_schedules();
    
    if (!isset($schedules['slippy_every_5min'])) {
        error_log('Slippy cron: slippy_every_5min schedule not registered. Cron schedules filter may not be active.');
        return;
    }
    if (!isset($schedules['slippy_every_2min'])) {
        error_log('Slippy cron: slippy_every_2min schedule not registered. Cron schedules filter may not be active.');
        return;
    }
    
    if (!wp_next_scheduled('slippy_backfill_intersections')) {
        $result = wp_schedule_event(time(), 'slippy_every_5min', 'slippy_backfill_intersections');
        if ($result === false) {
            error_log('Slippy cron: Failed to schedule slippy_backfill_intersections');
        }
    }
    if (!wp_next_scheduled('slippy_moderate_comments')) {
        $result = wp_schedule_event(time(), 'slippy_every_2min', 'slippy_moderate_comments');
        if ($result === false) {
            error_log('Slippy cron: Failed to schedule slippy_moderate_comments');
        }
    }
}
add_action('after_switch_theme', 'slippy_schedule_cron_jobs');

// Also ensure they're scheduled on init (in case theme was already active)
// Use priority 20 to ensure schedules filter has run first
add_action('init', 'slippy_schedule_cron_jobs', 20);

/**
 * Unschedule the cron events on theme deactivation
 */
function slippy_unschedule_cron_jobs() {
    $timestamp1 = wp_next_scheduled('slippy_backfill_intersections');
    if ($timestamp1) {
        wp_unschedule_event($timestamp1, 'slippy_backfill_intersections');
    }
    $timestamp2 = wp_next_scheduled('slippy_moderate_comments');
    if ($timestamp2) {
        wp_unschedule_event($timestamp2, 'slippy_moderate_comments');
    }
}
add_action('switch_theme', 'slippy_unschedule_cron_jobs');

/**
 * Backfill intersections for reports with NULL intersection
 * This runs every 5 minutes via WordPress cron
 */
function slippy_backfill_intersections_cron() {
    // Include the helper function
    $helper_path = get_template_directory() . '/../../api/helpers/intersection_helper.php';
    if (file_exists($helper_path)) {
        require_once $helper_path;
    } else {
        // Try alternative path
        $helper_path = ABSPATH . 'api/helpers/intersection_helper.php';
        if (file_exists($helper_path)) {
            require_once $helper_path;
        } else {
            error_log('Slippy cron: Could not find intersection_helper.php');
            return;
        }
    }
    
    // Include database config
    $db_config_path = ABSPATH . 'config/database.php';
    if (file_exists($db_config_path)) {
        require_once $db_config_path;
    } else {
        error_log('Slippy cron: Could not find database.php');
        return;
    }
    
    $table_prefix = defined('DB_TABLE_PREFIX') ? DB_TABLE_PREFIX : 'slippy_';
    
    $conn = getDBConnection();
    if (!$conn) {
        error_log('Slippy cron: Database connection failed');
        return;
    }
    
    // Get up to 10 reports without intersection (process in small batches)
    $query = "SELECT id, latitude, longitude FROM `{$table_prefix}reports` 
              WHERE intersection IS NULL 
              ORDER BY id ASC 
              LIMIT 10";
    
    $result = $conn->query($query);
    if (!$result) {
        error_log('Slippy cron: Query failed - ' . $conn->error);
        $conn->close();
        return;
    }
    
    $processed = 0;
    $updated = 0;
    $failed = 0;
    
    while ($row = $result->fetch_assoc()) {
        $processed++;
        $report_id = intval($row['id']);
        $lat = floatval($row['latitude']);
        $lng = floatval($row['longitude']);
        
        // Try to fetch intersection with retries (up to 5 attempts)
        $intersection = null;
        $maxRetries = 5;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $intersection = fetchIntersection($lat, $lng);
            if ($intersection !== null && $intersection !== '') {
                break; // Success - exit retry loop
            }
            // Wait 1 second between retries (respect Nominatim rate limits)
            if ($attempt < $maxRetries) {
                sleep(1);
            }
        }
        
        if ($intersection !== null && $intersection !== '') {
            // Update the report
            $update_stmt = $conn->prepare("UPDATE `{$table_prefix}reports` SET intersection = ? WHERE id = ?");
            if ($update_stmt) {
                $update_stmt->bind_param("si", $intersection, $report_id);
                if ($update_stmt->execute()) {
                    $updated++;
                } else {
                    $failed++;
                    error_log("Slippy cron: Failed to update report {$report_id} - " . $update_stmt->error);
                }
                $update_stmt->close();
            } else {
                $failed++;
                error_log("Slippy cron: Failed to prepare update for report {$report_id} - " . $conn->error);
            }
        } else {
            $failed++;
        }
        
        // Respect Nominatim rate limits (1 request per second)
        if ($processed < $result->num_rows) {
            sleep(1);
        }
    }
    
    $conn->close();
    
    // Log summary (only if there was activity)
    if ($processed > 0) {
        error_log("Slippy cron: Processed {$processed} reports, updated {$updated}, failed {$failed}");
    }
}
add_action('slippy_backfill_intersections', 'slippy_backfill_intersections_cron');

/**
 * Moderate pending comments using OpenAI Moderation API
 * This runs every 2 minutes via WordPress cron
 */
function slippy_moderate_comments_cron() {
    // Include the helper function
    $helper_path = ABSPATH . 'api/helpers/openai_moderation_helper.php';
    if (!file_exists($helper_path)) {
        // Try alternative path
        $helper_path = get_template_directory() . '/../../api/helpers/openai_moderation_helper.php';
    }
    
    if (file_exists($helper_path)) {
        require_once $helper_path;
    } else {
        error_log('Slippy moderation cron: Could not find openai_moderation_helper.php');
        return;
    }
    
    // Include database config
    $db_config_path = ABSPATH . 'config/database.php';
    if (file_exists($db_config_path)) {
        require_once $db_config_path;
    } else {
        error_log('Slippy moderation cron: Could not find database.php');
        return;
    }
    
    // Include banned words helper for fallback check
    $banned_words_path = ABSPATH . 'api/helpers/banned_words_helper.php';
    if (file_exists($banned_words_path)) {
        require_once $banned_words_path;
    } else {
        // Try alternative path
        $banned_words_path = get_stylesheet_directory() . '/../../api/helpers/banned_words_helper.php';
        if (file_exists($banned_words_path)) {
            require_once $banned_words_path;
        }
    }
    
    $table_prefix = defined('DB_TABLE_PREFIX') ? DB_TABLE_PREFIX : 'slippy_';
    
    $conn = getDBConnection();
    if (!$conn) {
        error_log('Slippy moderation cron: Database connection failed');
        return;
    }
    
    // Get up to 10 pending comments (process in small batches)
    $query = "SELECT id, comment_text FROM `{$table_prefix}comments` 
              WHERE status = 'pending' 
              ORDER BY created_at ASC 
              LIMIT 10";
    
    $result = $conn->query($query);
    if (!$result) {
        error_log('Slippy moderation cron: Query failed - ' . $conn->error);
        $conn->close();
        return;
    }
    
    $processed = 0;
    $approved = 0;
    $rejected = 0;
    $errors = 0;
    
    while ($row = $result->fetch_assoc()) {
        $processed++;
        $comment_id = intval($row['id']);
        $comment_text = $row['comment_text'];
        
        // Decode HTML entities before moderation (comment is stored HTML-encoded)
        $comment_text_for_moderation = html_entity_decode($comment_text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Check moderation using OpenAI (use decoded text)
        $moderation_result = checkOpenAIModeration($comment_text_for_moderation);
        
        if ($moderation_result['error']) {
            // API error - leave as pending for retry
            $errors++;
            error_log("Slippy moderation cron: Comment {$comment_id} moderation error - " . $moderation_result['error']);
            continue;
        }
        
        // Fallback: Check banned words if OpenAI didn't flag it
        $is_flagged = $moderation_result['flagged'];
        $flagged_reason = 'openai';
        
        if (!$is_flagged && function_exists('checkBannedWords')) {
            $banned_check = checkBannedWords($comment_text_for_moderation);
            if ($banned_check['contains_banned']) {
                $is_flagged = true;
                $flagged_reason = 'banned_words';
                error_log("Slippy moderation cron: Comment {$comment_id} flagged by banned words check. Matched: " . implode(', ', $banned_check['matched_words']));
            }
        }
        
        // Determine status based on moderation result (OpenAI or banned words)
        $new_status = $is_flagged ? 'rejected' : 'approved';
        
        // Update comment status
        $update_stmt = $conn->prepare("UPDATE `{$table_prefix}comments` SET status = ? WHERE id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("si", $new_status, $comment_id);
            if ($update_stmt->execute()) {
                if ($new_status === 'approved') {
                    $approved++;
                } else {
                    $rejected++;
                    if ($flagged_reason === 'openai') {
                        error_log("Slippy moderation cron: Comment {$comment_id} rejected by OpenAI. Categories: " . implode(', ', $moderation_result['categories']));
                    } else {
                        error_log("Slippy moderation cron: Comment {$comment_id} rejected by banned words check.");
                    }
                }
            } else {
                $errors++;
                error_log("Slippy moderation cron: Failed to update comment {$comment_id} - " . $update_stmt->error);
            }
            $update_stmt->close();
        } else {
            $errors++;
            error_log("Slippy moderation cron: Failed to prepare update for comment {$comment_id} - " . $conn->error);
        }
        
        // Small delay to avoid rate limits (OpenAI has rate limits too)
        if ($processed < $result->num_rows) {
            usleep(200000); // 0.2 seconds between requests
        }
    }
    
    $conn->close();
    
    // Log summary (only if there was activity)
    if ($processed > 0) {
        error_log("Slippy moderation cron: Processed {$processed} comments, approved {$approved}, rejected {$rejected}, errors {$errors}");
    }
}
add_action('slippy_moderate_comments', 'slippy_moderate_comments_cron');

/**
 * Manually trigger the cron (for testing)
 * Usage: wp-cron.php?do=slippy_backfill_intersections or wp-cron.php?do=slippy_moderate_comments
 */
if (isset($_GET['do']) && current_user_can('manage_options')) {
    if ($_GET['do'] === 'slippy_backfill_intersections') {
        slippy_backfill_intersections_cron();
        echo 'Intersection backfill cron executed. Check error logs for results.';
    } elseif ($_GET['do'] === 'slippy_moderate_comments') {
        slippy_moderate_comments_cron();
        echo 'Comment moderation cron executed. Check error logs for results.';
    }
    exit;
}

