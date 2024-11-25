<?php
require 'db_connect.php';

function createExpense($userId, $description, $amount, $category, $expenseDate, $comment, $receiptPath = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO expense_notes (user_id, description, amount, category, expense_date, comment, receipt_path, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'brouillon')
        ");
        $stmt->execute([$userId, $description, $amount, $category, $expenseDate, $comment, $receiptPath]);
        return true;
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
        return false;
    }
}

function listExpensesByUser($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT * 
            FROM expense_notes 
            WHERE user_id = ? 
            ORDER BY date_submitted DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
        return [];
    }
}

function listAllExpenses() {
    global $pdo;
    try {
        $stmt = $pdo->query("
            SELECT e.*, u.name AS user_name, u.email AS user_email 
            FROM expense_notes e 
            JOIN users u ON e.user_id = u.id 
            WHERE e.status != 'brouillon' 
            ORDER BY e.date_submitted DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
        return [];
    }
}

function updateExpenseStatus($expenseId, $status) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            UPDATE expense_notes 
            SET status = ? 
            WHERE id = ?
        ");
        $stmt->execute([$status, $expenseId]);
        return true;
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
        return false;
    }
}

function deleteExpense($expenseId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            DELETE FROM expense_notes 
            WHERE id = ?
        ");
        $stmt->execute([$expenseId]);
        return true;
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
        return false;
    }
}

function getExpenseDetails($expenseId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT * 
            FROM expense_notes 
            WHERE id = ?
        ");
        $stmt->execute([$expenseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
        return null;
    }
}
?>
