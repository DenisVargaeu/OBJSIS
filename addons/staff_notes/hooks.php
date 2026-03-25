<?php
// addons/staff_notes/hooks.php

$notes_html = '
<div class="nav-item" style="margin-right:15px;">
    <button class="theme-toggle" id="staffNotesBtn" title="Staff Notes">
        <i class="fas fa-sticky-note"></i>
    </button>
</div>

<div id="notesPanel" style="display:none; position:fixed; top:80px; right:20px; width:300px; background:var(--card-bg); border:1px solid var(--border-color); border-radius:20px; box-shadow:0 10px 40px rgba(0,0,0,0.4); z-index:1001; padding:20px; backdrop-filter:blur(20px);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <h4 style="margin:0; font-size:1rem; font-weight:800; color:var(--primary-color);">Staff Notes</h4>
        <button id="closeNotes" style="background:none; border:none; color:var(--text-muted); cursor:pointer;"><i class="fas fa-times"></i></button>
    </div>
    <textarea id="staffNotesArea" style="width:100%; height:200px; background:rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:12px; padding:15px; color:var(--text-main); font-family:inherit; font-size:0.9rem; resize:none; outline:none;" placeholder="Type a note for the team..."></textarea>
    <div style="margin-top:15px; display:flex; gap:10px;">
        <button id="clearNotes" class="btn" style="flex:1; background:rgba(239,68,68,0.1); color:var(--danger); font-size:0.75rem; padding:8px; border-radius:8px; border:1px solid rgba(239,68,68,0.2);"><i class="fas fa-trash"></i> Clear</button>
        <button id="exportNotes" class="btn" style="flex:1; background:rgba(59,130,246,0.1); color:#3b82f6; font-size:0.75rem; padding:8px; border-radius:8px; border:1px solid rgba(59,130,246,0.2);"><i class="fas fa-file-export"></i> Export</button>
    </div>
    <div style="margin-top:10px; font-size:0.7rem; color:var(--text-muted); text-align:center;">Auto-saved to local device</div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("staffNotesBtn");
    const panel = document.getElementById("notesPanel");
    const close = document.getElementById("closeNotes");
    const area = document.getElementById("staffNotesArea");
    const clearBtn = document.getElementById("clearNotes");
    const exportBtn = document.getElementById("exportNotes");

    btn.onclick = (e) => {
        e.stopPropagation();
        const isShown = panel.style.display === "block";
        document.querySelectorAll(".dropdown").forEach(d => d.classList.remove("show"));
        panel.style.display = isShown ? "none" : "block";
        if (!isShown) area.focus();
    };

    close.onclick = () => panel.style.display = "none";

    // Load saved notes
    area.value = localStorage.getItem("objsis_staff_notes") || "";

    // Auto-save on input
    area.oninput = () => {
        localStorage.setItem("objsis_staff_notes", area.value);
    };

    clearBtn.onclick = () => {
        if(confirm("Clear all notes?")) {
            area.value = "";
            localStorage.setItem("objsis_staff_notes", "");
        }
    };

    exportBtn.onclick = () => {
        const text = area.value;
        if(!text) return alert("Nothing to export!");
        const blob = new Blob([text], { type: "text/plain" });
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = `staff_notes_${new Date().toISOString().split("T")[0]}.txt`;
        a.click();
        URL.revokeObjectURL(url);
    };

    // Close on outside click
    window.addEventListener("click", (e) => {
        if (!panel.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
            panel.style.display = "none";
        }
    });
});
</script>
';

$GLOBALS['addon_navbar_items'][] = $notes_html;
?>
