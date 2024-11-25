<?php
require 'db_connect.php';

function createExpense($userId, $description, $amount, $category, $receiptPath = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO expense_notes (user_id, description, amount, category, receipt_path) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $description, $amount, $category, $receiptPath]);
        return true;
    } catch (PDOException $e) {
        error_log("Erreur PDO (createExpense) : " . $e->getMessage());
        return false;
    }
}

function listExpensesByUser($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM expense_notes WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur PDO (listExpensesByUser) : " . $e->getMessage());
        return [];
    }
}

function listAllExpenses() {
    global $pdo;
    try {
        $stmt = $pdo->query("
            SELECT 
                e.id, 
                e.description, 
                e.amount, 
                e.category, 
                e.status, 
                e.date_submitted, 
                e.receipt_path, 
                u.name AS user_name, 
                u.email AS user_email
            FROM expense_notes e
            INNER JOIN users u ON e.user_id = u.id
            ORDER BY e.date_submitted DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur PDO (listAllExpenses) : " . $e->getMessage());
        return [];
    }
}

function updateExpenseStatus($expenseId, $status) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE expense_notes SET status = ? WHERE id = ?");
        $stmt->execute([$status, $expenseId]);
        return true;
    } catch (PDOException $e) {
        error_log("Erreur PDO (updateExpenseStatus) : " . $e->getMessage());
        return false;
    }
}

function getExpenseById($expenseId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM expense_notes WHERE id = ?");
        $stmt->execute([$expenseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur PDO (getExpenseById) : " . $e->getMessage());
        return null;
    }
}
?>
