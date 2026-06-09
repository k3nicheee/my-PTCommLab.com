<?php
header('Content-Type: application/json');

// ============================================
// UPDATE THESE TWO VALUES WITH YOUR REAL DATA
// ============================================
$apiKey  = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiMThkODc1NDc2YThhYmQxNGQ2NzU0YWU4YTZkOWQ2OTAzMDM4YzQ2NWRmNGMxMWY4Y2RhNjcyYTE3NjkzNzg2MzEwNzlmZjI4Y2ZkM2QzYzEiLCJpYXQiOjE3Nzk2NDU5OTguNTUxNDQ2LCJuYmYiOjE3Nzk2NDU5OTguNTUxNDQ4LCJleHAiOjQ5MzMyNDU5OTguNTQ5MzY2LCJzdWIiOiIxMDczNDcyIiwic2NvcGVzIjpbXX0.TP06aWpPm4ypaD_JCimpR26W7LQ2E9rrjzOyb0CyL5G2Uc2LxsGSHM21FFvdPd5LSWXTEmqMxBKeUbg9dObLhw6tztYljgoi6XtzhzpFsQkWLhBdBcHybQ37wD593SPzjX74qSbM1vbQxsDzwVysXG-DkIbfJzDiAG9Tnj82mJwDCPWRfQL6ZBbd8FpwB1E0MPUDst6-vGXHv-dH6LF_ePPz0N5XjDAm-wt88siDUa2iDUun6iq8XJAfjpWydsNYacQeV4zsz6n1kZM-CibU7VyQJ8TZ9woP-CW9sPs280GfhWqFcV7SQ7Gaq4cwmigXvjhwiP81PggtXC8i3zkuiPt0waBfceiZhmAEwJFh6BV5oZcgRtlGGy-viXvEFUnlJLoABjZy1IAPkEmXwRscgw1QqqjjSYiBrq7bgM0MRNVbIOjrhWCCgWDltgMX1QbagJ2lr-5p6SLaho8bGFk-tepK8_ZAVhAIj-C2Ykr-X8RisB0dQnQaRSl1q_Qt1t1eIcreFAIAKT4RKGhZDISHM6vAIw1egjaVxYdmjjqbYfTzIVjV0h3zVHiczo1_jtP3CopIYFhrrqOSvIhEDktIfNij-C3VkqGEGeNnCNjmgZocEF6xM2EP6fFjkvGuFYSxwgv22C7FFSooseSnPTcoHn6rEcsnvb7jirWmED4SbKQ';
$groupId = 'aMXgV3';
$saveToCSV = true;
$csvFile = __DIR__ . '/private/leads.csv';
// ============================================

// Read input — supports BOTH FormData AND JSON
$firstName = '';
$email = '';

// Check if it's a JSON request (Content-Type: application/json)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    // JSON request
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);
    if ($body) {
        $firstName = trim($body['first_name'] ?? '');
        $email = trim($body['email'] ?? '');
    }
} else {
    // FormData or regular POST
    $firstName = trim($_POST['first_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
}

// Validate email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Valid email required']);
    exit;
}

// Save to CSV (for backup)
if ($saveToCSV && $csvFile) {
    $dir = dirname($csvFile);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $handle = fopen($csvFile, 'a');
    if ($handle) {
        fputcsv($handle, [$firstName, $email, date('Y-m-d H:i:s'), $_SERVER['REMOTE_ADDR'] ?? '']);
        fclose($handle);
    }
}

// Call Sender.net API
$payload = json_encode(['email' => $email, 'firstname' => $firstName ?: '', 'groups' => [$groupId]]);
$ch = curl_init('https://api.sender.net/v2/subscribers');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'Authorization: Bearer ' . $apiKey],
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT => 15,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Facebook CAPI Lead event (fire regardless of Sender.net result)
$hashed_email = hash('sha256', strtolower(trim($email)));
$fbData = ['data' => [[
    'event_name' => 'Lead',
    'event_time' => time(),
    'action_source' => 'website',
    'user_data' => ['em' => [$hashed_email]],
    'custom_data' => ['content_name' => '5 Scripts Lead Magnet'],
    'event_source_url' => 'https://ptcommlab.com',
]]];
$fbCh = curl_init('https://graph.facebook.com/v18.0/2035990150320727/events?access_token=EAA9vZClb85dgBRiddcF8dFABLgfI2Wy7GVYbUlXYffVKCsnrZCBQIWE4knvuatMfkcpQnPcXwjVzBQ3dSijofvA3zkE10kqTKRoGHZAZCZCFzFXPlZBbGRUCdlwpLCPcKGBQHXoZCMRBAw0dnzLE64qLmsoJSxoZALAFscPTtyB7e6BRECBQgWPRcTsHPhWcSwZDZD');
curl_setopt($fbCh, CURLOPT_POSTFIELDS, json_encode($fbData));
curl_setopt($fbCh, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($fbCh, CURLOPT_RETURNTRANSFER, true);
curl_setopt($fbCh, CURLOPT_TIMEOUT, 5);
curl_exec($fbCh);
curl_close($fbCh);

// Return success response
if ($httpCode === 200 || $httpCode === 201) {
    echo json_encode([
        'success' => true,
        'message' => 'Check your email! Your free chapter is on its way.'
    ]);
} else {
    $senderError = json_decode($response, true);
    $detail = $senderError['message'] ?? ($senderError['error'] ?? 'Unknown error');
    error_log("Sender.net error detail: $detail");
    
    echo json_encode([
        'success' => false,
        'message' => 'Please try again or contact support.',
    ]);
}
?>