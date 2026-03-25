<?php
// addons/api_manager/hooks.php

$api_manager_html = '
<div class="nav-item">
    <button class="theme-toggle" id="apiManagerBtn" title="API Manager">
        <i class="fas fa-key"></i>
    </button>
</div>

<div id="apiManagerPanel" style="display:none; position:fixed; top:80px; right:20px; width:400px; background:var(--card-bg); border:1px solid var(--border-color); border-radius:24px; box-shadow:0 20px 60px rgba(0,0,0,0.6); z-index:1001; padding:25px; backdrop-filter:blur(30px);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h3 style="margin:0; font-size:1.1rem; font-weight:900; color:var(--primary-color);">API Manager</h3>
        <button id="closeApiManager" style="background:none; border:none; color:var(--text-muted); cursor:pointer;"><i class="fas fa-times"></i></button>
    </div>
    
    <div style="background:rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:15px; padding:15px; margin-bottom:20px;">
        <div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:10px;">YOUR MASTER API KEY</div>
        <div style="display:flex; gap:10px; align-items:center;">
            <input type="password" id="apiKeyDisplay" value="************************" readonly style="flex:1; background:transparent; border:none; color:var(--text-main); font-family:monospace; font-size:0.9rem; outline:none;">
            <button id="toggleApiKey" style="background:none; border:none; color:var(--primary-color); cursor:pointer;"><i class="fas fa-eye"></i></button>
            <button id="copyApiKey" style="background:none; border:none; color:var(--primary-color); cursor:pointer;"><i class="fas fa-copy"></i></button>
        </div>
    </div>

    <div style="display:flex; flex-direction:column; gap:12px;">
        <button id="regenApiKey" class="btn-primary" style="width:100%; border-radius:12px; height:45px; font-weight:700;">Regenerate Key</button>
        <div style="display:flex; gap:10px;">
            <a href="../addons/api_manager/dashboard.php" target="_blank" style="flex:1; text-align:center; padding:10px; background:rgba(249,115,22,0.1); border:1px solid var(--primary-color); border-radius:10px; font-size:0.8rem; color:var(--primary-color); text-decoration:none; font-weight:700;"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="../addons/api_manager/docs.php" target="_blank" style="flex:1; text-align:center; padding:10px; background:rgba(255,255,255,0.05); border:1px solid var(--border-color); border-radius:10px; font-size:0.8rem; color:var(--text-muted); text-decoration:none;"><i class="fas fa-book"></i> Docs</a>
        </div>
    </div>

    <div style="margin-top:20px; padding-top:20px; border-top:1px solid var(--border-color);">
        <div style="font-size:0.7rem; color:var(--text-muted); line-height:1.5;">
            Use this key to integrate with delivery apps, mobile menu systems, or custom dashboards. Keep it secret!
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("apiManagerBtn");
    const panel = document.getElementById("apiManagerPanel");
    const close = document.getElementById("closeApiManager");
    const regen = document.getElementById("regenApiKey");
    const toggle = document.getElementById("toggleApiKey");
    const copy = document.getElementById("copyApiKey");
    const display = document.getElementById("apiKeyDisplay");

    let realKey = localStorage.getItem("objsis_api_key") || "OBJSIS_" + Math.random().toString(36).substr(2, 16).toUpperCase();
    if (!localStorage.getItem("objsis_api_key")) localStorage.setItem("objsis_api_key", realKey);

    btn.onclick = (e) => {
        e.stopPropagation();
        const isShown = panel.style.display === "block";
        document.querySelectorAll(".dropdown").forEach(d => d.classList.remove("show"));
        panel.style.display = isShown ? "none" : "block";
    };

    close.onclick = () => panel.style.display = "none";

    toggle.onclick = () => {
        if (display.type === "password") {
            display.type = "text";
            display.value = realKey;
            toggle.innerHTML = \'<i class="fas fa-eye-slash"></i>\';
        } else {
            display.type = "password";
            display.value = "************************";
            toggle.innerHTML = \'<i class="fas fa-eye"></i>\';
        }
    };

    copy.onclick = () => {
        navigator.clipboard.writeText(realKey);
        alert("API Key copied to clipboard!");
    };

    regen.onclick = () => {
        if (confirm("Revoke current key and generate new one? This will break existing integrations.")) {
            realKey = "OBJSIS_" + Math.random().toString(36).substr(2, 16).toUpperCase();
            localStorage.setItem("objsis_api_key", realKey);
            if (display.type === "text") display.value = realKey;
            alert("New API Key generated.");
        }
    };

    window.addEventListener("click", (e) => {
        if (!panel.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
            panel.style.display = "none";
        }
    });
});
</script>
';

if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    $GLOBALS['addon_navbar_items'][] = $api_manager_html;
}
?>
