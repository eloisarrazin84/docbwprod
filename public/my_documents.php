<?php
require '../src/session_manager.php';
require '../src/db_connect.php';
require '../src/document_manager.php';

requireLogin(); // Vérifie si l'utilisateur est connecté

// Définir le titre de la page
$pageTitle = "Mes Documents";

// Vérifier le rôle de l'utilisateur connecté
$userId = $_SESSION['user_id'];
$userRole = getUserRole();

// Récupérer les documents de l'utilisateur connecté
$documents = listDocumentsByUser($userId);
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
            background-color: #f8f9fa;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            flex-wrap: wrap;
        }
        .dashboard-header img {
            max-height: 50px;
        }
        .dashboard-header h1 {
            margin: 10px 0;
            flex-grow: 1;
            text-align: center;
        }
        .logout-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .logout-btn:hover {
            background-color: #c82333;
        }
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: center;
            }
            .logout-btn {
                margin-top: 10px;
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-header">
    <img src="https://images.squarespace-cdn.com/content/v1/56893684d8af102bf3e403f1/1571317878518-X3DEUWJNOFZKBZ4LKQ54/Logo_BeWitness_Full.png?format=1500w" alt="Logo Be Witness">
    <h1><?= htmlspecialchars($pageTitle) ?></h1>
    <form action="logout.php" method="post">
        <button type="submit" class="logout-btn">Se déconnecter</button>
    </form>
</div>

<div class="container mt-5">
    <h2 class="text-center mt-4">Mes Documents</h2>
    <?php if (!empty($documents)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Nom du Document</th>
                        <th>Date d'Upload</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $document): ?>
                        <tr>
                            <td><i class="fas fa-file-alt"></i> <?= htmlspecialchars($document['file_name']) ?></td>
                            <td><?= htmlspecialchars($document['upload_date']) ?></td>
                            <td>
                                <a href="/uploads/<?= htmlspecialchars($document['file_path']) ?>" download class="btn btn-success btn-sm">
                                    <i class="fas fa-download"></i> Télécharger
                                </a>
                                <a href="delete_document.php?document_id=<?= $document['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce document ?');">
                                    <i class="fas fa-trash"></i> Supprimer
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-center">Aucun document disponible.</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
