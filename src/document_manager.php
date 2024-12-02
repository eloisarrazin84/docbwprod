<?php
require 'db_connect.php';
require 'vendor/autoload.php'; // Pour les bibliothèques nécessaires
require_once '/var/www/src/mail/email_manager.php'; // Inclusion de la fonction d'envoi des e-mails

// Fonction pour téléverser un document
function uploadDocument($folderId, $file) {
    global $pdo;

    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        return ['success' => false, 'message' => 'Erreur : Utilisateur non connecté.'];
    }

    $userId = $_SESSION['user_id'];
    $uploadDir = '/var/www/uploads/';

    // Vérifier que le dossier existe ou le créer
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
        return ['success' => false, 'message' => 'Erreur : Impossible de créer le répertoire.'];
    }

    // Valider le fichier (type et taille)
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Erreur : Type de fichier non autorisé.'];
    }
    if ($file['size'] > 5 * 1024 * 1024) { // Limite de 5 Mo
        return ['success' => false, 'message' => 'Erreur : Taille du fichier trop grande.'];
    }

    $fileName = uniqid() . '-' . basename($file['name']);
    $filePath = $uploadDir . $fileName;

    // Déplacer le fichier téléversé
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        error_log("Erreur lors du téléversement : " . print_r(error_get_last(), true));
        return ['success' => false, 'message' => 'Erreur : Téléversement échoué.'];
    }

    // Enregistrer dans la base de données
try {
    // Enregistrer le document dans la base
    $stmt = $pdo->prepare("INSERT INTO documents (folder_id, user_id, file_name, file_path) VALUES (?, ?, ?, ?)");
    $stmt->execute([$folderId, $userId, $file['name'], $fileName]);

    // Récupérer l'e-mail de l'utilisateur propriétaire du dossier
    $stmt = $pdo->prepare("
        SELECT u.email 
        FROM users u 
        JOIN folders f ON f.user_id = u.id 
        WHERE f.id = ?
    ");
    $stmt->execute([$folderId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['email']) {
        // Charger le modèle d'email HTML
        $emailTemplatePath = '/var/www/src/mail/templates/document_notification.html';
        if (file_exists($emailTemplatePath)) {
            $emailTemplate = file_get_contents($emailTemplatePath);
        } else {
            error_log("Erreur : Le fichier de modèle d'email $emailTemplatePath est introuvable.");
            return ['success' => false, 'message' => 'Erreur : Notification email impossible.'];
        }

        // Remplacer les placeholders par les valeurs dynamiques
        $emailContent = str_replace(
            ['{{document_name}}', '{{logo_url}}'],
            [$file['name'], 'https://images.squarespace-cdn.com/content/v1/56893684d8af102bf3e403f1/1571317878518-X3DEUWJNOFZKBZ4LKQ54/Logo_BeWitness_Full.png?format=1500w'],
            $emailTemplate
        );

        // Envoyer l'e-mail de notification
        $subject = "Nouveau document disponible";
        sendEmailNotification($user['email'], $subject, $emailContent);
    }
} catch (PDOException $e) {
    error_log("Erreur PDO : " . $e->getMessage());
    return ['success' => false, 'message' => 'Erreur : Impossible de sauvegarder le fichier dans la base de données.'];
}

    return ['success' => true, 'message' => 'Fichier téléversé avec succès.'];
}

// Fonction pour lister les documents par dossier
function listDocumentsByFolder($folderId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, file_name, file_path, upload_date FROM documents WHERE folder_id = ?");
        $stmt->execute([$folderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
        return [];
    }
}

// Fonction pour lister les documents par utilisateur
function listDocumentsByUser($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT id, file_name, file_path, upload_date 
            FROM documents 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir un document par son ID
function getDocumentById($documentId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
        $stmt->execute([$documentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
        return null;
    }
}

// Fonction pour supprimer un document
function deleteDocument($documentId) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT file_path FROM documents WHERE id = ?");
        $stmt->execute([$documentId]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($document) {
            $filePath = '/var/www/uploads/' . $document['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath); // Supprimer le fichier
            }

            // Supprimer l'entrée de la base de données
            $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
            $stmt->execute([$documentId]);
            return true;
        }
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
        return false;
    }

    return false;
}
?>
