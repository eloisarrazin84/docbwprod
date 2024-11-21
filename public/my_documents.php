<?php
require '../src/session_manager.php';
require '../src/db_connect.php';
require '../src/document_manager.php';

requireLogin(); // Vérifie si l'utilisateur est connecté

// Vérifie que l'utilisateur est un admin
if (getUserRole() !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

// Récupérer les documents assignés à l'utilisateur connecté
$userId = $_SESSION['user_id'];
$documents = listDocumentsByUser($userId); // Fonction à implémenter pour récupérer les documents liés à un utilisateur
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Documents</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }
        .card:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .table-responsive {
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center">Mes Documents</h1>
    <p class="text-center">Liste des documents qui vous sont assignés.</p>

    <?php if (!empty($documents)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Nom du Document</th>
                        <th>Date de Téléversement</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $document): ?>
                        <tr>
                            <td><i class="fas fa-file-alt"></i> <?= htmlspecialchars($document['file_name']) ?></td>
                            <td><?= htmlspecialchars($document['upload_date']) ?></td>
                            <td>
                                <a href="/uploads/<?= htmlspecialchars($document['file_path']) ?>" class="btn btn-success btn-sm" download>
                                    <i class="fas fa-download"></i> Télécharger
                                </a>
                                <form method="POST" action="delete_document.php" class="d-inline">
                                    <input type="hidden" name="document_id" value="<?= $document['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-center">Aucun document disponible pour le moment.</p>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
