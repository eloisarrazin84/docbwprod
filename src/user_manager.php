<?php
require 'db_connect.php';
require '/var/www/src/mail/templates/welcome_email.php'; // Charger le template d'email
require_once '/var/www/src/mail/email_manager.php'; // Inclusion de la fonction d'envoi des e-mails

function createUser($name, $email, $password, $role = 'user') {
    global $pdo;

    // Hasher le mot de passe
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    try {
        // Insérer l'utilisateur dans la base de données
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hashedPassword, $role]);

        // Envoyer un email à l'utilisateur avec ses identifiants
        sendWelcomeEmail($email, $name, $password);
    } catch (PDOException $e) {
        error_log("Erreur lors de la création de l'utilisateur : " . $e->getMessage());
        throw new Exception("Impossible de créer l'utilisateur. Veuillez réessayer.");
    }
}

function updateUserRole($userId, $role) {
    global $pdo;
    $stmt = $pdo->prepare('UPDATE users SET role = :role WHERE id = :id');
    $stmt->execute(['role' => $role, 'id' => $userId]);
}

function listUsers() {
    global $pdo;
    $stmt = $pdo->query("SELECT id, name, email, role FROM users");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fonction pour envoyer un email de bienvenue
function sendWelcomeEmail($email, $name, $temporaryPassword) {
    $subject = "Bienvenue sur notre plateforme";

    // Charger le contenu de l'email depuis le template
    $emailContent = getWelcomeEmailTemplate(
        $name,
        $email,
        $temporaryPassword,
        'https://images.squarespace-cdn.com/content/v1/56893684d8af102bf3e403f1/1571317878518-X3DEUWJNOFZKBZ4LKQ54/Logo_BeWitness_Full.png?format=1500w' // URL du logo
    );

    // Envoyer l'email avec `email_manager.php`
    if (!sendEmailNotification($email, $subject, $emailContent)) {
        error_log("Erreur lors de l'envoi de l'email de bienvenue à $email");
        throw new Exception("Impossible d'envoyer l'email de bienvenue.");
    }
}

// Fonction pour supprimer un utilisateur
function deleteUser($id) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            return true; // Suppression réussie
        } else {
            return false; // Aucun utilisateur supprimé
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la suppression de l'utilisateur : " . $e->getMessage());
        throw new Exception("Impossible de supprimer l'utilisateur.");
    }
}
?>
