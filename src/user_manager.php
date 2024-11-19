<?php
require 'db_connect.php';

function createUser($identifier, $name, $email, $password, $role = 'user') {
    global $pdo;
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (identifier, name, email, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$identifier, $name, $email, $hashedPassword, $role]);
}

function listUsers() {
    global $pdo;
    $stmt = $pdo->query("SELECT id, identifier, name, email, role FROM users");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
