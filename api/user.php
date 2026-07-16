<?php
// api/user.php

function handleUser($action, $subaction, $pdo, $body) {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit();
    $userId = authenticateToken();

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'profile') {
        $stmt = $pdo->prepare('SELECT id, name, email, balance, earnings, role, referral_code, referred_by FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            sendJson(['message' => 'User not found'], 404);
        }

        $stmt = $pdo->prepare('SELECT SUM(amount) as activeSum FROM investments WHERE user_id = ? AND status = ?');
        $stmt->execute([$userId, 'Active']);
        $activeInvestmentsSum = $stmt->fetch();
        $activeTotal = $activeInvestmentsSum['activeSum'] ? (float)$activeInvestmentsSum['activeSum'] : 0.00;

        $stmt = $pdo->prepare('SELECT COUNT(*) as refCount FROM users WHERE referred_by = ?');
        $stmt->execute([$userId]);
        $refCountRow = $stmt->fetch();
        $totalReferrals = $refCountRow['refCount'] ? (int)$refCountRow['refCount'] : 0;

        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) as activeRefCount FROM investments JOIN users ON investments.user_id = users.id WHERE users.referred_by = ? AND investments.status = 'Active'");
        $stmt->execute([$userId]);
        $activeRefRow = $stmt->fetch();
        $activeReferralsCount = $activeRefRow['activeRefCount'] ? (int)$activeRefRow['activeRefCount'] : 0;

        $stmt = $pdo->prepare("SELECT SUM(amount) as comSum FROM transactions WHERE user_id = ? AND type = 'Referral Bonus'");
        $stmt->execute([$userId]);
        $comSumRow = $stmt->fetch();
        $totalComEarned = $comSumRow['comSum'] ? (float)$comSumRow['comSum'] : 0.00;

        $stmt = $pdo->prepare("SELECT SUM(amount) as refActiveSum FROM investments JOIN users ON investments.user_id = users.id WHERE users.referred_by = ? AND investments.status = 'Active'");
        $stmt->execute([$userId]);
        $refActiveSumRow = $stmt->fetch();
        $refActiveTotal = $refActiveSumRow['refActiveSum'] ? (float)$refActiveSumRow['refActiveSum'] : 0.00;

        $todayProfit = ($activeTotal * 0.025) + ($refActiveTotal * 0.0025);

        $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE referred_by = ? ORDER BY id DESC LIMIT 5');
        $stmt->execute([$userId]);
        $signupsRows = $stmt->fetchAll();

        $user['active_investments'] = $activeTotal;
        $user['today_profit'] = $todayProfit;
        $user['referralsStats'] = [
            'totalReferrals' => $totalReferrals,
            'activeReferralsCount' => $activeReferralsCount,
            'totalComEarned' => $totalComEarned,
            'signups' => $signupsRows
        ];

        sendJson($user);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'password') {
        $newPassword = $body['newPassword'] ?? '';
        if (strlen($newPassword) < 6) {
            sendJson(['message' => 'Password must be at least 6 characters'], 400);
        }
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$hashedPassword, $userId]);
        sendJson(['message' => 'Password updated successfully']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'change-email') {
        if ($subaction === 'send-otp') {
            $password = $body['password'] ?? '';
            $newEmail = $body['newEmail'] ?? '';

            if (!$password || !$newEmail) {
                sendJson(['message' => 'Current password and new email are required'], 400);
            }

            $stmt = $pdo->prepare('SELECT password, email FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                sendJson(['message' => 'Incorrect password'], 400);
            }

            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$newEmail, $userId]);
            if ($stmt->fetch()) {
                sendJson(['message' => 'New email address is already in use'], 400);
            }

            file_put_contents('../otp_' . $userId . '_email.txt', '123456'); // Mocking OTP
            sendJson(['message' => 'OTP sent to your new email! (Use 123456 for now)']);
        }

        if ($subaction === 'verify') {
            $newEmail = $body['newEmail'] ?? '';
            $otpCode = $body['otpCode'] ?? '';

            if (!$newEmail || !$otpCode) {
                sendJson(['message' => 'New email and OTP code are required'], 400);
            }

            $storedOtp = @file_get_contents('../otp_' . $userId . '_email.txt');
            if ($otpCode !== '123456' && $otpCode !== $storedOtp) {
                sendJson(['message' => 'Invalid OTP code'], 400);
            }

            @unlink('../otp_' . $userId . '_email.txt');
            $stmt = $pdo->prepare('UPDATE users SET email = ?, username = ? WHERE id = ?');
            $stmt->execute([$newEmail, $newEmail, $userId]);

            sendJson(['message' => 'Email address updated successfully!']);
        }
    }

    sendJson(['message' => 'Invalid User Action'], 404);
}
?>
