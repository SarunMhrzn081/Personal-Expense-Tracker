<?php
require_once 'auth.php';
require_once 'transactions.php';

// Require login
requireLogin();

$user = $auth->getCurrentUser();
$transactions = $transaction->getAll($user['id']);

// Filter only income transactions
$incomeTransactions = array_filter($transactions, function($transaction) {
    return $transaction['type'] === 'income';
});

// Sort by date (newest first)
usort($incomeTransactions, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Calculate total income
$totalIncome = array_sum(array_column($incomeTransactions, 'amount'));

// Calculate income by category
$incomeByCategory = [];
foreach ($incomeTransactions as $transaction) {
    $category = $transaction['category'];
    if (!isset($incomeByCategory[$category])) {
        $incomeByCategory[$category] = 0;
    }
    $incomeByCategory[$category] += $transaction['amount'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income Report - Expense Tracker</title>
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

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
            color: #10b981;
        }

        .summary-amount {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 4px;
            color: #10b981;
        }

        .summary-label {
            font-size: 0.9rem;
            color: #94a3b8;
            text-transform: uppercase;
            font-weight: 500;
        }

        .category-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .category-stat {
            background: #1e293b;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid #334155;
        }

        .category-stat i {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .category-stat.food i { color: #f59e0b; }
        .category-stat.transport i { color: #10b981; }
        .category-stat.entertainment i { color: #8b5cf6; }
        .category-stat.other i { color: #06b6d4; }

        .category-amount {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 4px;
            color: #10b981;
        }

        .category-name {
            font-size: 0.9rem;
            color: #94a3b8;
            text-transform: uppercase;
            font-weight: 500;
        }

        .transactions-list {
            background: #1e293b;
            border-radius: 16px;
            border: 1px solid #334155;
        }

        .transactions-header {
            padding: 30px 30px 0;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .transactions-header h2 {
            font-size: 1.5rem;
            color: #f8fafc;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .transaction-item {
            padding: 20px 30px;
            border-bottom: 1px solid #334155;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .transaction-item:last-child {
            border-bottom: none;
        }

        .transaction-info {
            flex: 1;
            display: flex;
            align-items: center;
        }

        .transaction-description {
            font-size: 1.1rem;
            color: #f8fafc;
            margin-bottom: 4px;
        }

        .transaction-meta {
            font-size: 0.9rem;
            color: #94a3b8;
        }

        .transaction-amount {
            font-size: 1.2rem;
            font-weight: 700;
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

        .navigation {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
            
            .summary-grid, .category-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .user-info {
                flex-direction: column;
                gap: 15px;
                text-align: center;
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
            <h1><i class="fas fa-chart-line"></i> Income Report</h1>
            <p>Track your income sources and patterns</p>
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
            <div>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="logout.php" class="btn btn-secondary">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Navigation -->
        <div class="navigation">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="expense_report.php" class="btn btn-secondary">
                <i class="fas fa-chart-pie"></i> Expense Report
            </a>
            <a href="income_report.php" class="btn btn-success">
                <i class="fas fa-chart-line"></i> Income Report
            </a>
        </div>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="summary-card">
                <i class="fas fa-hand-holding-usd"></i>
                <div class="summary-amount">रु<?php echo number_format($totalIncome, 2); ?></div>
                <div class="summary-label">Total Income</div>
            </div>
            <div class="summary-card">
                <i class="fas fa-receipt"></i>
                <div class="summary-amount"><?php echo count($incomeTransactions); ?></div>
                <div class="summary-label">Income Transactions</div>
            </div>
        </div>

        <!-- Category Statistics -->
        <?php if (!empty($incomeByCategory)): ?>
        <div class="category-stats">
            <?php foreach ($incomeByCategory as $category => $amount): ?>
                <div class="category-stat <?php echo $category; ?>">
                    <i class="<?php 
                        switch($category) {
                            case 'food': echo 'fas fa-utensils'; break;
                            case 'transport': echo 'fas fa-car'; break;
                            case 'entertainment': echo 'fas fa-gamepad'; break;
                            default: echo 'fas fa-shopping-bag';
                        }
                    ?>"></i>
                    <div class="category-amount">रु<?php echo number_format($amount, 2); ?></div>
                    <div class="category-name"><?php echo ucfirst($category); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Income Transactions List -->
        <div class="transactions-list">
            <div class="transactions-header">
                <h2><i class="fas fa-list"></i> Income Transactions</h2>
            </div>
            <div id="transactions-container">
                <?php if (empty($incomeTransactions)): ?>
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <h3>No income transactions yet</h3>
                        <p>Add your first income transaction to get started</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($incomeTransactions as $transactionItem): ?>
                        <div class="transaction-item">
                            <div class="transaction-info">
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
                                    <div class="transaction-description"><?php echo htmlspecialchars($transactionItem['description']); ?></div>
                                    <div class="transaction-meta">
                                        <?php echo date('M j, Y', strtotime($transactionItem['date'])); ?> • 
                                        <?php echo ucfirst($transactionItem['category']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="transaction-amount">
                                +रु<?php echo number_format($transactionItem['amount'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>