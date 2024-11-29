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

        // Validation des statuts autorisés
        $validStatuses = ['soumise', 'approuvée', 'rejetée'];
        if (in_array($status, $validStatuses, true)) {
            if (updateExpenseStatus($expenseId, $status)) {
                $success = "Le statut de la note de frais #{$expenseId} a été mis à jour.";
                $expenses = listSubmittedExpenses($categoryFilter, $statusFilter, $dateFilter, $userFilter);
            } else {
                $error = "Erreur lors de la mise à jour du statut.";
            }
        } else {
            $error = "Statut non valide.";
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

    if (isset($_POST['export_to_excel'])) {
        exportExpensesToExcel($expenses);
    }
}

// Fonction pour récupérer les notes de frais filtrées
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

        $query .= " ORDER BY e.date_submitted DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
        return [];
    }
}

function updateExpenseStatus($expenseId, $status) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE expense_notes SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $expenseId]);
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
        return false;
    }
}

function exportExpensesToExcel($expenses) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="expenses_export_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');

    echo "<table border='1'>";
    echo "<tr>
        <th>ID</th>
        <th>Description</th>
        <th>Montant (€)</th>
        <th>Catégorie</th>
        <th>Statut</th>
        <th>Date de Soumission</th>
        <th>Date de Dépense</th>
        <th>Utilisateur</th>
        <th>Commentaire</th>
    </tr>";

    foreach ($expenses as $expense) {
        echo "<tr>
            <td>{$expense['id']}</td>
            <td>{$expense['description']}</td>
            <td>{$expense['amount']}</td>
            <td>{$expense['category']}</td>
            <td>{$expense['status']}</td>
            <td>{$expense['date_submitted']}</td>
            <td>{$expense['expense_date']}</td>
            <td>{$expense['user_name']} ({$expense['user_email']})</td>
            <td>{$expense['comment']}</td>
        </tr>";
    }

    echo "</table>";
    exit();
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
            font-size: 14px;
        }

        .btn-back:hover {
            background-color: #0056b3;
        }

        .badge-soumise {
            background-color: #007bff;
            color: white;
        }

        .badge-approuvée {
            background-color: #28a745;
            color: white;
        }

        .badge-rejetée {
            background-color: #dc3545;
            color: white;
        }

        .table {
            border-collapse: collapse;
            width: 100%;
            background-color: white;
        }

        .table thead th {
            background-color: #17a2b8;
            color: white;
            padding: 10px;
            text-align: center;
        }

        .table tbody td {
            padding: 12px;
            text-align: center;
        }

        .table tbody tr:nth-child(odd) {
            background-color: #f9f9f9;
        }

        .table tbody tr:hover {
            background-color: #e8f5ff;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <a href="dashboard.php" class="btn-back mb-3"><i class="fas fa-arrow-left"></i> Retour au Tableau de Bord</a>

    <h1 class="text-center mb-4">Gestion des Notes de Frais</h1>

    <!-- Messages de succès/erreur -->
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Filtres -->
    <form method="GET" class="row mb-4">
        <div class="col-md-3">
            <label for="category" class="form-label">Catégorie</label>
            <select name="category" id="category" class="form-select">
                <option value="">Toutes</option>
                <option value="transport">Transport</option>
                <option value="repas">Repas</option>
                <option value="hebergement">Hébergement</option>
                <option value="autre">Autre</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="status" class="form-label">Statut</label>
            <select name="status" id="status" class="form-select">
                <option value="">Tous</option>
                <option value="soumise">Soumise</option>
                <option value="approuvée">Approuvée</option>
                <option value="rejetée">Rejetée</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="date" class="form-label">Date de Dépense</label>
            <input type="date" name="date" id="date" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="user" class="form-label">Utilisateur</label>
            <select name="user" id="user" class="form-select">
                <option value="">Tous</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= htmlspecialchars($user['id']) ?>"><?= htmlspecialchars($user['name'] . ' (' . $user['email'] . ')') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 text-end mt-3">
            <button type="submit" class="btn btn-secondary">Filtrer</button>
            <button type="submit" name="export_to_excel" class="btn btn-warning"><i class="fas fa-file-export"></i> Exporter vers Excel</button>
        </div>
    </form>

    <!-- Tableau -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Description</th>
                    <th>Montant (€)</th>
                    <th>Catégorie</th>
                    <th>Statut</th>
                    <th>Date de Soumission</th>
                    <th>Utilisateur</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td><?= htmlspecialchars($expense['id']) ?></td>
                        <td><?= htmlspecialchars($expense['description']) ?></td>
                        <td><?= htmlspecialchars($expense['amount']) ?></td>
                        <td><?= htmlspecialchars($expense['category']) ?></td>
                        <td><span class="badge badge-<?= htmlspecialchars($expense['status']) ?>"><?= ucfirst(htmlspecialchars($expense['status'])) ?></span></td>
                        <td><?= htmlspecialchars($expense['date_submitted']) ?></td>
                        <td><?= htmlspecialchars($expense['user_name']) ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="expense_id" value="<?= htmlspecialchars($expense['id']) ?>">
                                <select name="status" class="form-select form-select-sm">
                                    <option value="soumise" <?= $expense['status'] === 'soumise' ? 'selected' : '' ?>>Soumise</option>
                                    <option value="approuvée" <?= $expense['status'] === 'approuvée' ? 'selected' : '' ?>>Approuvée</option>
                                    <option value="rejetée" <?= $expense['status'] === 'rejetée' ? 'selected' : '' ?>>Rejetée</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-primary btn-sm mt-1">Mettre à jour</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
