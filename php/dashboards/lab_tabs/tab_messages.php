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
    <h2><i class="fas fa-comments"></i> Doctor-Lab Communication</h2>
</div>

<div style="display: flex; gap: 1.5rem; height: calc(100vh - 200px); min-height: 600px;">
    
    <!-- Contacts Sidebar -->
    <div style="width: 350px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); display: flex; flex-direction: column; overflow: hidden;">
        <div style="padding: 1.2rem; border-bottom: 1px solid var(--border); background: var(--surface-2);">
            <input type="text" class="form-control" placeholder="Search doctors...">
        </div>
        <div style="flex: 1; overflow-y: auto;">
            <?php if(empty($doctors)): ?>
                <div style="padding: 2rem; text-align: center; color: var(--text-muted);">No doctors found.</div>
            <?php else: ?>
                <?php foreach($doctors as $doc): ?>
                    <a href="?tab=messages&doc_id=<?= $doc['id'] ?>" style="display: flex; align-items: center; gap: 1rem; padding: 1.2rem; border-bottom: 1px solid var(--border); text-decoration: none; transition: var(--transition); background: <?= $active_doc == $doc['id'] ? 'var(--role-accent-light)' : 'transparent' ?>;">
                        <div style="width: 45px; height: 45px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 600;">
                            <?= substr($doc['full_name'], 0, 1) ?>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: var(--text-primary); font-size: 1.1rem;">Dr. <?= e($doc['full_name']) ?></div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);"><?= e($doc['specialization']) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chat Pane -->
    <div style="flex: 1; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); display: flex; flex-direction: column; overflow: hidden;">
        <?php if($active_doc > 0): 
            $current_doc_name = array_values(array_filter($doctors, function($d) use ($active_doc) { return $d['id'] == $active_doc; }))[0]['full_name'] ?? 'Doctor';
        ?>
            <!-- Chat Header -->
            <div style="padding: 1.2rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: var(--surface-2);">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 45px; height: 45px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 600;">
                        <?= substr($current_doc_name, 0, 1) ?>
                    </div>
                    <div>
                        <h4 style="margin: 0; font-size: 1.2rem; color: var(--text-primary);">Dr. <?= e($current_doc_name) ?></h4>
                        <span style="font-size: 0.85rem; color: var(--success);"><i class="fas fa-circle" style="font-size: 0.6rem;"></i> Connected</span>
                    </div>
                </div>
            </div>

            <!-- Messages Area -->
            <div id="chatMessages" style="flex: 1; padding: 1.5rem; overflow-y: auto; background: var(--surface-2); display: flex; flex-direction: column; gap: 1rem;">
                <?php if(empty($messages)): ?>
                    <div style="text-align: center; color: var(--text-muted); margin-top: auto; margin-bottom: auto;">
                        <i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i><br>
                        No messages yet. Start the conversation.
                    </div>
                <?php else: ?>
                    <?php foreach($messages as $m): 
                        $is_me = ($m['sender_role'] === 'lab_technician');
                    ?>
                        <div style="max-width: 75%; padding: 1rem; border-radius: var(--radius-md); <?= $is_me ? 'align-self: flex-end; background: var(--role-accent); color: white; border-bottom-right-radius: 0;' : 'align-self: flex-start; background: var(--surface); color: var(--text-primary); border: 1px solid var(--border); border-bottom-left-radius: 0;' ?>">
                            <div style="font-size: 0.95rem; line-height: 1.5;"><?= nl2br(e($m['message'])) ?></div>
                            <div style="font-size: 0.75rem; text-align: right; margin-top: 5px; opacity: 0.8;">
                                <?= date('h:i A', strtotime($m['created_at'])) ?>
                                <?php if($is_me && $m['is_read']): ?>
                                    <i class="fas fa-check-double" style="margin-left: 5px;"></i>
                                <?php elseif($is_me): ?>
                                    <i class="fas fa-check" style="margin-left: 5px;"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Input Area -->
            <div style="padding: 1.2rem; border-top: 1px solid var(--border); background: var(--surface);">
                
                <!-- Quick Templates -->
                <div style="display: flex; gap: 0.5rem; margin-bottom: 0.8rem; overflow-x: auto; padding-bottom: 5px;">
                    <button class="adm-badge" style="background:var(--surface-2); color:var(--text-secondary); border:1px solid var(--border); cursor:pointer;" onclick="insertTpl('Sample rejected due to hemolysis. Please re-draw.')">Sample Hemolysed</button>
                    <button class="adm-badge" style="background:var(--surface-2); color:var(--text-secondary); border:1px solid var(--border); cursor:pointer;" onclick="insertTpl('Results attached. Flagged critical parameters, please review immediately.')">Critical Results</button>
                    <button class="adm-badge" style="background:var(--surface-2); color:var(--text-secondary); border:1px solid var(--border); cursor:pointer;" onclick="insertTpl('Test delayed due to equipment calibration. Expect results in 2 hours.')">Delay Notice</button>
                </div>

                <form id="chatForm" onsubmit="sendMessage(event)" style="display: flex; gap: 1rem;">
                    <input type="hidden" id="receiver_doc_id" value="<?= $active_doc ?>">
                    <textarea id="chatInput" class="form-control" rows="2" placeholder="Type your message here..." style="resize: none;" required></textarea>
                    <button type="submit" class="adm-btn adm-btn-primary" style="padding: 0 2rem;"><i class="fas fa-paper-plane" style="font-size: 1.4rem;"></i></button>
                </form>
            </div>
            
        <?php else: ?>
            <div style="flex: 1; display: flex; align-items: center; justify-content: center; color: var(--text-muted); flex-direction: column;">
                <i class="fas fa-user-md" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <p style="font-size: 1.2rem;">Select a doctor from the list to view or send messages.</p>
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
    input.focus();
}

function sendMessage(e) {
    e.preventDefault();
    alert("Message queuing for Doctor ID: " + $('#receiver_doc_id').val() + "\nRequires backend integration in lab_actions.php ('send_internal_message').");
    // Pseudo-code for live integration:
    // $.post('lab_actions.php', { action: 'send_internal_message', to: doc_id, message: msg }, function(res) { location.reload(); });
}
</script>
