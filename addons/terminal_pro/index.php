<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
requireLogin();

if ($_SESSION['user_role'] !== 'admin') {
    die("Unauthorized");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OBJSIS | Terminal Pro Ultra</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --term-bg: #050505;
            --term-green: #00ff41;
            --term-dim: #008f11;
            --term-text: #e2e8f0;
        }
        body, html {
            margin: 0; padding: 0;
            height: 100%;
            background: var(--term-bg);
            color: var(--term-green);
            font-family: 'Courier New', Courier, monospace;
            overflow: hidden;
        }
        #terminal-overlay {
            position: fixed; inset: 0;
            background: radial-gradient(circle, transparent 20%, rgba(0,0,0,0.8) 100%);
            pointer-events: none;
            z-index: 10;
        }
        #scanlines {
            position: fixed; inset: 0;
            width: 100%; height: 100%;
            background: linear-gradient(
                rgba(18, 16, 16, 0) 50%,
                rgba(0, 0, 0, 0.25) 50%
            ), linear-gradient(
                90deg,
                rgba(255, 0, 0, 0.06),
                rgba(0, 255, 0, 0.02),
                rgba(0, 0, 255, 0.06)
            );
            background-size: 100% 4px, 3px 100%;
            pointer-events: none;
            z-index: 11;
        }
        #terminal {
            padding: 30px;
            height: calc(100% - 60px);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .line { display: flex; gap: 10px; opacity: 0.9; line-height: 1.4; }
        .prompt { color: var(--term-dim); font-weight: bold; white-space: nowrap; }
        .command { color: #fff; }
        .output { color: var(--term-green); white-space: pre-wrap; }
        .error { color: #ff5555; }
        .system { color: #3b82f6; }

        #input-line { display: flex; gap: 10px; align-items: center; }
        #input-line input {
            background: transparent;
            border: none;
            color: #fff;
            font-family: 'Courier New', Courier, monospace;
            font-size: 1rem;
            flex: 1;
            outline: none;
        }
        
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }
        .cursor { width: 10px; height: 1.2rem; background: var(--term-green); display: inline-block; animation: blink 1s infinite; }
    </style>
</head>
<body>
    <div id="terminal-overlay"></div>
    <div id="scanlines"></div>
    <div id="terminal">
        <div id="history"></div>
        <div id="input-line" style="display:none;">
            <span class="prompt">objsis@admin:~$</span>
            <input type="text" id="cmd-input" autofocus autocomplete="off">
        </div>
    </div>

    <script>
        const input = document.getElementById('cmd-input');
        const history = document.getElementById('history');
        const term = document.getElementById('terminal');
        const inputLine = document.getElementById('input-line');

        let cmdHistory = JSON.parse(localStorage.getItem('objsis_term_history') || '[]');
        let historyIndex = -1;

        const BOOT_LOGS = [
            "OBJSIS BIOS v4.0.1 (c) 2026 Antigravity Systems",
            "Checking RAM... 16384KB OK",
            "Initializing kernel... DONE",
            "Loading local drivers... [SUCCESS]",
            "Connecting to database... CONNECTED",
            "Starting terminal service...",
            "Welcome, <?= $_SESSION['user_name'] ?>. System ready.",
            "Type 'help' for commands."
        ];

        async function boot() {
            for (const log of BOOT_LOGS) {
                const line = document.createElement('div');
                line.className = 'line ' + (log.includes('DONE') || log.includes('OK') ? 'system' : '');
                line.innerText = log;
                history.appendChild(line);
                term.scrollTop = term.scrollHeight;
                await new Promise(r => setTimeout(r, Math.random() * 200 + 100));
            }
            inputLine.style.display = 'flex';
            input.focus();
        }

        const COMMANDS = {
            'help': () => `Available commands:
- ls [dir]: List files
- cat [file]: Read file content
- stats: Live system metrics
- sysinfo: Server environment details
- uptime / disk / mem: System health
- whoami: Session info
- clear: Clear history
- exit: Close terminal`,
            'whoami': async () => callApi('whoami'),
            'stats': async () => callApi('stats'),
            'uptime': async () => callApi('uptime'),
            'disk': async () => callApi('disk'),
            'mem': async () => callApi('mem'),
            'sysinfo': async () => callApi('sysinfo'),
            'ls': async (arg) => callApi('ls', arg),
            'cat': async (arg) => callApi('cat', arg),
            'clear': () => { history.innerHTML = ''; return null; },
            'exit': () => { window.close(); return 'Closing session...'; }
        };

        async function callApi(cmd, arg = '') {
            try {
                const res = await fetch(`../../api/addons_api.php?action=terminal&cmd=${cmd}&arg=${encodeURIComponent(arg)}`);
                const data = await res.json();
                return data.success ? data.output : `<span class="error">${data.message}</span>`;
            } catch (e) {
                return `<span class="error">Communication failure.</span>`;
            }
        }

        input.addEventListener('keydown', async (e) => {
            if (e.key === 'Enter') {
                const rawLine = input.value.trim();
                const [cmd, ...args] = rawLine.toLowerCase().split(' ');
                input.value = '';

                if (!rawLine) return;

                // History
                cmdHistory.unshift(rawLine);
                if (cmdHistory.length > 50) cmdHistory.pop();
                localStorage.setItem('objsis_term_history', JSON.stringify(cmdHistory));
                historyIndex = -1;

                // Add to view
                const line = document.createElement('div');
                line.className = 'line';
                line.innerHTML = `<span class="prompt">objsis@admin:~$</span><span class="command">${rawLine}</span>`;
                history.appendChild(line);

                // Execute
                if (COMMANDS[cmd]) {
                    const result = await COMMANDS[cmd](args.join(' '));
                    if (result) {
                        const out = document.createElement('div');
                        out.className = 'line output';
                        out.innerHTML = result;
                        history.appendChild(out);
                    }
                } else {
                    const out = document.createElement('div');
                    out.className = 'line error';
                    out.innerHTML = `Command not found: ${cmd}`;
                    history.appendChild(out);
                }

                term.scrollTop = term.scrollHeight;
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (historyIndex < cmdHistory.length - 1) {
                    historyIndex++;
                    input.value = cmdHistory[historyIndex];
                }
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (historyIndex > 0) {
                    historyIndex--;
                    input.value = cmdHistory[historyIndex];
                } else {
                    historyIndex = -1;
                    input.value = '';
                }
            } else if (e.key === 'Tab') {
                e.preventDefault();
                const current = input.value.toLowerCase();
                const match = Object.keys(COMMANDS).find(c => c.startsWith(current));
                if (match) input.value = match;
            }
        });

        document.body.onclick = () => input.focus();
        boot();
    </script>
</body>
</html>
