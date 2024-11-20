<?php
require '../src/db_connect.php';
require '../src/document_manager.php';
require '../src/session_manager.php';

requireLogin(); // Vérifie si l'utilisateur est connecté

// Vérifie si un document ID est passé en paramètre
$documentId = isset($_GET['document_id']) ? intval($_GET['document_id']) : null;
if (!$documentId) {
    die("Erreur : aucun document spécifié.");
}

// Récupère les informations du document
$document = getDocumentById($documentId);
if (!$document) {
    die("Erreur : document non trouvé.");
}

// Si l'utilisateur soumet le formulaire de signature
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $signature = $_POST['signature']; // Récupérer les données de la signature (par exemple, base64)
    
    // Ajouter la signature au document PDF
    $result = signDocument($document['file_path'], $signature);

    if ($result) {
        // Mettre à jour la base de données pour indiquer que le document a été signé
        markDocumentAsSigned($documentId);

        // Rediriger l'utilisateur
        header("Location: documents.php?folder_id=" . $document['folder_id']);
        exit();
    } else {
        echo "<div class='alert alert-danger'>Erreur : impossible de signer le document.</div>";
    }
}

function getDocumentById($documentId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
    $stmt->execute([$documentId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function signDocument($filePath, $signature) {
    // Implémenter ici la logique pour signer le fichier PDF
    // Par exemple, utiliser FPDI pour ajouter une signature
    // Retourner true en cas de succès
    return true;
}

function markDocumentAsSigned($documentId) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE documents SET signed_by_user = 1 WHERE id = ?");
    $stmt->execute([$documentId]);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signer le Document</title>
</head>
<body>
    <h1>Signer le Document : <?= htmlspecialchars($document['file_name']) ?></h1>
    <form method="POST">
        <!-- Zone pour capturer la signature (par exemple, un canvas) -->
        <label for="signature">Signature :</label>
        <textarea name="signature" id="signature" required></textarea>
        <button type="submit">Signer</button>
    </form>
</body>
</html>
