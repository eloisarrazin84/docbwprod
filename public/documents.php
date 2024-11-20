<?php
require '../src/db_connect.php';
require '../src/document_manager.php';
require '../src/session_manager.php';

requireLogin(); // Vérifie si l'utilisateur est connecté

// Récupérer le rôle de l'utilisateur connecté
$userRole = getUserRole();

// ID du dossier
$folderId = isset($_GET['folder_id']) ? $_GET['folder_id'] : null;
if (!$folderId) {
    header('Location: error_page.php?error=no_folder'); // Redirige vers une page d'erreur personnalisée
    exit();
}

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($userRole === 'admin') {
        if (isset($_POST['upload_document'])) {
            $requireSignature = isset($_POST['require_signature']);
            $userEmail = $_POST['user_email'] ?? null;

            // Validation du fichier
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $result = uploadDocument($folderId, $_FILES['file'], $requireSignature, $userEmail);

                if ($result['success']) {
                    echo "<div class='alert alert-success'>Fichier téléversé avec succès.</div>";
                } else {
                    echo "<div class='alert alert-danger'>" . htmlspecialchars($result['message']) . "</div>";
                }
            } else {
                echo "<div class='alert alert-danger'>Erreur : Fichier invalide ou téléversement échoué.</div>";
            }
        }
    } elseif (isset($_POST['sign_document'])) {
        $documentId = $_POST['document_id'];
        header("Location: signature.php?document_id=$documentId&folder_id=$folderId");
        exit();
    }
}

// Récupérer les documents du dossier
$documents = listDocumentsByFolder($folderId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents du Dossier</title>
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
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="header-container">
        <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Retour au Tableau de Bord</a>
        <h1>Documents du Dossier</h1>
    </div>

    <?php if ($userRole === 'admin'): ?>
        <div class="card mb-4">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="file" class="form-label">Sélectionner un fichier</label>
                        <input type="file" class="form-control" id="file" name="file" required>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="require_signature" name="require_signature">
                        <label class="form-check-label" for="require_signature">
                            Ce fichier nécessite une signature
                        </label>
                    </div>
                    <div class="mb-3">
                        <label for="user_email" class="form-label">E-mail de l'utilisateur pour la signature</label>
                        <input type="email" class="form-control" id="user_email" name="user_email">
                    </div>
                    <button type="submit" name="upload_document" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Téléverser
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-info text-white">
            <h2 class="card-title">Liste des Documents</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Date d'Upload</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($documents)): ?>
                            <?php foreach ($documents as $document): ?>
                                <tr>
                                    <td data-label="Nom"><?= htmlspecialchars($document['file_name']) ?></td>
                                    <td data-label="Date"><?= htmlspecialchars($document['upload_date']) ?></td>
                                    <td data-label="Actions">
                                        <a href="/uploads/<?= htmlspecialchars($document['file_path']) ?>" download class="btn btn-success btn-sm">Télécharger</a>
                                        <?php if (!$document['signed_by_user']): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="document_id" value="<?= $document['id'] ?>">
                                                <button type="submit" name="sign_document" class="btn btn-primary btn-sm">Signer</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">Aucun document trouvé.</td>
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

