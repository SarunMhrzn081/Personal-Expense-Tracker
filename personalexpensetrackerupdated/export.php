<?php
require_once 'auth.php';
require_once 'transactions.php';

// Require login
requireLogin();

$user = $auth->getCurrentUser();
$transactions = $transaction->getAll($user['id']);

// Set headers for download
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="expense-tracker-data-' . date('Y-m-d') . '.json"');

// Prepare data for export
$exportData = [
    'user' => [
        'name' => $user['name'],
        'email' => $user['email'],
        'export_date' => date('Y-m-d H:i:s')
    ],
    'transactions' => $transactions
];

// Output JSON
echo json_encode($exportData, JSON_PRETTY_PRINT);
exit();
?>