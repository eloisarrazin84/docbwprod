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

// Récupérer les recherches (si existantes)
$searchQuery = $_GET['search'] ?? '';

// Fonction pour récupérer les dossiers et leurs documents
function getAllFoldersWithDocuments($userId, $userRole, $searchQuery = '') {
    global $pdo;
    try {
        $query = "
            SELECT f.id AS folder_id, f.name AS folder_name, d.id AS document_id, d.file_name, d.upload_date 
            FROM folders f
            LEFT JOIN documents d ON f.id = d.folder_id
            WHERE f.user_id = :userId
        ";
        
        // Ajout du filtre de recherche
        if (!empty($searchQuery)) {
            $query .= " AND (d.file_name LIKE :search OR f.name LIKE :search)";
        }
        
        $query .= " ORDER BY f.name ASC, d.upload_date DESC";

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        if (!empty($searchQuery)) {
            $searchTerm = '%' . $searchQuery . '%';
            $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        }
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
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 15px;
            text-align: center;
            height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .folder-card:hover {
            transform: scale(1.03);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .folder-card i {
            font-size: 1.8rem;
        }

        .folder-card h5 {
            font-size: 1rem;
            font-weight: bold;
            margin: 0;
        }

        .document-list {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 15px;
            margin: 15px 0;
        }

        .document-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .document-item:last-child {
            border-bottom: none;
        }

        .document-item i {
            margin-right: 10px;
        }

        h1 {
            font-size: 1.8rem;
            font-weight: bold;
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }

        .accordion-header {
            margin-top: 10px;
        }

        .accordion-button {
            background-color: #007bff;
            color: white;
            font-size: 14px;
            border: none;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .accordion-button:not(.collapsed) {
            background-color: #0056b3;
        }

        .accordion-body {
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .search-bar {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <a href="dashboard.php" class="btn-back mb-4"><i class="fas fa-arrow-left"></i> Retour au tableau de bord</a>
    <h1 class="mb-4"><?= htmlspecialchars($pageTitle) ?></h1>

    <!-- Barre de recherche -->
    <form method="GET" class="search-bar">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Rechercher un document ou un dossier..." value="<?= htmlspecialchars($searchQuery) ?>">
            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Rechercher</button>
        </div>
    </form>

    <div class="row g-3">
        <?php foreach ($folders as $folderId => $folder): ?>
            <div class="col-md-4 col-sm-6">
                <div class="card folder-card">
                    <i class="fas fa-folder"></i>
                    <h5><?= htmlspecialchars($folder['name']) ?></h5>
                    <a href="#collapse<?= $folderId ?>" data-bs-toggle="collapse" class="btn btn-sm btn-light" aria-expanded="false">
                        Voir les documents
                    </a>
                </div>
            </div>
            <div class="col-12 collapse" id="collapse<?= $folderId ?>">
                <div class="document-list">
                    <h6 class="text-primary mb-3">Documents dans le dossier "<?= htmlspecialchars($folder['name']) ?>"</h6>
                    <?php if (!empty($folder['documents'])): ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Date d'ajout</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($folder['documents'] as $document): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($document['name']) ?></td>
                                        <td><?= htmlspecialchars($document['upload_date']) ?></td>
                                        <td>
                                            <a href="/uploads/<?= htmlspecialchars($document['name']) ?>" class="btn btn-success btn-sm" download>
                                                <i class="fas fa-download"></i> Télécharger
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
