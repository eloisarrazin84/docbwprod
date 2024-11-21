<?php
require '../src/session_manager.php';
require '../src/db_connect.php';
require '../src/document_manager.php';
require '../src/folder_manager.php';

requireLogin(); // Vérifie si l'utilisateur est connecté

$pageTitle = "Mes Documents";

// Récupérer tous les dossiers avec leurs documents
$folders = getAllFoldersWithDocuments();

function getAllFoldersWithDocuments() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT f.id AS folder_id, f.name AS folder_name, d.id AS document_id, d.file_name, d.upload_date 
            FROM folders f
            LEFT JOIN documents d ON f.id = d.folder_id
            ORDER BY f.name ASC, d.upload_date DESC
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Organiser les documents par dossiers
        $folders = [];
        foreach ($results as $row) {
            $folderId = $row['folder_id'];
            if (!isset($folders[$folderId])) {
                $folders[$folderId] = [
                    'name' => $row['folder_name'],
                    'documents' => []
                ];
            }
            if (!empty($row['document_id'])) {
                $folders[$folderId]['documents'][] = [
                    'id' => $row['document_id'],
                    'name' => $row['file_name'],
                    'upload_date' => $row['upload_date']
                ];
            }
        }
        return $folders;
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
        return [];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .folder-header {
            background-color: #007bff;
            color: white;
            padding: 10px;
            margin-top: 20px;
            border-radius: 5px;
        }
        .table {
            margin-top: 10px;
        }
        .btn-download {
            background-color: #28a745;
            color: white;
        }
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center"><?= htmlspecialchars($pageTitle) ?></h1>

    <?php if (!empty($folders)): ?>
        <?php foreach ($folders as $folder): ?>
            <div class="folder-header">
                <h4><i class="fas fa-folder"></i> <?= htmlspecialchars($folder['name']) ?></h4>
            </div>
            <?php if (!empty($folder['documents'])): ?>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nom du Document</th>
                            <th>Date d'Upload</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($folder['documents'] as $document): ?>
                            <tr>
                                <td><i class="fas fa-file"></i> <?= htmlspecialchars($document['name']) ?></td>
                                <td><?= htmlspecialchars($document['upload_date']) ?></td>
                                <td>
                                    <a href="/uploads/<?= htmlspecialchars($document['name']) ?>" class="btn btn-download btn-sm" download>Télécharger</a>
                                    <form method="POST" action="delete_document.php" class="d-inline">
                                        <input type="hidden" name="document_id" value="<?= $document['id'] ?>">
                                        <button type="submit" class="btn btn-delete btn-sm">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Aucun document dans ce dossier.</p>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-center">Aucun document disponible.</p>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
