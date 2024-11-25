<?php
require '../src/db_connect.php';
require '../src/expense_manager.php';
require '../src/session_manager.php';

requireAdmin(); // Vérifie si l'utilisateur est administrateur

// Récupérer toutes les notes de frais
$expenses = listAllExpenses();

// Gestion des actions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'], $_POST['expense_id'], $_POST['status'])) {
        $expenseId = intval($_POST['expense_id']);
        $status = $_POST['status'];
        if (updateExpenseStatus($expenseId, $status)) {
            $success = "Le statut de la note de frais #{$expenseId} a été mis à jour.";
        } else {
            $error = "Erreur lors de la mise à jour du statut.";
        }
    }

    if (isset($_POST['delete_expense'], $_POST['expense_id'])) {
        $expenseId = intval($_POST['expense_id']);
        if (deleteExpense($expenseId)) {
            $success = "La note de frais #{$expenseId} a été supprimée avec succès.";
            // Recharger les notes après suppression
            $expenses = listAllExpenses();
        } else {
            $error = "Erreur lors de la suppression de la note de frais.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Notes de Frais</title>
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
    <h1 class="text-center mb-4">Gestion des Notes de Frais</h1>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Liste des notes de frais -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h2 class="card-title">Liste des Notes de Frais</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Description</th>
                            <th>Montant (€)</th>
                            <th>Catégorie</th>
                            <th>Statut</th>
                            <th>Date Soumise</th>
                            <th>Utilisateur</th>
                            <th>Justificatif</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($expenses)): ?>
                            <?php foreach ($expenses as $expense): ?>
                                <tr>
                                    <td><?= htmlspecialchars($expense['id'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($expense['description'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($expense['amount'] ?? '0.00') ?></td>
                                    <td><?= htmlspecialchars($expense['category'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($expense['status'] ?? 'en attente') ?></td>
                                    <td><?= htmlspecialchars($expense['date_submitted'] ?? 'N/A') ?></td>
                                    <td>
                                        <?= htmlspecialchars($expense['user_name'] ?? 'Inconnu') ?> 
                                        (<?= htmlspecialchars($expense['user_email'] ?? 'Inconnu') ?>)
                                    </td>
                                    <td>
                                        <?php if (!empty($expense['receipt_path'])): ?>
                                            <a href="/uploads/receipts/<?= htmlspecialchars($expense['receipt_path']) ?>" target="_blank" class="btn btn-sm btn-info">
                                                Voir
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Aucun</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="expense_id" value="<?= htmlspecialchars($expense['id']) ?>">
                                            <select name="status" class="form-select form-select-sm d-inline-block" style="width: auto;">
                                                <option value="en attente" <?= $expense['status'] === 'en attente' ? 'selected' : '' ?>>En attente</option>
                                                <option value="approuvée" <?= $expense['status'] === 'approuvée' ? 'selected' : '' ?>>Approuvée</option>
                                                <option value="rejetée" <?= $expense['status'] === 'rejetée' ? 'selected' : '' ?>>Rejetée</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-sm btn-primary">Mettre à jour</button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="expense_id" value="<?= htmlspecialchars($expense['id']) ?>">
                                            <button type="submit" name="delete_expense" class="btn btn-sm btn-danger">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">Aucune note de frais trouvée.</td>
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
