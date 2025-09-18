<?php
$datetime = $_GET['d'] ?? date('YmdHis');
$autorefresh = !isset($_GET['autorefresh']) && !isset($_GET['d']) || $_GET['autorefresh'] === '1';

if (!preg_match('/^20\d{12}$/', $datetime)) {
    http_response_code(400);
    echo "Error: Invalid date format " . $datetime;
    exit;
}
$date = substr($datetime, 0, 8); // YYYYMMDD
$hour = substr($datetime, 8, 2); // HH
$minute = substr($datetime, 10, 2); // MM
$second = substr($datetime, 12, 2); // SS
$time = substr($datetime, 8, 6); // HHMMSS

// Directory containing images
$dir = __DIR__;
//$images = glob($dir . "/$date/$hour$minute??.jpg");
//if (!$images) {
    $images = glob($dir . "/$date/*.jpg");
    if (!$images) {
        http_response_code(404);
        echo "Error: No images found.";
        exit;
    }
//}
$images = array_map(function ($img) {
    return basename($img, '.jpg');
}, $images);
sort($images); // Oldest first

//echo 'Found ' . count($images) . " images for date $date in $dir\n";
function timestampFromFilename($n)
{
    return DateTime::createFromFormat('His', $n);
}

$closestIndex = -1;
$closestDiff = PHP_INT_MAX;
$targetTime = timestampFromFilename($time);
$currentIndex = 0;

foreach ($images as $index => $img) {
    $ts = timestampFromFilename($img);
    if (!$ts) continue;

    $diff = abs($ts->getTimestamp() - $targetTime->getTimestamp());
    //echo "$index : " . $img . " _  " . $ts->getTimestamp() . " - " . $targetTime->getTimestamp() . " = $diff -> $currentIndex\n";
    if ($diff < $closestDiff) {
        $closestDiff = $diff;
        $currentIndex = $index;
    }
}

$currentTime = $images[$currentIndex];
$currentImage = "$date" . "/" . $currentTime;
//echo "Current image: $currentImage (index $currentIndex)\n";

// Previous / Next indices
if ($currentIndex > 0) {
    $prevImage = "$date" . $images[$currentIndex - 1];
} else {
    $prevDate = date('Ymd', strtotime($date . ' -1 day'));
    $prevImage = $prevDate . "235900";
}

if ($currentIndex < count($images) - 1)
    $nextImage = "$date" . $images[$currentIndex + 1];
else {
    $nextDate = date('Ymd', strtotime($date . ' +1 day'));
    if ($nextDate > date('Ymd')) {
        $nextDate = date('Ymd');
        $nextImage = "235959";
    } else {
        $nextImage = $nextDate . "000000";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Camera History Navigator</title>
    <style>
        body {
            background: #1e1e1e;
            color: #fff;
            font-family: 'Segoe UI', sans-serif;
            text-align: center;
            padding: 20px;
        }

        h1 {
            margin-bottom: 30px;
        }

        img {
            max-width: 90%;
            max-height: 70vh;
            border: 2px solid #444;
            border-radius: 8px;
            margin: 20px auto;
            display: block;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
        }

        form {
            display: inline;
            margin-bottom: 20px;
        }

        input[type="date"], input[type="time"] {
            padding: 10px 15px;
            margin: 5px;
            border-radius: 5px;
            border: none;
            font-size: 16px;
        }

        button {
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            background-color: #00b894;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            margin: 5px;
        }

        button:hover {
            background-color: #019874;
        }

        p {
            margin-top: 10px;
            color: #aaa;
        }

        .nav-buttons {
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="nav-buttons">
    <a href="?d=<?php echo $prevImage; ?>">
        <button>&#9664;</button>
    </a>
    <form method="get" id="selectorForm">
        <input type="date" id="dateInput" value="<?php
        echo substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
        ?>" required>
        <input type="time" id="timeInput"
               value="<?php echo substr($currentTime, 0, 2) . ':' . substr($currentTime, 2, 2) . ':' . substr($currentTime, 4, 2);
               ?>" required>
        <button type="submit">Afficher</button>
        <label>
            <input type="checkbox" id="autorefreshBox" <?php echo $autorefresh ? 'checked' : ''; ?>> En direct
        </label>
    </form>
    <a href="?d=<?php echo $nextImage; ?>">
        <button>&#9654;</button>
    </a>
</div>

<img src="<?php echo "$currentImage" ?>.jpg" alt="Current image">

<script>
    const form = document.getElementById('selectorForm');
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const dateInput = document.getElementById('dateInput');
        const timeInput = document.getElementById('timeInput');
        window.location.replace('?d=' + dateInput.value.replace(/-/g, '') + timeInput.value.replace(/:/g, ''));
    });
</script>

<script>
    // Handle auto-refresh checkbox
    const box = document.getElementById('autorefreshBox');

    const refresh = () => {
        const params = new URLSearchParams(window.location.search);
        params.set('autorefresh', box.checked ? '1' : '0');
        params.delete('d')
        if (box.checked) {
            window.location.replace('?' + params.toString());
        } else {
            window.location.search = params.toString();
        }
    }

    if (box) {
        box.addEventListener('change', refresh);
    }

    // Auto-refresh every 10s if enabled
    <?php if($autorefresh): ?>
    setTimeout(refresh, 20000);
    <?php endif; ?>
</script>
</body>
</html>
