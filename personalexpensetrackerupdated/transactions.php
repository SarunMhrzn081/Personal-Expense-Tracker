<?php
require_once 'config.php';

class Transaction {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Add new transaction
    public function add($userId, $type, $description, $amount, $category, $date) {
        // Validate amount is positive
        if ($amount <= 0) {
            return ["success" => false, "message" => "Amount must be greater than 0"];
        }
        
        // Validate date is not in the past (if needed, remove this validation for past dates)
        // $today = date('Y-m-d');
        // if ($date > $today) {
        //     return ["success" => false, "message" => "Date cannot be in the future"];
        // }
        
        $stmt = $this->pdo->prepare("INSERT INTO transactions (user_id, type, description, amount, category, date) VALUES (?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$userId, $type, $description, $amount, $category, $date])) {
            return ["success" => true, "message" => "Transaction added successfully"];
        }
        
        return ["success" => false, "message" => "Failed to add transaction"];
    }
    
    // Get all transactions for a user
    public function getAll($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY date DESC, created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get transaction by ID
    public function getById($id, $userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Update transaction
    public function update($id, $userId, $type, $description, $amount, $category, $date) {
        // Validate amount is positive
        if ($amount <= 0) {
            return ["success" => false, "message" => "Amount must be greater than 0"];
        }
        
        // Validate date is not in the past (if needed)
        // $today = date('Y-m-d');
        // if ($date > $today) {
        //     return ["success" => false, "message" => "Date cannot be in the future"];
        // }
        
        $stmt = $this->pdo->prepare("UPDATE transactions SET type = ?, description = ?, amount = ?, category = ?, date = ? WHERE id = ? AND user_id = ?");
        
        if ($stmt->execute([$type, $description, $amount, $category, $date, $id, $userId])) {
            return ["success" => true, "message" => "Transaction updated successfully"];
        }
        
        return ["success" => false, "message" => "Failed to update transaction"];
    }
    
    // Delete transaction
    public function delete($id, $userId) {
        $stmt = $this->pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
        
        if ($stmt->execute([$id, $userId])) {
            return ["success" => true, "message" => "Transaction deleted successfully"];
        }
        
        return ["success" => false, "message" => "Failed to delete transaction"];
    }
    
    // Clear all transactions for a user
    public function clearAll($userId) {
        $stmt = $this->pdo->prepare("DELETE FROM transactions WHERE user_id = ?");
        
        if ($stmt->execute([$userId])) {
            return ["success" => true, "message" => "All transactions cleared successfully"];
        }
        
        return ["success" => false, "message" => "Failed to clear transactions"];
    }
    
    // Get financial summary
    public function getSummary($userId) {
        // Total expenses
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE user_id = ? AND type = 'expense'");
        $stmt->execute([$userId]);
        $totalExpense = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total income
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE user_id = ? AND type = 'income'");
        $stmt->execute([$userId]);
        $totalIncome = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Balance
        $balance = $totalIncome - $totalExpense;
        
        // Category-wise expenses
        $stmt = $this->pdo->prepare("SELECT category, COALESCE(SUM(amount), 0) as total FROM transactions WHERE user_id = ? AND type = 'expense' GROUP BY category");
        $stmt->execute([$userId]);
        $categoryExpenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $categoryTotals = [];
        foreach ($categoryExpenses as $category) {
            $categoryTotals[$category['category']] = $category['total'];
        }
        
        return [
            'total_expense' => $totalExpense,
            'total_income' => $totalIncome,
            'balance' => $balance,
            'category_totals' => $categoryTotals
        ];
    }
}

$transaction = new Transaction($pdo);
?>