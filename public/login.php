<?php
require '../src/db_connect.php';
require '../src/session_manager.php';

$error = '';

// Vérifie si un message d'erreur est défini dans la session (par exemple, en cas de session expirée)
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']); // Supprime le message après affichage
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login']; // Peut être un identifiant ou un e-mail
    $password = $_POST['password'];

    // Rechercher l'utilisateur par e-mail
    $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Connexion réussie
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time(); // Initialise l'activité pour la gestion de l'inactivité

        // Redirection vers le tableau de bord
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'E-mail ou mot de passe incorrect.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            background: white;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .logo {
            display: block;
            margin: 0 auto 20px;
            max-width: 150px;
        }
        .welcome-message {
            text-align: center;
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
        }
        .btn-primary {
            width: 100%;
        }
        .alert {
            text-align: center;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="login-container">
    <!-- Logo -->
    <img src="https://images.squarespace-cdn.com/content/v1/56893684d8af102bf3e403f1/1571317878518-X3DEUWJNOFZKBZ4LKQ54/Logo_BeWitness_Full.png?format=1500w" alt="Logo BeWitness" class="logo">

    <!-- Message de Bienvenue -->
    <div class="welcome-message">Bienvenue dans l'Espace Documentaire</div>

    <!-- Affiche un message d'erreur, si nécessaire -->
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Formulaire de Connexion -->
    <form method="POST">
        <div class="mb-3">
            <label for="login" class="form-label">E-mail</label>
            <input type="text" class="form-control" id="login" name="login" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Mot de passe</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary">Se connecter</button>
    </form>
</div>
</body>
</html>
