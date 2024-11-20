<?php
require '../src/db_connect.php';
require '../src/document_manager.php';
require '../src/session_manager.php';

requireLogin();

$documentId = $_GET['document_id'] ?? null;
$folderId = $_GET['folder_id'] ?? null;

if (!$documentId || !$folderId) {
    die("Erreur : Document ou dossier introuvable.");
}

// Récupérer les informations du document
$document = getDocumentById($documentId);
if (!$document) {
    die("Erreur : Document introuvable.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $signatureData = $_POST['signature'];

    if (addSignatureToDocument($document['file_path'], $signatureData)) {
        markDocumentAsSigned($documentId);
        header("Location: document.php?folder_id=$folderId");
        exit();
    } else {
        echo "Erreur lors de l'ajout de la signature.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Signer le Document</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsignature"></script>
    <style>
        /* Ajoutez ici votre CSS si nécessaire */
    </style>
</head>
<body>
<div class="container mt-5">
    <h1>Signer le Document</h1>
    <form method="POST">
        <div id="signature-pad" class="mb-3"></div>
        <input type="hidden" name="signature" id="signature">
        <button type="submit" class="btn btn-primary">Soumettre la signature</button>
    </form>
</div>
<script>
    $(document).ready(function() {
        var $sigdiv = $("#signature-pad").jSignature({'UndoButton':true});

        $("form").on("submit", function(e) {
            var datapair = $sigdiv.jSignature("getData", "image");
            $("#signature").val(datapair);
        });
    });
</script>
</body>
</html>
