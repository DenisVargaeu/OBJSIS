<?php
// admin/addons.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();

// Access: System updates permission
checkPermission('manage_system');

$page_title = "Addon Manager";
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
        .addon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        .addon-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }
        .addon-status {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 10px;
        }
        .status-enabled { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .status-disabled { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .status-uninstalled { background: rgba(100, 116, 139, 0.1); color: var(--text-muted); }
        
        .addon-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <div class="app-container" style="display: block;"> <!-- Overriding grid for standalone feel -->
        <main class="main-content" style="margin: 0; padding: 40px; max-width: 1200px; margin: 0 auto;">
            <header class="page-header" style="margin-bottom: 40px; border:none; display: flex; justify-content: space-between; align-items: center;">
                <div class="page-title-group">
                    <h1 style="font-size: 3rem; font-weight: 900; letter-spacing: -1px; margin: 0;">OBJSIS <span style="color:var(--primary-color);">ADDONS</span></h1>
                    <p style="color:var(--text-muted); margin:0; font-size: 1.1rem;">Discover and manage powerful system extensions</p>
                </div>
                <a href="dashboard.php" class="btn btn-secondary" style="border-radius: 30px; padding: 10px 25px;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </header>

            <div style="display: flex; gap: 20px; margin-bottom: 40px; overflow-x: auto; padding-bottom: 10px;">
                <button class="btn" style="background: var(--primary-color); border-radius: 20px;">All Extensions</button>
                <button class="btn btn-secondary" style="border-radius: 20px;">System</button>
                <button class="btn btn-secondary" style="border-radius: 20px;">Analytics</button>
                <button class="btn btn-secondary" style="border-radius: 20px;">Tools</button>
            </div>

            <div id="addon-list" class="addon-grid">
                <!-- Addons will be loaded here via JS -->
                <div style="grid-column: 1/-1; text-align: center; padding: 100px; opacity: 0.5;">
                    <i class="fas fa-circle-notch fa-spin fa-3x"></i>
                </div>
            </div>
        </main>
    </div>

    <script>
        function loadAddons() {
            fetch('../api/addons.php?action=list')
                .then(res => res.json())
                .then(res => {
                    const grid = document.getElementById('addon-list');
                    if (res.success && res.addons.length > 0) {
                        grid.innerHTML = '';
                        res.addons.forEach(addon => {
                            const card = document.createElement('div');
                            card.className = 'glass-card addon-card';
                            card.style.padding = '30px';
                            card.style.transition = 'transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease';
                            card.onmouseenter = () => {
                                card.style.transform = 'translateY(-5px)';
                                card.style.borderColor = 'var(--primary-color)';
                                card.style.boxShadow = '0 20px 40px rgba(0,0,0,0.3)';
                            };
                            card.onmouseleave = () => {
                                card.style.transform = 'translateY(0)';
                                card.style.borderColor = '';
                                card.style.boxShadow = '';
                            };
                            
                            const statusClass = addon.status.installed ? (addon.status.enabled ? 'status-enabled' : 'status-disabled') : 'status-uninstalled';
                            const statusLabel = addon.status.installed ? (addon.status.enabled ? 'ENABLED' : 'DISABLED') : 'NOT INSTALLED';
                            
                            card.innerHTML = `
                                <div>
                                    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:20px;">
                                        <div style="width:60px; height:60px; background:rgba(255,255,255,0.03); border-radius:15px; display:flex; align-items:center; justify-content:center; font-size:1.8rem; color:var(--primary-color);">
                                            <i class="fas ${addon.icon || 'fa-puzzle-piece'}"></i>
                                        </div>
                                        <span class="addon-status ${statusClass}">${statusLabel}</span>
                                    </div>
                                    <h3 style="margin: 0 0 10px 0; font-weight: 800; font-size: 1.4rem;">${addon.name}</h3>
                                    <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6; min-height: 80px;">${addon.description}</p>
                                    <div class="addon-meta" style="border-top: 1px solid var(--border-color); padding-top: 15px;">
                                        <span><i class="fas fa-user-circle"></i> ${addon.author}</span>
                                        <span style="margin-left: auto; opacity: 0.5;">v${addon.version}</span>
                                    </div>
                                </div>
                                <div style="margin-top: 25px; display: flex; gap: 10px;">
                                    ${addon.status.installed 
                                        ? `<button class="btn ${addon.status.enabled ? 'btn-secondary' : ''}" onclick="toggleAddon('${addon.addon_id}', ${addon.status.enabled ? 0 : 1})" style="flex: 1; border-radius:12px;">
                                            ${addon.status.enabled ? '<i class="fas fa-power-off"></i> Disable' : '<i class="fas fa-play"></i> Enable'}
                                           </button>
                                           ${(addon.status.enabled && addon.has_ui) ? `<a href="../addons/${addon.addon_id}/index.php" class="btn" style="border-radius:12px; background:var(--primary-color); display:flex; align-items:center; gap:8px; flex:1.5; justify-content:center; color:white;">
                                                <i class="fas fa-external-link-alt"></i> OPEN APP
                                           </a>` : ''}`
                                        : `<button class="btn" onclick="installAddon('${addon.addon_id}')" style="flex: 1; border-radius:12px; font-weight:800;">
                                            <i class="fas fa-download"></i> Install Extension
                                           </button>`
                                    }
                                </div>
                            `;
                            grid.appendChild(card);
                        });
                    } else if (res.success) {
                        grid.innerHTML = `
                            <div class="glass-card" style="grid-column: 1/-1; text-align: center; padding: 50px;">
                                <i class="fas fa-box-open fa-3x" style="opacity: 0.2; margin-bottom: 20px;"></i>
                                <h3>No addons found</h3>
                                <p style="color: var(--text-muted);">Place addon folders in the <code>/addons</code> directory to see them here.</p>
                            </div>
                        `;
                    } else {
                        grid.innerHTML = `<div class="alert alert-error">${res.message}</div>`;
                    }
                });
        }

        function toggleAddon(addonId, enable) {
            const formData = new FormData();
            formData.append('action', 'toggle');
            formData.append('addon_id', addonId);
            formData.append('enable', enable);

            fetch('../api/addons.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.success) loadAddons();
                    else alert(res.message);
                });
        }

        function installAddon(addonId) {
            if (!confirm('Are you sure you want to install this module? Some addons may modify your database.')) return;
            
            const formData = new FormData();
            formData.append('action', 'install');
            formData.append('addon_id', addonId);

            fetch('../api/addons.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.success) loadAddons();
                    else alert(res.message);
                });
        }

        document.addEventListener('DOMContentLoaded', loadAddons);
    </script>
    <script src="../assets/js/theme.js"></script>
</body>

</html>
