<?php
require 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
$externalId = $data['external_id'];
$status = $data['status']; // "completed" ou autre

if ($status === 'completed') {
    // Mettre à jour l'état du document dans la base de données
    $stmt = $pdo->prepare("UPDATE documents SET signature_status = 'completed' WHERE external_id = ?");
    $stmt->execute([$externalId]);
}
http_response_code(200);
