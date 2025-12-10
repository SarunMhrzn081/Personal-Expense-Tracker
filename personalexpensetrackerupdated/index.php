<?php
require_once 'auth.php';
require_once 'transactions.php';

// Require login - redirect to login.php if not logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user = $auth->getCurrentUser();
$transactions = $transaction->getAll($user['id']);
$summary = $transaction->getSummary($user['id']);

// Check if editing a transaction
$editingTransaction = null;
if (isset($_GET['edit'])) {
    $editingTransaction = $transaction->getById($_GET['edit'], $user['id']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_transaction']) || isset($_POST['update_transaction'])) {
        $type = $_POST['type'] ?? 'expense';
        $description = $_POST['description'] ?? '';
        $amount = floatval($_POST['amount'] ?? 0);
        $category = $_POST['category'] ?? 'other';
        $date = $_POST['date'] ?? date('Y-m-d');
        $transactionId = $_POST['transaction_id'] ?? null;
        
        // Validate amount is positive
        if ($amount <= 0) {
            $message = "Amount must be greater than 0";
        } else {
            if (isset($_POST['update_transaction']) && $transactionId) {
                // Update existing transaction
                $result = $transaction->update($transactionId, $user['id'], $type, $description, $amount, $category, $date);
                $message = $result['message'];
                if ($result['success']) {
                    header("Location: index.php?message=" . urlencode($message));
                    exit();
                }
            } else {
                // Add new transaction
                $result = $transaction->add($user['id'], $type, $description, $amount, $category, $date);
                $message = $result['message'];
                if ($result['success']) {
                    header("Location: index.php?message=" . urlencode($message));
                    exit();
                }
            }
        }
    }
    
    if (isset($_POST['delete_transaction'])) {
        $id = $_POST['transaction_id'] ?? 0;
        $result = $transaction->delete($id, $user['id']);
        header("Location: index.php?message=" . urlencode($result['message']));
        exit();
    }
    
    if (isset($_POST['clear_all'])) {
        $result = $transaction->clearAll($user['id']);
        header("Location: index.php?message=" . urlencode($result['message']));
        exit();
    }
}

