<?php
/**
 * chatbot_landing.php — Floating Chatbot Widget
 * Include before </body> on any page that should have the chatbot.
 * Requires landing-chatbot.js (already loaded on HTML pages).
 * For PHP pages, also ensure landing.css is loaded.
 */
$_placeholder = $chatbot_placeholder ?? 'Ask me anything…';
?>
<!-- ═══════════════════════════════════════════════════════
     CHATBOT WIDGET
     ══════════════════════════════════════════════════════ -->
<button class="lp-chatbot-btn" id="lpChatBtn" title="Chat with RMU Medical Assistant">
  <i class="fas fa-comment-medical"></i>
  <span class="lp-chat-notif" id="lpChatNotif" style="display:none;"></span>
</button>

<div class="lp-chatbot-window" id="lpChatWindow">
  <div class="lp-chat-header">
    <div class="lp-chat-avatar"><i class="fas fa-robot"></i></div>
    <div class="lp-chat-header-info">
      <div class="lp-chat-header-name">RMU Medical Assistant</div>
      <div class="lp-chat-header-status"><span class="lp-chat-online-dot"></span> Online</div>
    </div>
    <div class="lp-chat-header-actions">
      <button class="lp-chat-header-btn" id="lpChatMinimize" title="Minimize"><i class="fas fa-minus"></i></button>
      <button class="lp-chat-header-btn" id="lpChatClose" title="Close"><i class="fas fa-times"></i></button>
    </div>
  </div>
  <div class="lp-chat-messages" id="lpChatMessages"></div>
  <div class="lp-chat-suggestions" id="lpChatSuggestions"></div>
  <div class="lp-chat-input-area">
    <textarea class="lp-chat-input" id="lpChatInput" placeholder="<?= htmlspecialchars($_placeholder) ?>" rows="1"></textarea>
    <button class="lp-chat-send" id="lpChatSend" title="Send"><i class="fas fa-paper-plane"></i></button>
  </div>
</div>
