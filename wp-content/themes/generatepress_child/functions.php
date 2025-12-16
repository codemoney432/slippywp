<?php
/**
 * GeneratePress Child Theme Functions
 * 
 * Add your custom functions here
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Include Slippy Cron Jobs
 * This ensures cron schedules are registered early
 */
require_once get_stylesheet_directory() . '/slippy-cron.php';

/**
 * Reschedule Slippy Cron Jobs
 * 
 * Call this function to manually reschedule the cron jobs.
 * Useful for fixing scheduling issues or after theme updates.
 * 
 * Usage:
 * - Add to admin action: add_action('admin_init', 'slippy_reschedule_cron_jobs');
 * - Call directly: slippy_reschedule_cron_jobs();
 * - Via URL: ?slippy_reschedule_cron=1 (admin only)
 */
function slippy_reschedule_cron_jobs() {
    // Ensure schedules are registered
    $schedules = wp_get_schedules();
    
    if (!isset($schedules['slippy_every_5min'])) {
        error_log('Slippy: slippy_every_5min schedule not registered');
        return false;
    }
    if (!isset($schedules['slippy_every_2min'])) {
        error_log('Slippy: slippy_every_2min schedule not registered');
        return false;
    }
    
    // Unschedule existing events
    $timestamp1 = wp_next_scheduled('slippy_backfill_intersections');
    if ($timestamp1) {
        wp_unschedule_event($timestamp1, 'slippy_backfill_intersections');
    }
    
    $timestamp2 = wp_next_scheduled('slippy_moderate_comments');
    if ($timestamp2) {
        wp_unschedule_event($timestamp2, 'slippy_moderate_comments');
    }
    
    // Reschedule with correct schedules
    $result1 = wp_schedule_event(time(), 'slippy_every_5min', 'slippy_backfill_intersections');
    $result2 = wp_schedule_event(time(), 'slippy_every_2min', 'slippy_moderate_comments');
    
    if ($result1 === false || $result2 === false) {
        error_log('Slippy: Failed to reschedule cron jobs. Result1: ' . ($result1 === false ? 'failed' : 'success') . ', Result2: ' . ($result2 === false ? 'failed' : 'success'));
        return false;
    }
    
    error_log('Slippy: Cron jobs rescheduled successfully');
    return true;
}

/**
 * Auto-reschedule cron jobs on theme activation
 */
add_action('after_switch_theme', 'slippy_reschedule_cron_jobs');

/**
 * Ensure cron jobs are scheduled when wp-cron runs
 * This hook fires on every page load, including wp-cron.php
 * Priority 5 ensures it runs early, before WordPress processes cron events
 */
add_action('wp_loaded', 'slippy_ensure_cron_jobs_scheduled', 5);

/**
 * Ensure cron jobs are scheduled - runs on every page load including wp-cron
 * This is safer than relying on admin_menu which only runs in admin area
 */
function slippy_ensure_cron_jobs_scheduled() {
    // Only check/schedule if not already scheduled
    // This prevents unnecessary work on every page load
    $needs_scheduling = false;
    
    if (!wp_next_scheduled('slippy_backfill_intersections')) {
        $needs_scheduling = true;
    }
    if (!wp_next_scheduled('slippy_moderate_comments')) {
        $needs_scheduling = true;
    }
    
    if ($needs_scheduling) {
        // Use the existing scheduling function from slippy-cron.php
        if (function_exists('slippy_schedule_cron_jobs')) {
            slippy_schedule_cron_jobs();
        }
    }
}

/**
 * Allow manual reschedule via URL parameter (admin only)
 * Usage: ?slippy_reschedule_cron=1
 */
if (isset($_GET['slippy_reschedule_cron']) && current_user_can('manage_options')) {
    add_action('admin_init', function() {
        if (slippy_reschedule_cron_jobs()) {
            wp_die('Cron jobs rescheduled successfully!', 'Success', array('back_link' => true));
        } else {
            wp_die('Failed to reschedule cron jobs. Check error logs.', 'Error', array('back_link' => true));
        }
    });
}

/**
 * Optional: Add admin notice after rescheduling
 */
function slippy_cron_reschedule_notice() {
    if (isset($_GET['slippy_cron_rescheduled']) && current_user_can('manage_options')) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>Slippy cron jobs have been rescheduled successfully!</p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'slippy_cron_reschedule_notice');

