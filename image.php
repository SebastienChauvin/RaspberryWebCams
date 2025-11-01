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

function dateForFilename($filename)
{
    // Accepts HHMMSS or YYYYMMDDHHMMSS
    if (preg_match('/^([0-9]{6})$/', $filename, $m)) {
        return DateTime::createFromFormat('His', $m[1]);
    } elseif (preg_match('/^([0-9]{8})([0-9]{6})$/', $filename, $m)) {
        return DateTime::createFromFormat('YmdHis', $m[1] . $m[2]);
    }
    return false;
}

$closestIndex = -1;
$closestDiff = PHP_INT_MAX;
$targetTime = dateForFilename($time);
$currentIndex = 0;

foreach ($images as $index => $img) {
    $ts = dateForFilename($img);
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
    $nextDay = strtotime($date . ' +1 day');
    if ($nextDay > time()) {
        $nextDate = date('Ymd');
        $nextImage = $nextDate . "235959";
    } else {
        $nextDate = date('Ymd', $nextDay);
        $nextImage = $nextDate . "000000";
    }
}

$now = new DateTime();
$currentDT = DateTime::createFromFormat('YmdHis', $date . $currentTime);
$prevHourDT = clone $currentDT;
$prevHourDT->modify('-1 hour');
$nextHourDT = clone $currentDT;
$nextHourDT->modify('+1 hour');
$nextHourImage = $nextHourDT <= $now ? $nextHourDT->format('YmdHis') : null;
$prevHourImage = $prevHourDT->format('YmdHis');

// Prev/Next Day
$prevDayDT = clone $currentDT;
$prevDayDT->modify('-1 day');
$nextDayDT = clone $currentDT;
$nextDayDT->modify('+1 day');
$nextDayImage = $nextDayDT <= $now ? $nextDayDT->format('YmdHis') : null;
$prevDayImage = $prevDayDT->format('YmdHis');

