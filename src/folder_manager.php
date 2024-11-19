<?php
require 'db_connect.php';

function createFolder($userId, $folderName) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO folders (user_id, name) VALUES (?, ?)");
    $stmt->execute([$userId, $folderName]);
}

function listFoldersByUser($userId = null) {
    global $pdo;
    if ($userId) {
        // Filtrer par utilisateur
        $stmt = $pdo->prepare("
            SELECT folders.id, folders.name, folders.created_at, users.name AS user_name, users.email AS user_email 
            FROM folders 
            INNER JOIN users ON folders.user_id = users.id 
            WHERE folders.user_id = ?
        ");
        $stmt->execute([$userId]);
    } else {
        // Afficher tous les dossiers
        $stmt = $pdo->query("
            SELECT folders.id, folders.name, folders.created_at, users.name AS user_name, users.email AS user_email 
            FROM folders 
            INNER JOIN users ON folders.user_id = users.id
        ");
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateFolder($folderId, $newName) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE folders SET name = ? WHERE id = ?");
    $stmt->execute([$newName, $folderId]);
}

function deleteFolder($folderId) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM folders WHERE id = ?");
    $stmt->execute([$folderId]);
}
?>
