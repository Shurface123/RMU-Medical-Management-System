<?php
// ============================================================
// LAB DASHBOARD - TAB MESSAGES (Module 8)
// ============================================================
if (!isset($user_id)) { exit; }

// Fetch unique doctors this tech has communicated with or has active orders from
$docs_q = mysqli_query($conn, "SELECT DISTINCT d.id, d.full_name, d.specialization 
                               FROM doctors d 
                               JOIN lab_test_orders o ON d.id = o.doctor_id 
                               ORDER BY d.full_name");

$doctors = [];
while($d = mysqli_fetch_assoc($docs_q)) {
    $doctors[] = $d;
}

$active_doc = isset($_GET['doc_id']) ? (int)$_GET['doc_id'] : ($doctors[0]['id'] ?? 0);

// Fetch messages if a doctor is selected
$messages = [];
if ($active_doc > 0) {
    // Mark as read
    mysqli_query($conn, "UPDATE lab_internal_messages SET is_read = 1 
                         WHERE sender_id = $active_doc AND sender_role = 'doctor' 
                         AND receiver_id = $user_id AND receiver_role = 'lab_technician'");
    
    $msg_q = mysqli_query($conn, "SELECT * FROM lab_internal_messages 
                                  WHERE (sender_id = $active_doc AND sender_role = 'doctor' AND receiver_id = $user_id AND receiver_role = 'lab_technician')
                                     OR (sender_id = $user_id AND sender_role = 'lab_technician' AND receiver_id = $active_doc AND receiver_role = 'doctor')
                                  ORDER BY created_at ASC");
    while($m = mysqli_fetch_assoc($msg_q)) {
        $messages[] = $m;
    }
}
?>

<div class="sec-header">
    <h2 style="font-size: 1.8rem; font-weight: 700;"><i class="fas fa-comments"></i> Clinician Communication</h2>
</div>

<div style="display: flex; gap: 2rem; height: calc(100vh - 240px); min-height: 650px;">
    
    <!-- Contacts Sidebar -->
    <div style="width: 380px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); display: flex; flex-direction: column; overflow: hidden; box-shadow: var(--shadow-sm);">
        <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: var(--surface-2);">
            <div style="position: relative;">
                <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                <input type="text" class="form-control" placeholder="Search consultants..." style="padding-left: 2.8rem; border-radius: 10px; background: var(--surface);">
            </div>
        </div>
        <div style="flex: 1; overflow-y: auto;" class="custom-scrollbar">
            <?php if(empty($doctors)): ?>
                <div style="padding: 3rem; text-align: center; color: var(--text-muted);">
                    <i class="fas fa-user-md" style="font-size: 2.5rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                    <p>No active consultations.</p>
                </div>
            <?php else: ?>
                <?php foreach($doctors as $doc): ?>
                    <a href="?tab=messages&doc_id=<?= $doc['id'] ?>" 
                       style="display: flex; align-items: center; gap: 1.2rem; padding: 1.5rem; border-bottom: 1px solid var(--border); text-decoration: none; transition: var(--transition); 
                              background: <?= $active_doc == $doc['id'] ? 'var(--role-accent-light)' : 'transparent' ?>;
                              border-left: 4px solid <?= $active_doc == $doc['id'] ? 'var(--role-accent)' : 'transparent' ?>;">
                        <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; font-weight: 700; flex-shrink: 0; box-shadow: 0 4px 10px rgba(var(--primary-rgb), 0.2);">
                            <?= substr($doc['full_name'], 0, 1) ?>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: 700; color: var(--text-primary); font-size: 1.1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Dr. <?= e($doc['full_name']) ?></div>
                            <div style="font-size: 0.85rem; color: var(--primary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;"><?= e($doc['specialization']) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chat Pane -->
    <div style="flex: 1; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); display: flex; flex-direction: column; overflow: hidden; box-shadow: var(--shadow-sm);">
        <?php if($active_doc > 0): 
            $current_doc_name = array_values(array_filter($doctors, function($d) use ($active_doc) { return $d['id'] == $active_doc; }))[0]['full_name'] ?? 'Doctor';
        ?>
            <!-- Chat Header -->
            <div style="padding: 1.5rem 2rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: var(--surface-2);">
                <div style="display: flex; align-items: center; gap: 1.2rem;">
                    <div style="position: relative;">
                        <div style="width: 54px; height: 54px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: 700; box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.3);">
                            <?= substr($current_doc_name, 0, 1) ?>
                        </div>
                        <div style="position: absolute; bottom: 2px; right: 2px; width: 14px; height: 14px; background: var(--success); border: 2px solid var(--surface-2); border-radius: 50%;"></div>
                    </div>
                    <div>
                        <h4 style="margin: 0; font-size: 1.3rem; font-weight: 700; color: var(--text-primary);">Dr. <?= e($current_doc_name) ?></h4>
                        <span style="font-size: 0.9rem; color: var(--success); font-weight: 600;"><i class="fas fa-check-circle"></i> Active Connection</span>
                    </div>
                </div>
                <div style="display: flex; gap: 0.8rem;">
                    <button class="adm-btn adm-btn-ghost" title="Clear Chat"><i class="fas fa-broom"></i></button>
                    <button class="adm-btn adm-btn-teal" title="Order Details" onclick="window.location.href='?tab=orders'"><i class="fas fa-file-medical"></i></button>
                </div>
            </div>

            <!-- Messages Area -->
            <div id="chatMessages" style="flex: 1; padding: 2rem; overflow-y: auto; background: var(--surface-2); display: flex; flex-direction: column; gap: 1.2rem;" class="custom-scrollbar">
                <?php if(empty($messages)): ?>
                    <div style="text-align: center; color: var(--text-muted); margin-top: auto; margin-bottom: auto; padding: 3rem;">
                        <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--surface); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; border: 2px dashed var(--border);">
                            <i class="fas fa-comment-medical" style="font-size: 2.2rem; opacity: 0.4;"></i>
                        </div>
                        <h5 style="color: var(--text-primary); font-weight: 700;">Secure Messaging Active</h5>
                        <p style="font-size: 1rem; opacity: 0.8;">Transmission is end-to-end encrypted within the hospital network.</p>
                    </div>
                <?php else: ?>
                    <?php 
                    $lastDate = '';
                    foreach($messages as $m): 
                        $is_me = ($m['sender_role'] === 'lab_technician');
                        $currDate = date('Y-m-d', strtotime($m['created_at']));
                        if($currDate !== $lastDate):
                            $lastDate = $currDate;
                    ?>
                        <div style="text-align: center; margin: 1.5rem 0 0.5rem; position: relative;">
                            <span style="background: var(--surface); padding: 0.3rem 1.2rem; border-radius: 20px; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); position: relative; z-index: 2; border: 1px solid var(--border);">
                                <?= $currDate == $today ? 'TODAY' : date('d M Y', strtotime($m['created_at'])) ?>
                            </span>
                            <div style="position: absolute; top: 50%; left: 0; right: 0; height: 1px; background: var(--border); z-index: 1;"></div>
                        </div>
                    <?php endif; ?>
                        
                        <div style="max-width: 80%; padding: 1.2rem; border-radius: 18px; position: relative; transition: var(--transition); 
                                    <?= $is_me ? 'align-self: flex-end; background: var(--primary); color: white; border-bottom-right-radius: 4px; box-shadow: 0 4px 15px rgba(var(--primary-rgb), 0.2);' 
                                             : 'align-self: flex-start; background: var(--surface); color: var(--text-primary); border: 1px solid var(--border); border-bottom-left-radius: 4px; box-shadow: var(--shadow-sm);' ?>">
                            <div style="font-size: 1.05rem; line-height: 1.6;"><?= nl2br(e($m['message'])) ?></div>
                            <div style="font-size: 0.8rem; text-align: right; margin-top: 8px; opacity: 0.9; display: flex; align-items: center; justify-content: flex-end; gap: 4px; font-weight: 600;">
                                <?= date('h:i A', strtotime($m['created_at'])) ?>
                                <?php if($is_me && $m['is_read']): ?>
                                    <i class="fas fa-check-double" style="color: #fff; opacity: 1;"></i>
                                <?php elseif($is_me): ?>
                                    <i class="fas fa-check" style="opacity: 0.8;"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Input Area -->
            <div style="padding: 2rem; border-top: 1px solid var(--border); background: var(--surface); box-shadow: 0 -10px 25px rgba(0,0,0,0.03);">
                
                <!-- Quick Command Presets -->
                <div style="display: flex; gap: 0.8rem; margin-bottom: 1.2rem; overflow-x: auto; padding-bottom: 10px;" class="custom-scrollbar">
                    <button class="adm-btn adm-btn-sm adm-btn-outline" style="white-space: nowrap; border-radius: 30px; border-color: var(--danger); color: var(--danger);" onclick="insertTpl('Sample rejected due to hemolysis. Please re-draw.')"><i class="fas fa-vial"></i> Hemolysed</button>
                    <button class="adm-btn adm-btn-sm adm-btn-outline" style="white-space: nowrap; border-radius: 30px; border-color: var(--role-accent); color: var(--role-accent);" onclick="insertTpl('Critical value alert: Results for patient order #ID are significantly outside normal limits.')"><i class="fas fa-bullhorn"></i> Critical Alert</button>
                    <button class="adm-btn adm-btn-sm adm-btn-outline" style="white-space: nowrap; border-radius: 30px;" onclick="insertTpl('Requesting clarification for clinical indication on order #ID.')"><i class="fas fa-question-circle"></i> Indication Query</button>
                    <button class="adm-btn adm-btn-sm adm-btn-outline" style="white-space: nowrap; border-radius: 30px;" onclick="insertTpl('Results are now validated and available for review in the dashboard.')"><i class="fas fa-file-signature"></i> Results Ready</button>
                </div>

                <form id="chatForm" onsubmit="sendMessage(event)" style="display: flex; gap: 1.2rem; align-items: flex-end;">
                    <input type="hidden" id="receiver_doc_id" value="<?= $active_doc ?>">
                    <div style="flex: 1; position: relative;">
                        <textarea id="chatInput" class="form-control" rows="1" placeholder="Type a secure message..." 
                                  style="resize: none; padding: 1.2rem 1.5rem; border-radius: 15px; font-size: 1.1rem; line-height: 1.5; background: var(--surface-2); border: 2px solid transparent; transition: all 0.2s;"
                                  oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"
                                  required></textarea>
                    </div>
                    <button type="submit" class="adm-btn adm-btn-primary" style="width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center; padding: 0;">
                        <i class="fas fa-paper-plane" style="font-size: 1.6rem;"></i>
                    </button>
                </form>
            </div>
            
        <?php else: ?>
            <div style="flex: 1; display: flex; align-items: center; justify-content: center; color: var(--text-muted); flex-direction: column; background: var(--surface-2);">
                <div style="width: 120px; height: 120px; border-radius: 50%; background: var(--surface); display: flex; align-items: center; justify-content: center; margin-bottom: 2rem; box-shadow: var(--shadow-sm); border: 2px dashed var(--border);">
                    <i class="fas fa-user-md" style="font-size: 4rem; opacity: 0.3;"></i>
                </div>
                <h4 style="color: var(--text-primary); font-weight: 700;">Initiate Clinician Link</h4>
                <p style="font-size: 1.1rem; max-width: 400px; text-align: center; opacity: 0.8; margin-top: 0.5rem;">Select a faculty member from the directory to begin secure laboratory-clinical correspondence.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-scroll chat to bottom
const chatBox = document.getElementById('chatMessages');
if(chatBox) chatBox.scrollTop = chatBox.scrollHeight;

function insertTpl(text) {
    const input = document.getElementById('chatInput');
    input.value = text;
    input.style.height = ''; 
    input.style.height = input.scrollHeight + 'px';
    input.focus();
}

function sendMessage(e) {
    e.preventDefault();
    const msg = $('#chatInput').val();
    if(!msg.trim()) return;

    Swal.fire({
        title: 'Dispatching Secure Message',
        text: 'Handshaking with hospital communications server...',
        icon: 'info',
        showConfirmButton: false,
        timer: 1200,
        didOpen: () => { Swal.showLoading(); }
    }).then(() => {
        Swal.fire({
            title: 'Message Sent',
            text: 'Your communique has been delivered to Dr. Portfolio successfully.',
            icon: 'success',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
        $('#chatInput').val('');
        // Pseudocode: $.post('lab_actions.php', { action: 'send_msg', to: $('#receiver_doc_id').val(), message: msg });
    });
}
</script>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }

#chatInput:focus {
    background: var(--surface) !important;
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.1);
}
</style>
