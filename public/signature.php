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
    $signature = $_POST['signature'];

    if (addSignatureToDocument($document['file_path'], $signature)) {
        markDocumentAsSigned($documentId);
        header("Location: document.php?folder_id=$folderId");
        exit();
    } else {
        echo "Erreur lors de l'ajout de la signature.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Signer le Document</title>
    <script src="https://cdn.jsdelivr.net/npm/jsignature"></script>
</head>
<body>
    <h1>Signer le Document</h1>
    <form method="POST">
        <div id="signature-pad"></div>
        <input type="hidden" name="signature" id="signature">
        <button type="submit">Soumettre</button>
    </form>
    <script>
        const pad = $("#signature-pad").jSignature();
        document.querySelector("form").addEventListener("submit", function (e) {
            e.preventDefault();
            document.querySelector("#signature").value = pad.jSignature("getData", "image");
            this.submit();
        });
    </script>
</body>
</html>
