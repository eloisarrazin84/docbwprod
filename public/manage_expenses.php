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

// Récupérer toutes les notes de frais sauf celles en statut "brouillon"
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
        } else {
            $error = "Erreur lors de la mise à jour du statut.";
        }
    }

    if (isset($_POST['delete_expense'], $_POST['expense_id'])) {
        $expenseId = intval($_POST['expense_id']);
        if (deleteExpense($expenseId)) {
            $success = "La note de frais #{$expenseId} a été supprimée avec succès.";
            // Recharger les notes après suppression
            $expenses = listSubmittedExpenses();
        } else {
            $error = "Erreur lors de la suppression de la note de frais.";
        }
    }

    // Exporter les données vers Excel
    if (isset($_POST['export_to_excel'])) {
        exportExpensesToExcel($expenses);
    }
}

// Fonction pour récupérer uniquement les notes de frais soumises ou approuvées
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

        if ($category) {
            $query .= " AND e.category = ?";
            $params[] = $category;
        }

        if ($status) {
            $query .= " AND e.status = ?";
            $params[] = $status;
        }

        if ($date) {
            $query .= " AND e.expense_date = ?";
            $params[] = $date;
        }

        if ($user) {
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
    <!-- Retour au tableau de bord -->
    <a href="dashboard.php" class="btn btn-primary mb-3"><i class="fas fa-arrow-left"></i> Retour au Tableau de Bord</a>

    <!-- Titre -->
    <h1 class="text-center mb-4">Gestion des Notes de Frais</h1>

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
                <option value="transport" <?= $categoryFilter === 'transport' ? 'selected' : '' ?>>Transport</option>
                <option value="repas" <?= $categoryFilter === 'repas' ? 'selected' : '' ?>>Repas</option>
                <option value="hebergement" <?= $categoryFilter === 'hebergement' ? 'selected' : '' ?>>Hébergement</option>
                <option value="autre" <?= $categoryFilter === 'autre' ? 'selected' : '' ?>>Autre</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="status" class="form-label">Statut</label>
            <select name="status" id="status" class="form-select">
                <option value="">Tous</option>
                <option value="soumise" <?= $statusFilter === 'soumise' ? 'selected' : '' ?>>Soumise</option>
                <option value="approuvée" <?= $statusFilter === 'approuvée' ? 'selected' : '' ?>>Approuvée</option>
                <option value="rejetée" <?= $statusFilter === 'rejetée' ? 'selected' : '' ?>>Rejetée</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="date" class="form-label">Date de Dépense</label>
            <input type="date" name="date" id="date" class="form-control" value="<?= htmlspecialchars($dateFilter) ?>">
        </div>
        <div class="col-md-3">
            <label for="user" class="form-label">Utilisateur</label>
            <select name="user" id="user" class="form-select">
                <option value="">Tous</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= htmlspecialchars($user['id']) ?>" <?= $userFilter == $user['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['name'] . ' (' . $user['email'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 text-end mt-3">
            <button type="submit" class="btn btn-secondary">Filtrer</button>
            <form method="POST" style="display: inline;">
                <button type="submit" name="export_to_excel" class="btn btn-success">Exporter vers Excel</button>
            </form>
        </div>
    </form>

    <!-- Tableau des notes de frais -->
    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
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
</body>
</html>
