/**
 * landing-chatbot.js — RMU Medical Sickbay Advanced Chatbot v2
 * Features: Intent matching, session memory, typing indicator,
 *           quick replies, personalized greeting, conversation logging
 */

const Chatbot = (() => {
  const BASE        = '/RMU-Medical-Management-System';
  const CHAT_API    = `${BASE}/php/public/chatbot_api.php`;
  const SESSION_KEY = 'rmu_chat_conv_id';

  let convId    = parseInt(sessionStorage.getItem(SESSION_KEY)) || 0;
  let isOpen    = false;
  let hasGreeted= false;

  // Context memory: last intent for follow-up understanding
  let lastIntent = null;

  /* ── DOM refs ─────────────────────────────────────────── */
  let winEl, btnEl, messagesEl, inputEl, notifDot;

  /* ── Init ─────────────────────────────────────────────── */
  function init() {
    winEl      = document.getElementById('lpChatWindow');
    btnEl      = document.getElementById('lpChatBtn');
    messagesEl = document.getElementById('lpChatMessages');
    inputEl    = document.getElementById('lpChatInput');
    notifDot   = document.getElementById('lpChatNotif');

    if (!winEl || !btnEl) return;

    // Toggle open/close
    btnEl.addEventListener('click', () => isOpen ? close() : open());

    // Close / minimize buttons
    document.getElementById('lpChatClose')?.addEventListener('click', close);
    document.getElementById('lpChatMinimize')?.addEventListener('click', close);

    // Send on button click
    document.getElementById('lpChatSend')?.addEventListener('click', sendMessage);

    // Send on Enter (Shift+Enter = newline)
    inputEl?.addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });

    // Pulse notification after 2s to attract attention
    setTimeout(() => {
      if (!isOpen && notifDot) notifDot.style.display = 'block';
    }, 2000);

    // Load initial suggestions
    loadInitialSuggestions();
  }

  /* ── Open / Close ─────────────────────────────────────── */
  function open() {
    isOpen = true;
    winEl.classList.add('open');
    btnEl.querySelector('i').className = 'fas fa-times';
    if (notifDot) notifDot.style.display = 'none';
    inputEl?.focus();

    if (!hasGreeted) {
      hasGreeted = true;
      setTimeout(() => greet(), 400);
    }
  }

  function close() {
    isOpen = false;
    winEl.classList.remove('open');
    btnEl.querySelector('i').className = 'fas fa-comment-medical';
  }

  /* ── Greeting ─────────────────────────────────────────── */
  async function greet() {
    try {
      const fd = new FormData();
      fd.append('action', 'greeting');
      const res  = await fetch(CHAT_API, { method: 'POST', body: fd });
      const data = await res.json();
      const msg  = data.success ? data.greeting : 'Hello! Welcome to RMU Medical Sickbay. How can I assist you today? 🏥';
      addMessage('bot', msg);
      // Show quick suggestion chips after greeting
      showQuickReplies(['Book an appointment', 'Emergency contact', 'Our services', 'Lab tests', 'Pharmacy']);
    } catch {
      addMessage('bot', 'Hello! Welcome to RMU Medical Sickbay. How can I assist you today? 🏥');
      showQuickReplies(['Book an appointment', 'Emergency contact', 'Our services']);
    }
  }

  /* ── Load initial suggestion chips ───────────────────── */
  async function loadInitialSuggestions() {
    try {
      const fd = new FormData();
      fd.append('action', 'suggestions');
      const res  = await fetch(CHAT_API, { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) window._chatSuggestions = data.data;
    } catch {}
  }

  /* ── Send Message ─────────────────────────────────────── */
  async function sendMessage() {
    const text = (inputEl?.value || '').trim();
    if (!text) return;

    // Clear input
    inputEl.value = '';
    inputEl.style.height = 'auto';

    // Remove quick reply chips
    clearSuggestions();

    // Show user message
    addMessage('user', text);

    // Show typing indicator
    const typingEl = showTyping();

    try {
      const fd = new FormData();
      fd.append('action', 'query');
      fd.append('message', text);
      fd.append('conversation_id', convId);
      // Send context
      if (lastIntent) fd.append('last_intent', lastIntent);

      const res  = await fetch(CHAT_API, { method: 'POST', body: fd });
      const data = await res.json();

      // Save conversation id
      if (data.conversation_id) {
        convId = data.conversation_id;
        sessionStorage.setItem(SESSION_KEY, convId);
      }

      // Update context
      if (data.intent) lastIntent = data.intent;

      // Remove typing
      typingEl?.remove();

      // Empathetic preamble for health concerns
      const empathyTriggers = ['sick', 'pain', 'hurt', 'ill', 'fever', 'not feeling', 'emergency', 'accident', 'bleed'];
      const lowerText = text.toLowerCase();
      const isHealthConcern = empathyTriggers.some(t => lowerText.includes(t));

      if (data.success) {
        let reply = data.reply;
        if (isHealthConcern && !reply.toLowerCase().includes("sorry")) {
          reply = "I'm sorry to hear you're not feeling well. " + reply;
        }
        addMessage('bot', reply);

        // Show follow-up suggestions
        const suggestions = data.quick_replies || [];
        if (suggestions.length) showQuickReplies(suggestions);

      } else {
        addMessage('bot', "I'm having trouble understanding. Could you rephrase your question? 😊");
        showQuickReplies(['Book an appointment', 'Emergency: 153', 'Our services']);
      }

    } catch (err) {
      typingEl?.remove();
      addMessage('bot', "I'm sorry, I'm having a connection issue. Please try again or call us directly at 0502371207. 📞");
    }
  }

  /* ── Add Message Bubble ───────────────────────────────── */
  function addMessage(sender, text) {
    const isBot = sender === 'bot';
    const now   = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

    const msgEl = document.createElement('div');
    msgEl.className = `lp-chat-msg lp-chat-msg-${sender}`;
    msgEl.innerHTML = `
      ${isBot ? `<div class="lp-chat-msg-avatar"><i class="fas fa-robot"></i></div>` : ''}
      <div style="display:flex;flex-direction:column;gap:2px;">
        <div class="lp-chat-bubble">${formatBotText(text)}</div>
        <div class="lp-chat-bubble-time">${now}</div>
      </div>
      ${!isBot ? `<div class="lp-chat-msg-avatar" style="background:var(--lp-primary);color:#fff;"><i class="fas fa-user"></i></div>` : ''}
    `;

    if (messagesEl) {
      messagesEl.appendChild(msgEl);
      scrollToBottom();
    }
  }

  /* ── Typing Indicator ─────────────────────────────────── */
  function showTyping() {
    const el = document.createElement('div');
    el.className = 'lp-chat-msg lp-chat-msg-bot';
    el.id = 'chatTyping';
    el.innerHTML = `
      <div class="lp-chat-msg-avatar"><i class="fas fa-robot"></i></div>
      <div class="lp-chat-typing"><span></span><span></span><span></span></div>
    `;
    if (messagesEl) {
      messagesEl.appendChild(el);
      scrollToBottom();
    }
    return el;
  }

  /* ── Quick Reply Chips ────────────────────────────────── */
  function showQuickReplies(suggestions) {
    clearSuggestions();
    const container = document.getElementById('lpChatSuggestions');
    if (!container) return;
    container.innerHTML = suggestions.slice(0, 4).map(s => `
      <button class="lp-chat-suggestion" onclick="Chatbot.sendQuick(this)">${escHtml(s)}</button>
    `).join('');
  }

  function clearSuggestions() {
    const container = document.getElementById('lpChatSuggestions');
    if (container) container.innerHTML = '';
  }

  window.Chatbot = { sendQuick };

  function sendQuick(btn) {
    if (!inputEl) return;
    inputEl.value = btn.textContent;
    sendMessage();
  }

  /* ── Helpers ──────────────────────────────────────────── */
  function scrollToBottom() {
    if (messagesEl) messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  // Format bot text: convert **bold**, URLs, line breaks
  function formatBotText(text) {
    if (!text) return '';
    return escHtml(text)
      .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
      .replace(/\n/g, '<br>')
      .replace(/(https?:\/\/[^\s<]+)/g, `<a href="$1" target="_blank" style="color:var(--lp-primary);text-decoration:underline;">$1</a>`)
      .replace(/(\d{3,})/g, '<strong>$1</strong>');  // Highlight numbers (phone, etc)
  }

  return { init, open, close, sendQuick };
})();

document.addEventListener('DOMContentLoaded', () => Chatbot.init());
