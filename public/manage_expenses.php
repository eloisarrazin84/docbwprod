<?php
require '../src/session_manager.php';
require '../src/db_connect.php';
require '../src/expense_manager.php';

requireAdmin();

$expenses = listAllExpenses();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $expenseId = $_POST['expense_id'];
    $status = $_POST['status'];

    if (updateExpenseStatus($expenseId, $status)) {
        echo "<div class='alert alert-success'>Statut mis à jour avec succès.</div>";
    } else {
        echo "<div class='alert alert-danger'>Erreur lors de la mise à jour du statut.</div>";
    }

    $expenses = listAllExpenses(); // Rafraîchir la liste
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Notes de Frais</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1>Gestion des Notes de Frais</h1>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Utilisateur</th>
                <th>Description</th>
                <th>Montant</th>
                <th>Catégorie</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($expenses as $expense): ?>
                <tr>
                    <td><?= htmlspecialchars($expense['user_id']) ?></td>
                    <td><?= htmlspecialchars($expense['description']) ?></td>
                    <td><?= htmlspecialchars($expense['amount']) ?> €</td>
                    <td><?= htmlspecialchars($expense['category']) ?></td>
                    <td><?= htmlspecialchars($expense['status']) ?></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="expense_id" value="<?= $expense['id'] ?>">
                            <select name="status" class="form-control form-control-sm">
                                <option value="en attente" <?= $expense['status'] === 'en attente' ? 'selected' : '' ?>>En attente</option>
                                <option value="approuvé" <?= $expense['status'] === 'approuvé' ? 'selected' : '' ?>>Approuvé</option>
                                <option value="rejeté" <?= $expense['status'] === 'rejeté' ? 'selected' : '' ?>>Rejeté</option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-sm btn-primary mt-2">Mettre à jour</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
