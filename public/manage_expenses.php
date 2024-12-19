<?php
require '../src/db_connect.php';
require '../src/expense_manager.php';
require '../src/session_manager.php';

requireAdmin(); // Vérifie si l'utilisateur est administrateur

// Récupérer les filtres
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';
$userFilter = $_GET['user'] ?? '';

// Récupérer la liste des utilisateurs
function listAllUsers() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT id, name, email FROM users ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
        return [];
    }
}

$users = listAllUsers();

// Récupérer toutes les notes de frais avec les filtres
$expenses = listSubmittedExpenses($categoryFilter, $statusFilter, $dateFilter, $userFilter);

// Gestion des actions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'], $_POST['expense_id'], $_POST['status'])) {
        $expenseId = intval($_POST['expense_id']);
        $status = $_POST['status'];
        if (updateExpenseStatus($expenseId, $status)) {
            $success = "Le statut de la note de frais #{$expenseId} a été mis à jour.";
            $expenses = listSubmittedExpenses($categoryFilter, $statusFilter, $dateFilter, $userFilter);
        } else {
            $error = "Erreur lors de la mise à jour du statut.";
        }
    }

    if (isset($_POST['delete_expense'], $_POST['expense_id'])) {
        $expenseId = intval($_POST['expense_id']);
        if (deleteExpense($expenseId)) {
            $success = "La note de frais #{$expenseId} a été supprimée avec succès.";
            $expenses = listSubmittedExpenses($categoryFilter, $statusFilter, $dateFilter, $userFilter);
        } else {
            $error = "Erreur lors de la suppression de la note de frais.";
        }
    }
}

// Fonction pour récupérer uniquement les notes de frais soumises ou approuvées avec des filtres
function listSubmittedExpenses($category = '', $status = '', $date = '', $user = '') {
    global $pdo;
    try {
        $query = "
            SELECT e.*, u.name AS user_name, u.email AS user_email 
            FROM expense_notes e 
            JOIN users u ON e.user_id = u.id 
            WHERE e.status != 'brouillon'
        ";
        $params = [];

        if (!empty($category)) {
            $query .= " AND e.category = ?";
            $params[] = $category;
        }

        if (!empty($status)) {
            $query .= " AND e.status = ?";
            $params[] = $status;
        }

        if (!empty($date)) {
            $query .= " AND e.expense_date = ?";
            $params[] = $date;
        }

        if (!empty($user)) {
            $query .= " AND e.user_id = ?";
            $params[] = $user;
        }

        $query .= " ORDER BY u.name ASC, e.date_submitted DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            overflow: hidden;
        }

        .card-header {
            background-color: #17a2b8;
            color: white;
            font-size: 1.25rem;
            text-align: center;
            padding: 0.8rem;
            font-weight: bold;
        }

        .badge-status {
            display: inline-block;
            padding: 0.4rem 0.7rem;
            font-size: 0.9rem;
            font-weight: bold;
            border-radius: 12px;
            color: white;
        }

        .badge-soumise {
            background-color: #007bff;
        }

        .badge-approuvée {
            background-color: #28a745;
        }

        .badge-rejetée {
            background-color: #dc3545;
        }

        .badge-envoyé-paiement {
            background-color: #ffc107;
        }

        .badge-payé {
            background-color: #17a2b8;
        }

        .btn-sm {
            font-size: 0.85rem;
        }

        .table th, .table td {
            text-align: center;
            vertical-align: middle;
        }

        /* Responsiveness */
        @media (max-width: 768px) {
            .table {
                font-size: 0.85rem;
            }

            .table thead {
                display: none;
            }

            .table tr {
                display: block;
                margin-bottom: 1rem;
            }

            .table td {
                display: flex;
                justify-content: space-between;
                padding: 0.5rem;
                border-bottom: 1px solid #dee2e6;
            }

            .table td::before {
                content: attr(data-label);
                font-weight: bold;
                margin-right: 0.5rem;
            }

            .card-header {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <a href="dashboard.php" class="btn-back mb-3"><i class="fas fa-arrow-left"></i> Retour au Tableau de Bord</a>
    <h1 class="text-center mb-4">Gestion des Notes de Frais</h1>

    <?php if (!empty($expenses)): ?>
        <?php
        $groupedExpenses = [];
        foreach ($expenses as $expense) {
            $groupedExpenses[$expense['user_email']][] = $expense;
        }
        ?>

        <?php foreach ($groupedExpenses as $userEmail => $userExpenses): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <?= htmlspecialchars($userExpenses[0]['user_name']) ?> (<?= htmlspecialchars($userEmail) ?>)
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Description</th>
                                    <th>Montant (€)</th>
                                    <th>Catégorie</th>
                                    <th>Statut</th>
                                    <th>Date de Dépense</th>
                                    <th>Justificatif</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userExpenses as $expense): ?>
                                    <tr>
                                        <td data-label="ID"><?= htmlspecialchars($expense['id']) ?></td>
                                        <td data-label="Description"><?= htmlspecialchars($expense['description']) ?></td>
                                        <td data-label="Montant (€)"><?= htmlspecialchars($expense['amount']) ?></td>
                                        <td data-label="Catégorie"><?= htmlspecialchars($expense['category']) ?></td>
                                        <td data-label="Statut">
                                            <?php
                                            $statusClass = match (strtolower($expense['status'])) {
                                                'soumise' => 'badge-soumise',
                                                'approuvée' => 'badge-approuvée',
                                                'rejetée' => 'badge-rejetée',
                                                'envoyé en paiement' => 'badge-envoyé-paiement',
                                                'payé' => 'badge-payé',
                                                default => 'badge-secondary',
                                            };
                                            ?>
                                            <span class="badge-status <?= $statusClass ?>"><?= htmlspecialchars($expense['status']) ?></span>
                                        </td>
                                        <td data-label="Date de Dépense"><?= htmlspecialchars($expense['expense_date']) ?></td>
                                        <td data-label="Justificatif">
                                            <?php if (!empty($expense['receipt_path'])): ?>
                                                <a href="/uploads/receipts/<?= htmlspecialchars($expense['receipt_path']) ?>" target="_blank" class="btn btn-link">Voir</a>
                                            <?php else: ?>
                                                Aucun
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Actions">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="expense_id" value="<?= htmlspecialchars($expense['id']) ?>">
                                                <select name="status" class="form-select form-select-sm mb-1">
                                                    <option value="soumise" <?= $expense['status'] === 'soumise' ? 'selected' : '' ?>>Soumise</option>
                                                    <option value="approuvée" <?= $expense['status'] === 'approuvée' ? 'selected' : '' ?>>Approuvée</option>
                                                    <option value="rejetée" <?= $expense['status'] === 'rejetée' ? 'selected' : '' ?>>Rejetée</option>
                                                    <option value="envoyé en paiement" <?= $expense['status'] === 'envoyé en paiement' ? 'selected' : '' ?>>Envoyé en Paiement</option>
                                                    <option value="payé" <?= $expense['status'] === 'payé' ? 'selected' : '' ?>>Payé</option>
                                                </select>
                                                <button type="submit" name="update_status" class="btn btn-primary btn-sm w-100">Mettre à jour</button>
                                            </form>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="expense_id" value="<?= htmlspecialchars($expense['id']) ?>">
                                                <button type="submit" name="delete_expense" class="btn btn-danger btn-sm w-100 mt-1">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-center">Aucune note de frais trouvée.</p>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
