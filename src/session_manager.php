<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        if (!headers_sent()) {
            header('Location: login.php');
            exit();
        } else {
            die("Redirection échouée : les en-têtes ont déjà été envoyés.");
        }
    }
}

function requireAdmin() {
    if (getUserRole() !== 'admin') {
        if (!headers_sent()) {
            header('Location: unauthorized.php');
            exit();
        } else {
            die("Redirection échouée : les en-têtes ont déjà été envoyés.");
        }
    }
}
?>
