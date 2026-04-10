<?php
/**
 * Landing Page Data API
 * Returns JSON data for all landing page sections
 * Used by AJAX calls from HTML/JS pages
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once dirname(__DIR__) . '/db_conn.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'hero';

$response = ['success' => false];

switch ($action) {

    // ── Hero Content ───────────────────────────────────────────
    case 'hero':
        $r = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT * FROM landing_hero_content WHERE is_active=1 LIMIT 1"));
        $response = ['success' => true, 'data' => $r ?: [
            'headline_text'    => 'Your Health, Our Priority',
            'subheadline_text' => 'RMU Medical Sickbay provides comprehensive healthcare services for students and staff of the Regional Maritime University. We are here for you 24/7.',
            'hero_bg_image_url'=> '/RMU-Medical-Management-System/image/home.jpg',
            'overlay_opacity'  => 0.55,
            'cta1_text'        => 'Book Appointment',
            'cta1_url'         => '/RMU-Medical-Management-System/php/booking.php',
            'cta2_text'        => 'Explore Services',
            'cta2_url'         => '/RMU-Medical-Management-System/html/services.html',
        ]];
        break;

    // ── Statistics ─────────────────────────────────────────────
    case 'stats':
        $rows = [];
        $q = mysqli_query($conn,
            "SELECT * FROM landing_statistics WHERE is_active=1 ORDER BY display_order");
        if ($q) while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
        if (empty($rows)) {
            $rows = [
                ['label'=>'Patients Served',  'stat_value'=>'5,000+', 'icon_class'=>'fas fa-users'],
                ['label'=>'Qualified Doctors', 'stat_value'=>'12+',   'icon_class'=>'fas fa-user-doctor'],
                ['label'=>'Services Offered',  'stat_value'=>'20+',   'icon_class'=>'fas fa-stethoscope'],
                ['label'=>'Years of Service',  'stat_value'=>'10+',   'icon_class'=>'fas fa-award'],
            ];
        }
        $response = ['success' => true, 'data' => $rows];
        break;

    // ── Services ───────────────────────────────────────────────
    case 'services':
        $rows = [];
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        $q = mysqli_query($conn,
            "SELECT * FROM landing_services WHERE is_active=1 ORDER BY display_order LIMIT $limit");
        if ($q) while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
        $response = ['success' => true, 'data' => $rows];
        break;

    // ── Featured Doctors ───────────────────────────────────────
    case 'doctors':
        $rows = [];
        $q = mysqli_query($conn,
            "SELECT d.id as doctor_id, u.name, u.profile_image, d.specialization,
                    d.department, d.experience_years, d.bio, d.consultation_hours,
                    d.qualifications, d.languages
             FROM doctors d
             JOIN users u ON d.user_id = u.id
             WHERE d.is_active = 1
             ORDER BY d.created_at ASC");
        if ($q) while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
        $response = ['success' => true, 'data' => $rows];
        break;

    // ── Staff ─────────────────────────────────────────────────
    case 'staff':
        $rows = [];
        $dept = isset($_GET['department']) ? mysqli_real_escape_string($conn, $_GET['department']) : '';
        $where = $dept ? "WHERE is_active=1 AND department='$dept'" : "WHERE is_active=1";
        $q = mysqli_query($conn,
            "SELECT * FROM landing_staff $where ORDER BY display_order");
        if ($q) while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
        $response = ['success' => true, 'data' => $rows];
        break;

    // ── Staff Departments ──────────────────────────────────────
    case 'staff_departments':
        $rows = [];
        $q = mysqli_query($conn,
            "SELECT DISTINCT department FROM landing_staff WHERE is_active=1 AND department IS NOT NULL ORDER BY department");
        if ($q) while ($r = mysqli_fetch_assoc($q)) $rows[] = $r['department'];
        $response = ['success' => true, 'data' => $rows];
        break;

    // ── Director ──────────────────────────────────────────────
    case 'director':
        $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM landing_director LIMIT 1")) ?: [
            'name'           => 'Dr. Emmanuel Mensah',
            'title'          => 'Chief Medical Officer',
            'bio'            => 'Dr. Emmanuel Mensah serves as the Chief Medical Officer of RMU Medical Sickbay, bringing over 20 years of experience in university healthcare and medical administration.',
            'message'        => 'As the Chief Medical Officer of the RMU Medical Sickbay, I am committed to ensuring every student, faculty member, and staff person receives the highest standard of healthcare. Our dedicated team works tirelessly to provide compassionate, accessible, and evidence-based care. Your health is our mission.',
            'qualifications' => 'MBChB - University of Ghana Medical School|MPH - Johns Hopkins University|Fellow, Ghana College of Physicians',
            'photo_path'     => '/RMU-Medical-Management-System/image/director.jpg',
        ];
        $response = ['success' => true, 'data' => $r];
        break;

    // ── About Content ─────────────────────────────────────────
    case 'about':
        $rows = [];
        $q = mysqli_query($conn,
            "SELECT * FROM landing_about WHERE is_active=1 ORDER BY display_order");
        if ($q) while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
        $response = ['success' => true, 'data' => $rows];
        break;

    // ── Gallery ───────────────────────────────────────────────
    case 'gallery':
        $rows = [];
        $q = mysqli_query($conn,
            "SELECT * FROM landing_gallery WHERE is_active=1 ORDER BY display_order, uploaded_at DESC LIMIT 20");
        if ($q) while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
        $response = ['success' => true, 'data' => $rows];
        break;

    // ── Testimonials ──────────────────────────────────────────
    case 'testimonials':
        $rows = [];
        $q = mysqli_query($conn,
            "SELECT * FROM landing_testimonials WHERE is_approved=1 ORDER BY created_at DESC LIMIT 10");
        if ($q) while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
        $response = ['success' => true, 'data' => $rows];
        break;

    // ── FAQ ───────────────────────────────────────────────────
    case 'faq':
        $rows = [];
        $cat = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : '';
        $where = $cat ? "WHERE is_active=1 AND category='$cat'" : "WHERE is_active=1";
        $q = mysqli_query($conn,
            "SELECT * FROM landing_faq $where ORDER BY display_order");
        if ($q) while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
        $response = ['success' => true, 'data' => $rows];
        break;

    // ── FAQ Categories ────────────────────────────────────────
    case 'faq_categories':
        $rows = [];
        $q = mysqli_query($conn,
            "SELECT DISTINCT category FROM landing_faq WHERE is_active=1 ORDER BY category");
        if ($q) while ($r = mysqli_fetch_assoc($q)) $rows[] = $r['category'];
        $response = ['success' => true, 'data' => $rows];
        break;

    // ── Announcements ─────────────────────────────────────────
    case 'announcements':
        $rows = [];
        $q = mysqli_query($conn,
            "SELECT * FROM landing_announcements
             WHERE is_active=1
               AND (display_from IS NULL OR display_from <= CURDATE())
               AND (display_to IS NULL OR display_to >= CURDATE())
             ORDER BY created_at DESC LIMIT 10");
        if ($q) while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
        $response = ['success' => true, 'data' => $rows];
        break;

    // ── Config (public settings) ──────────────────────────────
    case 'config':
        $cfg = [];
        $safe_keys = [
            'emergency_hotline','contact_email','contact_phone',
            'facility_name','facility_address','facebook_url','twitter_url',
            'instagram_url','chatbot_enabled','chatbot_greeting',
            'announcements_enabled','testimonials_enabled','faq_enabled',
            'statistics_enabled','online_booking_enabled','google_maps_url'
        ];
        $in = "'" . implode("','", $safe_keys) . "'";
        $q = mysqli_query($conn,
            "SELECT setting_key, setting_value FROM landing_page_config WHERE setting_key IN ($in)");
        if ($q) while ($r = mysqli_fetch_assoc($q)) $cfg[$r['setting_key']] = $r['setting_value'];
        // Defaults
        $defaults = [
            'emergency_hotline' => '153',
            'contact_phone'     => '0502371207',
            'contact_email'     => 'sickbay.text@st.rmu.edu.gh',
            'facility_name'     => 'RMU Medical Sickbay',
            'facility_address'  => 'Regional Maritime University, Nungua, Accra, Ghana',
            'chatbot_enabled'   => '1',
            'chatbot_greeting'  => 'Hello! I am the RMU Medical Assistant. How can I help you today?',
        ];
        foreach ($defaults as $k => $v) {
            if (!isset($cfg[$k])) $cfg[$k] = $v;
        }
        $response = ['success' => true, 'data' => $cfg];
        break;

    // ── All page data (bulk load) ─────────────────────────────
    case 'all':
        // Fetch everything needed for the home page in one shot
        $hero = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM landing_hero_content WHERE is_active=1 LIMIT 1"));
        $stats = [];
        $q = mysqli_query($conn, "SELECT * FROM landing_statistics WHERE is_active=1 ORDER BY display_order");
        if ($q) while ($r = mysqli_fetch_assoc($q)) $stats[] = $r;
        $services = [];
        $q = mysqli_query($conn, "SELECT * FROM landing_services WHERE is_active=1 ORDER BY display_order LIMIT 6");
        if ($q) while ($r = mysqli_fetch_assoc($q)) $services[] = $r;
        $cfg = [];
        $q = mysqli_query($conn, "SELECT setting_key, setting_value FROM landing_page_config");
        if ($q) while ($r = mysqli_fetch_assoc($q)) $cfg[$r['setting_key']] = $r['setting_value'];
        $announcements = [];
        $q = mysqli_query($conn,
            "SELECT * FROM landing_announcements WHERE is_active=1 AND (display_from IS NULL OR display_from <= CURDATE()) AND (display_to IS NULL OR display_to >= CURDATE()) ORDER BY created_at DESC LIMIT 5");
        if ($q) while ($r = mysqli_fetch_assoc($q)) $announcements[] = $r;
        $response = [
            'success' => true,
            'data' => [
                'hero'          => $hero,
                'stats'         => $stats,
                'services'      => $services,
                'config'        => $cfg,
                'announcements' => $announcements,
            ]
        ];
        break;

    default:
        $response = ['success' => false, 'error' => 'Unknown action'];
}

mysqli_close($conn);
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
