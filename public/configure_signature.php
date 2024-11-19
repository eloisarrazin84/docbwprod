<?php
if (!isset($_GET['token']) || !isset($_GET['fileName'])) {
    die('Paramètres manquants.');
}

$token = htmlspecialchars($_GET['token']);
$fileName = htmlspecialchars($_GET['fileName']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurer la Signature</title>
    <script src="https://cdn.docuseal.com/js/builder.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }
        h1 {
            font-size: 1.8rem;
            color: #333;
        }
        #docuSealContainer {
            margin-top: 20px;
            border: 1px solid #ddd;
            padding: 10px;
            background-color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <h1>Configurer la Signature pour <?= $fileName ?></h1>
    <!-- Intégration du composant DocuSeal -->
    <div id="docuSealContainer">
        <docuseal-builder data-token="<?= $token ?>"></docuseal-builder>
    </div>
</body>
</html>
