<?php
// admin/updates.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();

if ($_SESSION['user_role'] !== 'admin') {
    die("Unauthorized");
}

$page_title = "System Updates";
?>
<!DOCTYPE html>
<html lang="sk">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $page_title ?> - OBJSIS
    </title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?= getCustomStyles() ?>
</head>

<body class="admin-page">
    <div class="app-container"> <!-- Changed from admin-container for consistency -->
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1><i class="fas fa-sync"></i> System Updates</h1>
            </header>

            <div class="card" style="max-width: 800px; margin: 0 auto;">
                <div id="check-section" style="text-align: center; padding: 40px 0;">
                    <div style="font-size: 4rem; color: var(--primary-color); margin-bottom: 20px;">
                        <i class="fas fa-cloud-download-alt"></i>
                    </div>
                    <h2>Software Update</h2>
                    <p style="color: var(--text-muted); margin-bottom: 30px;">
                        Current Version: <strong style="color: var(--text-main);">
                            <?= OBJSIS_VERSION ?>
                        </strong>
                    </p>
                    <button class="btn" id="check-btn" onclick="checkUpdate()">
                        Check for Updates
                    </button>
                </div>

                <div id="update-details" style="display: none; padding: 20px 0;">
                    <div class="alert alert-info"
                        style="background: rgba(34, 197, 94, 0.1); border-color: var(--success); color: var(--text-main); margin-bottom: 30px;">
                        <h3 style="color: var(--success); margin-bottom: 10px;">✨ Update Available!</h3>
                        <p>A new version (<strong id="new-version"></strong>) is ready to be installed.</p>
                    </div>

                    <h3>What's New:</h3>
                    <div id="changelog"
                        style="background: var(--card-bg-glass); padding: 20px; border-radius: 12px; margin: 15px 0 30px 0; font-family: monospace; white-space: pre-wrap;">
                    </div>

                    <div style="border-top: 1px solid var(--border-color); padding-top: 30px; text-align: center;">
                        <button class="btn" id="install-btn" onclick="confirmUpdate()"
                            style="padding: 15px 40px; font-size: 1.1rem;">
                            Install Update Now
                        </button>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 15px;">
                            <i class="fas fa-info-circle"></i> The system will backup your data before proceeding.
                        </p>
                    </div>
                </div>

                <div id="update-progress" style="display: none; text-align: center; padding: 60px 0;">
                    <div class="loading-spinner"
                        style="width: 60px; height: 60px; border: 5px solid rgba(255,255,255,0.1); border-top-color: var(--primary-color); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 30px auto;">
                    </div>
                    <h2>Installing Update...</h2>
                    <p style="color: var(--text-muted);">Please do not close this window. This might take a minute.</p>
                </div>

                <div id="update-success" style="display: none; text-align: center; padding: 60px 0;">
                    <div style="font-size: 5rem; color: var(--success); margin-bottom: 20px;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2>Update Successful!</h2>
                    <p id="success-msg" style="margin-bottom: 30px;"></p>
                    <button class="btn" onclick="location.reload()">Finish</button>
                </div>
            </div>

            <div class="card" style="max-width: 800px; margin: 40px auto 0 auto; opacity: 0.7;">
                <h3><i class="fas fa-history"></i> Update History</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; padding: 20px 0;">No updates performed through
                    the built-in system yet.</p>
            </div>
        </main>
    </div>

    <style>
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .alert {
            padding: 20px;
            border-radius: 12px;
            border-left: 5px solid;
        }
    </style>

    <script>
        let latestUpdateInfo = null;

        function checkUpdate() {
            const btn = document.getElementById('check-btn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
            btn.disabled = true;

            fetch('../api/software_update.php?action=check_update')
                .then(res => res.json())
                .then(res => {
                    btn.innerHTML = 'Check for Updates';
                    btn.disabled = false;

                    if (res.success) {
                        if (res.has_update) {
                            latestUpdateInfo = res;
                            document.getElementById('check-section').style.display = 'none';
                            document.getElementById('update-details').style.display = 'block';
                            document.getElementById('new-version').innerText = res.latest_version;
                            document.getElementById('changelog').innerText = res.notes;
                        } else {
                            alert('You are up to date! (v' + res.current_version + ')');
                        }
                    } else {
                        alert('Check failed: ' + res.message);
                    }
                })
                .catch(err => {
                    alert('Error: ' + err.message);
                    btn.innerHTML = 'Check for Updates';
                    btn.disabled = false;
                });
        }

        function confirmUpdate() {
            if (!confirm('Are you sure you want to install version ' + latestUpdateInfo.latest_version + '? \n\nYour data will be backed up, but we recommend manually backing up your config/db.php first.')) return;

            document.getElementById('update-details').style.display = 'none';
            document.getElementById('update-progress').style.display = 'block';

            const formData = new FormData();
            formData.append('action', 'start_update');

            fetch('../api/software_update.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    document.getElementById('update-progress').style.display = 'none';
                    if (res.success) {
                        document.getElementById('update-success').style.display = 'block';
                        document.getElementById('success-msg').innerText = res.message;
                    } else {
                        document.getElementById('update-details').style.display = 'block';
                        alert('Update failed: ' + res.message);
                    }
                })
                .catch(err => {
                    document.getElementById('update-progress').style.display = 'none';
                    document.getElementById('update-details').style.display = 'block';
                    alert('Critical Error: ' + err.message);
                });
        }
    </script>
    <script src="../assets/js/theme.js"></script>
</body>

</html>