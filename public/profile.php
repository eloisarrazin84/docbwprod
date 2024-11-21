<?php
require '../src/session_manager.php';
require '../src/db_connect.php';

requireLogin(); // Assurez-vous que l'utilisateur est connecté

// Récupérer l'utilisateur actuel
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newEmail = $_POST['email'];
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    $errors = [];
    $success = false;

    // Vérifier l'adresse e-mail
    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse e-mail est invalide.";
    }

    // Vérifier les mots de passe
    if (!empty($newPassword)) {
        if ($newPassword !== $confirmPassword) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        }
        if (strlen($newPassword) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
        }
    }

    // Vérifier le mot de passe actuel
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userPasswordHash = $stmt->fetchColumn();

    if (!password_verify($currentPassword, $userPasswordHash)) {
        $errors[] = "Le mot de passe actuel est incorrect.";
    }

    if (empty($errors)) {
        // Mettre à jour l'adresse e-mail
        $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->execute([$newEmail, $userId]);

        // Mettre à jour le mot de passe si un nouveau est défini
        if (!empty($newPassword)) {
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$newPasswordHash, $userId]);
        }

        $success = true;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 50px;
        }
        h1 {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
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
    </style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Retour au Tableau de Bord</a>

    <div class="card p-4">
        <h1>Mon Profil</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (isset($success) && $success): ?>
            <div class="alert alert-success">
                Votre profil a été mis à jour avec succès.
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Adresse E-mail</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="current_password" class="form-label">Mot de Passe Actuel</label>
                <input type="password" class="form-control" id="current_password" name="current_password" required>
            </div>
            <div class="mb-3">
                <label for="new_password" class="form-label">Nouveau Mot de Passe</label>
                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Laisser vide pour conserver l'ancien">
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirmer le Nouveau Mot de Passe</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Laisser vide pour conserver l'ancien">
            </div>
            <button type="submit" class="btn btn-primary">Mettre à jour</button>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
