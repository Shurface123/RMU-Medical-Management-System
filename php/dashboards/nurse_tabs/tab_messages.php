<?php
// ============================================================
// NURSE DASHBOARD - MESSAGES (MODULE 10)
// ============================================================
if (!isset($conn)) exit;

// ── GET PATIENTS (For context linking) ───────────────────────
$patients_list = [];
$q_pw = mysqli_query($conn, "
    SELECT p.id, p.patient_id, u.name 
    FROM patients p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY u.name ASC
");
if ($q_pw) {
    while($r = mysqli_fetch_assoc($q_pw)) $patients_list[] = $r;
}

// ── GET DOCTORS & ADMINS ─────────────────────────────────────
$staff_list = [];
$q_staff = mysqli_query($conn, "
    SELECT id AS user_id, name, user_role 
    FROM users 
    WHERE user_role IN ('doctor', 'admin') AND id != $user_id
    ORDER BY user_role ASC, name ASC
");
if ($q_staff) {
    while($r = mysqli_fetch_assoc($q_staff)) $staff_list[] = $r;
}

// ── GET INBOX MESSAGES ───────────────────────────────────────
$inbox = [];
$q_inbox = mysqli_query($conn, "
    SELECT m.*, 
           u.name AS sender_name, u.user_role AS sender_role_display,
           p.patient_id as pid, pu.name as patient_name
    FROM nurse_doctor_messages m
    JOIN users u ON m.sender_id = u.id
    LEFT JOIN patients p ON m.patient_id = p.id
    LEFT JOIN users pu ON p.user_id = pu.id
    WHERE m.receiver_id = $user_id
    ORDER BY m.sent_at DESC
");
if ($q_inbox) {
    while($r = mysqli_fetch_assoc($q_inbox)) $inbox[] = $r;
}

// ── GET SENT MESSAGES ────────────────────────────────────────
$sent = [];
$q_sent = mysqli_query($conn, "
    SELECT m.*, 
           u.name AS receiver_name, u.user_role AS receiver_role_display,
           p.patient_id as pid, pu.name as patient_name
    FROM nurse_doctor_messages m
    JOIN users u ON m.receiver_id = u.id
    LEFT JOIN patients p ON m.patient_id = p.id
    LEFT JOIN users pu ON p.user_id = pu.id
    WHERE m.sender_id = $user_id
    ORDER BY m.sent_at DESC LIMIT 50
");
if ($q_sent) {
    while($r = mysqli_fetch_assoc($q_sent)) $sent[] = $r;
}
?>

<div class="tab-content" id="messages">

    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="mb-0 text-primary fw-bold"><i class="fas fa-comments me-2"></i> Messages</h4>
            <p class="text-muted mb-0">Secure communication with doctors and administration.</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="new bootstrap.Modal(document.getElementById('composeModal')).show();">
                <i class="fas fa-pen me-2"></i> Compose
            </button>
        </div>
    </div>

    <div class="card" style="border-radius: 12px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05); min-height: 600px;">
        <div class="row g-0 h-100">
            
            <!-- Left Sidebar (Folders/List) -->
            <div class="col-md-4 border-end bg-light" style="border-radius: 12px 0 0 12px;">
                <div class="p-3 border-bottom bg-white" style="border-radius: 12px 0 0 0;">
                    <ul class="nav nav-pills nav-fill" id="msgTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill fw-bold" id="inbox-tab" data-bs-toggle="tab" data-bs-target="#inbox-list" type="button">
                                <i class="fas fa-inbox me-1"></i> Inbox
                                <?php 
                                    $unread = array_reduce($inbox, fn($c,$m) => $c + ($m['is_read']?0:1), 0);
                                    if($unread > 0) echo "<span class='badge bg-danger ms-1'>$unread</span>";
                                ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill fw-bold" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent-list" type="button">
                                <i class="fas fa-paper-plane me-1"></i> Sent
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="tab-content h-100" style="overflow-y: auto; max-height: 550px;">
                    <!-- Inbox List -->
                    <div class="tab-pane fade show active" id="inbox-list">
                        <?php if(empty($inbox)): ?>
                            <div class="text-center p-4 text-muted"><i class="fas fa-envelope-open-text fs-2 mb-2 opacity-50"></i><br>Inbox is empty.</div>
                        <?php else: ?>
                            <div class="list-group list-group-flush msg-list">
                                <?php foreach($inbox as $m): 
                                    $bg = $m['is_read'] ? 'bg-white' : 'bg-primary bg-opacity-10 header-border';
                                ?>
                                    <a href="#" class="list-group-item list-group-item-action p-3 <?= $bg ?>" onclick="viewMessage('inbox', <?= htmlspecialchars(json_encode($m)) ?>)">
                                        <div class="d-flex w-100 justify-content-between mb-1">
                                            <h6 class="mb-0 fw-bold <?= $m['is_read']?'text-dark':'text-primary' ?> text-truncate" style="max-width:70%;">
                                                <?= e($m['sender_name']) ?> <small class="text-muted">(<?= ucfirst(e($m['sender_role'])) ?>)</small>
                                            </h6>
                                            <small class="text-muted" style="font-size: 0.75rem;"><?= date('d M, H:i', strtotime($m['sent_at'])) ?></small>
                                        </div>
                                        <p class="mb-1 text-truncate fw-bold <?= $m['is_read']?'text-secondary':'text-dark' ?>" style="font-size: 0.9rem;">
                                            <?= e($m['subject'] ?: 'No Subject') ?>
                                        </p>
                                        <small class="text-muted text-truncate d-block"><?= e(substr($m['message_content'], 0, 50)) ?>...</small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sent List -->
                    <div class="tab-pane fade" id="sent-list">
                        <?php if(empty($sent)): ?>
                            <div class="text-center p-4 text-muted"><i class="fas fa-paper-plane fs-2 mb-2 opacity-50"></i><br>No messages sent.</div>
                        <?php else: ?>
                            <div class="list-group list-group-flush msg-list">
                                <?php foreach($sent as $m): ?>
                                    <a href="#" class="list-group-item list-group-item-action p-3 bg-white" onclick="viewMessage('sent', <?= htmlspecialchars(json_encode($m)) ?>)">
                                        <div class="d-flex w-100 justify-content-between mb-1">
                                            <h6 class="mb-0 fw-bold text-dark text-truncate" style="max-width:70%;">
                                                To: <?= e($m['receiver_name']) ?>
                                            </h6>
                                            <small class="text-muted" style="font-size: 0.75rem;"><?= date('d M', strtotime($m['sent_at'])) ?></small>
                                        </div>
                                        <p class="mb-1 text-truncate fw-bold text-secondary" style="font-size: 0.9rem;">
                                            <?= e($m['subject'] ?: 'No Subject') ?>
                                        </p>
                                        <small class="text-muted text-truncate d-block">
                                            <?php if($m['is_read']): ?><i class="fas fa-check-double text-success me-1"></i><?php endif; ?>
                                            <?= e(substr($m['message_content'], 0, 50)) ?>...
                                        </small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Reading Pane -->
            <div class="col-md-8 bg-white" style="border-radius: 0 12px 12px 0;">
                <div id="read-pane-empty" class="h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                    <div class="bg-light rounded-circle p-4 mb-3 d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                        <i class="fas fa-envelope-open fs-1 opacity-25"></i>
                    </div>
                    <h5 class="fw-bold opacity-50">Select a message to read</h5>
                </div>

                <div id="read-pane-content" class="h-100 d-none flex-column">
                    <div class="p-4 border-bottom w-100 bg-light" style="border-radius: 0 12px 0 0;">
                        <h4 id="msgSubject" class="fw-bold text-dark mb-3">Subject</h4>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3 shadow-sm" style="width: 45px; height: 45px; font-size: 1.2rem;" id="msgAvatar">
                                    ?
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold" id="msgFromTo">Sender -> Me</h6>
                                    <small class="text-muted" id="msgDate">Date Time</small>
                                </div>
                            </div>
                            <div>
                                <button class="btn btn-outline-primary btn-sm rounded-pill px-3" id="btnReply" onclick="replyMessage()"><i class="fas fa-reply me-1"></i> Reply</button>
                            </div>
                        </div>
                    </div>

                    <div id="msgPatientContext" class="bg-primary bg-opacity-10 p-2 text-center text-primary d-none border-bottom" style="font-size: 0.85rem; font-weight: 600;">
                        <i class="fas fa-user-injured me-1"></i> Regarding Patient: <span id="msgPatientName"></span>
                    </div>

                    <div class="p-4" style="overflow-y: auto; flex-grow: 1; font-size: 1rem; line-height: 1.6;" id="msgBody">
                        Message Content Goes Here
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
.msg-list .list-group-item { border-left: none; border-right: none; transition: all 0.2s; }
.msg-list .list-group-item:first-child { border-top: none; }
.msg-list .list-group-item.header-border { border-left: 4px solid var(--primary-color); }
</style>

<!-- ========================================== -->
<!-- MODAL: COMPOSE MESSAGE                     -->
<!-- ========================================== -->
<div class="modal fade" id="composeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 15px; border: none;">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); border-radius: 15px 15px 0 0;">
                <h5 class="modal-title"><i class="fas fa-pen me-2"></i> Compose Message</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="composeForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="send_message">
                <div class="modal-body p-4 bg-light">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">To (Recipient)</label>
                            <select class="form-select border-primary" name="receiver_id" id="compReceiver" required>
                                <option value="">-- Select Staff Member --</option>
                                <?php foreach($staff_list as $s): ?>
                                    <option value="<?= $s['user_id'] ?>"><?= ucfirst(e($s['user_role'])) ?>: <?= e($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Regarding Patient (Optional)</label>
                            <select class="form-select" name="patient_id" id="compPatient">
                                <option value="">-- No specific patient --</option>
                                <?php foreach($patients_list as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= e($p['name']) ?> (<?= e($p['patient_id']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small text-uppercase">Subject</label>
                        <input type="text" class="form-control" name="subject" id="compSubject" placeholder="Brief subject line..." required>
                    </div>

                    <div class="mb-0">
                        <label class="form-label text-muted fw-bold small text-uppercase">Message Body</label>
                        <textarea class="form-control" name="message_content" id="compBody" rows="6" placeholder="Type your message here..." required></textarea>
                    </div>

                </div>
                <div class="modal-footer border-0 bg-white" style="border-radius:0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Discard</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold" id="btnSendMsg"><i class="fas fa-paper-plane me-2"></i> Send</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentMsg = null;
let currentFolder = '';

function viewMessage(folder, msg) {
    currentMsg = msg;
    currentFolder = folder;
    
    $('#read-pane-empty').addClass('d-none');
    $('#read-pane-content').removeClass('d-none').addClass('d-flex');
    
    $('#msgSubject').text(msg.subject || 'No Subject');
    $('#msgBody').html(msg.message_content.replace(/\n/g, '<br>'));
    
    // Formatting Dates beautifully
    const msgDate = new Date(msg.sent_at);
    $('#msgDate').text(msgDate.toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit' }));
    
    if(folder === 'inbox') {
        const initial = msg.sender_name.charAt(0).toUpperCase();
        $('#msgAvatar').text(initial).removeClass('bg-secondary').addClass('bg-primary');
        $('#msgFromTo').html(`${msg.sender_name} <small class="text-muted fw-normal">to me</small>`);
        $('#btnReply').removeClass('d-none');
        
        // Mark as read via AJAX if unread
        if(msg.is_read == 0) {
            $.post('../nurse/process_messages.php', { action: 'mark_read', msg_id: msg.id, _csrf: '<?= generateCsrfToken() ?>' }, function(res) {
               // Silently update
            });
            msg.is_read = 1; // local update
        }
    } else {
        const initial = msg.receiver_name.charAt(0).toUpperCase();
        $('#msgAvatar').text(initial).removeClass('bg-primary').addClass('bg-secondary');
        $('#msgFromTo').html(`Me <small class="text-muted fw-normal">to ${msg.receiver_name}</small>`);
        $('#btnReply').addClass('d-none');
    }

    if(msg.patient_name) {
        $('#msgPatientContext').removeClass('d-none');
        $('#msgPatientName').text(`${msg.patient_name} (${msg.pid})`);
    } else {
        $('#msgPatientContext').addClass('d-none');
    }
}

function replyMessage() {
    if(!currentMsg || currentFolder !== 'inbox') return;
    
    document.getElementById('composeForm').reset();
    $('#compReceiver').val(currentMsg.sender_id); // sender_id points to users.id
    $('#compPatient').val(currentMsg.patient_id);
    
    let subj = currentMsg.subject || '';
    if(!subj.toUpperCase().startsWith('RE:')) subj = 'Re: ' + subj;
    $('#compSubject').val(subj);
    
    $('#compBody').val('');
    new bootstrap.Modal(document.getElementById('composeModal')).show();
}

$(document).ready(function() {
    $('#composeForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#btnSendMsg');
        const origText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');
        
        $.ajax({
            url: '../nurse/process_messages.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    location.reload();
                } else {
                    alert('Error: ' + res.message);
                    btn.prop('disabled', false).html(origText);
                }
            },
            error: function() {
                alert('An error occurred.');
                btn.prop('disabled', false).html(origText);
            }
        });
    });
});
</script>
