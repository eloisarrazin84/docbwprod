<?php
require '../src/db_connect.php';
require '../src/session_manager.php';
require '../src/expense_manager.php';

requireLogin(); // Vérifie si l'utilisateur est connecté

$error = '';
$success = '';

// Gestion de la soumission de la note de frais
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $description = trim($_POST['description'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $category = $_POST['category'] ?? '';
    $receiptPath = null;

    // Validation des champs
    if (empty($description)) {
        $error = 'La description est obligatoire.';
    } elseif ($amount <= 0) {
        $error = 'Le montant doit être supérieur à 0.';
    } elseif (empty($category)) {
        $error = 'La catégorie est obligatoire.';
    }

    // Gestion du téléchargement du justificatif
    if (empty($error) && isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '/var/www/uploads/receipts/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
                $error = 'Impossible de créer le répertoire pour les justificatifs.';
            }
        }

        if (empty($error)) {
            $receiptPath = uniqid() . '-' . basename($_FILES['receipt']['name']);
            $filePath = $uploadDir . $receiptPath;

            if (!move_uploaded_file($_FILES['receipt']['tmp_name'], $filePath)) {
                $error = 'Erreur lors du téléchargement du justificatif.';
            }
        }
    }

    // Si aucune erreur, ajouter la note de frais
    if (empty($error)) {
        if (createExpense($userId, $description, $amount, $category, $receiptPath)) {
            $success = 'Note de frais soumise avec succès.';
        } else {
            $error = 'Une erreur est survenue lors de la soumission de la note de frais.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soumettre une Note de Frais</title>
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
    <h1 class="text-center mb-4">Soumettre une Note de Frais</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="description" class="form-label">Description</label>
            <input type="text" class="form-control" id="description" name="description" placeholder="Exemple : Taxi pour conférence" required>
        </div>
        <div class="form-group">
            <label for="amount" class="form-label">Montant (€)</label>
            <input type="number" step="0.01" class="form-control" id="amount" name="amount" min="0.01" placeholder="Exemple : 25.50" required>
        </div>
        <div class="form-group">
            <label for="category" class="form-label">Catégorie</label>
            <select class="form-control" id="category" name="category" required>
                <option value="" disabled selected>Choisissez une catégorie</option>
                <option value="transport">Transport</option>
                <option value="repas">Repas</option>
                <option value="hebergement">Hébergement</option>
                <option value="autre">Autre</option>
            </select>
        </div>
        <div class="form-group">
            <label for="receipt" class="form-label">Justificatif (optionnel)</label>
            <input type="file" class="form-control" id="receipt" name="receipt" accept="image/*,application/pdf">
            <small class="text-muted">Formats acceptés : images, PDF. Taille max : 5 Mo</small>
        </div>
        <button type="submit" class="btn btn-primary">Soumettre</button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
