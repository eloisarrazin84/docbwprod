<?php
require '../src/db_connect.php';
require '../src/expense_manager.php';
require '../src/session_manager.php';

requireLogin(); // Vérifie si l'utilisateur est connecté

// Récupérer l'ID de la note de frais
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: user_dashboard_expenses.php');
    exit();
}

$expenseId = intval($_GET['id']);
$expense = getExpenseDetails($expenseId);

if (!$expense || $expense['user_id'] != $_SESSION['user_id']) {
    header('Location: user_dashboard_expenses.php');
    exit();
}

$success = '';
$error = '';

// Gestion de la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $category = $_POST['category'] ?? '';
    $expenseDate = $_POST['expense_date'] ?? null;
    $comment = trim($_POST['comment'] ?? '');

    // Validation des champs
    if (empty($description)) {
        $error = 'La description est obligatoire.';
    } elseif ($amount <= 0) {
        $error = 'Le montant doit être supérieur à 0.';
    } elseif (empty($category)) {
        $error = 'La catégorie est obligatoire.';
    } else {
        // Mise à jour de la note de frais
        if (updateExpense($expenseId, $description, $amount, $category, $expenseDate, $comment)) {
            $success = 'Note de frais mise à jour avec succès.';
            // Rafraîchir les données
            $expense = getExpenseDetails($expenseId);
        } else {
            $error = 'Erreur lors de la mise à jour de la note de frais.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une Note de Frais</title>
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
<div class="container">
    <h1 class="text-center mb-4">Modifier une Note de Frais</h1>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="description" class="form-label">Description</label>
            <input type="text" class="form-control" id="description" name="description" value="<?= htmlspecialchars($expense['description']) ?>" required>
        </div>
        <div class="form-group">
            <label for="amount" class="form-label">Montant (€)</label>
            <input type="number" step="0.01" class="form-control" id="amount" name="amount" value="<?= htmlspecialchars($expense['amount']) ?>" min="0.01" required>
        </div>
        <div class="form-group">
            <label for="category" class="form-label">Catégorie</label>
            <select class="form-control" id="category" name="category" required>
                <option value="" disabled <?= empty($expense['category']) ? 'selected' : '' ?>>Choisissez une catégorie</option>
                <option value="transport" <?= $expense['category'] === 'transport' ? 'selected' : '' ?>>Transport</option>
                <option value="repas" <?= $expense['category'] === 'repas' ? 'selected' : '' ?>>Repas</option>
                <option value="hebergement" <?= $expense['category'] === 'hebergement' ? 'selected' : '' ?>>Hébergement</option>
                <option value="autre" <?= $expense['category'] === 'autre' ? 'selected' : '' ?>>Autre</option>
            </select>
        </div>
        <div class="form-group">
            <label for="expense_date" class="form-label">Date de Dépense</label>
            <input type="date" class="form-control" id="expense_date" name="expense_date" value="<?= htmlspecialchars($expense['expense_date']) ?>">
        </div>
        <div class="form-group">
            <label for="comment" class="form-label">Commentaire</label>
            <textarea class="form-control" id="comment" name="comment" rows="3"><?= htmlspecialchars($expense['comment']) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Mettre à jour</button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
