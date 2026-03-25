<?php
// includes/addon_loader.php

/**
 * Automatically load and initialize all enabled addons
 */
function loadAddons($pdo) {
    try {
        $stmt = $pdo->query("SELECT addon_id FROM addons WHERE is_enabled = 1");
        $enabledAddons = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($enabledAddons as $addonId) {
            $hookPath = dirname(__DIR__) . '/addons/' . $addonId . '/hooks.php';
            if (file_exists($hookPath)) {
                include_once $hookPath;
            }
        }
    } catch (Exception $e) {
        // Silently fail if database is not ready
    }
}

// Initial load
if (isset($pdo)) {
    loadAddons($pdo);
}
?>
