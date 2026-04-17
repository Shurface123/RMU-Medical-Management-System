<?php
/**
 * Server-Sent Events (SSE) Endpoint for Broadcasts
 * Pushes new broadcasts to connected clients in real-time.
 */
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable buffering for Nginx/WAMP

require_once '../db_conn.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    echo "data: " . json_encode(['error' => 'Unauthorized']) . "\n\n";
    exit();
}

$user_id = (int)$_SESSION['user_id'];
session_write_close(); // Release session lock for long-running process
$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

// Function to send event
function sendEvent($data) {
    echo "data: " . json_encode($data) . "\n\n";
    ob_flush();
    flush();
}

// Initial connection success
sendEvent(['status' => 'connected', 'timestamp' => time()]);

// Main loop
$start_time = time();
while (time() - $start_time < 30) { // Timeout after 30 seconds to prevent lingering processes
    // Check for new broadcasts assigned to this user that haven't been delivered/seen yet
    // We check for broadcasts sent after the 'last_id' or simply unread ones
    $query = "SELECT b.id, b.subject, b.body, b.priority, b.requires_acknowledgement, b.attachment_path, b.sender_id, b.created_at
              FROM broadcasts b
              JOIN broadcast_recipients r ON b.id = r.broadcast_id
              WHERE r.recipient_id = ? 
              AND b.status = 'Sent'
              AND (b.expires_at IS NULL OR b.expires_at > NOW())
              AND r.delivered_at IS NULL
              LIMIT 5";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $broadcasts = [];
        while ($row = $result->fetch_assoc()) {
            $broadcasts[] = $row;
            // Mark as delivered in the loop so we don't send it again
            $upd = $conn->prepare("UPDATE broadcast_recipients SET delivered_at = NOW() WHERE broadcast_id = ? AND recipient_id = ?");
            $upd->bind_param("ii", $row['id'], $user_id);
            $upd->execute();
        }
        sendEvent(['type' => 'new_broadcast', 'data' => $broadcasts]);
    }

    // Check if connection is still alive
    if (connection_aborted()) break;

    usleep(2000000); // Wait 2 seconds before next check
}

echo "event: close\ndata: timeout\n\n";
?>
