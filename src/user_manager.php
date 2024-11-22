<?php
require 'db_connect.php';
require '/var/www/src/mail/templates/welcome_email.php'; // Charger le template d'email

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

    // Envoyer l'email
    try {
        sendEmail($email, $subject, $emailContent);
    } catch (Exception $e) {
        error_log("Erreur lors de l'envoi de l'email de bienvenue : " . $e->getMessage());
    }
}
?>
