<?php
// addons/system_notifications/hooks.php

$notification_html = '
<div class="nav-item dropdown notification-dropdown" style="position:relative; margin-right:15px;" id="notificationContainer">
    <button class="theme-toggle" id="notificationBtn" title="Notifications" style="position:relative;">
        <i class="fas fa-bell"></i>
        <span id="notificationBadge" style="position:absolute; top:-5px; right:-5px; background:#ef4444; color:white; font-size:0.65rem; padding:2px 5px; border-radius:10px; display:none; font-weight:800; border:2px solid var(--card-bg);">0</span>
    </button>
    <ul class="dropdown-menu" id="notificationMenu" style="right: 0; left: auto; min-width: 320px; padding:0; overflow:hidden; border-radius:15px;">
        <div style="padding:15px; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.02);">
            <h4 style="margin:0; font-size:0.9rem; font-weight:800;">Notifications</h4>
            <span id="clearNotifications" style="font-size:0.75rem; color:var(--primary-color); cursor:pointer; font-weight:700;">Clear All</span>
        </div>
        <div id="notificationList" style="max-height:400px; overflow-y:auto; padding:10px;">
            <div style="text-align:center; padding:40px; color:var(--text-muted); opacity:0.5;">
                <i class="fas fa-bell-slash fa-2x" style="margin-bottom:10px; display:block;"></i>
                <p style="font-size:0.8rem; margin:0;">No new notifications</p>
            </div>
        </div>
    </ul>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("notificationBtn");
    const badge = document.getElementById("notificationBadge");
    const list = document.getElementById("notificationList");
    const clearBtn = document.getElementById("clearNotifications");
    
    let notifications = JSON.parse(localStorage.getItem("objsis_notifications") || "[]");
    let lastOrderCount = -1;

    function renderNotifications() {
        if (notifications.length === 0) {
            list.innerHTML = `<div style="text-align:center; padding:40px; color:var(--text-muted); opacity:0.5;"><i class="fas fa-bell-slash fa-2x" style="margin-bottom:10px; display:block;"></i><p style="font-size:0.8rem; margin:0;">No new notifications</p></div>`;
            badge.style.display = "none";
            return;
        }

        badge.innerText = notifications.length;
        badge.style.display = "block";

        list.innerHTML = notifications.map((n, i) => `
            <div style="padding:12px; border-radius:10px; background:rgba(255,255,255,0.02); margin-bottom:8px; border:1px solid var(--border-color); display:flex; gap:12px; align-items:start;">
                <div style="width:32px; height:32px; border-radius:8px; background:${n.type === "order" ? "rgba(249,115,22,0.1)" : "rgba(34,197,94,0.1)"}; color:${n.type === "order" ? "var(--primary-color)" : "var(--success)"}; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="fas ${n.type === "order" ? "fa-shopping-bag" : "fa-check"}"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-size:0.85rem; font-weight:700; margin-bottom:2px;">${n.title}</div>
                    <div style="font-size:0.75rem; color:var(--text-muted); line-height:1.4;">${n.message}</div>
                    <div style="font-size:0.65rem; color:var(--primary-color); margin-top:5px; font-weight:800;">${n.time}</div>
                </div>
            </div>
        `).join("");
    }

    async function checkUpdates() {
        try {
            const res = await fetch("../api/addons_api.php?action=notifications");
            const data = await res.json();
            if (data.success) {
                // 1. Check for New Orders
                if (lastOrderCount !== -1 && data.orders.length > 0) {
                    const latest = data.orders[0].id;
                    if (latest > lastOrderCount) {
                        addNotification({
                            type: "order",
                            title: "New Order #" + latest,
                            message: `Table ${data.orders[0].table_number} just placed an order.`,
                            time: new Date().toLocaleTimeString([], {hour: "2-digit", minute:"2-digit"})
                        });
                        lastOrderCount = latest;
                    }
                } else if (data.orders.length > 0) {
                    lastOrderCount = data.orders[0].id;
                }

                // 2. Check for Low Stock
                data.low_stock.forEach(item => {
                    const key = "alert_stock_" + item.name;
                    if (!localStorage.getItem(key)) {
                        addNotification({
                            type: "stock",
                            title: "Low Stock Alert!",
                            message: `${item.name} is running low (${item.current_quantity} ${item.unit} left).`,
                            time: new Date().toLocaleTimeString([], {hour: "2-digit", minute:"2-digit"})
                        });
                        localStorage.setItem(key, "notified");
                    }
                });
            }
        } catch(e) {}
    }

    function addNotification(n) {
        notifications.unshift(n);
        if (notifications.length > 20) notifications.pop();
        localStorage.setItem("objsis_notifications", JSON.stringify(notifications));
        renderNotifications();
        showToast(n.title, n.message, n.type);
    }

    function showToast(title, msg, type) {
        let toast = document.createElement("div");
        const color = type === "order" ? "var(--primary-color)" : "var(--danger)";
        const bg = type === "order" ? "rgba(249,115,22,0.1)" : "rgba(239,68,68,0.1)";
        const icon = type === "order" ? "fa-shopping-bag" : "fa-exclamation-triangle";
        
        toast.style.cssText = `position:fixed; bottom:20px; left:20px; background:var(--card-bg); border:1px solid ${color}; padding:15px 20px; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.5); z-index:9999; display:flex; align-items:center; gap:15px; animation: slideUp 0.5s ease-out;`;
        toast.innerHTML = `
            <div style="width:40px; height:40px; background:${bg}; color:${color}; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.2rem;"><i class="fas ${icon}"></i></div>
            <div>
                <div style="font-weight:800; font-size:0.9rem;">${title}</div>
                <div style="font-size:0.8rem; opacity:0.7;">${msg}</div>
            </div>
        `;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.animation = "slideDown 0.5s ease-in forwards";
            setTimeout(() => toast.remove(), 500);
        }, 5000);
    }

    clearBtn.onclick = () => {
        notifications = [];
        localStorage.setItem("objsis_notifications", "[]");
        // Clear stock suppression
        Object.keys(localStorage).forEach(key => { if(key.startsWith("alert_stock_")) localStorage.removeItem(key); });
        renderNotifications();
    };

    btn.onclick = (e) => {
        e.stopPropagation();
        const menu = document.getElementById("notificationMenu");
        const isShown = menu.parentElement.classList.contains("show");
        document.querySelectorAll(".dropdown").forEach(d => d.classList.remove("show"));
        if (!isShown) menu.parentElement.classList.add("show");
    };

    renderNotifications();
    setInterval(checkUpdates, 15000);
});

// CSS for toast animations
const style = document.createElement("style");
style.textContent = `
    @keyframes slideUp { from { transform: translateY(100px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    @keyframes slideDown { from { transform: translateY(0); opacity: 1; } to { transform: translateY(100px); opacity: 0; } }
`;
document.head.appendChild(style);
</script>
';

$GLOBALS['addon_navbar_items'][] = $notification_html;
?>
