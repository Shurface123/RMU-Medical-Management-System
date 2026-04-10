<?php
/**
 * Chatbot API — RMU Medical Sickbay
 * Handles chatbot queries: keyword intent matching against knowledge base
 * Logs conversations & messages to DB
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once dirname(__DIR__) . '/db_conn.php';

$action = $_POST['action'] ?? $_GET['action'] ?? 'query';
$response = ['success' => false];

switch ($action) {

    // ── Query: User sends a message ────────────────────────────
    case 'query':
        $msg       = trim($_POST['message'] ?? '');
        $session   = session_id() ?: (string)uniqid('bot_', true);
        $conv_id   = (int)($_POST['conversation_id'] ?? 0);

        if (empty($msg)) {
            $response = ['success' => false, 'error' => 'No message provided'];
            break;
        }

        // Start or continue conversation
        if (!$conv_id) {
            $sid = mysqli_real_escape_string($conn, $session);
            mysqli_query($conn, "INSERT INTO chatbot_conversations (session_id, message_count) VALUES ('$sid', 0)");
            $conv_id = (int)mysqli_insert_id($conn);
        }

        // Log user message
        $msg_esc   = mysqli_real_escape_string($conn, $msg);
        mysqli_query($conn,
            "INSERT INTO chatbot_messages (conversation_id, sender, message_text) VALUES ($conv_id, 'user', '$msg_esc')");

        // ── Intent Matching ────────────────────────────────────
        $best_intent  = null;
        $best_score   = 0;

        // Normalise input
        $input_lower  = mb_strtolower(trim($msg));
        $input_words  = preg_split('/[\s,\.!?]+/', $input_lower, -1, PREG_SPLIT_NO_EMPTY);

        // Detect gibberish / punctuation only
        if (preg_match('/^[\W\d_]+$/', $msg) || strlen($msg) < 2) {
            $bot_reply = "It looks like your message may not have come through clearly. Could you try asking your question again? I'd love to help! 😊";
            $suggestion = null;
        } else {
            // Load knowledge base
            $kb = [];
            $q  = mysqli_query($conn, "SELECT * FROM chatbot_knowledge_base WHERE is_active=1");
            if ($q) while ($r = mysqli_fetch_assoc($q)) $kb[] = $r;

            foreach ($kb as $entry) {
                $score = 0;

                // Parse keywords JSON
                $keywords = [];
                if (!empty($entry['keywords'])) {
                    $kw_arr = json_decode($entry['keywords'], true);
                    if (is_array($kw_arr)) {
                        foreach ($kw_arr as $kw) { if (mb_stripos($input_lower, mb_strtolower($kw)) !== false) $score += 3; }
                    }
                }

                // Parse question variants
                if (!empty($entry['question_variants'])) {
                    $qv_arr = json_decode($entry['question_variants'], true);
                    if (is_array($qv_arr)) {
                        foreach ($qv_arr as $qv) {
                            similar_text(mb_strtolower($qv), $input_lower, $pct);
                            if ($pct > 68) $score += (int)($pct / 15);
                        }
                    }
                }

                // Direct substring of intent tag words
                $tag_words = explode('_', $entry['intent_tag']);
                foreach ($tag_words as $tw) {
                    if ($tw && mb_stripos($input_lower, $tw) !== false) $score += 1;
                }

                if ($score > $best_score) { $best_score = $score; $best_intent = $entry; }
            }

            $confidence_threshold = 2;

            if ($best_intent && $best_score >= $confidence_threshold) {
                $bot_reply  = $best_intent['response_text'];
                $suggestion = $best_intent['followup_suggestion'];
                $matched    = $best_intent['intent_tag'];
            } else {
                $bot_reply  = "I'm not quite sure I understand what you're asking. Could you rephrase that for me? I'm here to help with questions about our medical services, appointments, and more. 🏥";
                $suggestion = null;
                $matched    = null;
            }
        }

        // Log bot reply
        $bot_esc = mysqli_real_escape_string($conn, $bot_reply);
        $intent_esc = mysqli_real_escape_string($conn, $matched ?? '');
        mysqli_query($conn,
            "INSERT INTO chatbot_messages (conversation_id, sender, message_text, intent_matched)
             VALUES ($conv_id, 'bot', '$bot_esc', " . ($matched ? "'$intent_esc'" : 'NULL') . ")");

        // Update conversation message count
        mysqli_query($conn, "UPDATE chatbot_conversations SET message_count = message_count + 2 WHERE conversation_id = $conv_id");

        // Load 2-3 related suggestions based on KB entries (related intents)
        $quick_replies = [];
        if ($best_intent && $best_score >= 2) {
            $cat_esc = mysqli_real_escape_string($conn, $best_intent['category'] ?? '');
            $qr = mysqli_query($conn,
                "SELECT followup_suggestion FROM chatbot_knowledge_base
                 WHERE is_active=1 AND intent_tag != '{$best_intent['intent_tag']}'
                   AND category = '$cat_esc'
                   AND followup_suggestion IS NOT NULL
                 ORDER BY RAND() LIMIT 3");
            if ($qr) while ($row = mysqli_fetch_assoc($qr)) {
                if (!empty($row['followup_suggestion'])) $quick_replies[] = $row['followup_suggestion'];
            }
        }

        // Fallback quick replies
        if (empty($quick_replies)) {
            $quick_replies = ['Book an appointment', 'Emergency contact', 'Our services'];
        }

        $response = [
            'success'         => true,
            'reply'           => $bot_reply,
            'suggestion'      => $suggestion,
            'quick_replies'   => array_slice($quick_replies, 0, 3),
            'conversation_id' => $conv_id,
            'intent'          => $matched ?? null,
        ];
        break;

    // ── Get greeting ──────────────────────────────────────────
    case 'greeting':
        $r = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT setting_value FROM landing_page_config WHERE setting_key='chatbot_greeting'"));
        $greeting = $r['setting_value'] ?? 'Hello! Welcome to RMU Medical. How can I assist you?';
        $response = ['success' => true, 'greeting' => $greeting];
        break;

    // ── Get suggestions for empty state ───────────────────────
    case 'suggestions':
        $rows = [];
        $q = mysqli_query($conn,
            "SELECT followup_suggestion FROM chatbot_knowledge_base
             WHERE is_active=1 AND followup_suggestion IS NOT NULL
             ORDER BY RAND() LIMIT 6");
        if ($q) while ($r = mysqli_fetch_assoc($q)) $rows[] = $r['followup_suggestion'];
        if (empty($rows)) $rows = ['Book an appointment', 'Our services', 'Emergency contact', 'Lab tests', 'Pharmacy hours'];
        $response = ['success' => true, 'data' => $rows];
        break;

    default:
        $response = ['success' => false, 'error' => 'Unknown action'];
}

mysqli_close($conn);
echo json_encode($response, JSON_UNESCAPED_UNICODE);
