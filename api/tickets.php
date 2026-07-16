<?php
// api/tickets.php

function handleTickets($action, $pdo, $body) {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit();
    $userId = authenticateToken();

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$action) {
        $stmt = $pdo->prepare('SELECT * FROM tickets WHERE user_id = ? ORDER BY id DESC');
        $stmt->execute([$userId]);
        sendJson($stmt->fetchAll());
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$action) {
        $title = $body['title'] ?? 'Support Query';
        $message = $body['message'] ?? '';
        $screenshotBase64 = $body['screenshotBase64'] ?? '';

        if (!$message) {
            sendJson(['message' => 'Message is required'], 400);
        }

        $savedImagePath = null;
        if ($screenshotBase64) {
            if (preg_match('/^data:([A-Za-z-+\/]+);base64,(.+)$/', $screenshotBase64, $matches)) {
                $type = $matches[1];
                $base64Data = base64_decode($matches[2]);
                $ext = explode('/', $type)[1] ?? 'png';
                $fileName = 'support_' . time() . '_' . substr(md5(uniqid()), 0, 6) . '.' . $ext;
                $uploadsDir = '../public/uploads/';
                if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
                
                $fullSavePath = $uploadsDir . $fileName;
                file_put_contents($fullSavePath, $base64Data);
                $savedImagePath = '/uploads/' . $fileName;
            }
        }

        $dateStr = date('M j, Y h:i A');
        $ticketId = "#" . rand(10000, 99999);

        try {
            $stmt = $pdo->prepare('UPDATE tickets SET status = ? WHERE user_id = ?');
            $stmt->execute(['Open', $userId]);

            $stmt = $pdo->prepare('INSERT INTO tickets (user_id, title, ticket_id, date, status, message, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$userId, $title, $ticketId, $dateStr, 'Open', $message, $savedImagePath]);

            sendJson(['message' => 'Support ticket submitted.']);
        } catch (Exception $e) {
            sendJson(['message' => 'Server error'], 500);
        }
    }

    sendJson(['message' => 'Invalid Tickets Action'], 404);
}
?>
