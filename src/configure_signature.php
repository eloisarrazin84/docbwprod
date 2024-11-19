<?php
if (!isset($_GET['token']) || !isset($_GET['fileName'])) {
    die('Token manquant ou fichier introuvable.');
}
$token = $_GET['token'];
$fileName = $_GET['fileName'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurer la Signature</title>
    <script src="https://cdn.docuseal.com/js/builder.js"></script>
</head>
<body>
    <h1>Configurer la Signature pour <?= htmlspecialchars($fileName) ?></h1>
    <docuseal-builder
        style="height: 600px; width: 100%;"
        data-token="<?= htmlspecialchars($token) ?>">
    </docuseal-builder>
</body>
</html>
