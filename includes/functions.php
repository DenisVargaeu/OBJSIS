<?php
// includes/functions.php

session_start();

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: /login.php");
        exit;
    }
}

function redirect($url)
{
    header("Location: $url");
    exit;
}

// Simple flash message helper
function setFlashMessage($message, $type = 'success')
{
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}

function getFlashMessage()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Hash PIN for storage
 */
function hashPin($pin)
{
    return password_hash($pin, PASSWORD_DEFAULT);
}

/**
 * Verify PIN
 */
function verifyPin($pin, $hash)
{
    return password_verify($pin, $hash);
}

/**
 * Get Global Setting
 */
function getSetting($key, $default = '')
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $res = $stmt->fetchColumn();
        return $res !== false ? $res : $default;
    } catch (Exception $e) {
        return $default;
    }
}
?>