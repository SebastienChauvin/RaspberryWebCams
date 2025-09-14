<?php
// Directory containing images
$dir = __DIR__;
$images = glob($dir . '/*.jpg');
sort($images); // Oldest first

// Get list of subdirectories (ignore . and ..)
$dirs = array_filter(glob($dir . '/../cam*'), 'is_dir');
sort($dirs); // Optional: sort alphabetically

// Determine current directory (from query)
$currentDir = isset($_GET['dir']) ? $_GET['dir'] : (count($dirs) ? basename(reset($dirs)) : '');
$currentPath = $dir . '/../' . $currentDir;

// Helper: extract timestamp from filename
function timestampFromFilename($filename)
{
    $base = basename($filename, '.jpg');
    return DateTime::createFromFormat('Ymd_His', $base);
}

// Determine current index
if (!isset($_GET['i']) || $_GET['i'] === 'last') {
    $currentIndex = count($images) - 1;
} else {
    $currentIndex = (int)$_GET['i'];
}
if ($currentIndex < 0) $currentIndex = 0;
if ($currentIndex >= count($images)) $currentIndex = count($images) - 1;

$currentImage = $images[$currentIndex];

// Previous / Next indices
$prevIndex = max($currentIndex - 1, 0);
$nextIndex = min($currentIndex + 1, count($images) - 1);
$lastIndex = count($images) - 1;

// Current timestamp for date/time inputs
$currentTimestamp = timestampFromFilename($currentImage)->format('Y-m-d H:i');
$datePart = explode(' ', $currentTimestamp)[0];
$timePart = explode(' ', $currentTimestamp)[1];
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

        .dir-buttons {
            margin-bottom: 20px;
        }

        .dir-buttons form {
            display: inline-block;
            margin: 0 5px;
        }

        .dir-buttons button {
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            background-color: #0984e3;
            color: #fff;
            font-weight: bold;
            cursor: pointer;
        }

        .dir-buttons button:hover {
            background-color: #0652dd;
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
    <a href="?i=<?php echo $prevIndex; ?>">
        <button>&#9664;</button>
    </a>
    <form method="get" id="selectorForm">
        <input type="date" id="dateInput" value="<?php echo $datePart; ?>" required>
        <input type="time" id="timeInput" value="<?php echo $timePart; ?>" required>
        <input type="hidden" name="i" id="hiddenIndex" value="<?php echo $currentIndex; ?>">
        <button type="submit">Afficher</button>
    </form>
    <a href="?i=last">
        <button>Maintenant</button>
    </a>
    <a href="?i=<?php echo $nextIndex; ?>">
        <button>&#9654;</button>
    </a>
</div>

<img src="<?php echo basename($currentImage); ?>" alt="Current image">

<script>
    // Combine date and time when submitting form to find closest image
    const form = document.getElementById('selectorForm');
    const dateInput = document.getElementById('dateInput');
    const timeInput = document.getElementById('timeInput');
    const hiddenIndex = document.getElementById('hiddenIndex');

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const selectedDateTime = dateInput.value + ' ' + timeInput.value;

        // Find closest image index
        const images = <?php echo json_encode(array_map('basename', $images)); ?>;
        let closestDiff = null;
        let closestIdx = <?php echo $currentIndex; ?>;

        function parseTimestamp(filename) {
            const parts = filename.replace('.jpg', '').split('_');
            const date = parts[0], time = parts[1];
            return new Date(date.substr(0, 4) + '-' + date.substr(4, 2) + '-' + date.substr(6, 2) + 'T' + time.substr(0, 2) + ':' + time.substr(2, 2) + ':' + time.substr(4, 2));
        }

        const targetTime = new Date(selectedDateTime + ':00');

        images.forEach((img, idx) => {
            const imgTime = parseTimestamp(img);
            const diff = Math.abs(imgTime - targetTime);
            if (closestDiff === null || diff < closestDiff) {
                closestDiff = diff;
                closestIdx = idx;
            }
        });

        window.location.href = '?i=' + closestIdx;
    });
</script>

</body>
</html>
