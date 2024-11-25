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
            SELECT id, description, amount, category, expense_date, comment, status, receipt_path 
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
    $validStatuses = ['brouillon', 'soumise', 'approuvé', 'rejeté']; // Correspond à l'ENUM
    if (!in_array($status, $validStatuses)) {
        error_log("Erreur : Statut invalide '{$status}' transmis.");
        return false;
    }

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

function updateExpense($expenseId, $description, $amount, $category, $expenseDate, $comment) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            UPDATE expense_notes 
            SET description = ?, amount = ?, category = ?, expense_date = ?, comment = ? 
            WHERE id = ?
        ");
        $stmt->execute([$description, $amount, $category, $expenseDate, $comment, $expenseId]);
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
            SELECT id, user_id, description, amount, category, expense_date, comment, status, receipt_path 
            FROM expense_notes 
            WHERE id = ?
        ");
        $stmt->execute([$expenseId]);
        $expense = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$expense) {
            throw new Exception("Aucune note de frais trouvée pour l'ID spécifié : $expenseId");
        }

        return $expense;
    } catch (Exception $e) {
        error_log("Erreur : " . $e->getMessage());
        return null;
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
        return null;
    }
}
?>
