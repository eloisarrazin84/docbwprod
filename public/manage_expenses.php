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
            // Recharge les dépenses après la mise à jour
            $expenses = listSubmittedExpenses($categoryFilter, $statusFilter, $dateFilter, $userFilter);
        } else {
            $error = "Erreur lors de la mise à jour du statut.";
        }
    }

    if (isset($_POST['delete_expense'], $_POST['expense_id'])) {
        $expenseId = intval($_POST['expense_id']);
        if (deleteExpense($expenseId)) {
            $success = "La note de frais #{$expenseId} a été supprimée avec succès.";
            // Recharger les notes après suppression
            $expenses = listSubmittedExpenses($categoryFilter, $statusFilter, $dateFilter, $userFilter);
        } else {
            $error = "Erreur lors de la suppression de la note de frais.";
        }
    }

    // Exporter les données vers Excel
    if (isset($_POST['export_to_excel'])) {
        exportExpensesToExcel($expenses);
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

        $query .= " ORDER BY e.date_submitted DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
        return [];
    }
}

// Fonction pour exporter les données au format Excel
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
        }
        .btn-create {
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            padding: 10px 20px;
            transition: background-color 0.3s ease;
            display: inline-block;
        }
        .btn-create:hover {
            background-color: #218838;
        }
        .table-responsive {
            margin-top: 20px;
        }
        h1, h2 {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
        }
        .form-select, .form-control {
            border-radius: 5px;
        }
        .btn-export {
            background-color: #ffc107;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .btn-export:hover {
            background-color: #e0a800;
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
            <button type="submit" name="export_to_excel" class="btn btn-export"><i class="fas fa-file-export"></i> Exporter vers Excel</button>
        </div>
    </form>

    <!-- Tableau des notes de frais -->
    <div class="card">
        <div class="card-header">
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
                            <th>Date de Soumission</th>
                            <th>Date de Dépense</th>
                            <th>Utilisateur</th>
                            <th>Commentaire</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($expenses)): ?>
                            <?php foreach ($expenses as $expense): ?>
                                <tr>
                                    <td><?= htmlspecialchars($expense['id']) ?></td>
                                    <td><?= htmlspecialchars($expense['description']) ?></td>
                                    <td><?= htmlspecialchars($expense['amount']) ?></td>
                                    <td><?= htmlspecialchars($expense['category']) ?></td>
                                    <td><?= htmlspecialchars($expense['status']) ?></td>
                                    <td><?= htmlspecialchars($expense['date_submitted']) ?></td>
                                    <td><?= htmlspecialchars($expense['expense_date']) ?></td>
                                    <td><?= htmlspecialchars($expense['user_name'] . ' (' . $expense['user_email'] . ')') ?></td>
                                    <td><?= htmlspecialchars($expense['comment']) ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="expense_id" value="<?= htmlspecialchars($expense['id']) ?>">
                                            <select name="status" class="form-select form-select-sm d-inline-block" style="width: auto;">
                                                <option value="soumise" <?= $expense['status'] === 'soumise' ? 'selected' : '' ?>>Soumise</option>
                                                <option value="approuvée" <?= $expense['status'] === 'approuvée' ? 'selected' : '' ?>>Approuvée</option>
                                                <option value="rejetée" <?= $expense['status'] === 'rejetée' ? 'selected' : '' ?>>Rejetée</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-primary btn-sm">Mettre à jour</button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="expense_id" value="<?= htmlspecialchars($expense['id']) ?>">
                                            <button type="submit" name="delete_expense" class="btn btn-danger btn-sm">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center">Aucune note de frais trouvée.</td>
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
