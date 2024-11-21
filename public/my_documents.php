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
    <title>Mes Documents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .btn-back {
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            padding: 10px 20px;
            transition: background-color 0.3s ease;
            font-size: 14px;
        }
        .btn-back:hover {
            background-color: #0056b3;
        }
        .card {
            border-radius: 10px;
            border: none;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0px 6px 10px rgba(0, 0, 0, 0.15);
        }
        .accordion-button {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            border-radius: 5px;
            margin-bottom: 10px;
            transition: background-color 0.3s;
        }
        .accordion-button:hover {
            background-color: #0056b3;
        }
        .accordion-item {
            border: none;
        }
        .accordion-button:not(.collapsed) {
            background-color: #0056b3;
        }
        .accordion-body {
            padding: 10px 20px;
            background-color: #e9ecef;
            border-radius: 5px;
        }
        .btn-download {
            background-color: #28a745;
            color: white;
        }
        .btn-download:hover {
            background-color: #218838;
        }
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            background-color: #c82333;
        }
        h1 {
            font-size: 1.8rem;
            font-weight: bold;
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <!-- Bouton retour -->
    <a href="dashboard.php" class="btn-back mb-3"><i class="fas fa-arrow-left"></i> Retour au tableau de bord</a>

    <!-- Titre principal -->
    <h1>Mes Documents</h1>

    <?php if (!empty($folders)): ?>
        <!-- Liste des dossiers avec documents -->
        <div class="accordion" id="documentsAccordion">
            <?php foreach ($folders as $folderId => $folder): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?= $folderId ?>">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $folderId ?>" aria-expanded="false" aria-controls="collapse<?= $folderId ?>">
                            <i class="fas fa-folder"></i> <?= htmlspecialchars($folder['name']) ?>
                        </button>
                    </h2>
                    <div id="collapse<?= $folderId ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $folderId ?>" data-bs-parent="#documentsAccordion">
                        <div class="accordion-body">
                            <?php if (!empty($folder['documents'])): ?>
                                <table class="table table-striped table-hover">
                                    <thead class="table-light">
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
                                                    <a href="/uploads/<?= htmlspecialchars($document['name']) ?>" class="btn btn-download btn-sm" download><i class="fas fa-download"></i> Télécharger</a>
                                                    <form method="POST" action="delete_document.php" class="d-inline">
                                                        <input type="hidden" name="document_id" value="<?= $document['id'] ?>">
                                                        <button type="submit" class="btn btn-delete btn-sm"><i class="fas fa-trash-alt"></i> Supprimer</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>Aucun document dans ce dossier.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-center">Aucun document disponible.</p>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
