<?php
require '../src/session_manager.php';
require '../src/db_connect.php';
require '../src/expense_manager.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $category = $_POST['category'];
    $userId = $_SESSION['user_id'];
    $receiptPath = null;

    // Gestion du fichier de justificatif
    if (!empty($_FILES['receipt']['name'])) {
        $uploadDir = '/var/www/uploads/receipts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . '-' . basename($_FILES['receipt']['name']);
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['receipt']['tmp_name'], $filePath)) {
            $receiptPath = $fileName;
        } else {
            echo "<div class='alert alert-danger'>Erreur lors du téléchargement du fichier justificatif.</div>";
        }
    }

    if (createExpense($userId, $description, $amount, $category, $receiptPath)) {
        echo "<div class='alert alert-success'>Note de frais soumise avec succès.</div>";
    } else {
        echo "<div class='alert alert-danger'>Erreur lors de la soumission de la note de frais.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soumettre une note de frais</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1>Soumettre une note de frais</h1>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <input type="text" class="form-control" id="description" name="description" required>
        </div>
        <div class="mb-3">
            <label for="amount" class="form-label">Montant</label>
            <input type="number" step="0.01" class="form-control" id="amount" name="amount" required>
        </div>
        <div class="mb-3">
            <label for="category" class="form-label">Catégorie</label>
            <select class="form-control" id="category" name="category" required>
                <option value="transport">Transport</option>
                <option value="repas">Repas</option>
                <option value="hébergement">Hébergement</option>
                <option value="autre">Autre</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="receipt" class="form-label">Justificatif (optionnel)</label>
            <input type="file" class="form-control" id="receipt" name="receipt">
        </div>
        <button type="submit" class="btn btn-primary">Soumettre</button>
    </form>
</div>
</body>
</html>
