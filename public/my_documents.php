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
            border-radius: 10px;
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 15px;
            text-align: center;
            height: 180px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .folder-card:hover {
            transform: scale(1.03);
            box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.2);
        }

        .folder-card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .folder-card h5 {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .card-actions a {
            display: inline-block;
            text-decoration: none;
            font-size: 14px;
            color: #fff;
            background-color: rgba(0, 0, 0, 0.7);
            padding: 5px 10px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .card-actions a:hover {
            background-color: rgba(0, 0, 0, 0.9);
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
    <a href="dashboard.php" class="btn-back mb-4"><i class="fas fa-arrow-left"></i> Retour au tableau de bord</a>
    <h1 class="mb-4">Mes Documents</h1>

    <div class="row">
        <?php foreach ($folders as $folderId => $folder): ?>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card folder-card">
                    <i class="fas fa-folder"></i>
                    <h5><?= htmlspecialchars($folder['name']) ?></h5>
                    <div class="card-actions">
                        <a href="#collapse<?= $folderId ?>" data-bs-toggle="collapse" aria-expanded="false">
                            <i class="fas fa-eye"></i> Voir les documents
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-12 collapse" id="collapse<?= $folderId ?>">
                <div class="document-list mt-3 p-3 border rounded bg-light">
                    <h6 class="text-primary mb-3">Documents dans le dossier "<?= htmlspecialchars($folder['name']) ?>"</h6>
                    <?php if (!empty($folder['documents'])): ?>
                        <?php foreach ($folder['documents'] as $document): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
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

