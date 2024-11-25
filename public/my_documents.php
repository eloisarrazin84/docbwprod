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
            background-color: #f5f7fa;
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

        .folder-card {
            border: none;
            border-radius: 15px;
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }

        .folder-card:hover {
            transform: scale(1.05);
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.2);
        }

        .folder-card i {
            font-size: 2.5rem;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .card-actions {
            margin-top: 10px;
        }

        .card-actions a {
            text-decoration: none;
            color: white;
            font-size: 14px;
            margin-right: 10px;
        }

        .card-actions a:hover {
            text-decoration: underline;
        }

        .document-list {
            margin-top: 20px;
            background-color: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        .document-list h4 {
            font-size: 1.2rem;
            color: #333;
        }

        .btn-download {
            background-color: #28a745;
            color: white;
            font-size: 14px;
            border-radius: 5px;
        }

        .btn-download:hover {
            background-color: #218838;
        }

        .icon-folder {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .document-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
        }

        .document-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <a href="dashboard.php" class="btn-back mb-4"><i class="fas fa-arrow-left"></i> Retour au tableau de bord</a>
    <h1 class="text-center mb-4">Mes Documents</h1>

    <div class="row">
        <?php foreach ($folders as $folderId => $folder): ?>
            <div class="col-md-4 col-sm-6 mb-4">
                <div class="card folder-card text-center p-3">
                    <i class="fas fa-folder icon-folder"></i>
                    <h5 class="card-title"><?= htmlspecialchars($folder['name']) ?></h5>
                    <div class="card-actions">
                        <a href="#collapse<?= $folderId ?>" data-bs-toggle="collapse" aria-expanded="false" class="btn btn-light btn-sm">
                            <i class="fas fa-eye"></i> Voir les documents
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-12 collapse" id="collapse<?= $folderId ?>">
                <div class="document-list">
                    <h4>Documents dans le dossier "<?= htmlspecialchars($folder['name']) ?>"</h4>
                    <?php if (!empty($folder['documents'])): ?>
                        <?php foreach ($folder['documents'] as $document): ?>
                            <div class="document-item">
                                <span><i class="fas fa-file-alt text-muted"></i> <?= htmlspecialchars($document['name']) ?></span>
                                <a href="/uploads/<?= htmlspecialchars($document['name']) ?>" class="btn btn-download btn-sm" download>
                                    <i class="fas fa-download"></i> Télécharger
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">Aucun document disponible.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
