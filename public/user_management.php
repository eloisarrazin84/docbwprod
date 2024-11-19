<?php
require '../src/db_connect.php';
require '../src/user_manager.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_user'])) {
    createUser($_POST['identifier'], $_POST['name'], $_POST['email'], $_POST['password'], $_POST['role']);
}

$users = listUsers();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs</title>
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
    <a href="dashboard.php" class="btn-back mb-3"><i class="fas fa-arrow-left"></i> Retour au Tableau de Bord</a>

    <!-- Titre -->
    <h1 class="text-center mb-4">Gestion des Utilisateurs</h1>

    <!-- Formulaire pour ajouter un utilisateur -->
    <div class="card mb-4">
        <div class="card-body">
            <h2>Créer un Nouvel Utilisateur</h2>
            <form method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label">Nom</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Nom de l'utilisateur" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Email de l'utilisateur" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Mot de passe" required>
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Rôle</label>
                    <select class="form-control" id="role" name="role">
                        <option value="user">Utilisateur</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" name="create_user" class="btn btn-primary"><i class="fas fa-user-plus"></i> Créer</button>
            </form>
        </div>
    </div>

    <!-- Liste des utilisateurs -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h2 class="card-title">Liste des Utilisateurs</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>E-mail</th>
                            <th>Rôle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td data-label="ID"><?= htmlspecialchars($user['id']) ?></td>
                                    <td data-label="Nom"><?= htmlspecialchars($user['name']) ?></td>
                                    <td data-label="E-mail"><?= htmlspecialchars($user['email']) ?></td>
                                    <td data-label="Rôle"><?= htmlspecialchars($user['role']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">Aucun utilisateur trouvé.</td>
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
