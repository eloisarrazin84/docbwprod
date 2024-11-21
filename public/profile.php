<?php
require '../src/session_manager.php';
require '../src/db_connect.php';

requireLogin(); // Assurez-vous que l'utilisateur est connecté

// Récupérer l'utilisateur actuel
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT email, profile_image FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// URL par défaut pour la photo de profil
$defaultProfileImage = 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
$profileImageUrl = $user['profile_image'] ? '/uploads/profiles/' . $user['profile_image'] : $defaultProfileImage;

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

        // Gestion du téléversement de la photo de profil
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '/var/www/uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = uniqid() . '-' . basename($_FILES['profile_image']['name']);
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $filePath)) {
                // Mettre à jour le chemin de l'image dans la base de données
                $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $stmt->execute([$fileName, $userId]);
                $profileImageUrl = '/uploads/profiles/' . $fileName;
            } else {
                $errors[] = "Une erreur s'est produite lors du téléversement de la photo de profil.";
            }
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
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" class="btn-back mb-3"><i class="fas fa-arrow-left"></i> Retour au Tableau de Bord</a>

    <div class="card p-4">
        <h1>Mon Profil</h1>
        <div class="text-center mb-3">
            <img src="<?= htmlspecialchars($profileImageUrl) ?>" alt="Photo de profil" class="profile-image">
        </div>

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

        <form method="POST" enctype="multipart/form-data">
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
            <div class="mb-3">
                <label for="profile_image" class="form-label">Changer de photo de profil</label>
                <input type="file" name="profile_image" id="profile_image" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Mettre à jour</button>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
