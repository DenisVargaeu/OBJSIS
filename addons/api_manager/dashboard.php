<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
requireLogin();

if ($_SESSION['user_role'] !== 'admin') die("Unauthorized");

// 1. Manage Whitelist (Add/Delete)
if (isset($_POST['add_ip'])) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO api_whitelist (ip_address, label) VALUES (?, ?)");
    $stmt->execute([$_POST['ip'], $_POST['label']]);
}
if (isset($_GET['delete_ip'])) {
    $stmt = $pdo->prepare("DELETE FROM api_whitelist WHERE id = ?");
    $stmt->execute([$_GET['delete_ip']]);
}

// 2. Manage Keys (Generate/Status)
if (isset($_POST['gen_key'])) {
    $new_key = "OBJSIS_" . bin2hex(random_bytes(12));
    $stmt = $pdo->prepare("INSERT INTO api_keys (key_name, api_key) VALUES (?, ?)");
    $stmt->execute([$_POST['key_name'], $new_key]);
}
if (isset($_GET['toggle_key'])) {
    $stmt = $pdo->prepare("UPDATE api_keys SET is_active = !is_active WHERE id = ?");
    $stmt->execute([$_GET['toggle_key']]);
}

// 3. Stats for Overview
$total_requests = $pdo->query("SELECT COUNT(*) FROM api_logs")->fetchColumn() ?: 0;
$today_requests = $pdo->query("SELECT COUNT(*) FROM api_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;

// Fetch Data for Dashboard
$whitelist = $pdo->query("SELECT * FROM api_whitelist ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$api_keys = $pdo->query("SELECT * FROM api_keys ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$logs = $pdo->query("SELECT * FROM api_logs ORDER BY created_at DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);

$tab = $_GET['tab'] ?? 'monitor';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OBJSIS | API Pro Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --primary: #f97316; --bg: #0f172a; --card: rgba(30, 41, 59, 0.7); --border: rgba(255,255,255,0.1); }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: #f1f5f9; margin: 0; display: flex; height: 100vh; overflow: hidden; }
        
        .sidebar { width: 280px; background: rgba(15, 23, 42, 0.9); border-right: 1px solid var(--border); padding: 40px 20px; display: flex; flex-direction: column; gap: 10px; }
        .sidebar h2 { font-weight: 900; color: var(--primary); font-size: 1.4rem; padding: 0 10px; margin-bottom: 30px; }
        .nav-link { padding: 15px 20px; border-radius: 15px; color: #94a3b8; text-decoration: none; display: flex; align-items: center; gap: 15px; font-weight: 600; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: rgba(249, 115, 22, 0.1); color: var(--primary); }

        .main { flex: 1; padding: 50px; overflow-y: auto; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 40px; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 24px; padding: 30px; backdrop-filter: blur(20px); box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 0.75rem; color: #94a3b8; padding: 15px; border-bottom: 2px solid var(--border); }
        td { padding: 15px; font-size: 0.85rem; border-bottom: 1px solid rgba(255,255,255,0.03); }
        
        .btn { padding: 10px 20px; border-radius: 12px; font-weight: 700; cursor: pointer; border: none; transition: 0.2s; font-size: 0.85rem; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-danger { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
        
        input { background: rgba(0,0,0,0.2); border: 1px solid var(--border); border-radius: 10px; padding: 10px 15px; color: white; outline: none; margin-right: 10px; }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: bold; }
        .badge-success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .badge-warn { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>API PRO</h2>
        <a href="?tab=monitor" class="nav-link <?= $tab==='monitor'?'active':'' ?>"><i class="fas fa-chart-line"></i> Monitoring</a>
        <a href="?tab=keys" class="nav-link <?= $tab==='keys'?'active':'' ?>"><i class="fas fa-key"></i> API Keys</a>
        <a href="?tab=whitelist" class="nav-link <?= $tab==='whitelist'?'active':'' ?>"><i class="fas fa-shield-alt"></i> IP Whitelist</a>
        <a href="docs.php" class="nav-link" target="_blank"><i class="fas fa-book"></i> Documentation</a>
        <div style="margin-top:auto; padding:10px; color:#64748b; font-size:0.7rem;">v1.2.0-STABLE</div>
    </div>

    <div class="main">
        <?php if ($tab === 'monitor'): ?>
            <div class="grid">
                <div class="card">
                    <div style="font-size:0.8rem; color:#94a3b8;">TRAFFIC ALL-TIME</div>
                    <div style="font-size:2.5rem; font-weight:900; margin:10px 0;"><?= number_format($total_requests) ?></div>
                    <div style="font-size:0.8rem; color:#10b981;">Total successful requests</div>
                </div>
                <div class="card">
                    <div style="font-size:0.8rem; color:#94a3b8;">REQUESTS TODAY</div>
                    <div style="font-size:2.5rem; font-weight:900; margin:10px 0;"><?= number_format($today_requests) ?></div>
                    <div style="font-size:0.8rem; color:var(--primary);">Real-time peak activity</div>
                </div>
            </div>
            
            <div class="card">
                <h3>Live Activity Feed</h3>
                <table>
                    <thead>
                        <tr><th>ENDPOINT</th><th>IP ADDRESS</th><th>RESPONSE</th><th>TIME</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($logs as $l): ?>
                        <tr>
                            <td><code><?= $l['endpoint'] ?></code></td>
                            <td><?= $l['user_ip'] ?></td>
                            <td><span class="badge badge-success">200 OK</span></td>
                            <td><?= date('H:i:s', strtotime($l['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($tab === 'keys'): ?>
            <div class="card" style="margin-bottom:30px;">
                <h3>Generate New API Key</h3>
                <form action="?tab=keys" method="POST" style="display:flex; align-items:center;">
                    <input type="text" name="key_name" placeholder="Key Label (e.g. Mobile App)" required>
                    <button type="submit" name="gen_key" class="btn btn-primary">Generate Secret Key</button>
                </form>
            </div>
            
            <div class="card">
                <h3>Active API Keys</h3>
                <table>
                    <thead>
                        <tr><th>NAME</th><th>SECURE KEY</th><th>STATUS</th><th>ACTIONS</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($api_keys as $k): ?>
                        <tr>
                            <td><?= htmlspecialchars($k['key_name']) ?></td>
                            <td><code><?= $k['api_key'] ?></code></td>
                            <td><span class="badge <?= $k['is_active']?'badge-success':'badge-warn' ?>"><?= $k['is_active']?'ACTIVE':'DISABLED' ?></span></td>
                            <td>
                                <a href="?tab=keys&toggle_key=<?= $k['id'] ?>" class="btn" style="background:rgba(255,255,255,0.05); color:white; font-size:0.7rem;"><?= $k['is_active']?'Disable':'Enable' ?></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($tab === 'whitelist'): ?>
            <div class="card" style="margin-bottom:30px;">
                <h3>Protect Your API (IP Whitelisting)</h3>
                <p style="font-size:0.8rem; color:#94a3b8; margin-bottom:20px;">If this list is empty, all IPs are allowed. If you add at least one IP, all others will be blocked.</p>
                <form action="?tab=whitelist" method="POST" style="display:flex; align-items:center;">
                    <input type="text" name="ip" placeholder="IP Address (e.g. 192.168.1.1)" required>
                    <input type="text" name="label" placeholder="Internal Label">
                    <button type="submit" name="add_ip" class="btn btn-primary">Whitelist IP Address</button>
                </form>
            </div>
            
            <div class="card">
                <h3>Whitelisted Safe-IPs</h3>
                <table>
                    <thead>
                        <tr><th>IP ADDRESS</th><th>LABEL</th><th>ADDED</th><th>ACTION</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($whitelist as $w): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($w['ip_address']) ?></code></td>
                            <td><?= htmlspecialchars($w['label']) ?></td>
                            <td><?= date('Y-m-d', strtotime($w['created_at'])) ?></td>
                            <td><a href="?tab=whitelist&delete_ip=<?= $w['id'] ?>" class="btn btn-danger" style="font-size:0.7rem;"><i class="fas fa-trash"></i></a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
