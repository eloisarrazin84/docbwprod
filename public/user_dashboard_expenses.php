<?php
require '../src/db_connect.php';
require '../src/expense_manager.php';
require '../src/session_manager.php';

requireLogin(); // Vérifie si l'utilisateur est connecté

$userId = $_SESSION['user_id'];
$expenses = listExpensesByUser($userId);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Notes de Frais</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
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
        .btn-create-expense {
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            padding: 10px 20px;
            transition: background-color 0.3s ease;
            display: inline-block;
            margin-bottom: 20px;
        }
        .btn-create-expense:hover {
            background-color: #218838;
        }
        .table-responsive {
            margin-top: 20px;
        }
        h1 {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <!-- Titre et bouton de création -->
    <h1>Mes Notes de Frais</h1>
    <div class="text-center">
        <a href="create_expense.php" class="btn-create-expense"><i class="fas fa-plus"></i> Créer une note de frais</a>
    </div>

    <!-- Tableau des notes de frais -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Description</th>
                            <th>Montant (€)</th>
                            <th>Catégorie</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($expenses)): ?>
                            <?php foreach ($expenses as $expense): ?>
                                <tr>
                                    <td><?= htmlspecialchars($expense['description']) ?></td>
                                    <td><?= htmlspecialchars($expense['amount']) ?></td>
                                    <td><?= htmlspecialchars($expense['category']) ?></td>
                                    <td>
                                        <span class="badge 
                                            <?= $expense['status'] === 'brouillon' ? 'bg-warning text-dark' : 
                                                ($expense['status'] === 'approuvée' ? 'bg-success' : 'bg-danger') ?>">
                                            <?= htmlspecialchars($expense['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($expense['status'] === 'brouillon'): ?>
                                            <a href="edit_expense.php?id=<?= $expense['id'] ?>" class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit"></i> Modifier
                                            </a>
                                            <a href="delete_expense.php?id=<?= $expense['id'] ?>" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> Supprimer
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Non modifiable</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Aucune note de frais trouvée.</td>
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
