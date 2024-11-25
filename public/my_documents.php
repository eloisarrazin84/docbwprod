<?php
require '../src/session_manager.php';
require '../src/db_connect.php';
require '../src/document_manager.php';
require '../src/folder_manager.php';

requireLogin(); // Vérifie si l'utilisateur est connecté

$pageTitle = "Mes Documents";

// Récupérer l'utilisateur connecté
$userId = $_SESSION['user_id'];
$userRole = getUserRole(); // Récupère le rôle de l'utilisateur connecté

// Récupérer tous les dossiers avec leurs documents pour l'utilisateur connecté
$folders = getAllFoldersWithDocuments($userId, $userRole);

// Fonction pour récupérer les dossiers et leurs documents
function getAllFoldersWithDocuments($userId, $userRole) {
    global $pdo;
    try {
        $query = "
            SELECT f.id AS folder_id, f.name AS folder_name, d.id AS document_id, d.file_name, d.upload_date 
            FROM folders f
            LEFT JOIN documents d ON f.id = d.folder_id
            WHERE f.user_id = :userId
            ORDER BY f.name ASC, d.upload_date DESC
        ";

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
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

        .accordion-button {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            border-radius: 5px;
            margin-bottom: 10px;
            transition: background-color 0.3s;
            padding: 10px;
        }

        .accordion-button:hover {
            background-color: #0056b3;
        }

        .accordion-button:not(.collapsed) {
            background-color: #0056b3;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        .accordion-body {
            padding: 15px 20px;
            background-color: #f0f0f0;
            border-radius: 5px;
        }

        .btn-download {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .btn-download:hover {
            background-color: #218838;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #e9ecef;
        }

        .table-hover tbody tr:hover {
            background-color: #d6e4f0;
        }

        .icon-file {
            font-size: 1.2rem;
            color: #007bff;
        }

        h1 {
            font-size: 2rem;
            font-weight: bold;
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <a href="dashboard.php" class="btn-back mb-3"><i class="fas fa-arrow-left"></i> Retour au tableau de bord</a>

    <h1>Mes Documents</h1>

    <?php if (!empty($folders)): ?>
        <div class="accordion" id="documentsAccordion">
            <?php foreach ($folders as $folderId => $folder): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?= $folderId ?>">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $folderId ?>" aria-expanded="false" aria-controls="collapse<?= $folderId ?>">
                            <i class="fas fa-folder icon-file"></i> <?= htmlspecialchars($folder['name']) ?>
                        </button>
                    </h2>
                    <div id="collapse<?= $folderId ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $folderId ?>" data-bs-parent="#documentsAccordion">
                        <div class="accordion-body">
                            <?php if (!empty($folder['documents'])): ?>
                                <table class="table table-striped table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th><i class="fas fa-file"></i> Nom du Document</th>
                                            <th>Date d'Upload</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($folder['documents'] as $document): ?>
                                            <tr>
                                                <td><i class="fas fa-file-pdf"></i> <?= htmlspecialchars($document['name']) ?></td>
                                                <td><?= htmlspecialchars($document['upload_date']) ?></td>
                                                <td>
                                                    <a href="/uploads/<?= htmlspecialchars($document['name']) ?>" class="btn btn-download btn-sm" download>
                                                        <i class="fas fa-download"></i> Télécharger
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="text-muted">Aucun document dans ce dossier.</p>
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
