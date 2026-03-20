<?php
// ============================================================
// NURSE DASHBOARD - ADVANCED PROFILE PROCESSOR (MODULE 15)
// ============================================================
require_once '../db_conn.php';
require_once '../dashboards/nurse_security.php';

initSecureSession();
enforceNurseRole();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Security token validation failed.']);
    exit;
}

$action = $_POST['action'] ?? '';
$nurse_id = $_SESSION['user_id']; // This is actually user_id mapped
$real_nurse_id = null;

// Resolve true nurse table ID
$q_nm = mysqli_query($conn, "SELECT id, full_name, profile_photo FROM nurses WHERE user_id = $nurse_id LIMIT 1");
if(mysqli_num_rows($q_nm) > 0) {
    $n_row = mysqli_fetch_assoc($q_nm);
    $real_nurse_id = $n_row['id'];
} else {
    echo json_encode(['success' => false, 'message' => 'Nurse account integrity exception.']);
    exit;
}

// ── RECALC COMPLETENESS ENGINE ─────────────────────────
function recalcCompleteness($conn, $nid) {
    $cProfile = mysqli_query($conn, "SELECT * FROM nurses WHERE id = $nid")->fetch_assoc();
    $cProf = mysqli_query($conn, "SELECT * FROM nurse_professional_profile WHERE nurse_id = $nid")->fetch_assoc();
    $cQual = mysqli_query($conn, "SELECT COUNT(*) as c FROM nurse_qualifications WHERE nurse_id = $nid")->fetch_assoc()['c'];
    $cDoc = mysqli_query($conn, "SELECT COUNT(*) as c FROM nurse_documents WHERE nurse_id = $nid")->fetch_assoc()['c'];
    
    // Personal check
    $p_req = ['full_name','date_of_birth','gender','phone'];
    $p_com = 1; foreach($p_req as $r) { if(empty($cProfile[$r])) $p_com = 0; }
    
    // Professional check
    $pr_req = ['designation','specialization','license_number'];
    $pr_com = 1; foreach($pr_req as $r) { if(empty($cProf[$r])) $pr_com = 0; }
    
    $q_com = ($cQual > 0) ? 1 : 0;
    $d_com = ($cDoc > 0) ? 1 : 0;
    $ph_com = !empty($cProfile['profile_photo']) ? 1 : 0;
    
    $total_sections = 5;
    $score = $p_com + $pr_com + $q_com + $d_com + $ph_com;
    $percent = round(($score / $total_sections) * 100);
    
    $stmt = mysqli_prepare($conn, "UPDATE nurse_profile_completeness SET personal_info_complete=?, professional_profile_complete=?, qualifications_complete=?, photo_uploaded=?, documents_uploaded=?, overall_percentage=? WHERE nurse_id=?");
    mysqli_stmt_bind_param($stmt, "iiiiiii", $p_com, $pr_com, $q_com, $ph_com, $d_com, $percent, $nid);
    mysqli_stmt_execute($stmt);
}

// ── ACTION ROUTER ──────────────────────────────────────

