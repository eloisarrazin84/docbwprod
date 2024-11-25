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
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center">Mes Notes de Frais</h1>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Description</th>
                <th>Montant (€)</th>
                <th>Catégorie</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($expenses as $expense): ?>
                <tr>
                    <td><?= htmlspecialchars($expense['description']) ?></td>
                    <td><?= htmlspecialchars($expense['amount']) ?></td>
                    <td><?= htmlspecialchars($expense['category']) ?></td>
                    <td><?= htmlspecialchars($expense['status']) ?></td>
                    <td>
                        <?php if ($expense['status'] === 'brouillon'): ?>
                            <a href="edit_expense.php?id=<?= $expense['id'] ?>" class="btn btn-warning btn-sm">Modifier</a>
                            <a href="delete_expense.php?id=<?= $expense['id'] ?>" class="btn btn-danger btn-sm">Supprimer</a>
                        <?php else: ?>
                            <span class="text-muted">Non modifiable</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
