<?php
/**
 * landing_page_actions.php
 * AJAX handler for all Landing Page Manager CRUD operations.
 * Supports: hero, stats, announcements, services, FAQ, gallery,
 *           testimonials, team_members, director, chatbot KB, bookings.
 */

session_start();
require_once '../db_conn.php';

// ── Security: Admin only ──────────────────────────────────────────
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Helper functions ──────────────────────────────────────────────
function respond($ok, $msg = '', $data = []) {
    echo json_encode(['success' => $ok, 'message' => $msg, 'data' => $data]);
    exit;
}

function esc($conn, $v) {
    return mysqli_real_escape_string($conn, trim($v ?? ''));
}

function getRows($conn, $table, $where = '1', $order = 'id ASC') {
    $r = mysqli_query($conn, "SELECT * FROM `$table` WHERE $where ORDER BY $order");
    if (!$r) return [];
    $rows = [];
    while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
    return $rows;
}

// ── Handle actions ────────────────────────────────────────────────
switch ($action) {

    /* ═══════════════════════ HERO CONFIG ═══════════════════════ */
    case 'get_hero':
        $rows = getRows($conn, 'lp_hero_config', '1', 'id DESC LIMIT 1');
        respond(true, '', $rows[0] ?? []);

    case 'save_hero':
        $headline    = esc($conn, $_POST['headline'] ?? '');
        $subheadline = esc($conn, $_POST['subheadline'] ?? '');
        $cta1_text   = esc($conn, $_POST['cta1_text'] ?? '');
        $cta1_url    = esc($conn, $_POST['cta1_url'] ?? '');
        $cta2_text   = esc($conn, $_POST['cta2_text'] ?? '');
        $cta2_url    = esc($conn, $_POST['cta2_url'] ?? '');
        $bg_image    = esc($conn, $_POST['bg_image'] ?? '');

        // Check if row exists
        $exists = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM lp_hero_config LIMIT 1"));
        if ($exists) {
            $q = "UPDATE lp_hero_config SET headline='$headline', subheadline='$subheadline',
                  cta1_text='$cta1_text', cta1_url='$cta1_url',
                  cta2_text='$cta2_text', cta2_url='$cta2_url',
                  bg_image='$bg_image', updated_at=NOW()";
        } else {
            $q = "INSERT INTO lp_hero_config (headline, subheadline, cta1_text, cta1_url, cta2_text, cta2_url, bg_image, updated_at)
                  VALUES ('$headline','$subheadline','$cta1_text','$cta1_url','$cta2_text','$cta2_url','$bg_image',NOW())";
        }
        mysqli_query($conn, $q) ? respond(true, 'Hero saved.') : respond(false, mysqli_error($conn));

    /* ═══════════════════════ STATS ═════════════════════════════ */
    case 'get_stats':
        respond(true, '', getRows($conn, 'lp_stats', '1', 'display_order ASC'));

    case 'save_stat':
        $id      = (int)($_POST['id'] ?? 0);
        $icon    = esc($conn, $_POST['icon'] ?? '');
        $num     = esc($conn, $_POST['stat_number'] ?? '');
        $suffix  = esc($conn, $_POST['stat_suffix'] ?? '');
        $label   = esc($conn, $_POST['stat_label'] ?? '');
        $order   = (int)($_POST['display_order'] ?? 0);

        if ($id) {
            $q = "UPDATE lp_stats SET icon_class='$icon', stat_number='$num', stat_suffix='$suffix',
                  stat_label='$label', display_order=$order WHERE id=$id";
        } else {
            $q = "INSERT INTO lp_stats (icon_class, stat_number, stat_suffix, stat_label, display_order)
                  VALUES ('$icon','$num','$suffix','$label',$order)";
        }
        mysqli_query($conn, $q) ? respond(true, 'Stat saved.') : respond(false, mysqli_error($conn));

    case 'delete_stat':
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($conn, "DELETE FROM lp_stats WHERE id=$id");
        respond(true, 'Stat deleted.');

    /* ════════════════════ ANNOUNCEMENTS ════════════════════════ */
    case 'get_announcements':
        respond(true, '', getRows($conn, 'lp_announcements', '1', 'display_order ASC, id DESC'));

    case 'save_announcement':
        $id       = (int)($_POST['id'] ?? 0);
        $title    = esc($conn, $_POST['title'] ?? '');
        $body     = esc($conn, $_POST['body'] ?? '');
        $type     = esc($conn, $_POST['type'] ?? 'info');
        $active   = (int)($_POST['is_active'] ?? 1);
        $start    = esc($conn, $_POST['start_date'] ?? '');
        $end      = esc($conn, $_POST['end_date'] ?? '');
        $order    = (int)($_POST['display_order'] ?? 0);

        $start_v  = $start ? "'$start'" : 'NULL';
        $end_v    = $end   ? "'$end'"   : 'NULL';

        if ($id) {
            $q = "UPDATE lp_announcements SET title='$title', body='$body', type='$type',
                  is_active=$active, start_date=$start_v, end_date=$end_v, display_order=$order,
                  updated_at=NOW() WHERE id=$id";
        } else {
            $q = "INSERT INTO lp_announcements (title, body, type, is_active, start_date, end_date, display_order, created_at, updated_at)
                  VALUES ('$title','$body','$type',$active,$start_v,$end_v,$order,NOW(),NOW())";
        }
        mysqli_query($conn, $q) ? respond(true, 'Announcement saved.') : respond(false, mysqli_error($conn));

    case 'delete_announcement':
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($conn, "DELETE FROM lp_announcements WHERE id=$id");
        respond(true, 'Announcement deleted.');

    case 'toggle_announcement':
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($conn, "UPDATE lp_announcements SET is_active = IF(is_active=1,0,1) WHERE id=$id");
        respond(true, 'Status toggled.');

    /* ════════════════════ SERVICES ═════════════════════════════ */
    case 'get_services':
        respond(true, '', getRows($conn, 'lp_services', '1', 'display_order ASC'));

    case 'save_service':
        $id      = (int)($_POST['id'] ?? 0);
        $name    = esc($conn, $_POST['name'] ?? '');
        $desc    = esc($conn, $_POST['description'] ?? '');
        $icon    = esc($conn, $_POST['icon_class'] ?? '');
        $cat     = esc($conn, $_POST['category'] ?? '');
        $avail   = esc($conn, $_POST['availability'] ?? '');
        $active  = (int)($_POST['is_active'] ?? 1);
        $feat    = (int)($_POST['is_featured'] ?? 0);
        $order   = (int)($_POST['display_order'] ?? 0);

        if ($id) {
            $q = "UPDATE lp_services SET name='$name', description='$desc', icon_class='$icon',
                  category='$cat', availability='$avail', is_active=$active,
                  is_featured=$feat, display_order=$order, updated_at=NOW() WHERE id=$id";
        } else {
            $q = "INSERT INTO lp_services (name, description, icon_class, category, availability, is_active, is_featured, display_order, created_at, updated_at)
                  VALUES ('$name','$desc','$icon','$cat','$avail',$active,$feat,$order,NOW(),NOW())";
        }
        mysqli_query($conn, $q) ? respond(true, 'Service saved.') : respond(false, mysqli_error($conn));

    case 'delete_service':
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($conn, "DELETE FROM lp_services WHERE id=$id");
        respond(true, 'Service deleted.');

    /* ════════════════════ FAQ ══════════════════════════════════ */
    case 'get_faq':
        respond(true, '', getRows($conn, 'lp_faq', '1', 'category ASC, display_order ASC'));

    case 'save_faq':
        $id       = (int)($_POST['id'] ?? 0);
        $q_text   = esc($conn, $_POST['question'] ?? '');
        $a_text   = esc($conn, $_POST['answer'] ?? '');
        $cat      = esc($conn, $_POST['category'] ?? 'General');
        $active   = (int)($_POST['is_active'] ?? 1);
        $order    = (int)($_POST['display_order'] ?? 0);

        if ($id) {
            $q = "UPDATE lp_faq SET question='$q_text', answer='$a_text', category='$cat',
                  is_active=$active, display_order=$order WHERE id=$id";
        } else {
            $q = "INSERT INTO lp_faq (question, answer, category, is_active, display_order)
                  VALUES ('$q_text','$a_text','$cat',$active,$order)";
        }
        mysqli_query($conn, $q) ? respond(true, 'FAQ saved.') : respond(false, mysqli_error($conn));

    case 'delete_faq':
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($conn, "DELETE FROM lp_faq WHERE id=$id");
        respond(true, 'FAQ deleted.');

    /* ════════════════════ GALLERY ══════════════════════════════ */
    case 'get_gallery':
        respond(true, '', getRows($conn, 'lp_gallery', '1', 'display_order ASC'));

    case 'save_gallery':
        $id      = (int)($_POST['id'] ?? 0);
        $title   = esc($conn, $_POST['title'] ?? '');
        $path    = esc($conn, $_POST['image_path'] ?? '');
        $alt     = esc($conn, $_POST['alt_text'] ?? $title);
        $cat     = esc($conn, $_POST['category'] ?? '');
        $order   = (int)($_POST['display_order'] ?? 0);
        $active  = (int)($_POST['is_active'] ?? 1);

        if ($id) {
            $q = "UPDATE lp_gallery SET title='$title', image_path='$path', alt_text='$alt',
                  category='$cat', display_order=$order, is_active=$active WHERE id=$id";
        } else {
            $q = "INSERT INTO lp_gallery (title, image_path, alt_text, category, display_order, is_active)
                  VALUES ('$title','$path','$alt','$cat',$order,$active)";
        }
        mysqli_query($conn, $q) ? respond(true, 'Gallery item saved.') : respond(false, mysqli_error($conn));

    case 'delete_gallery':
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($conn, "DELETE FROM lp_gallery WHERE id=$id");
        respond(true, 'Gallery item deleted.');

    /* ════════════════════ TESTIMONIALS ═════════════════════════ */
    case 'get_testimonials':
        respond(true, '', getRows($conn, 'lp_testimonials', '1', 'display_order ASC'));

    case 'save_testimonial':
        $id      = (int)($_POST['id'] ?? 0);
        $name    = esc($conn, $_POST['patient_name'] ?? '');
        $role    = esc($conn, $_POST['patient_role'] ?? '');
        $text    = esc($conn, $_POST['review_text'] ?? '');
        $rating  = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $active  = (int)($_POST['is_active'] ?? 1);
        $feat    = (int)($_POST['is_featured'] ?? 0);
        $order   = (int)($_POST['display_order'] ?? 0);

        if ($id) {
            $q = "UPDATE lp_testimonials SET patient_name='$name', patient_role='$role',
                  review_text='$text', rating=$rating, is_active=$active,
                  is_featured=$feat, display_order=$order WHERE id=$id";
        } else {
            $q = "INSERT INTO lp_testimonials (patient_name, patient_role, review_text, rating, is_active, is_featured, display_order, created_at)
                  VALUES ('$name','$role','$text',$rating,$active,$feat,$order,NOW())";
        }
        mysqli_query($conn, $q) ? respond(true, 'Testimonial saved.') : respond(false, mysqli_error($conn));

    case 'delete_testimonial':
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($conn, "DELETE FROM lp_testimonials WHERE id=$id");
        respond(true, 'Testimonial deleted.');

    /* ════════════════════ TEAM MEMBERS ═════════════════════════ */
    case 'get_team':
        respond(true, '', getRows($conn, 'lp_team_members', '1', 'display_order ASC'));

    case 'save_team':
        $id      = (int)($_POST['id'] ?? 0);
        $name    = esc($conn, $_POST['name'] ?? '');
        $title   = esc($conn, $_POST['title'] ?? '');
        $dept    = esc($conn, $_POST['department'] ?? '');
        $bio     = esc($conn, $_POST['bio'] ?? '');
        $quals   = esc($conn, $_POST['qualifications'] ?? '');
        $photo   = esc($conn, $_POST['photo_path'] ?? '');
        $email   = esc($conn, $_POST['email'] ?? '');
        $active  = (int)($_POST['is_active'] ?? 1);
        $order   = (int)($_POST['display_order'] ?? 0);
        $type    = esc($conn, $_POST['member_type'] ?? 'staff');

        if ($id) {
            $q = "UPDATE lp_team_members SET name='$name', title='$title', department='$dept',
                  bio='$bio', qualifications='$quals', photo_path='$photo', email='$email',
                  is_active=$active, display_order=$order, member_type='$type' WHERE id=$id";
        } else {
            $q = "INSERT INTO lp_team_members (name, title, department, bio, qualifications, photo_path, email, is_active, display_order, member_type)
                  VALUES ('$name','$title','$dept','$bio','$quals','$photo','$email',$active,$order,'$type')";
        }
        mysqli_query($conn, $q) ? respond(true, 'Team member saved.') : respond(false, mysqli_error($conn));

    case 'delete_team':
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($conn, "DELETE FROM lp_team_members WHERE id=$id");
        respond(true, 'Team member deleted.');

    /* ════════════════════ DIRECTOR ═════════════════════════════ */
    case 'get_director':
        $rows = getRows($conn, 'lp_director_profile', '1', 'id LIMIT 1');
        respond(true, '', $rows[0] ?? []);

    case 'save_director':
        $name    = esc($conn, $_POST['name'] ?? '');
        $title   = esc($conn, $_POST['title'] ?? '');
        $bio     = esc($conn, $_POST['bio'] ?? '');
        $message = esc($conn, $_POST['message'] ?? '');
        $quals   = esc($conn, $_POST['qualifications'] ?? '');
        $photo   = esc($conn, $_POST['photo_path'] ?? '');
        $email   = esc($conn, $_POST['email'] ?? '');

        $exists = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM lp_director_profile LIMIT 1"));
        if ($exists) {
            $q = "UPDATE lp_director_profile SET name='$name', title='$title', bio='$bio',
                  message='$message', qualifications='$quals', photo_path='$photo',
                  email='$email', updated_at=NOW()";
        } else {
            $q = "INSERT INTO lp_director_profile (name, title, bio, message, qualifications, photo_path, email, updated_at)
                  VALUES ('$name','$title','$bio','$message','$quals','$photo','$email',NOW())";
        }
        mysqli_query($conn, $q) ? respond(true, 'Director profile saved.') : respond(false, mysqli_error($conn));

    /* ════════════════════ CHATBOT KB ═══════════════════════════ */
    case 'get_chatbot_kb':
        respond(true, '', getRows($conn, 'lp_chatbot_knowledge', '1', 'category ASC, id DESC'));

    case 'save_chatbot_kb':
        $id        = (int)($_POST['id'] ?? 0);
        $cat       = esc($conn, $_POST['category'] ?? '');
        $keywords  = esc($conn, $_POST['keywords'] ?? '');
        $question  = esc($conn, $_POST['question'] ?? '');
        $answer    = esc($conn, $_POST['answer'] ?? '');
        $priority  = (int)($_POST['priority'] ?? 5);
        $active    = (int)($_POST['is_active'] ?? 1);

        if ($id) {
            $q = "UPDATE lp_chatbot_knowledge SET category='$cat', keywords='$keywords',
                  question='$question', answer='$answer', priority=$priority,
                  is_active=$active WHERE id=$id";
        } else {
            $q = "INSERT INTO lp_chatbot_knowledge (category, keywords, question, answer, priority, is_active)
                  VALUES ('$cat','$keywords','$question','$answer',$priority,$active)";
        }
        mysqli_query($conn, $q) ? respond(true, 'Knowledge base entry saved.') : respond(false, mysqli_error($conn));

    case 'delete_chatbot_kb':
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($conn, "DELETE FROM lp_chatbot_knowledge WHERE id=$id");
        respond(true, 'KB entry deleted.');

    /* ════════════════════ CHAT LOGS ════════════════════════════ */
    case 'get_chat_logs':
        $limit = (int)($_GET['limit'] ?? 50);
        $rows = getRows($conn, 'lp_chat_logs', '1', 'id DESC LIMIT ' . $limit);
        respond(true, '', $rows);

    case 'delete_chat_log':
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($conn, "DELETE FROM lp_chat_logs WHERE id=$id");
        respond(true, 'Log deleted.');

    case 'clear_all_chat_logs':
        mysqli_query($conn, "TRUNCATE TABLE lp_chat_logs");
        respond(true, 'All chat logs cleared.');

    /* ════════════════════ BOOKINGS (Public Requests) ═══════════ */
    case 'get_public_bookings':
        $status = esc($conn, $_GET['status'] ?? '');
        $where  = $status ? "status='$status'" : '1';
        respond(true, '', getRows($conn, 'public_appointment_requests', $where, 'created_at DESC LIMIT 100'));

    case 'update_booking_status':
        $id     = (int)($_POST['id'] ?? 0);
        $status = esc($conn, $_POST['status'] ?? 'Pending');
        $notes  = esc($conn, $_POST['admin_notes'] ?? '');
        mysqli_query($conn, "UPDATE public_appointment_requests
                             SET status='$status', admin_notes='$notes', reviewed_at=NOW()
                             WHERE id=$id");
        respond(true, 'Booking status updated.');

    /* ════════════════════ SITE CONFIG ══════════════════════════ */
    case 'get_site_config':
        $rows = getRows($conn, 'lp_site_config', '1', 'config_key ASC');
        $config = [];
        foreach ($rows as $r) $config[$r['config_key']] = $r['config_value'];
        respond(true, '', $config);

    case 'save_site_config':
        $pairs = $_POST['config'] ?? [];
        if (!is_array($pairs)) respond(false, 'Invalid data.');
        foreach ($pairs as $key => $val) {
            $k = esc($conn, $key);
            $v = esc($conn, $val);
            mysqli_query($conn, "INSERT INTO lp_site_config (config_key, config_value, updated_at)
                                 VALUES ('$k','$v',NOW())
                                 ON DUPLICATE KEY UPDATE config_value='$v', updated_at=NOW()");
        }
        respond(true, 'Site config saved.');

    /* ════════════════════ DEFAULT ══════════════════════════════ */
    default:
        respond(false, 'Unknown action: ' . htmlspecialchars($action));
}
