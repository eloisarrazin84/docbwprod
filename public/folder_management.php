<?php
require '../src/db_connect.php';
require '../src/folder_manager.php';
require '../src/user_manager.php';

// Récupérer tous les utilisateurs pour la liste déroulante
$users = listUsers();

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_folder'])) {
        createFolder($_POST['user_id'], $_POST['folder_name']);
    } elseif (isset($_POST['update_folder'])) {
        updateFolder($_POST['folder_id'], $_POST['new_name']);
    } elseif (isset($_POST['delete_folder'])) {
        deleteFolder($_POST['folder_id']);
    }
}

// Récupérer les dossiers
$folders = isset($_POST['filter_user_id']) ? listFoldersByUser($_POST['filter_user_id']) : listFoldersByUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Dossiers</title>
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
        .table-responsive {
            margin-top: 20px;
        }
        h1, h2 {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
        }
        @media (max-width: 768px) {
            .table thead {
                display: none;
            }
            .table tbody td {
                display: block;
                width: 100%;
                text-align: right;
                border-bottom: 1px solid #dee2e6;
            }
            .table tbody td:before {
                content: attr(data-label);
                float: left;
                font-weight: bold;
                text-transform: capitalize;
            }
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <!-- Retour au tableau de bord -->
    <a href="folder_management.php" class="btn-back mb-3"><i class="fas fa-arrow-left"></i> Retour au Tableau de Bord</a>

    <!-- Titre -->
    <h1 class="text-center mb-4">Gestion des Dossiers</h1>

    <!-- Formulaire pour ajouter un dossier -->
    <div class="card mb-4">
        <div class="card-body">
            <h2>Créer un Nouveau Dossier</h2>
            <form method="POST">
                <div class="mb-3">
                    <label for="folder_name" class="form-label">Nom du dossier</label>
                    <input type="text" class="form-control" id="folder_name" name="folder_name" placeholder="Nom du dossier" required>
                </div>
                <div class="mb-3">
                    <label for="user_id" class="form-label">Utilisateur</label>
                    <select class="form-control" id="user_id" name="user_id" required>
                        <option value="">Sélectionner un utilisateur</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= htmlspecialchars($user['id']) ?>"><?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="create_folder" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Créer</button>
            </form>
        </div>
    </div>

    <!-- Formulaire pour filtrer les dossiers -->
    <div class="card mb-4">
        <div class="card-body">
            <h2>Filtrer les Dossiers</h2>
            <form method="POST">
                <div class="mb-3">
                    <label for="filter_user_id" class="form-label">Filtrer par utilisateur</label>
                    <select class="form-control" id="filter_user_id" name="filter_user_id">
                        <option value="">Afficher tous les dossiers</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= htmlspecialchars($user['id']) ?>" <?= isset($_POST['filter_user_id']) && $_POST['filter_user_id'] == $user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i> Filtrer</button>
            </form>
        </div>
    </div>

    <!-- Liste des dossiers -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h2 class="card-title">Liste des Dossiers</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Utilisateur</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($folders)): ?>
                            <?php foreach ($folders as $folder): ?>
                                <tr>
                                    <td data-label="ID"><?= htmlspecialchars($folder['id']) ?></td>
                                    <td data-label="Nom"><?= htmlspecialchars($folder['name']) ?></td>
                                    <td data-label="Utilisateur"><?= htmlspecialchars($folder['user_name']) ?> (<?= htmlspecialchars($folder['user_email']) ?>)</td>
                                    <td data-label="Actions">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="folder_id" value="<?= $folder['id'] ?>">
                                            <input type="text" name="new_name" placeholder="Nouveau nom" required class="form-control form-control-sm d-inline-block" style="width: 150px;">
                                            <button type="submit" name="update_folder" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                                        </form>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="folder_id" value="<?= $folder['id'] ?>">
                                            <button type="submit" name="delete_folder" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                        </form>
                                        <a href="documents.php?folder_id=<?= $folder['id'] ?>" class="btn btn-info btn-sm"><i class="fas fa-folder-open"></i> Gérer</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">Aucun dossier trouvé.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
