<?php
require 'db_connect.php';
require 'vendor/autoload.php'; // Pour DocuSeal et JWT
use \Firebase\JWT\JWT;

function uploadDocument($folderId, $file, $requireSignature = false, $userEmail = null) {
    global $pdo;

    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        die("Erreur : Utilisateur non connecté.");
    }

    // Récupérer l'ID utilisateur depuis la session
    $userId = $_SESSION['user_id'];

    // Dossier de stockage
    $uploadDir = '/var/www/uploads/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            die("Erreur : Impossible de créer le répertoire $uploadDir");
        }
    }

    // Générer un nom unique pour le fichier
    $fileName = uniqid() . '-' . basename($file['name']);
    $filePath = $uploadDir . $fileName;

    // Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        die("Erreur : Impossible de téléverser le fichier.");
    }

    // Sauvegarder le document dans la base de données
    $stmt = $pdo->prepare("INSERT INTO documents (folder_id, user_id, file_name, file_path) VALUES (?, ?, ?, ?)");
    $stmt->execute([$folderId, $userId, $file['name'], $fileName]);

    // Si une signature est requise, générer le DocuSeal token
    if ($requireSignature && $userEmail) {
        $docuSealToken = generateDocuSealToken($userEmail, [$filePath]);

        return [
            'success' => true,
            'signatureRequired' => true,
            'docuSealToken' => $docuSealToken,
            'fileName' => $fileName,
        ];
    }

    return [
        'success' => true,
        'signatureRequired' => false,
        'message' => 'Fichier téléversé avec succès.',
    ];
}

function listDocumentsByFolder($folderId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, file_name, file_path, upload_date FROM documents WHERE folder_id = ?");
    $stmt->execute([$folderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function deleteDocument($documentId) {
    global $pdo;

    // Récupérer le chemin du fichier
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

    return false;
}

function generateDocuSealToken($integrationEmail, $documentUrls) {
    $apiKey = 'VOTRE_API_KEY'; // Remplacez par votre clé API DocuSeal
    $userEmail = 'admin@company.com'; // L'email de l'admin DocuSeal

    $payload = [
        'user_email' => $userEmail,
        'integration_email' => $integrationEmail,
        'external_id' => uniqid(),
        'name' => 'Signature Document',
        'document_urls' => $documentUrls,
    ];

    return JWT::encode($payload, $apiKey, 'HS256');
}
?>

