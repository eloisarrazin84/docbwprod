<?php
require 'db_connect.php';
require 'vendor/autoload.php'; // Pour les bibliothèques nécessaires

use setasign\Fpdi\Fpdi;

// Fonction pour téléverser un document
function uploadDocument($folderId, $file, $requireSignature = false, $userEmail = null) {
    global $pdo;

    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        return ['success' => false, 'message' => 'Erreur : Utilisateur non connecté.'];
    }

    // Récupérer l'ID utilisateur depuis la session
    $userId = $_SESSION['user_id'];

    // Dossier de stockage
    $uploadDir = '/var/www/uploads/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            return ['success' => false, 'message' => "Erreur : Impossible de créer le répertoire $uploadDir"];
        }
    }

    // Générer un nom unique pour le fichier
    $fileName = uniqid() . '-' . basename($file['name']);
    $filePath = $uploadDir . $fileName;

    // Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        error_log("Erreur : Impossible de téléverser le fichier. Détails : " . print_r(error_get_last(), true));
        return ['success' => false, 'message' => 'Erreur : Impossible de téléverser le fichier.'];
    }

    // Initialiser le statut de signature
    $signedByUser = 0;

    // Sauvegarder le document dans la base de données
    try {
        $stmt = $pdo->prepare("INSERT INTO documents (folder_id, user_id, file_name, file_path, signed_by_user) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$folderId, $userId, $file['name'], $fileName, $signedByUser]);
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur : Impossible de sauvegarder le fichier dans la base de données.'];
    }

    return [
        'success' => true,
        'signatureRequired' => $requireSignature,
        'message' => 'Fichier téléversé avec succès.',
    ];
}

// Fonction pour lister les documents par dossier
function listDocumentsByFolder($folderId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, file_name, file_path, upload_date, signed_by_user FROM documents WHERE folder_id = ?");
        $stmt->execute([$folderId]);
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

// Fonction pour marquer un document comme signé
function markDocumentAsSigned($documentId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE documents SET signed_by_user = 1 WHERE id = ?");
        $stmt->execute([$documentId]);
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
    }
}

// Fonction pour ajouter une signature au document PDF
function addSignatureToDocument($filePath, $signatureData) {
    $uploadDir = '/var/www/uploads/';
    $fullFilePath = $uploadDir . $filePath;

    // Vérifier si le fichier existe
    if (!file_exists($fullFilePath)) {
        throw new Exception("Erreur : Le fichier $filePath n'existe pas.");
    }

    // Créer une nouvelle instance de FPDI
    $pdf = new Fpdi();
    $pageCount = $pdf->setSourceFile($fullFilePath);

    // Importer toutes les pages du PDF
    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $pdf->AddPage();
        $templateId = $pdf->importPage($pageNo);
        $pdf->useTemplate($templateId);
    }

    // Créer une image temporaire pour la signature
    $signatureImagePath = $uploadDir . 'signatures/' . uniqid() . '.png';
    if (!is_dir($uploadDir . 'signatures/')) {
        mkdir($uploadDir . 'signatures/', 0777, true);
    }
    $signatureData = str_replace('data:image/png;base64,', '', $signatureData);
    $signatureData = base64_decode($signatureData);
    file_put_contents($signatureImagePath, $signatureData);

    // Ajouter la signature à la dernière page
    $pdf->Image($signatureImagePath, 50, 200, 100, 30);

    // Enregistrer le nouveau fichier
    $signedFileName = str_replace('.pdf', '-signed.pdf', $filePath);
    $signedFilePath = $uploadDir . $signedFileName;
    $pdf->Output($signedFilePath, 'F');

    // Supprimer le fichier temporaire de signature
    unlink($signatureImagePath);

    // Mettre à jour le fichier dans la base de données
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE documents SET file_path = ? WHERE file_path = ?");
        $stmt->execute([$signedFileName, $filePath]);
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
        return false;
    }

    return true;
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
