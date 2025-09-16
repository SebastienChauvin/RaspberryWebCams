<?php
// Remove old images based on retention policy
// Usage: set up as a cron job see https://www.ovh.com/manager/#/web/hosting/cvvmc.com/cron
// This script will delete old images based on the following rules:
// - Keep all images from the last 24 hours
// - From 1 to 7 days old, keep one image every 5 minutes
// - From 7 to 30 days old, keep one image every hour
// - Older than 30 days, keep one image per day (the one closest to noon)

// Base directory
$baseDir = __DIR__;

// Get all subdirectories
$dirs = array_filter(glob($baseDir . '/*'), 'is_dir');
sort($dirs);

// Helper: extract timestamp
function timestampFromFilename($filename) {
    $base = basename($filename, '.jpg');
    return DateTime::createFromFormat('Ymd_His', $base);
}

// Retention thresholds
$now = time();
$keep24h   = $now - 24 * 3600;
$keep7d    = $now - 7  * 24 * 3600;
$keep30d   = $now - 30 * 24 * 3600;

foreach ($dirs as $dir) {
    echo "Cleaning directory: $dir\n";
    $images = glob($dir . '/*.jpg');
    sort($images);

    $toDelete = [];
    $kept = [];

    $lastKept = [
        '5min' => 0,
        'hour' => 0,
        'day'  => []
    ];

    foreach ($images as $img) {
        $ts = timestampFromFilename($img);
        if (!$ts) continue;

        $t = $ts->getTimestamp();

        // Rule 1: last 24h, keep everything
        if ($t >= $keep24h) {
            $kept[] = $img;
            continue;
        }

        // Rule 2: last 7 days, keep 1 every 5 min
        if ($t >= $keep7d) {
            $slot = floor($t / 300); // 5 min slots
            if ($slot !== $lastKept['5min']) {
                $lastKept['5min'] = $slot;
                $kept[] = $img;
            } else {
                $toDelete[] = $img;
            }
            continue;
        }

        // Rule 3: last 30 days, keep 1 every hour
        if ($t >= $keep30d) {
            $slot = floor($t / 3600); // hourly slots
            if ($slot !== $lastKept['hour']) {
                $lastKept['hour'] = $slot;
                $kept[] = $img;
            } else {
                $toDelete[] = $img;
            }
            continue;
        }

        // Rule 4: older than 30 days, keep one at noon per day
        $dayKey = $ts->format('Y-m-d');
        if (!isset($lastKept['day'][$dayKey])) {
            $lastKept['day'][$dayKey] = $img;
            $kept[] = $img;
        } else {
            // Prefer the one closest to 12:00
            $existing = timestampFromFilename($lastKept['day'][$dayKey]);
            $target = strtotime($dayKey . ' 12:00:00');
            $diffExisting = abs($existing->getTimestamp() - $target);
            $diffNew = abs($t - $target);

            if ($diffNew < $diffExisting) {
                // Replace kept with better noon candidate
                $toDelete[] = $lastKept['day'][$dayKey];
                $lastKept['day'][$dayKey] = $img;
                $kept[] = $img;
            } else {
                $toDelete[] = $img;
            }
        }
    }

    // Delete old files
    foreach ($toDelete as $f) {
        if (unlink($f)) {
            echo "Deleted: $f\n";
        } else {
            echo "Failed to delete: $f\n";
        }
    }

    echo "Kept " . count($kept) . " images, deleted " . count($toDelete) . "\n\n";
}
?>
