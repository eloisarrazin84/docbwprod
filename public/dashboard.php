<?php
require '../src/session_manager.php';
require '../src/db_connect.php';
require '../src/folder_manager.php';

requireLogin(); // Vérifie si l'utilisateur est connecté

// Récupérer l'ID et le rôle de l'utilisateur
$userId = $_SESSION['user_id'];
$userRole = getUserRole(); // admin ou user

// Définir le titre de la page
$pageTitle = "Tableau de Bord";

// Récupération de l'image de profil ou icône par défaut
$stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
$stmt->execute([$userId]);
$profileImage = $stmt->fetchColumn();

if ($profileImage) {
    $profileImageUrl = '/uploads/profiles/' . htmlspecialchars($profileImage);
} else {
    $profileImageUrl = 'https://cdn-icons-png.flaticon.com/512/149/149071.png'; // Icône utilisateur par défaut
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }
        .card:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            flex-wrap: wrap;
        }
        .dashboard-header img {
            max-height: 50px;
        }
        .dashboard-header h1 {
            margin: 10px 0;
            flex-grow: 1;
            text-align: center;
        }
        .logout-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .logout-btn:hover {
            background-color: #c82333;
        }
        .user-profile {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: auto;
            margin-right: 20px;
        }
        .profile-link {
            display: inline-block;
            border-radius: 50%;
            overflow: hidden;
            width: 40px;
            height: 40px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .profile-link:hover {
            transform: scale(1.1);
        }
        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        @media (max-width: 768px) {
            .card {
                margin-bottom: 20px;
            }
            .dashboard-header {
                flex-direction: column;
                align-items: center;
            }
            .dashboard-header h1 {
                text-align: center;
                margin: 10px 0;
            }
            .logout-btn {
                margin-top: 10px;
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-header">
    <img src="https://images.squarespace-cdn.com/content/v1/56893684d8af102bf3e403f1/1571317878518-X3DEUWJNOFZKBZ4LKQ54/Logo_BeWitness_Full.png?format=1500w" alt="Logo Be Witness">
    <h1><?= htmlspecialchars($pageTitle) ?></h1>
    <!-- Badge utilisateur -->
    <div class="user-profile">
        <a href="profile.php" class="profile-link">
            <img src="<?= htmlspecialchars($profileImageUrl) ?>" 
                 alt="Photo de profil" class="profile-image">
        </a>
    </div>
    <form action="logout.php" method="post">
        <button type="submit" class="logout-btn">Se déconnecter</button>
    </form>
</div>

<div class="container mt-5">
    <h1 class="text-center mb-4"><?= htmlspecialchars($pageTitle) ?></h1>

    <?php if ($userRole === 'admin'): ?>
        <!-- Section Administration -->
        <div class="category-title">Administration</div>
        <div class="row">
            <div class="col-lg-4 col-md-6 col-sm-12">
                <div class="card text-white bg-primary mb-4 shadow">
                    <div class="card-header text-center"><i class="fas fa-users"></i> Gestion des Utilisateurs</div>
                    <div class="card-body text-center">
                        <p class="card-text">Ajouter, modifier et supprimer des utilisateurs.</p>
                        <a href="user_management.php" class="btn btn-light"><i class="fas fa-arrow-right"></i> Gérer</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-sm-12">
                <div class="card text-white bg-success mb-4 shadow">
                    <div class="card-header text-center"><i class="fas fa-folder"></i> Gestion des Dossiers</div>
                    <div class="card-body text-center">
                        <p class="card-text">Créer, modifier et supprimer des dossiers.</p>
                        <a href="folder_management.php" class="btn btn-light"><i class="fas fa-arrow-right"></i> Gérer</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-sm-12">
                <div class="card text-white bg-warning mb-4 shadow">
                    <div class="card-header text-center"><i class="fas fa-receipt"></i> Gestion des Notes de Frais</div>
                    <div class="card-body text-center">
                        <p class="card-text">Consulter et gérer les notes de frais des utilisateurs.</p>
                        <a href="manage_expenses.php" class="btn btn-light"><i class="fas fa-arrow-right"></i> Gérer</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Utilisateur -->
        <div class="category-title">Mes Fonctionnalités</div>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="card text-white bg-info mb-4 shadow">
                    <div class="card-header text-center"><i class="fas fa-folder-open"></i> Mes Documents</div>
                    <div class="card-body text-center">
                        <p class="card-text">Accédez aux documents qui vous sont assignés.</p>
                        <a href="my_documents.php" class="btn btn-light"><i class="fas fa-arrow-right"></i> Accéder</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="card text-white bg-secondary mb-4 shadow">
                    <div class="card-header text-center"><i class="fas fa-file-invoice-dollar"></i> Mes Notes de Frais</div>
                    <div class="card-body text-center">
                        <p class="card-text">Suivez vos notes de frais.</p>
                        <a href="user_dashboard_expenses.php" class="btn btn-light"><i class="fas fa-arrow-right"></i> Accéder</a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Section Utilisateur uniquement -->
        <div class="category-title">Mes Fonctionnalités</div>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="card text-white bg-info mb-4 shadow">
                    <div class="card-header text-center"><i class="fas fa-folder-open"></i> Mes Documents</div>
                    <div class="card-body text-center">
                        <p class="card-text">Accédez aux documents qui vous sont assignés.</p>
                        <a href="my_documents.php" class="btn btn-light"><i class="fas fa-arrow-right"></i> Accéder</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="card text-white bg-secondary mb-4 shadow">
                    <div class="card-header text-center"><i class="fas fa-file-invoice-dollar"></i> Mes Notes de Frais</div>
                    <div class="card-body text-center">
                        <p class="card-text">Suivez vos notes de frais.</p>
                        <a href="user_dashboard_expenses.php" class="btn btn-light"><i class="fas fa-arrow-right"></i> Accéder</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
    <!-- Section Utilisateur (toujours affichée, même pour admin) -->
    <div class="row">
        <div class="col-md-6 col-sm-12">
            <div class="card text-white bg-info mb-3 shadow">
                <div class="card-header text-center"><i class="fas fa-folder-open"></i> Mes Documents</div>
                <div class="card-body text-center">
                    <a href="my_documents.php" class="btn btn-light"><i class="fas fa-arrow-right"></i> Accéder</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-sm-12">
            <div class="card text-white bg-secondary mb-3 shadow">
                <div class="card-header text-center"><i class="fas fa-file-invoice-dollar"></i> Mes Notes de Frais</div>
                <div class="card-body text-center">
                    <a href="user_dashboard_expenses.php" class="btn btn-light"><i class="fas fa-arrow-right"></i> Accéder</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
