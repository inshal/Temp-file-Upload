<?php
session_start();

// Generate and store CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function generateShortCode() {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    $target_dir = "uploads/";
    $uploadOk = 1;
    $fileType = strtolower(pathinfo($_FILES["fileToUpload"]["name"], PATHINFO_EXTENSION));

    // Check file size (limit to 5MB)
    if ($_FILES["fileToUpload"]["size"] > 50000000) {
        echo json_encode(["error" => "File is too large. Max size is 50MB."]);
        exit;
    }

    // Disallow PHP, HTML, and CSS files
    if ($fileType == "php" || $fileType == "html" || $fileType == "css") {
        echo json_encode(["error" => "PHP, HTML, and CSS files are not allowed."]);
        exit;
    }

    // Generate a unique short code
    do {
        $shortCode = generateShortCode();
        $linkFile = "links/{$shortCode}.json";
    } while (file_exists($linkFile));

    $originalFilename = $_FILES["fileToUpload"]["name"];
    $target_file = $target_dir . $shortCode . "." . $fileType;

    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        $expiry = time() + 86400; // 24 hours from now
        $linkData = [
            "filename" => $target_file,
            "originalFilename" => $originalFilename,
            "expiry" => $expiry
        ];
        file_put_contents($linkFile, json_encode($linkData));
        
        $shareLink = "http://{$_SERVER['HTTP_HOST']}/up/s/{$shortCode}";
        echo json_encode(["message" => "File uploaded successfully.", "link" => $shareLink]);
    } else {
        echo json_encode(["error" => "Sorry, there was an error uploading your file."]);
    }
    exit;
}

// If it's not a POST request, display the HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern File Upload</title>
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
        }

        h2 {
            color: #333;
            margin-bottom: 1rem;
        }

        #upload-form {
            display: flex;
            flex-direction: column;
        }

        input[type="file"] {
            margin-bottom: 1rem;
        }

        input[type="submit"], .copy-btn {
            background-color: #8B0000;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 0.5rem;
        }

        input[type="submit"]:hover, .copy-btn:hover {
            background-color: #A52A2A;
        }

        #message {
            margin-top: 1rem;
            color: #333;
        }

        #link-container {
            margin-top: 1rem;
            display: none;
        }

        #share-link {
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Upload File</h2>
        <form id="upload-form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
            <input type="file" name="fileToUpload" id="fileToUpload">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="submit" value="Upload File" name="submit">
        </form>
        <div id="message"></div>
        <div id="link-container">
            <p>Share link (valid for 24 hours):</p>
            <p id="share-link"></p>
            <button class="copy-btn" onclick="copyLink()">Copy Link</button>
        </div>
    </div>

    <script>
        document.getElementById('upload-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    document.getElementById('message').innerHTML = data.error;
                    document.getElementById('link-container').style.display = 'none';
                } else {
                    document.getElementById('message').innerHTML = data.message;
                    document.getElementById('share-link').textContent = data.link;
                    document.getElementById('link-container').style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });

        function copyLink() {
            const linkText = document.getElementById('share-link').textContent;
            navigator.clipboard.writeText(linkText).then(function() {
                alert('Link copied to clipboard!');
            }, function(err) {
                console.error('Could not copy text: ', err);
            });
        }
    </script>
</body>
</html>