<?php
// addons/global_search/hooks.php

// Injected HTML for the search icon and the modal
$search_html = '
<button id="globalSearchBtn" class="theme-toggle" style="margin-right:10px;" title="Global Search (Ctrl+K)">
    <i class="fas fa-search"></i>
</button>

<div id="globalSearchModal" class="glass-card" style="display:none; position:fixed; top:80px; left:50%; transform:translateX(-50%); width:600px; max-width:90%; z-index:10000; padding:20px; box-shadow: 0 30px 60px rgba(0,0,0,0.5);">
    <div style="display:flex; align-items:center; gap:15px; border-bottom:1px solid var(--border-color); padding-bottom:15px; margin-bottom:15px;">
        <i class="fas fa-search" style="color:var(--primary-color);"></i>
        <input type="text" id="globalSearchInput" placeholder="Search orders, menu, staff..." style="flex:1; background:transparent; border:none; color:inherit; font-size:1.2rem; outline:none;">
        <kbd style="font-size:0.7rem; opacity:0.5; border:1px solid rgba(255,255,255,0.2); padding:2px 5px; border-radius:4px;">ESC</kbd>
    </div>
    <div id="globalSearchResults" style="max-height:400px; overflow-y:auto; display:flex; flex-direction:column; gap:10px;">
        <div style="text-align:center; opacity:0.5; padding:20px;">Start typing to search...</div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("globalSearchBtn");
    const modal = document.getElementById("globalSearchModal");
    const input = document.getElementById("globalSearchInput");
    const results = document.getElementById("globalSearchResults");

    btn.onclick = (e) => {
        e.stopPropagation();
        modal.style.display = modal.style.display === "none" ? "block" : "none";
        if (modal.style.display === "block") input.focus();
    };

    input.oninput = async () => {
        const query = input.value.trim();
        if (query.length < 2) {
            results.innerHTML = "";
            return;
        }
        
        const res = await fetch("../addons/global_search/api.php?q=" + encodeURIComponent(query));
        const data = await res.json();
        
        if (data.success) {
            results.innerHTML = data.results.map(r => `
                <a href="${r.url}" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:15px; padding:12px; border-radius:12px; background:rgba(255,255,255,0.03);">
                    <div style="width:30px; text-align:center;"><i class="fas ${r.icon}" style="color:var(--primary-color);"></i></div>
                    <div style="flex:1;">
                        <div style="font-weight:700;">${escHTML(r.title)}</div>
                        <div style="font-size:0.8rem; opacity:0.6;">${escHTML(r.subtitle)}</div>
                    </div>
                </a>
            `).join("") || `<div style="text-align:center; opacity:0.5; padding:20px;">No matches found</div>`;
        }
    };

    document.addEventListener("keydown", (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === "k") {
            e.preventDefault();
            btn.click();
        }
        if (e.key === "Escape") modal.style.display = "none";
    });

    window.onclick = (e) => {
        if (!modal.contains(e.target) && e.target !== btn) modal.style.display = "none";
    };
});
</script>
';

$GLOBALS['addon_navbar_items'][] = $search_html;
?>
