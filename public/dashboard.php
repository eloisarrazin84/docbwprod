<?php
require '../src/session_manager.php';
require '../src/db_connect.php';
require '../src/folder_manager.php';

requireLogin(); // Vérifie si l'utilisateur est connecté

// Définir le titre de la page selon le rôle de l'utilisateur
if (getUserRole() === 'admin') {
    $pageTitle = "Tableau de Bord Admin";
} else {
    $pageTitle = "Mes Dossiers";
    $folders = listFoldersByUser($_SESSION['user_id']); // Récupère les dossiers pour l'utilisateur connecté
}

// Récupération de l'image de profil ou icône par défaut
// Récupérer l'image de profil de l'utilisateur
$stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
$stmt->execute([$userId]);
$profileImage = $stmt->fetchColumn();

// Vérifier si une image de profil existe
if ($profileImage) {
    $profileImageUrl = '/uploads/profiles/' . $profileImage;
} else {
    // Fallback vers une icône utilisateur par défaut
    $profileImageUrl = 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
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
            <img src="<?= htmlspecialchars($userProfileImage) ?>" 
                 alt="Photo de profil" class="profile-image">
        </a>
    </div>
    <form action="logout.php" method="post">
        <button type="submit" class="logout-btn">Se déconnecter</button>
    </form>
</div>

<div class="container mt-5">
    <?php if (getUserRole() === 'admin'): ?>
        <!-- Tableau de bord Admin -->
        <div class="row">
            <div class="col-md-4 col-sm-12">
                <div class="card text-white bg-primary mb-3 shadow">
                    <div class="card-header text-center"><i class="fas fa-users"></i> Gestion des Utilisateurs</div>
                    <div class="card-body text-center">
                        <p class="card-text">Ajouter, modifier et supprimer des utilisateurs.</p>
                        <a href="user_management.php" class="btn btn-light"><i class="fas fa-arrow-right"></i> Gérer</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-12">
                <div class="card text-white bg-success mb-3 shadow">
                    <div class="card-header text-center"><i class="fas fa-folder"></i> Gestion des Dossiers</div>
                    <div class="card-body text-center">
                        <p class="card-text">Créer, modifier et supprimer des dossiers.</p>
                        <a href="folder_management.php" class="btn btn-light"><i class="fas fa-arrow-right"></i> Gérer</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-12">
                <div class="card text-white bg-info mb-3 shadow">
                    <div class="card-header text-center"><i class="fas fa-tools"></i> Autres Fonctionnalités</div>
                    <div class="card-body text-center">
                        <p class="card-text">Ajouter des fonctionnalités personnalisées ici.</p>
                        <a href="#" class="btn btn-light"><i class="fas fa-arrow-right"></i> Explorer</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 col-sm-12">
                <div class="card text-white bg-secondary mb-3 shadow">
                    <div class="card-header text-center"><i class="fas fa-folder-open"></i> Mes Documents</div>
                    <div class="card-body text-center">
                        <p class="card-text">Accédez aux documents qui vous sont assignés.</p>
                        <a href="my_documents.php" class="btn btn-light"><i class="fas fa-arrow-right"></i> Voir mes documents</a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Tableau de bord pour l'utilisateur -->
        <h2 class="text-center mt-4">Mes Dossiers</h2>
        <?php if (!empty($folders)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nom du Dossier</th>
                            <th>Date de Création</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($folders as $folder): ?>
                            <tr>
                                <td><i class="fas fa-folder"></i> <?= htmlspecialchars($folder['name']) ?></td>
                                <td><?= htmlspecialchars($folder['created_at']) ?></td>
                                <td>
                                    <a href="documents.php?folder_id=<?= $folder['id'] ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i> Voir les Documents
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center">Aucun dossier disponible.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>

