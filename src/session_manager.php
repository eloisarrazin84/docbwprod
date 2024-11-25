<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifie si un utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Récupère le rôle de l'utilisateur (ou retourne null si non défini)
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

// Redirige vers la page de connexion si l'utilisateur n'est pas connecté
function requireLogin() {
    if (!isLoggedIn()) {
        redirectTo('login.php', "Vous devez être connecté pour accéder à cette page.");
    }
}

// Redirige vers une page non autorisée si l'utilisateur n'est pas administrateur
function requireAdmin() {
    if (getUserRole() !== 'admin') {
        redirectTo('unauthorized.php', "Accès refusé : vous n'avez pas les permissions nécessaires.");
    }
}

// Fonction utilitaire pour effectuer une redirection
function redirectTo($location, $errorMessage = null) {
    if (!headers_sent()) {
        if ($errorMessage) {
            $_SESSION['error_message'] = $errorMessage; // Stocke le message d'erreur dans la session
        }
        header("Location: $location");
        exit();
    } else {
        die("Redirection échouée : les en-têtes ont déjà été envoyés. Veuillez vérifier votre code.");
    }
}
?>
