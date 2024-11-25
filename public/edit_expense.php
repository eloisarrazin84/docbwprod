<?php
session_start(); // Démarre la session
require '../src/session_manager.php';
require '../src/db_connect.php';
require '../src/expense_manager.php';

requireLogin(); // Vérifie si l'utilisateur est connecté

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id']; // Définit l'utilisateur connecté
error_log("Current User ID: $userId");

// Vérifie si une ID d'expense est fournie
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    error_log("Erreur : L'ID de la dépense n'est pas valide.");
    header('Location: user_dashboard_expenses.php');
    exit();
}

$expenseId = intval($_GET['id']);
error_log("Expense ID provided: $expenseId");

// Récupère les détails de la dépense
$expense = getExpenseDetails($expenseId);

// Vérifie si la dépense existe
if (!$expense) {
    error_log("Erreur : Aucune note de frais trouvée pour l'ID spécifié : $expenseId");
    die("Erreur : Aucune note de frais trouvée pour l'ID spécifié.");
}

// Vérifie si les données contiennent 'user_id'
if (!isset($expense['user_id'])) {
    error_log("Erreur : Le champ 'user_id' est manquant dans les données de la dépense pour ID : $expenseId");
    error_log("Données retournées : " . json_encode($expense));
    die("Erreur : Les données de la dépense sont invalides.");
}

// Vérifie si la dépense appartient bien à l'utilisateur connecté
if ($expense['user_id'] != $userId) {
    error_log("Erreur : Expense ID $expenseId does not belong to User ID $userId.");
    die("Erreur : Vous n'êtes pas autorisé à modifier cette note de frais.");
}

// Gestion des mises à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description']);
    $amount = floatval($_POST['amount']);
    $category = trim($_POST['category']);
    $expenseDate = $_POST['expense_date'];
    $comment = trim($_POST['comment']);

    if (updateExpense($expenseId, $description, $amount, $category, $expenseDate, $comment)) {
        header('Location: user_dashboard_expenses.php');
        exit();
    } else {
        $error = "Erreur lors de la mise à jour de la note de frais.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la Note de Frais</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .form-label {
            font-weight: bold;
        }
        .btn-primary {
            width: 100%;
        }
        .alert {
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center">Modifier la Note de Frais</h1>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <input type="text" class="form-control" id="description" name="description" value="<?= htmlspecialchars($expense['description']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="amount" class="form-label">Montant (€)</label>
            <input type="number" class="form-control" id="amount" name="amount" step="0.01" value="<?= htmlspecialchars($expense['amount']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="category" class="form-label">Catégorie</label>
            <select id="category" name="category" class="form-select" required>
                <option value="transport" <?= $expense['category'] === 'transport' ? 'selected' : '' ?>>Transport</option>
                <option value="repas" <?= $expense['category'] === 'repas' ? 'selected' : '' ?>>Repas</option>
                <option value="hebergement" <?= $expense['category'] === 'hebergement' ? 'selected' : '' ?>>Hébergement</option>
                <option value="autre" <?= $expense['category'] === 'autre' ? 'selected' : '' ?>>Autre</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="expense_date" class="form-label">Date de la Dépense</label>
            <input type="date" class="form-control" id="expense_date" name="expense_date" value="<?= htmlspecialchars($expense['expense_date']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="comment" class="form-label">Commentaire</label>
            <textarea id="comment" name="comment" class="form-control"><?= htmlspecialchars($expense['comment']) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Mettre à jour</button>
    </form>
</div>
</body>
</html>
