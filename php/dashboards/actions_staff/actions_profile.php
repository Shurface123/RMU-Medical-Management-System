<?php
/**
 * actions_profile.php — Profile-Related Staff Actions
 */
if (!defined('AJAX_REQUEST')) exit;

switch ($action) {

    case 'update_personal_info':
        $fields = ['full_name','date_of_birth','gender','nationality','marital_status','phone','secondary_phone','email','national_id','address'];
        $data = [];
        foreach ($fields as $f) $data[$f] = sanitize($_POST[$f] ?? '');
        if (!$data['full_name'] || !$data['email']) json_err('Name and email are required.');
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) json_err('Invalid email address.');

        $ok = dbExecute($conn,
            "UPDATE staff SET full_name=?,date_of_birth=?,gender=?,nationality=?,marital_status=?,phone=?,secondary_phone=?,email=?,national_id=?,address=?,updated_at=NOW() WHERE id=?",
            "ssssssssssi",
            [$data['full_name'],$data['date_of_birth'],$data['gender'],$data['nationality'],$data['marital_status'],
             $data['phone'],$data['secondary_phone'],$data['email'],$data['national_id'],$data['address'],$staff_id]
        );
        dbExecute($conn,"UPDATE users SET name=?,email=?,phone=? WHERE id=?","sssi",[$data['full_name'],$data['email'],$data['phone'],$user_id]);
        logStaffActivity($conn,$staff_id,'update_personal_info','profile');
        if ($ok !== false) json_ok('Personal information updated successfully.');
        json_err('Database error. Please try again.');

    case 'upload_photo':
        $path = handleUpload('photo','photos',['jpg','jpeg','png','webp'],2);
        if (is_array($path)) json_err($path['error']);
        if (!$path) json_err('No file uploaded or invalid type.');
        dbExecute($conn,"UPDATE staff SET profile_photo=?,updated_at=NOW() WHERE id=?","si",[$path,$staff_id]);
        dbExecute($conn,"UPDATE users SET profile_image=? WHERE id=?","si",[$path,$user_id]);
        logStaffActivity($conn,$staff_id,'upload_photo','profile');
        json_ok('Profile photo updated.', ['photo_url'=>"/RMU-Medical-Management-System/$path"]);

    case 'save_qualification':
        $cert_name = sanitize($_POST['certificate_name'] ?? '');
        $institution = sanitize($_POST['institution'] ?? '');
        $year = (int)($_POST['year_awarded'] ?? 0);
        if (!$cert_name || !$institution || !$year) json_err('All qualification fields required.');
        $file_path = handleUpload('document','qualifications',['pdf','jpg','jpeg','png'],5);
        if (is_array($file_path)) json_err($file_path['error']);
        $id = dbInsert($conn,
            "INSERT INTO staff_qualifications (staff_id,certificate_name,institution,year_awarded,file_path,created_at) VALUES (?,?,?,?,?,NOW())",
            "ississ",[$staff_id,$cert_name,$institution,$year,$file_path]
        );
        if ($id) { logStaffActivity($conn,$staff_id,'add_qualification','profile',$id); json_ok('Qualification added successfully.'); }
        json_err('Failed to save qualification.');

    case 'delete_qualification':
        $qid = (int)($_POST['qual_id'] ?? 0);
        $q = dbRow($conn,"SELECT file_path FROM staff_qualifications WHERE id=? AND staff_id=?","ii",[$qid,$staff_id]);
        if (!$q) json_err('Qualification not found.',403);
        dbExecute($conn,"DELETE FROM staff_qualifications WHERE id=? AND staff_id=?","ii",[$qid,$staff_id]);
        if ($q['file_path'] && file_exists(__DIR__."/../../".$q['file_path'])) @unlink(__DIR__."/../../".$q['file_path']);
        json_ok('Qualification deleted.');

    case 'upload_document':
        $doc_name = sanitize($_POST['doc_name'] ?? '');
        $doc_type = sanitize($_POST['doc_type'] ?? 'other');
        if (!$doc_name) json_err('Document name required.');
        $file_path = handleUpload('document','documents',['pdf','jpg','jpeg','png'],10);
        if (is_array($file_path)) json_err($file_path['error']);
        if (!$file_path) json_err('File upload failed.');
        $id = dbInsert($conn,
            "INSERT INTO staff_documents (staff_id,document_name,document_type,file_path,uploaded_at) VALUES (?,?,?,?,NOW())",
            "isss",[$staff_id,$doc_name,$doc_type,$file_path]
        );
        if ($id) json_ok('Document uploaded successfully.');
        json_err('Upload failed.');

    case 'delete_document':
        $did = (int)($_POST['doc_id'] ?? 0);
        $d = dbRow($conn,"SELECT file_path FROM staff_documents WHERE id=? AND staff_id=?","ii",[$did,$staff_id]);
        if (!$d) json_err('Document not found.',403);
        dbExecute($conn,"DELETE FROM staff_documents WHERE id=? AND staff_id=?","ii",[$did,$staff_id]);
        if ($d['file_path'] && file_exists(__DIR__."/../../".$d['file_path'])) @unlink(__DIR__."/../../".$d['file_path']);
        json_ok('Document deleted.');

    case 'compute_completeness':
        $score = 0;
        $max = 7;
        if ($staff && $staff['full_name']) $score++;
        if ($staff && $staff['date_of_birth'] && $staff['gender']) $score++;
        if ($staff && $staff['profile_photo']) $score++;
        if ($staff && $staff['phone'] && $staff['email']) $score++;
        $qual_count = (int)dbVal($conn,"SELECT COUNT(*) FROM staff_qualifications WHERE staff_id=?","i",[$staff_id]);
        if ($qual_count > 0) $score++;
        $doc_count = (int)dbVal($conn,"SELECT COUNT(*) FROM staff_documents WHERE staff_id=?","i",[$staff_id]);
        if ($doc_count > 0) $score++;
        $settings = dbRow($conn,"SELECT settings_id FROM staff_settings WHERE staff_id=? LIMIT 1","i",[$staff_id]);
        if ($settings) $score++;
        $pct = round(($score/$max)*100);
        $ex = dbRow($conn,"SELECT record_id FROM staff_profile_completeness WHERE staff_id=? LIMIT 1","i",[$staff_id]);
        if ($ex) dbExecute($conn,"UPDATE staff_profile_completeness SET overall_percentage=?,last_updated=NOW() WHERE staff_id=?","ii",[$pct,$staff_id]);
        else dbInsert($conn,"INSERT INTO staff_profile_completeness (staff_id,overall_percentage,last_updated) VALUES (?,?,NOW())","ii",[$staff_id,$pct]);
        json_ok('Completeness computed.',['percent'=>$pct,'score'=>$score,'max'=>$max]);
}
