<?php
/**
 * Cron Testing and Debugging Tool
 * 
 * This file helps you test and debug the Slippy cron jobs in development.
 * Access it at: http://your-site.com/api/test_cron.php
 * 
 * For manual execution: http://your-site.com/api/test_cron.php?run=intersections
 * or: http://your-site.com/api/test_cron.php?run=comments
 */

// Load WordPress
require_once __DIR__ . '/../wp-load.php';

// Security check - allow admins OR dev mode
// For dev: add ?dev=1 to bypass admin check, or if WP_DEBUG is enabled
$is_dev = (isset($_GET['dev']) && $_GET['dev'] === '1') || (defined('WP_DEBUG') && WP_DEBUG);
$is_admin = function_exists('current_user_can') && current_user_can('manage_options');

if (!$is_dev && !$is_admin) {
    die('Access denied. Admin privileges required.<br><br>For development, add <code>?dev=1</code> to the URL, or enable WP_DEBUG in wp-config.php');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Slippy Cron Debug Tool</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        h2 { color: #666; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
        .warning { color: #ffc107; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        button { padding: 10px 20px; margin: 5px; background: #007cba; color: white; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background: #005a87; }
        .status-good { color: #28a745; }
        .status-bad { color: #dc3545; }
    </style>
</head>
<body>
    <h1>🔧 Slippy Cron Debug Tool</h1>

<?php
// Check if we should run a cron job manually
if (isset($_GET['run'])) {
    echo '<div class="section">';
    echo '<h2>Manual Cron Execution</h2>';
    
    require_once get_stylesheet_directory() . '/slippy-cron.php';
    
    if ($_GET['run'] === 'intersections') {
        echo '<p class="info">Running intersection backfill cron...</p>';
        echo '<pre>';
        ob_start();
        slippy_backfill_intersections_cron();
        $output = ob_get_clean();
        echo $output;
        echo '</pre>';
        echo '<p class="success">✓ Intersection backfill cron executed. Check error logs for details.</p>';
    } elseif ($_GET['run'] === 'comments') {
        echo '<p class="info">Running comment moderation cron...</p>';
        echo '<pre>';
        ob_start();
        slippy_moderate_comments_cron();
        $output = ob_get_clean();
        echo $output;
        echo '</pre>';
        echo '<p class="success">✓ Comment moderation cron executed. Check error logs for details.</p>';
    } else {
        echo '<p class="error">Invalid cron job specified. Use "intersections" or "comments".</p>';
    }
    echo '</div>';
}
?>

    <div class="section">
        <h2>Quick Actions</h2>
        <p>
            <button onclick="window.location.href='?run=intersections'">Run Intersection Backfill</button>
            <button onclick="window.location.href='?run=comments'">Run Comment Moderation</button>
            <button onclick="window.location.reload()">Refresh Status</button>
        </p>
    </div>

    <div class="section">
        <h2>Cron Schedule Status</h2>
        <?php
        $intersection_next = wp_next_scheduled('slippy_backfill_intersections');
        $comments_next = wp_next_scheduled('slippy_moderate_comments');
        
        if ($intersection_next) {
            $time_until = $intersection_next - time();
            $status_class = $time_until > 0 ? 'status-good' : 'status-bad';
            echo '<p><strong>Intersection Backfill:</strong> ';
            echo '<span class="' . $status_class . '">';
            echo 'Scheduled - Next run: ' . date('Y-m-d H:i:s', $intersection_next);
            echo ' (' . ($time_until > 0 ? 'in ' . round($time_until / 60, 1) . ' minutes' : 'OVERDUE') . ')';
            echo '</span></p>';
        } else {
            echo '<p><strong>Intersection Backfill:</strong> <span class="status-bad">NOT SCHEDULED</span></p>';
        }
        
        if ($comments_next) {
            $time_until = $comments_next - time();
            $status_class = $time_until > 0 ? 'status-good' : 'status-bad';
            echo '<p><strong>Comment Moderation:</strong> ';
            echo '<span class="' . $status_class . '">';
            echo 'Scheduled - Next run: ' . date('Y-m-d H:i:s', $comments_next);
            echo ' (' . ($time_until > 0 ? 'in ' . round($time_until / 60, 1) . ' minutes' : 'OVERDUE') . ')';
            echo '</span></p>';
        } else {
            echo '<p><strong>Comment Moderation:</strong> <span class="status-bad">NOT SCHEDULED</span></p>';
        }
        ?>
        
        <p>
            <button onclick="window.location.href='?reschedule=1'">Reschedule Cron Jobs</button>
        </p>
        <p class="info">If cron jobs are not scheduled, click the button above. The cron file must be loaded for scheduling to work.</p>
    </div>

    <?php
    // Reschedule if requested
    if (isset($_GET['reschedule'])) {
        echo '<div class="section">';
        echo '<h2>Rescheduling Cron Jobs</h2>';
        
        // Make sure cron file is loaded to register schedules
        $cron_file = get_stylesheet_directory() . '/slippy-cron.php';
        if (file_exists($cron_file)) {
            require_once $cron_file;
            echo '<p class="info">Loaded cron file: ' . $cron_file . '</p>';
        } else {
            echo '<p class="error">Cron file not found: ' . $cron_file . '</p>';
        }
        
        // Clear existing schedules
        $timestamp1 = wp_next_scheduled('slippy_backfill_intersections');
        if ($timestamp1) {
            wp_unschedule_event($timestamp1, 'slippy_backfill_intersections');
            echo '<p>Cleared existing intersection backfill schedule.</p>';
        }
        $timestamp2 = wp_next_scheduled('slippy_moderate_comments');
        if ($timestamp2) {
            wp_unschedule_event($timestamp2, 'slippy_moderate_comments');
            echo '<p>Cleared existing comment moderation schedule.</p>';
        }
        
        // Reschedule
        $result1 = wp_schedule_event(time(), 'slippy_every_5min', 'slippy_backfill_intersections');
        $result2 = wp_schedule_event(time(), 'slippy_every_2min', 'slippy_moderate_comments');
        
        if ($result1 !== false && $result2 !== false) {
            echo '<p class="success">✓ Cron jobs rescheduled successfully!</p>';
            echo '<p>Intersection backfill: Every 5 minutes</p>';
            echo '<p>Comment moderation: Every 2 minutes</p>';
        } else {
            echo '<p class="error">✗ Failed to schedule cron jobs.</p>';
            if ($result1 === false) {
                echo '<p class="error">Failed to schedule intersection backfill. Error: ' . (is_wp_error($result1) ? $result1->get_error_message() : 'Unknown') . '</p>';
            }
            if ($result2 === false) {
                echo '<p class="error">Failed to schedule comment moderation. Error: ' . (is_wp_error($result2) ? $result2->get_error_message() : 'Unknown') . '</p>';
            }
        }
        
        echo '<p><a href="?">Refresh page to see new schedule</a></p>';
        echo '</div>';
    }
    ?>

    <div class="section">
        <h2>All Scheduled Cron Events</h2>
        <?php
        $crons = get_option('cron');
        if ($crons) {
            echo '<pre>';
            foreach ($crons as $timestamp => $cronhooks) {
                if (!is_array($cronhooks)) continue;
                foreach ($cronhooks as $hook => $keys) {
                    if (strpos($hook, 'slippy_') === 0) {
                        echo date('Y-m-d H:i:s', $timestamp) . ' - ' . $hook . "\n";
                    }
                }
            }
            echo '</pre>';
        } else {
            echo '<p class="warning">No cron events found.</p>';
        }
        ?>
    </div>

    <div class="section">
        <h2>File Paths Check</h2>
        <?php
        $paths_to_check = [
            'Intersection Helper' => [
                get_stylesheet_directory() . '/../../api/helpers/intersection_helper.php',
                ABSPATH . 'api/helpers/intersection_helper.php'
            ],
            'Moderation Helper' => [
                ABSPATH . 'api/helpers/openai_moderation_helper.php',
                get_stylesheet_directory() . '/../../api/helpers/openai_moderation_helper.php'
            ],
            'Database Config' => [
                ABSPATH . 'config/database.php'
            ]
        ];
        
        foreach ($paths_to_check as $name => $paths) {
            echo '<p><strong>' . $name . ':</strong> ';
            $found = false;
            foreach ($paths as $path) {
                if (file_exists($path)) {
                    echo '<span class="status-good">✓ Found: ' . $path . '</span>';
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                echo '<span class="status-bad">✗ Not found in any of these paths:</span><br>';
                foreach ($paths as $path) {
                    echo '&nbsp;&nbsp;- ' . $path . '<br>';
                }
            }
            echo '</p>';
        }
        ?>
    </div>

    <div class="section">
        <h2>WordPress Cron Status</h2>
        <?php
        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            echo '<p class="error">⚠️ WP_CRON is DISABLED (DISABLE_WP_CRON is true)</p>';
            echo '<p>WordPress cron will not run automatically. You need to set up a server cron job to call wp-cron.php</p>';
        } else {
            echo '<p class="success">✓ WP_CRON is ENABLED</p>';
            echo '<p>WordPress cron runs when someone visits your site. In development, you may need to visit the site regularly or trigger it manually.</p>';
        }
        ?>
        <p><strong>To manually trigger WordPress cron:</strong></p>
        <pre>curl <?php echo home_url('/wp-cron.php'); ?></pre>
        <p>Or visit: <a href="<?php echo home_url('/wp-cron.php'); ?>" target="_blank"><?php echo home_url('/wp-cron.php'); ?></a></p>
    </div>

    <div class="section">
        <h2>Database Status Check</h2>
        <?php
        require_once ABSPATH . 'config/database.php';
        $conn = getDBConnection();
        if ($conn) {
            echo '<p class="success">✓ Database connection successful</p>';
            
            $table_prefix = defined('DB_TABLE_PREFIX') ? DB_TABLE_PREFIX : 'slippy_';
            
            // Check for pending comments
            $pending_query = "SELECT COUNT(*) as count FROM `{$table_prefix}comments` WHERE status = 'pending'";
            $result = $conn->query($pending_query);
            if ($result) {
                $row = $result->fetch_assoc();
                $pending_count = intval($row['count']);
                echo '<p><strong>Pending Comments:</strong> ' . $pending_count . '</p>';
            }
            
            // Check for reports without intersection
            $no_intersection_query = "SELECT COUNT(*) as count FROM `{$table_prefix}reports` WHERE intersection IS NULL";
            $result = $conn->query($no_intersection_query);
            if ($result) {
                $row = $result->fetch_assoc();
                $no_intersection_count = intval($row['count']);
                echo '<p><strong>Reports without intersection:</strong> ' . $no_intersection_count . '</p>';
            }
            
            $conn->close();
        } else {
            echo '<p class="error">✗ Database connection failed</p>';
        }
        ?>
    </div>

    <div class="section">
        <h2>Error Log Location</h2>
        <p>Check your WordPress error log for cron execution details:</p>
        <pre><?php echo ini_get('error_log') ?: 'Check wp-config.php or php.ini for error_log location'; ?></pre>
        <p>Look for log entries starting with "Slippy cron:" or "Slippy moderation cron:"</p>
    </div>

</body>
</html>

