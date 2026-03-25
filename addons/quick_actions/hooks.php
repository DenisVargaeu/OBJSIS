<?php
// addons/quick_actions/hooks.php

$quick_actions_html = '
<div id="quickActionsContainer" style="position:fixed; bottom:30px; right:30px; z-index:999; display:flex; flex-direction:column-reverse; align-items:center; gap:15px;">
    <button id="mainQuickBtn" style="width:60px; height:60px; border-radius:30px; background:var(--primary-color); color:white; border:none; box-shadow:0 10px 40px rgba(249,115,22,0.4); cursor:pointer; font-size:1.5rem; transition:transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); display:flex; align-items:center; justify-content:center; z-index:1000;">
        <i class="fas fa-bolt"></i>
    </button>
    
    <div id="quickMenu" style="display:none; flex-direction:column; gap:10px; opacity:0; transform:translateY(20px); transition: all 0.3s ease;">
        <button onclick="location.href=\'pos.php\'" class="q-item" title="New Order" style="--i:1; background:#10b981;"><i class="fas fa-cart-plus"></i></button>
        <button onclick="location.href=\'orders.php\'" class="q-item" title="View Orders" style="--i:2; background:#3b82f6;"><i class="fas fa-list"></i></button>
        <button id="quickPrint" class="q-item" title="Print Recent" style="--i:3; background:#8b5cf6;"><i class="fas fa-print"></i></button>
        <button onclick="location.href=\'inventory.php\'" class="q-item" title="Stock" style="--i:4; background:#f59e0b;"><i class="fas fa-boxes"></i></button>
    </div>
</div>

<style>
.q-item {
    width: 48px; height: 48px; border-radius: 24px; color: white; border: none; cursor: pointer; font-size: 1.1rem;
    display: flex; align-items: center; justify-content: center; box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    transition: transform 0.2s, box-shadow 0.2s;
}
.q-item:hover { transform: scale(1.1); box-shadow: 0 8px 20px rgba(0,0,0,0.3); }

#mainQuickBtn.active { transform: rotate(45deg); background: #334155; }
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("mainQuickBtn");
    const menu = document.getElementById("quickMenu");
    const printBtn = document.getElementById("quickPrint");

    btn.onclick = () => {
        const isActive = btn.classList.toggle("active");
        if (isActive) {
            menu.style.display = "flex";
            setTimeout(() => {
                menu.style.opacity = "1";
                menu.style.transform = "translateY(0)";
            }, 10);
        } else {
            menu.style.opacity = "0";
            menu.style.transform = "translateY(20px)";
            setTimeout(() => menu.style.display = "none", 300);
        }
    };

    printBtn.onclick = () => {
        alert("Preparing recent receipts for bulk printing...");
        // In a real app, this would trigger a print job via API
    };

    // Auto-hide when clicking elsewhere
    document.addEventListener("click", (e) => {
        if (!document.getElementById("quickActionsContainer").contains(e.target)) {
            btn.classList.remove("active");
            menu.style.opacity = "0";
            menu.style.transform = "translateY(20px)";
            setTimeout(() => menu.style.display = "none", 300);
        }
    });
});
</script>
';

$GLOBALS['addon_navbar_items'][] = $quick_actions_html;
?>
