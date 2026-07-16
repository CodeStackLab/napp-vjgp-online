<?php
// api/transactions.php

function handleTransactions($action, $pdo, $body) {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit();
    $userId = authenticateToken();

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$action) {
        $stmt = $pdo->prepare('SELECT * FROM transactions WHERE user_id = ? ORDER BY id DESC');
        $stmt->execute([$userId]);
        sendJson($stmt->fetchAll());
    }

    sendJson(['message' => 'Invalid Transactions Action'], 404);
}
?>
