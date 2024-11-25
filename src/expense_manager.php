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
        error_log("Erreur PDO : " . $e->getMessage());
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
        error_log("Erreur PDO : " . $e->getMessage());
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
        error_log("Erreur PDO : " . $e->getMessage());
        return false;
    }
}
