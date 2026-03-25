<?php
// addons/terminal_pro/hooks.php

$terminal_link = '
<div class="nav-item" style="margin-right:15px;">
    <a href="../addons/terminal_pro/index.php" class="theme-toggle" title="Terminal Pro" target="_blank">
        <i class="fas fa-terminal"></i>
    </a>
</div>
';

if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    $GLOBALS['addon_navbar_items'][] = $terminal_link;
}
?>
