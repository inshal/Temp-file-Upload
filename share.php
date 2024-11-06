<?php
if (isset($_GET['id'])) {
    $shortLink = $_GET['id'];
    $linkFile = "links/{$shortLink}.json";
    
    if (file_exists($linkFile)) {
        $linkData = json_decode(file_get_contents($linkFile), true);
        
        if (time() < $linkData['expiry']) {
            $file = $linkData['filename'];
            $originalFilename = $linkData['originalFilename'];
            if (file_exists($file)) {
                $fileType = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $isImage = in_array($fileType, ['jpg', 'jpeg', 'png', 'gif']);

                if (isset($_GET['download'])) {
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . $originalFilename . '"');
                    header('Content-Length: ' . filesize($file));
                    readfile($file);
                    exit;
                }
            } else {
                $error = "File not found.";
            }
        } else {
            $error = "This link has expired.";
        }
    } else {
        $error = "Invalid or expired link.";
    }
} else {
    $error = "No file specified.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Download</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(-45deg, #000000, #8B0000);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .container {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 90%;
            width: 400px;
            text-align: center;
        }

        h2 {
            color: #333;
            margin-bottom: 1rem;
        }

        .download-btn {
            background-color: #8B0000;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 1rem;
            text-decoration: none;
            display: inline-block;
        }

        .download-btn:hover {
            background-color: #A52A2A;
        }

        .preview {
            max-width: 100%;
            max-height: 300px;
            margin-top: 1rem;
        }

        .error {
            color: #8B0000;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php else: ?>
            <h2>File: <?php echo htmlspecialchars($originalFilename); ?></h2>
            <?php if ($isImage): ?>
                <img src="<?php echo $file; ?>" alt="Preview" class="preview">
            <?php endif; ?>
            <a href="?id=<?php echo $shortLink; ?>&download=1" class="download-btn">Download File</a>
        <?php endif; ?>
    </div>
</body>
</html>