<?php
// admin/import_menu.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();

if ($_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$page_title = "Import Menu Items";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $page_title ?> - OBJSIS
    </title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?= getCustomStyles() ?>
    <style>
        .import-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .drop-zone {
            border: 2px dashed var(--border-color);
            border-radius: 16px;
            padding: 50px;
            text-align: center;
            background: rgba(255, 255, 255, 0.02);
            transition: all 0.3s ease;
            cursor: pointer;
            margin-bottom: 30px;
        }

        .drop-zone:hover,
        .drop-zone.drag-over {
            border-color: var(--primary-color);
            background: rgba(249, 115, 22, 0.05);
        }

        .drop-zone i {
            font-size: 3rem;
            color: var(--text-muted);
            margin-bottom: 15px;
        }

        .schema-preview {
            background: rgba(0, 0, 0, 0.2);
            padding: 20px;
            border-radius: 12px;
            font-family: monospace;
            font-size: 0.9rem;
            color: #86efac;
            margin-top: 20px;
            overflow-x: auto;
        }

        .result-card {
            display: none;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="import-container">
                <header style="margin-bottom: 30px;">
                    <a href="menu.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem;">
                        <i class="fas fa-arrow-left"></i> Back to Menu
                    </a>
                    <h2 style="font-size: 2rem; margin-top: 10px;">
                        <?= $page_title ?>
                    </h2>
                    <p style="color: var(--text-muted);">Quickly populate your menu using a JSON file.</p>
                </header>

                <div class="card">
                    <div id="drop-zone" class="drop-zone" onclick="document.getElementById('file-input').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h3>Drag & Drop JSON File</h3>
                        <p>or click to browse from your computer</p>
                        <input type="file" id="file-input" accept=".json" style="display: none"
                            onchange="handleFile(this.files[0])">
                    </div>

                    <div style="margin-top: 40px;">
                        <h4 style="margin-bottom: 15px;">Required JSON Format:</h4>
                        <div class="schema-preview">
                            [
                            {
                            "category": "Appetizers",
                            "name": "Spring Rolls",
                            "description": "Crispy spring rolls with vegetables",
                            "price": 5.50,
                            "image_url": "https://example.com/image.jpg",
                            "is_available": true
                            }
                            ]
                        </div>
                    </div>
                </div>

                <div id="result-card" class="card result-card">
                    <h3 id="result-title">Import Result</h3>
                    <p id="result-message"></p>
                    <div id="result-errors" style="margin-top: 15px; color: var(--danger); font-size: 0.9rem;"></div>
                    <div style="margin-top: 20px;">
                        <a href="menu.php" class="btn">View Menu</a>
                        <button onclick="location.reload()" class="btn btn-secondary">Import Another</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const dropZone = document.getElementById('drop-zone');

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('drag-over');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            const file = e.dataTransfer.files[0];
            if (file && file.type === 'application/json') {
                handleFile(file);
            } else {
                alert('Please upload a valid JSON file.');
            }
        });

        function handleFile(file) {
            if (!file) return;

            const formData = new FormData();
            formData.append('action', 'import_json');
            formData.append('json_file', file);

            dropZone.style.opacity = '0.5';
            dropZone.style.pointerEvents = 'none';
            dropZone.innerHTML = '<i class="fas fa-spinner fa-spin"></i><h3>Importing...</h3>';

            fetch('../api/import_actions.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    const resultCard = document.getElementById('result-card');
                    const resultMessage = document.getElementById('result-message');
                    const resultErrors = document.getElementById('result-errors');

                    resultCard.style.display = 'block';
                    resultMessage.innerText = data.message;

                    if (data.success) {
                        dropZone.style.display = 'none';
                        document.getElementById('result-title').style.color = 'var(--success)';
                    } else {
                        document.getElementById('result-title').style.color = 'var(--danger)';
                    }

                    if (data.errors && data.errors.length > 0) {
                        resultErrors.innerHTML = '<strong>Errors:</strong><br>' + data.errors.join('<br>');
                    }

                    resultCard.scrollIntoView({ behavior: 'smooth' });
                })
                .catch(err => {
                    alert('An error occurred during import: ' + err.message);
                    location.reload();
                });
        }
    </script>
</body>

</html>