// Get message from URL
$message = $_GET['message'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Expense Tracker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #0f172a;
            color: #e2e8f0;
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px 0;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1rem;
            color: #94a3b8;
        }

        .user-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #1e293b;
            border-radius: 16px;
            padding: 20px 30px;
            margin-bottom: 30px;
            border: 1px solid #334155;
        }

        .user-details {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #3b82f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .user-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #f8fafc;
        }

        .user-email {
            font-size: 0.9rem;
            color: #94a3b8;
        }

        .navigation {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .btn-full {
            width: 100%;
            justify-content: center;
        }

        .btn-danger {
            background: #ef4444;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-success {
            background: #10b981;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-secondary {
            background: #64748b;
        }

        .btn-secondary:hover {
            background: #475569;
        }

        .btn-warning {
            background: #f59e0b;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .card {
            background: #1e293b;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            border: 1px solid #334155;
        }

        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }

        .card-header i {
            font-size: 1.5rem;
            color: #3b82f6;
            margin-right: 12px;
        }

        .card-header h2 {
            font-size: 1.5rem;
            color: #f8fafc;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #cbd5e1;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            background: #0f172a;
            border: 2px solid #334155;
            border-radius: 8px;
            color: #e2e8f0;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
        }

        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .summary-card {
            background: #1e293b;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            border: 1px solid #334155;
        }

        .summary-card i {
            font-size: 2rem;
            margin-bottom: 12px;
        }

        .summary-card.total i {
            color: #ef4444;
        }

        .summary-card.food i {
            color: #f59e0b;
        }

        .summary-card.transport i {
            color: #10b981;
        }

        .summary-card.entertainment i {
            color: #8b5cf6;
        }

        .summary-card.other i {
            color: #06b6d4;
        }

        .summary-card.income i {
            color: #10b981;
        }

        .summary-card.balance i {
            color: #3b82f6;
        }

        .summary-amount {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .summary-label {
            font-size: 0.9rem;
            color: #94a3b8;
            text-transform: uppercase;
            font-weight: 500;
        }

        .expenses-list {
            background: #1e293b;
            border-radius: 16px;
            border: 1px solid #334155;
        }

        .expenses-header {
            padding: 30px 30px 0;
        }

        .expenses-header h2 {
            font-size: 1.5rem;
            color: #f8fafc;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .expense-item {
            padding: 20px 30px;
            border-bottom: 1px solid #334155;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .expense-item:last-child {
            border-bottom: none;
        }

        .expense-info {
            flex: 1;
            display: flex;
            align-items: center;
        }

        .expense-description {
            font-size: 1.1rem;
            color: #f8fafc;
            margin-bottom: 4px;
        }

        .expense-meta {
            font-size: 0.9rem;
            color: #94a3b8;
        }

        .expense-amount {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .expense-amount.negative {
            color: #ef4444;
        }

        .expense-amount.positive {
            color: #10b981;
        }

        .category-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            font-size: 1.2rem;
        }

        .category-food {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }

        .category-transport {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .category-entertainment {
            background: rgba(139, 92, 246, 0.2);
            color: #8b5cf6;
        }

        .category-other {
            background: rgba(6, 182, 212, 0.2);
            color: #06b6d4;
        }

        .category-income {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .empty-state {
            text-align: center;
            padding: 60px 30px;
        }

        .empty-state i {
            font-size: 4rem;
            color: #475569;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: #94a3b8;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #64748b;
        }

        .transaction-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 1rem;
            transition: color 0.3s ease;
            padding: 5px;
        }

        .action-btn:hover {
            color: #f8fafc;
        }

        .action-btn.edit:hover {
            color: #3b82f6;
        }

        .action-btn.delete:hover {
            color: #ef4444;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            font-weight: 600;
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            max-width: 300px;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.error {
            background: #ef4444;
        }

        @media (max-width: 768px) {
            .main-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .card {
                padding: 20px;
            }
            
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .user-info {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .transaction-actions {
                margin-top: 10px;
            }
            
            .navigation {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-wallet"></i> Personal Expense Tracker</h1>
            <p>Take control of your finances and track your spending</p>
        </div>

        <!-- User Info Section -->
        <div class="user-info">
            <div class="user-details">
                <div class="user-avatar"><?php echo htmlspecialchars($user['avatar']); ?></div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
            </div>
            <a href="logout.php" class="btn btn-secondary">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>

        <!-- Navigation -->
        <div class="navigation">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="expense_report.php" class="btn btn-secondary">
                <i class="fas fa-chart-pie"></i> Expense Report
            </a>
            <a href="income_report.php" class="btn btn-secondary">
                <i class="fas fa-chart-line"></i> Income Report
            </a>
        </div>

        <?php if ($message): ?>
            <div class="notification show"><?php echo htmlspecialchars($message); ?></div>
            <script>
                setTimeout(() => {
                    document.querySelector('.notification').classList.remove('show');
                }, 3000);
            </script>
        <?php endif; ?>

        <?php if (isset($message) && !empty($message) && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="notification show error"><?php echo htmlspecialchars($message); ?></div>
            <script>
                setTimeout(() => {
                    document.querySelector('.notification').classList.remove('show');
                }, 3000);
            </script>
        <?php endif; ?>

        <div class="main-grid">
            <!-- Add/Edit Transaction Form -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i>
                    <h2><?php echo $editingTransaction ? 'Edit Transaction' : 'Add Transaction'; ?></h2>
                </div>
                <form method="POST" action="">
                    <?php if ($editingTransaction): ?>
                        <input type="hidden" name="transaction_id" value="<?php echo $editingTransaction['id']; ?>">
                        <input type="hidden" name="update_transaction" value="1">
                    <?php else: ?>
                        <input type="hidden" name="add_transaction" value="1">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="type">Transaction Type</label>
                        <select id="type" name="type" class="form-control" required>
                            <option value="expense" <?php echo ($editingTransaction && $editingTransaction['type'] == 'expense') ? 'selected' : ''; ?>>Expense</option>
                            <option value="income" <?php echo ($editingTransaction && $editingTransaction['type'] == 'income') ? 'selected' : ''; ?>>Income</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <input type="text" id="description" name="description" class="form-control" 
                               placeholder="What is this transaction for?" 
                               value="<?php echo $editingTransaction ? htmlspecialchars($editingTransaction['description']) : ''; ?>" 
                               required>
                    </div>
                    <div class="form-group">
                        <label for="amount">Amount (Nrs)</label>
                        <input type="number" id="amount" name="amount" class="form-control" 
                               placeholder="0.00" step="0.01" min="0.01" 
                               value="<?php echo $editingTransaction ? $editingTransaction['amount'] : ''; ?>" 
                               required>
                        <small style="color: #94a3b8; font-size: 0.8rem;">Amount must be greater than 0</small>
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" class="form-control" required>
                            <option value="">Select a category</option>
                            <option value="food" <?php echo ($editingTransaction && $editingTransaction['category'] == 'food') ? 'selected' : ''; ?>>Food & Dining</option>
                            <option value="transport" <?php echo ($editingTransaction && $editingTransaction['category'] == 'transport') ? 'selected' : ''; ?>>Transportation</option>
                            <option value="entertainment" <?php echo ($editingTransaction && $editingTransaction['category'] == 'entertainment') ? 'selected' : ''; ?>>Entertainment</option>
                            <option value="other" <?php echo ($editingTransaction && $editingTransaction['category'] == 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" class="form-control" 
                               value="<?php echo $editingTransaction ? $editingTransaction['date'] : date('Y-m-d'); ?>" 
                               max="<?php echo date('Y-m-d'); ?>" 
                               required>
                        <small style="color: #94a3b8; font-size: 0.8rem;">Cannot select future dates</small>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-full <?php echo $editingTransaction ? 'btn-warning' : 'btn-success'; ?>">
                            <i class="fas <?php echo $editingTransaction ? 'fa-save' : 'fa-plus'; ?>"></i>
                            <?php echo $editingTransaction ? 'Update Transaction' : 'Add Transaction'; ?>
                        </button>
                        <?php if ($editingTransaction): ?>
                            <a href="index.php" class="btn btn-secondary" style="flex: 1; text-align: center;">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Quick Stats -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-line"></i>
                    <h2>Quick Actions</h2>
                </div>
                <div style="text-align: center;">
                    <p style="color: #94a3b8; margin-bottom: 20px;">Stay on top of your finances with smart tracking</p>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <button class="btn btn-danger" onclick="if(confirm('Are you sure you want to clear all transactions?')) { document.getElementById('clear-form').submit(); }">
                            <i class="fas fa-trash"></i>
                            Clear All Transactions
                        </button>
                        <a href="export.php" class="btn btn-secondary">
                            <i class="fas fa-download"></i>
                            Export Data
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Clear All Form -->
        <form id="clear-form" method="POST" action="" style="display: none;">
            <input type="hidden" name="clear_all" value="1">
        </form>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="summary-card total">
                <i class="fas fa-money-bill-wave"></i>
                <div class="summary-amount">रु<?php echo number_format($summary['total_expense'], 2); ?></div>
                <div class="summary-label">Total Expenses</div>
            </div>
            <div class="summary-card income">
                <i class="fas fa-hand-holding-usd"></i>
                <div class="summary-amount">रु<?php echo number_format($summary['total_income'], 2); ?></div>
                <div class="summary-label">Total Income</div>
            </div>
            <div class="summary-card balance">
                <i class="fas fa-balance-scale"></i>
                <div class="summary-amount" style="color: <?php echo $summary['balance'] >= 0 ? '#10b981' : '#ef4444'; ?>">
                    रु<?php echo number_format($summary['balance'], 2); ?>
                </div>
                <div class="summary-label">Balance</div>
            </div>
            <div class="summary-card food">
                <i class="fas fa-utensils"></i>
                <div class="summary-amount">रु<?php echo number_format($summary['category_totals']['food'] ?? 0, 2); ?></div>
                <div class="summary-label">Food & Dining</div>
            </div>
            <div class="summary-card transport">
                <i class="fas fa-car"></i>
                <div class="summary-amount">रु<?php echo number_format($summary['category_totals']['transport'] ?? 0, 2); ?></div>
                <div class="summary-label">Transportation</div>
            </div>
            <div class="summary-card entertainment">
                <i class="fas fa-gamepad"></i>
                <div class="summary-amount">रु<?php echo number_format($summary['category_totals']['entertainment'] ?? 0, 2); ?></div>
                <div class="summary-label">Entertainment</div>
            </div>
        </div>

        <!-- Transactions List -->
        <div class="expenses-list">
            <div class="expenses-header">
                <h2><i class="fas fa-list"></i> Recent Transactions</h2>
            </div>
            <div id="transactions-container">
                <?php if (empty($transactions)): ?>
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <h3>No transactions yet</h3>
                        <p>Add your first transaction to get started tracking your finances</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($transactions as $transactionItem): ?>
                        <div class="expense-item">
                            <div class="expense-info">
                                <div class="category-icon category-<?php echo $transactionItem['category']; ?>">
                                    <i class="<?php 
                                        switch($transactionItem['category']) {
                                            case 'food': echo 'fas fa-utensils'; break;
                                            case 'transport': echo 'fas fa-car'; break;
                                            case 'entertainment': echo 'fas fa-gamepad'; break;
                                            default: echo 'fas fa-shopping-bag';
                                        }
                                    ?>"></i>
                                </div>
                                <div>
                                    <div class="expense-description"><?php echo htmlspecialchars($transactionItem['description']); ?></div>
                                    <div class="expense-meta">
                                        <?php echo date('M j, Y', strtotime($transactionItem['date'])); ?> • 
                                        <?php echo ucfirst($transactionItem['category']); ?>
                                        <?php echo $transactionItem['type'] === 'income' ? ' • Income' : ' • Expense'; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="expense-amount <?php echo $transactionItem['type'] === 'income' ? 'positive' : 'negative'; ?>">
                                <?php echo $transactionItem['type'] === 'income' ? '+' : '-'; ?>रु<?php echo number_format($transactionItem['amount'], 2); ?>
                            </div>
                            <div class="transaction-actions">
                                <a href="index.php?edit=<?php echo $transactionItem['id']; ?>" class="action-btn edit" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="transaction_id" value="<?php echo $transactionItem['id']; ?>">
                                    <input type="hidden" name="delete_transaction" value="1">
                                    <button type="submit" class="action-btn delete" title="Delete" onclick="return confirm('Are you sure you want to delete this transaction?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Notification auto-hide
        document.addEventListener('DOMContentLoaded', function() {
            const notification = document.querySelector('.notification');
            if (notification) {
                setTimeout(() => {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }, 3000);
            }
            
            // Prevent negative values in amount input
            const amountInput = document.getElementById('amount');
            if (amountInput) {
                amountInput.addEventListener('input', function() {
                    if (this.value < 0) {
                        this.value = 0;
                    }
                });
            }
            
            // Set max date to today
            const dateInput = document.getElementById('date');
            if (dateInput) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.max = today;
            }
            
            // Prevent form submission if amount is negative
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const amountInput = this.querySelector('input[name="amount"]');
                    if (amountInput && parseFloat(amountInput.value) <= 0) {
                        e.preventDefault();
                        alert('Amount must be greater than 0');
                        amountInput.focus();
                    }
                });
            });
        });
    </script>
</body>
</html>