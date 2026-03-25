<?php
// addons/voice_control/hooks.php

$voice_html = '
<button id="voiceControlBtn" class="theme-toggle" style="margin-right:10px; position:relative;" title="Voice Control">
    <i class="fas fa-microphone" id="micIcon"></i>
    <span id="voiceStatus" style="position:absolute; top:-5px; right:-5px; width:10px; height:10px; background:var(--danger); border-radius:50%; display:none;"></span>
</button>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("voiceControlBtn");
    const icon = document.getElementById("micIcon");
    const status = document.getElementById("voiceStatus");

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
        btn.style.display = "none";
        return;
    }

    const recognition = new SpeechRecognition();
    recognition.continuous = false;
    recognition.lang = "en-US";
    recognition.interimResults = false;

    let isListening = false;

    btn.onclick = () => {
        if (isListening) {
            recognition.stop();
        } else {
            recognition.start();
        }
    };

    recognition.onstart = () => {
        isListening = true;
        icon.style.color = "var(--primary-color)";
        status.style.display = "block";
        status.style.background = "var(--success)";
    };

    recognition.onend = () => {
        isListening = false;
        icon.style.color = "";
        status.style.display = "none";
    };

    recognition.onresult = (event) => {
        const command = event.results[0][0].transcript.toLowerCase();
        console.log("Voice Command:", command);
        
        if (command.includes("dashboard")) window.location.href = "dashboard.php";
        else if (command.includes("kitchen")) window.location.href = "kitchen.php";
        else if (command.includes("order")) window.location.href = "orders.php";
        else if (command.includes("menu")) window.location.href = "menu.php";
        else if (command.includes("search")) document.getElementById("globalSearchBtn")?.click();
        else if (command.includes("logout")) window.location.href = "../logout.php";
        else if (command.includes("dark mode") || command.includes("theme")) toggleTheme();
        else {
            // Show a temporary help toast or something
            console.log("Unknown command:", command);
        }
    };

    recognition.onerror = (event) => {
        console.error("Speech Recognition Error", event.error);
        isListening = false;
    };
});
</script>
';

$GLOBALS['addon_navbar_items'][] = $voice_html;
?>
