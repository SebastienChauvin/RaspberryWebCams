<?php
// Remove old images based on retention policy
// Usage: set up as a cron job see https://www.ovh.com/manager/#/web/hosting/cvvmc.com/cron
// This script will delete old images based on the following rules:
// - Keep all images from the last 24 hours
// - From 1 to 7 days old, keep one image every 5 minutes
// - From 7 to 30 days old, keep one image every hour
// - Older than 30 days, keep one image per day (the one closest to noon)

// Enable implicit flushing to echo output progressively
ob_implicit_flush();
echo "<pre>\n";

// Base directory
$baseDir = __DIR__;

// Get all subdirectories
$dirs = array_filter(glob($baseDir . '/*'), 'is_dir');
sort($dirs);

if (empty($dirs)) {
    echo "No directories found in $baseDir\n";
    exit;
}
echo "Found " . count($dirs) . " directories.\n";
if (isset($_GET['index'])) {
    $dirs = [array_values($dirs)[intval($_GET['index'])]];
} else if (isset($_GET['all'])) {
    // keep all directories
} else {
    echo "Use ?all=1 to process all directories or ?index=N to process a specific one.\n";
    exit;
}

// Helper: extract timestamp
function dateForFilename($dir, $filename)
{
    $base = basename($filename, '.jpg');
    $d = basename($dir);
    return DateTime::createFromFormat('Ymd_His', $d . "_" . $base);
}

// Retention thresholds
$now = time();
$keep24h = $now - 24 * 3600;
$keep7d = $now - 7 * 24 * 3600;
$keep30d = $now - 30 * 24 * 3600;

echo "Starting cleanup at " . date('Y-m-d H:i:s', $now) . " for " . count($dirs) . " directories \n";
foreach ($dirs as $dir) {
    echo "Cleaning directory: $dir\n";
    $lastDate = dateForFilename($dir, "000000.jpg");
    $lastTime = $lastDate->getTimestamp();
    if ($lastTime > $keep24h) {
        echo "Keeping everything\n";
        continue;
    }
    if ($lastTime < $keep30d) {
        echo "Keeping one image per day.\n";
    } elseif ($lastTime < $keep7d) {
        echo "Keeping one image per hour.\n";
    } else {
        echo "Keeping one image every 5 minutes.\n";
    }
    $images = glob($dir . '/*.jpg');
    sort($images);

    $toDelete = [];
    $kept = [];

    $lastKept = [
        '5min' => 0,
        'hour' => 0,
        'day' => []
    ];

    echo "Found " . count($images) . " images\n";
    foreach ($images as $img) {
        $ts = dateForFilename($dir, $img);
        if (!$ts) {
            $skipped[] = $img;
            continue;
        }

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
            $existing = dateForFilename($dir, $lastKept['day'][$dayKey]);
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

    if (isset($skipped)) {
        echo "Skipped " . count($skipped)
            . " files due to naming issues : " . print_r(array_slice($skipped, 0, 3), true)
            . "\n";
        $base = basename($skipped[0], '.jpg');
        $d = basename($dir);
        echo "Example of problematic filename timestamp extraction: ";
        echo $d . "_" . $base;
        echo DateTime::createFromFormat('Ymd_His', $d . "_" . $base);
        echo "\n";
    }
    echo "Kept " . count($kept) . " images, deleted " . count($toDelete) . "\n\n";
}
echo "Cleanup completed at " . date('Y-m-d H:i:s') . "\n";
?>