// Prev/Next 5 minutes
$prev5MinDT = clone $currentDT;
$prev5MinDT->modify('-5 minutes');
$next5MinDT = clone $currentDT;
$next5MinDT->modify('+5 minutes');
$next5MinImage = $next5MinDT <= $now ? $next5MinDT->format('YmdHis') : null;
$prev5MinImage = $prev5MinDT->format('YmdHis');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Camera History Navigator</title>
    <style>
        body {
            color: #fff;
            font-family: 'Segoe UI', sans-serif;
            text-align: center;
        }

        img {
            max-width: 100%;
            max-height: 100vh;
            border: 2px solid #444;
            border-radius: 8px;
            display: block;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
        }

        form {
            display: inline;
            margin-bottom: 20px;
        }

        input[type="date"], input[type="time"] {
            border: none;
            padding-right: 8px;
            font-size: 14px;
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

        .image-container {
            position: relative;
            display: inline-block;
        }

        .image-container .nav-buttons {
            position: absolute;
            top: 10px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
            z-index: 2;
        }

        .image-container:hover .nav-buttons {
            opacity: 1;
            pointer-events: auto;
        }

        .image-container img {
            max-width: 100%;
            max-height: 100vh;
            border: 2px solid #444;
            border-radius: 8px;
            display: block;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
        }

        label {
            background: #fff;
            color: #000;
            border-radius: 5px;
            padding: 8px 12px;
            margin: 5px;
            font-size: 16px;
            display: inline-block;
        }

        label input[type="checkbox"] {
            margin-right: 6px;
        }

        .nav-buttons.nav-buttons-secondary {
            top: 60px;
            left: 0;
            right: 0;
            position: absolute;
            display: flex;
            justify-content: space-between;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
            z-index: 2;
        }

        .image-container:hover .nav-buttons.nav-buttons-secondary {
            opacity: 1;
            pointer-events: auto;
        }

        @media (hover: none) and (pointer: coarse) {
            .image-container .nav-buttons.nav-buttons-secondary {
                opacity: 0;
                pointer-events: none;
            }

            .image-container .nav-buttons.nav-buttons-secondary.show {
                opacity: 1 !important;
                pointer-events: auto !important;
            }
        }
    </style>
</head>
<body>

<div class="image-container">
    <div class="nav-toggle" id="navToggle"
         style="position: absolute; top: 10px; right: 10px; z-index: 3; display: none;">
        <button id="toggleNavBtn"
                style="padding: 8px 14px; font-size: 20px; background: #fff; color: #000; border-radius: 50%; border: 2px solid #444;">
            ☰
        </button>
    </div>
    <div class="nav-buttons" id="navButtons">
        <div style="flex:1; display:flex; justify-content:flex-start;">
            <a href="?d=<?php echo $prevImage; ?>">
                <button>&#9664;</button>
            </a>
        </div>
        <div style="display:flex; justify-content:center;">
            <form method="get" id="selectorForm" style="display: inline;">
                <label for="dateInput">
                    <input type="date" id="dateInput" value="<?php
                    echo substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
                    ?>" required>
                </label>
                <label for="timeInput">
                    <input type="time" id="timeInput"
                           value="<?php echo substr($currentTime, 0, 2) . ':' . substr($currentTime, 2, 2) . ':' . substr($currentTime, 4, 2);
                           ?>" required>
                </label>
                <button type="submit">Afficher</button>
                <label>
                    <input type="checkbox" id="autorefreshBox" <?php echo $autorefresh ? 'checked' : ''; ?>> En direct
                </label>
            </form>
        </div>
        <div style="flex:1; display:flex; justify-content:flex-end;">
            <?php if ($currentIndex < count($images) - 1): ?>
                <a href="?d=<?php echo $nextImage; ?>">
                    <button>&#9654;</button>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="nav-buttons nav-buttons-secondary" style="top: 100px;">
        <?php // Prev Day button leftmost ?>
        <div style="flex:1; display:flex; justify-content:flex-start;">
            <a href="?d=<?php echo $prevDayImage; ?>">
                <button title="<?php echo date('d/m/Y', strtotime(substr($prevDayImage, 0, 8))); ?>">
                   <&nbsp;Jour
                </button>
            </a>
            <a href="?d=<?php echo $prevHourImage; ?>">
                <button title="<?php echo date('H:i:s', strtotime(substr($prevHourImage, -6))); ?>">
                    <&nbsp;Heure
                </button>
            </a>
            <a href="?d=<?php echo $prev5MinImage; ?>">
                <button title="<?php echo date('H:i:s', strtotime(substr($prev5MinImage, -6))); ?>">
                    <&nbsp;5&nbsp;min
                </button>
        </div>
        <div style="flex:1; display:flex; justify-content:flex-end;">
            <?php if ($next5MinImage): ?>
                <a href="?d=<?php echo $next5MinImage; ?>">
                    <button title="<?php echo date('H:i:s', strtotime(substr($next5MinImage, -6))); ?>">
                        >&nbsp;5&nbsp;min
                    </button>
                </a>
            <?php endif; ?>
            <?php if ($nextHourImage): ?>
                <a href="?d=<?php echo $nextHourImage; ?>">
                    <button title="<?php echo date('H:i:s', strtotime(substr($nextHourImage, -6))); ?>">
                        >&nbsp;Heure
                    </button>
                </a>
            <?php endif; ?>
            <?php if ($nextDayImage): ?>
                <a href="?d=<?php echo $nextDayImage; ?>">
                    <button title="<?php echo date('d/m/Y', strtotime(substr($nextDayImage, 0, 8))); ?>">
                        >&nbsp;Jour
                    </button>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    if (!$currentTime) {
        echo "<p style='color: red;'>Pas d'image à cette date</p>";
    } else {
        echo "<img src='$currentImage.jpg' alt='Webcam La Motte du Caire' style='display: block;'>";
    }
    ?>
</div>


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

    // Cancel autorefresh timeout when clicking any nav or form button
    let autorefreshTimeoutId = null;

    function startAutorefreshIfSet() {
        if (!autorefreshTimeoutId) {
            <?php if($autorefresh): ?>
            autorefreshTimeoutId = setTimeout(refresh, 20000);
            <?php endif; ?>
        }
    }

    startAutorefreshIfSet();

    function cancelAutorefreshTimeout() {
        if (autorefreshTimeoutId) {
            clearTimeout(autorefreshTimeoutId);
            autorefreshTimeoutId = null;
        }
    }

    // Attach to all nav and form buttons
    document.addEventListener('DOMContentLoaded', function () {
        // Cancel autorefresh on any nav or form button click
        document.querySelectorAll('.nav-buttons button, .nav-buttons-secondary button, #selectorForm button').forEach(btn => {
            btn.addEventListener('click', cancelAutorefreshTimeout, {capture: true});
        });
        // Cancel autorefresh on hover/touch over image or navs
        const img = document.querySelector('.image-container img');
        const navs = [
            document.getElementById('navButtons'),
            document.querySelector('.nav-buttons-secondary')
        ];
        [img, ...navs].forEach(el => {
            if (!el) return;
            el.addEventListener('mouseenter', cancelAutorefreshTimeout);
            el.addEventListener('mouseout', startAutorefreshIfSet);
        });
    });
</script>

<script>
    // Mobile nav toggle logic
    function isMobile() {
        return window.matchMedia('(hover: none) and (pointer: coarse)').matches;
    }

    if (isMobile()) {
        const navButtons = document.getElementById('navButtons');
        const navButtonsSecondary = document.querySelector('.nav-buttons-secondary');
        const navToggle = document.getElementById('navToggle');
        const toggleNavBtn = document.getElementById('toggleNavBtn');
        navButtons.classList.remove('show');
        if (navButtonsSecondary) navButtonsSecondary.classList.remove('show');
        navToggle.style.display = 'block';
        toggleNavBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            cancelAutorefreshTimeout();
            navButtons.classList.toggle('show');
            if (navButtonsSecondary) navButtonsSecondary.classList.toggle('show');
        });
        // Hide nav on click outside
        document.addEventListener('click', function (e) {
            if (navButtons.classList.contains('show')) {
                navButtons.classList.remove('show');
            }
            if (navButtonsSecondary && navButtonsSecondary.classList.contains('show')) {
                navButtonsSecondary.classList.remove('show');
            }
            startAutorefreshIfSet();
        });
        // Prevent nav from closing when clicking inside
        navButtons.addEventListener('click', function (e) {
            e.stopPropagation();
        });
        if (navButtonsSecondary) navButtonsSecondary.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }
</script>
</body>
</html>
