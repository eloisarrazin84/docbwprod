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
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0px 6px 10px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background-color: #17a2b8;
            color: white;
            font-size: 1.5rem;
            text-align: center;
            padding: 1rem;
            font-weight: bold;
        }

        .table th, .table td {
            vertical-align: middle;
            text-align: center;
        }

        .badge-status {
            display: inline-block;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
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
    </style>
</head>
<body>
<div class="container mt-5">
    <a href="dashboard.php" class="btn-back mb-3"><i class="fas fa-arrow-left"></i> Retour au Tableau de Bord</a>
    <h1 class="text-center mb-4">Gestion des Notes de Frais</h1>

    <form method="GET" class="row mb-4">
        <!-- Formulaires de filtre -->
    </form>

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
                    <h2><?= htmlspecialchars($userExpenses[0]['user_name']) ?> (<?= htmlspecialchars($userEmail) ?>)</h2>
                </div>
                <div class="card-body">
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
                                    <td><?= htmlspecialchars($expense['id']) ?></td>
                                    <td><?= htmlspecialchars($expense['description']) ?></td>
                                    <td><?= htmlspecialchars($expense['amount']) ?></td>
                                    <td><?= htmlspecialchars($expense['category']) ?></td>
                                    <td><?= htmlspecialchars($expense['status']) ?></td>
                                    <td><?= htmlspecialchars($expense['expense_date']) ?></td>
                                    <td>
                                        <?php if (!empty($expense['receipt_path'])): ?>
                                            <a href="/uploads/<?= htmlspecialchars($expense['receipt_path']) ?>" target="_blank">Voir</a>
                                        <?php else: ?>
                                            Aucun
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <!-- Actions -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