try {
    switch ($action) {

        case 'save_personal':
            $fn = sanitize($_POST['full_name'] ?? '');
            $dob = sanitize($_POST['date_of_birth'] ?? '');
            $gnd = sanitize($_POST['gender'] ?? '');
            $nat = sanitize($_POST['nationality'] ?? '');
            $phn = sanitize($_POST['phone'] ?? '');
            $eml = sanitize($_POST['email'] ?? '');
            $adr = sanitize($_POST['address'] ?? '');

            $stmt = mysqli_prepare($conn, "UPDATE nurses SET full_name=?, date_of_birth=?, gender=?, nationality=?, phone=?, email=?, address=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "sssssssi", $fn, $dob, $gnd, $nat, $phn, $eml, $adr, $real_nurse_id);
            if(mysqli_stmt_execute($stmt)) {
                recalcCompleteness($conn, $real_nurse_id);
                secureLogNurse($conn, $real_nurse_id, "Updated personal information boundaries.");
                echo json_encode(['success'=>true, 'message'=>'Personal information updated successfully.']);
            } else throw new Exception("Database update failed.");
            break;

        case 'save_professional':
            $dsg = sanitize($_POST['designation'] ?? '');
            $spc = sanitize($_POST['specialization'] ?? '');
            $exp = (int)($_POST['years_of_experience'] ?? 0);
            $lic = sanitize($_POST['license_number'] ?? '');
            $lex = !empty($_POST['license_expiry_date']) ? sanitize($_POST['license_expiry_date']) : null;
            $sch = sanitize($_POST['nursing_school'] ?? '');
            $grd = !empty($_POST['graduation_year']) ? (int)$_POST['graduation_year'] : null;
            $bio = sanitize($_POST['bio'] ?? '');

            $stmt = mysqli_prepare($conn, "UPDATE nurse_professional_profile SET designation=?, specialization=?, years_of_experience=?, license_number=?, license_expiry_date=?, nursing_school=?, graduation_year=?, bio=? WHERE nurse_id=?");
            mysqli_stmt_bind_param($stmt, "ssisssisi", $dsg, $spc, $exp, $lic, $lex, $sch, $grd, $bio, $real_nurse_id);
            if(mysqli_stmt_execute($stmt)) {
                // Must mirror specific columns back to primary 'nurses' table for legacy module compatibility
                $m_stmt = mysqli_prepare($conn, "UPDATE nurses SET designation=?, specialization=?, years_of_experience=?, license_number=?, license_expiry=? WHERE id=?");
                mysqli_stmt_bind_param($m_stmt, "ssissi", $dsg, $spc, $exp, $lic, $lex, $real_nurse_id);
                mysqli_stmt_execute($m_stmt);
                
                recalcCompleteness($conn, $real_nurse_id);
                secureLogNurse($conn, $real_nurse_id, "Updated core professional credentials.");
                echo json_encode(['success'=>true, 'message'=>'Professional profile updated successfully.']);
            } else throw new Exception("Database update failed.");
            break;

        case 'update_availability':
            $status = sanitize($_POST['status'] ?? 'Active');
            $stmt = mysqli_prepare($conn, "UPDATE nurses SET status=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "si", $status, $real_nurse_id);
            if(mysqli_stmt_execute($stmt)) {
                secureLogNurse($conn, $real_nurse_id, "Toggled availability state to: $status");
                echo json_encode(['success'=>true, 'message'=>'Status broadcast updated.']);
            } else throw new Exception("Status alter failed.");
            break;

        case 'save_settings':
            $n1 = isset($_POST['notify_new_task']) ? 1 : 0;
            $n2 = isset($_POST['notify_abnormal_vitals']) ? 1 : 0;
            $n3 = isset($_POST['notify_med_reminder']) ? 1 : 0;
            $n4 = isset($_POST['notify_emergency_updates']) ? 1 : 0;
            $snd = isset($_POST['alert_sound_enabled']) ? 1 : 0;
            $chn = sanitize($_POST['preferred_channel'] ?? 'In-Dashboard');

            $stmt = mysqli_prepare($conn, "UPDATE nurse_settings SET notify_new_task=?, notify_abnormal_vitals=?, notify_med_reminder=?, notify_emergency_updates=?, alert_sound_enabled=?, preferred_channel=? WHERE nurse_id=?");
            mysqli_stmt_bind_param($stmt, "iiiiisi", $n1, $n2, $n3, $n4, $snd, $chn, $real_nurse_id);
            if(mysqli_stmt_execute($stmt)) {
                secureLogNurse($conn, $real_nurse_id, "Updated active notification routing matrix.");
                echo json_encode(['success'=>true, 'message'=>'Interface preferences saved.']);
            } else throw new Exception("Settings alter failed.");
            break;

        case 'add_qual':
            $deg = sanitize($_POST['degree_name']);
            $ins = sanitize($_POST['institution']);
            $yr = (int)$_POST['year_awarded'];
            $stmt = mysqli_prepare($conn, "INSERT INTO nurse_qualifications (nurse_id, degree_name, institution, year_awarded) VALUES (?,?,?,?)");
            mysqli_stmt_bind_param($stmt, "issi", $real_nurse_id, $deg, $ins, $yr);
            if(mysqli_stmt_execute($stmt)) {
                recalcCompleteness($conn, $real_nurse_id);
                secureLogNurse($conn, $real_nurse_id, "Appended academic qualification: $deg");
                echo json_encode(['success'=>true, 'message'=>'Educational qualification injected.']);
            } else throw new Exception("Insertion failed.");
            break;

        case 'add_cert':
            $crt = sanitize($_POST['certification_name']);
            $org = sanitize($_POST['issuing_organization']);
            $isd = sanitize($_POST['issue_date']);
            $exd = sanitize($_POST['expiry_date']);
            $stmt = mysqli_prepare($conn, "INSERT INTO nurse_certifications (nurse_id, certification_name, issuing_organization, issue_date, expiry_date) VALUES (?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, "issss", $real_nurse_id, $crt, $org, $isd, $exd);
            if(mysqli_stmt_execute($stmt)) {
                secureLogNurse($conn, $real_nurse_id, "Appended professional certification: $crt");
                echo json_encode(['success'=>true, 'message'=>'Certification credential mapped.']);
            } else throw new Exception("Insertion failed.");
            break;

        case 'delete_record':
            $type = sanitize($_POST['type']);
            $dr_id = (int)$_POST['id'];
            if($type == 'qualification') {
                $stmt = mysqli_prepare($conn, "DELETE FROM nurse_qualifications WHERE qualification_id=? AND nurse_id=?");
            } elseif($type == 'certification') {
                $stmt = mysqli_prepare($conn, "DELETE FROM nurse_certifications WHERE certification_id=? AND nurse_id=?");
            } elseif($type == 'document') {
                // Must delete physical file
                $gd = mysqli_prepare($conn, "SELECT file_path FROM nurse_documents WHERE document_id=? AND nurse_id=?");
                mysqli_stmt_bind_param($gd, "ii", $dr_id, $real_nurse_id);
                mysqli_stmt_execute($gd);
                $d_res = mysqli_stmt_get_result($gd)->fetch_assoc();
                if($d_res && file_exists("../../" . $d_res['file_path'])) {
                    @unlink("../../" . $d_res['file_path']);
                }
                $stmt = mysqli_prepare($conn, "DELETE FROM nurse_documents WHERE document_id=? AND nurse_id=?");
            } else throw new Exception("Unknown deletion vector.");
            
            mysqli_stmt_bind_param($stmt, "ii", $dr_id, $real_nurse_id);
            if(mysqli_stmt_execute($stmt)) {
                recalcCompleteness($conn, $real_nurse_id);
                secureLogNurse($conn, $real_nurse_id, "Deleted a $type record (ID:$dr_id)");
                echo json_encode(['success'=>true, 'message'=>ucfirst($type)." removed."]);
            } else throw new Exception("Record erasure blocked.");
            break;

        case 'upload_doc':
            $fname = sanitize($_POST['file_name']);
            if(!isset($_FILES['doc_file']) || $_FILES['doc_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("File payload empty or corrupted.");
            }
            $file = $_FILES['doc_file'];
            if($file['size'] > 2097152) throw new Exception("Maximum upload constraint (2MB) breached.");
            
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if(!in_array($ext, ['pdf','jpg','jpeg','png'])) throw new Exception("Disallowed file format. Strictly PDF/JPG/PNG.");
            
            $mime = mime_content_type($file['tmp_name']);
            if(!in_array($mime, ['application/pdf','image/jpeg','image/png'])) throw new Exception("Internal MIME mismatch.");
            
            $fSize = $file['size'];
            $new_name = 'DOC_' . $real_nurse_id . '_' . time() . '.' . $ext;
            $upload_path = '../../uploads/documents/' . $new_name;
            if(!is_dir('../../uploads/documents')) mkdir('../../uploads/documents', 0755, true);
            
            if(move_uploaded_file($file['tmp_name'], $upload_path)) {
                $db_path = 'uploads/documents/' . $new_name;
                $stmt = mysqli_prepare($conn, "INSERT INTO nurse_documents (nurse_id, file_name, file_path, file_type, file_size) VALUES (?,?,?,?,?)");
                mysqli_stmt_bind_param($stmt, "isssi", $real_nurse_id, $fname, $db_path, $ext, $fSize);
                mysqli_stmt_execute($stmt);
                recalcCompleteness($conn, $real_nurse_id);
                secureLogNurse($conn, $real_nurse_id, "Uploaded secure HR protocol document: $fname");
                echo json_encode(['success'=>true, 'message'=>'Document secured into internal drive.']);
            } else throw new Exception("Storage pipeline error.");
            break;

        case 'upload_photo':
            if(!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Image payload empty or corrupted.");
            }
            $file_info = $_FILES['profile_photo'];
            if($file_info['size'] > 2097152) throw new Exception("Max upload size 2MB exceeded.");
            $ext = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
            if(!in_array($ext, ['jpg', 'jpeg', 'png'])) throw new Exception("Only JPG and PNG are allowed.");
            $mime = mime_content_type($file_info['tmp_name']);
            if(!in_array($mime, ['image/jpeg', 'image/png'])) throw new Exception("Invalid form MIME type.");
            $file = ['tmp' => $file_info['tmp_name'], 'ext' => $ext];
            
            $new_name = 'avatar_' . $real_nurse_id . '_' . time() . '.' . $file['ext'];
            $upload_path = '../../uploads/profiles/' . $new_name;
            if(!is_dir('../../uploads/profiles')) mkdir('../../uploads/profiles', 0755, true);
            
            // Delete old
            if(!empty($n_row['profile_photo']) && file_exists('../../uploads/profiles/' . $n_row['profile_photo'])) {
                @unlink('../../uploads/profiles/' . $n_row['profile_photo']);
            }

            if(move_uploaded_file($file['tmp'], $upload_path)) {
                $stmt = mysqli_prepare($conn, "UPDATE nurses SET profile_photo=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "si", $new_name, $real_nurse_id);
                mysqli_stmt_execute($stmt);
                recalcCompleteness($conn, $real_nurse_id);
                secureLogNurse($conn, $real_nurse_id, "Replaced Identity Avatar.");
                echo json_encode(['success'=>true, 'message'=>'Identity Avatar synchronized.']);
            } else throw new Exception("Storage pipeline error during avatar processing.");
            break;

        case 'update_password':
            $cur = $_POST['current_password'];
            $new = $_POST['new_password'];
            $con = $_POST['confirm_password'];
            
            if($new !== $con) throw new Exception("Verification password block mismatch.");
            if(strlen($new) < 8) throw new Exception("Complexity rule requires >= 8 chars length.");

            $q_pw = mysqli_prepare($conn, "SELECT password FROM users WHERE id=?");
            mysqli_stmt_bind_param($q_pw, "i", $nurse_id);
            mysqli_stmt_execute($q_pw);
            $hx = mysqli_stmt_get_result($q_pw)->fetch_assoc()['password'];
            
            if(!password_verify($cur, $hx) && md5($cur) !== $hx) throw new Exception("Current Authentication block invalid.");
            
            $nHsh = password_hash($new, PASSWORD_BCRYPT);
            $stmt = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "si", $nHsh, $nurse_id);
            if(mysqli_stmt_execute($stmt)) {
                secureLogNurse($conn, $real_nurse_id, "Initiated critical User Token / Password reassignment.");
                echo json_encode(['success'=>true, 'message'=>'Security Authorization key locked.']);
            } else throw new Exception("User Auth table write failure.");
            break;

        case 'get_sessions':
            $sess = [];
            $q_sess = mysqli_query($conn, "SELECT * FROM nurse_sessions WHERE nurse_id=$real_nurse_id ORDER BY last_active DESC");
            while($s = mysqli_fetch_assoc($q_sess)) {
                // Formatting Last Active distance
                $la = strtotime($s['last_active']);
                $diff = time() - $la;
                if($diff < 300) $s['last_active'] = "Just Now";
                elseif($diff < 3600) $s['last_active'] = floor($diff/60) . " mins ago";
                else $s['last_active'] = date('M d, H:i', $la);
                
                $sess[] = $s;
            }
            if(count($sess) == 0) {
                // Mock one if none exist for active
                $sess[] = [
                    'session_id' => 999,
                    'device_info' => 'Windows / Chrome',
                    'browser' => 'System Dashboard Instance',
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'last_active' => 'Just Now',
                    'is_current_session' => 1
                ];
            }
            echo json_encode(['success'=>true, 'data'=>$sess]);
            break;

        case 'kill_session':
             $sid = (int)$_POST['session_id'];
             $stmt = mysqli_prepare($conn, "DELETE FROM nurse_sessions WHERE session_id=? AND nurse_id=?");
             mysqli_stmt_bind_param($stmt, "ii", $sid, $real_nurse_id);
             mysqli_stmt_execute($stmt);
             secureLogNurse($conn, $real_nurse_id, "Forcibly pruned active environment session (ID: $sid).");
             echo json_encode(['success'=>true]);
             break;

        default:
            echo json_encode(['success'=>false, 'message'=>'Unidentified processor action routing request.']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
