<?php
// admin/updates.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();

// Access: System updates permission
checkPermission('manage_system');

$page_title = "System Maintenance";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - OBJSIS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?= getCustomStyles() ?>
    <style>
        .update-box {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
            padding: 40px;
        }
        .version-tag {
            background: rgba(249, 115, 22, 0.1);
            color: var(--primary-color);
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 0.9rem;
            display: inline-block;
            margin-top: 10px;
        }
        .progress-indicator {
            width: 80px;
            height: 80px;
            border: 6px solid rgba(255,255,255,0.05);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 25px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        .changelog {
            text-align: left;
            background: rgba(0,0,0,0.2);
            padding: 25px;
            border-radius: 15px;
            border: 1px solid var(--border-color);
            margin: 30px 0;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 0.9rem;
            line-height: 1.6;
            white-space: pre-wrap;
        }
    </style>
</head>

<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="page-header">
                <div class="page-title-group">
                    <h2><?= $page_title ?></h2>
                    <p style="color:var(--text-muted); margin:0;">Keep your software secure and up to date</p>
                </div>
            </header>

            <div class="glass-card" style="max-width: 850px; margin: 0 auto;">
                <div id="check-section" class="update-box">
                    <div style="width: 100px; height: 100px; background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-radius: 30px; display: flex; align-items: center; justify-content: center; font-size: 3rem; margin: 0 auto 30px;">
                        <i class="fas fa-cloud-arrow-down"></i>
                    </div>
                    <h2 style="font-weight: 800; font-size: 1.8rem; color: var(--text-main);">Software Integrity</h2>
                    <p style="color: var(--text-muted); margin-bottom: 30px;">
                        Current Version <span class="version-tag"><?= OBJSIS_VERSION ?></span>
                    </p>
                    <button class="btn" id="check-btn" onclick="checkUpdate()" style="padding: 15px 40px; font-weight: 800; font-size: 1rem; border-radius: 12px;">
                        <i class="fas fa-search" style="margin-right: 10px;"></i> Check for Updates
                    </button>
                    <p style="margin-top: 20px; font-size: 0.8rem; color: var(--text-muted);"><i class="fas fa-info-circle"></i> Checks daily for security patches and new features.</p>
                </div>

                <div id="update-details" class="update-box" style="display: none;">
                    <div style="background: rgba(34, 197, 94, 0.1); border: 2px solid var(--success); padding: 25px; border-radius: 20px; display: flex; align-items: center; gap: 20px; text-align: left; margin-bottom: 30px;">
                        <div style="font-size: 2.5rem; color: var(--success);"><i class="fas fa-sparkles"></i></div>
                        <div>
                            <h3 style="margin:0; color: var(--success); font-weight: 900;">NEW UPDATE READY!</h3>
                            <p style="margin:0; color: var(--text-main); font-weight: 600;">Version <span id="new-version" style="color: var(--success);"></span> is available for installation.</p>
                        </div>
                    </div>

                    <h4 style="text-align: left; margin-bottom: 10px; color: var(--text-muted); font-size: 0.8rem; letter-spacing: 1px; text-transform: uppercase;">Official Changelog</h4>
                    <div id="changelog" class="changelog"></div>

                    <div style="padding-top: 20px;">
                        <button class="btn" id="install-btn" onclick="confirmUpdate()" style="width: 100%; height: 60px; font-size: 1.2rem; font-weight: 900; box-shadow: 0 10px 25px rgba(249, 115, 22, 0.3);">
                            START INSTALLATION
                        </button>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 20px;">
                            <i class="fas fa-shield-halved" style="color: var(--success); margin-right: 5px;"></i> All system data is automatically backed up before patching.
                        </p>
                    </div>
                </div>

                <div id="update-progress" class="update-box" style="display: none; padding: 100px 40px;">
                    <div class="progress-indicator"></div>
                    <h2 style="font-weight: 900;">INSTALLING PATCH</h2>
                    <p style="color: var(--text-muted); font-size: 1.1rem;">Writing files and updating database schema...</p>
                    <p style="color: var(--primary-color); font-weight: 700; font-size: 0.8rem; margin-top: 20px;">PLEASE DO NOT REFRESH THIS PAGE</p>
                </div>

                <div id="update-success" class="update-box" style="display: none; padding: 100px 40px;">
                    <div style="font-size: 5rem; color: var(--success); margin-bottom: 30px;">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <h2 style="font-weight: 900; font-size: 2.22rem;">SUCCESSFULLY UPDATED</h2>
                    <p id="success-msg" style="margin-bottom: 40px; color: var(--text-muted); font-size: 1.1rem;"></p>
                    <button class="btn" onclick="location.reload()" style="padding: 15px 50px; font-weight: 800; border-radius: 12px;">COMPLETE</button>
                </div>
            </div>

            <div class="glass-card" style="max-width: 850px; margin: 40px auto 0 auto; padding: 30px;">
                <h3 style="margin: 0 0 15px 0; font-size: 1.1rem; font-weight: 800;"><i class="fas fa-history" style="margin-right: 10px; opacity: 0.5;"></i> System Log</h3>
                <div style="border: 1px dashed var(--border-color); padding: 30px; border-radius: 15px; text-align: center; color: var(--text-muted); font-style: italic;">
                    No manual updates recorded in the current session.
                </div>
            </div>
        </main>
    </div>

    <script>
        let latestUpdateInfo = null;

        function checkUpdate() {
            const btn = document.getElementById('check-btn');
            btn.innerHTML = '<i class="fas fa-sync fa-spin"></i> Communicating with Cloud...';
            btn.disabled = true;

            fetch('../api/software_update.php?action=check_update')
                .then(res => res.json())
                .then(res => {
                    btn.innerHTML = '<i class="fas fa-search" style="margin-right: 10px;"></i> Check for Updates';
                    btn.disabled = false;

                    if (res.success) {
                        if (res.has_update) {
                            latestUpdateInfo = res;
                            document.getElementById('check-section').style.display = 'none';
                            document.getElementById('update-details').style.display = 'block';
                            document.getElementById('new-version').innerText = res.latest_version;
                            document.getElementById('changelog').innerText = res.notes;
                        } else {
                            alert('Váš systém je aktuálny! (v' + res.current_version + ')');
                        }
                    } else {
                        alert('Check failed: ' + res.message);
                    }
                })
                .catch(err => {
                    alert('Error connecting to update server');
                    btn.disabled = false;
                });
        }

        function confirmUpdate() {
            if (!confirm('Confirm installation of version ' + latestUpdateInfo.latest_version + '? \nInternal data backup will be initiated.')) return;

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
                        alert('Patch failed: ' + res.message);
                    }
                })
                .catch(err => {
                    document.getElementById('update-progress').style.display = 'none';
                    alert('Connection lost during update. Check server logs.');
                });
        }
    </script>
    <script src="../assets/js/theme.js"></script>
</body>
</html>