<?php
require 'db_connect.php';

// Fonction pour logger les erreurs
function logError($message) {
    file_put_contents('/var/log/document_manager.log', date('Y-m-d H:i:s') . " - $message\n", FILE_APPEND);
}

// Fonction pour uploader un document
function uploadDocument($folderId, $file) {
    global $pdo;

    // Types MIME autorisés
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
    $maxFileSize = 5 * 1024 * 1024; // Taille max : 5 Mo

    // Vérifier le type MIME
    if (!in_array($file['type'], $allowedTypes)) {
        die("Erreur : Type de fichier non autorisé.");
    }

    // Vérifier la taille
    if ($file['size'] > $maxFileSize) {
        die("Erreur : Le fichier est trop volumineux.");
    }

    // Dossier de stockage
    $uploadDir = '/var/www/uploads/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            logError("Erreur : Impossible de créer le répertoire $uploadDir");
            die("Erreur : Impossible de créer le répertoire $uploadDir");
        }
    }

    // Générer un nom unique pour le fichier
    $fileName = uniqid() . '-' . basename($file['name']);
    $filePath = $uploadDir . $fileName;

    // Déplacer le fichier
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        // Sauvegarder dans la base de données
        try {
            $stmt = $pdo->prepare("INSERT INTO documents (folder_id, file_name, file_path, upload_date) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$folderId, $file['name'], $fileName]);
            return true;
        } catch (PDOException $e) {
            logError("Erreur PDO lors de l'insertion du document : " . $e->getMessage());
            return false;
        }
    } else {
        logError("Erreur : Impossible de téléverser le fichier.");
        return false;
    }
}

// Fonction pour lister les documents d'un dossier
function listDocumentsByFolder($folderId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, file_name, file_path, upload_date FROM documents WHERE folder_id = ?");
        $stmt->execute([$folderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logError("Erreur PDO lors de la récupération des documents : " . $e->getMessage());
        return [];
    }
}

// Fonction pour supprimer un document
function deleteDocument($documentId) {
    global $pdo;

    try {
        // Récupérer le chemin du fichier
        $stmt = $pdo->prepare("SELECT file_path FROM documents WHERE id = ?");
        $stmt->execute([$documentId]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($document) {
            $filePath = '/var/www/uploads/' . $document['file_path'];
            if (file_exists($filePath)) {
                if (!unlink($filePath)) {
                    logError("Erreur : Impossible de supprimer le fichier $filePath");
                    return false;
                }
            } else {
                logError("Fichier introuvable : $filePath");
            }

            // Supprimer l'entrée de la base de données
            $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
            $stmt->execute([$documentId]);
            return true;
        }
        return false;
    } catch (PDOException $e) {
        logError("Erreur PDO lors de la suppression du document : " . $e->getMessage());
        return false;
    }
}
?>
