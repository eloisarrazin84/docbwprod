<?php
require '../src/db_connect.php';
require '../src/document_manager.php';
require '../src/session_manager.php';

requireLogin(); // Vérifie si l'utilisateur est connecté

// Récupérer l'ID du dossier
$folderId = $_GET['folder_id'] ?? null;
if (!$folderId) {
    die("Erreur : Aucun dossier sélectionné.");
}

// Récupérer les documents associés au dossier
$documents = listDocumentsByFolder($folderId);
if (empty($documents)) {
    die("Aucun document trouvé dans ce dossier.");
}

// Nom de l'archive ZIP
$zipFileName = "dossier_$folderId.zip";

// Créer un fichier ZIP temporaire
$zip = new ZipArchive();
$tmpFile = tempnam(sys_get_temp_dir(), 'zip');
if ($zip->open($tmpFile, ZipArchive::CREATE) !== true) {
    die("Erreur : Impossible de créer l'archive ZIP.");
}

// Ajouter chaque document au fichier ZIP
foreach ($documents as $document) {
    $filePath = '../uploads/' . $document['file_path'];
    if (file_exists($filePath)) {
        $zip->addFile($filePath, $document['file_name']);
    }
}
$zip->close();

// Envoyer le fichier ZIP au navigateur pour téléchargement
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
header('Content-Length: ' . filesize($tmpFile));

// Lire le fichier temporaire et le transmettre au navigateur
readfile($tmpFile);

// Supprimer le fichier temporaire
unlink($tmpFile);
exit;
?>
