<?php
// Render the existing HTML and rewrite links to PHP counterparts for consistency
$source = __DIR__ . '/../withHTML/settings.html';
if (!is_file($source)) {
  http_response_code(500);
  echo 'Source HTML not found.';
  exit;
}
$html = file_get_contents($source);
$replacements = [
  'homemain.html' => 'homemain.php',
  'listing.html' => 'listing.php',
  'historyandtransaction.html' => 'historyandtransaction.php',
  'settings.html' => 'settings.php',
  'request.html' => 'request.php',
  'report.html' => 'report.php',
  'profile.html' => 'profile.php',
];
$html = str_replace(array_keys($replacements), array_values($replacements), $html);

// Inject Notification and Chat containers + standardized header handlers before </body>
$injection = <<<'HTML'
  
  <!-- Injected: Standardized styles and containers (match homemain.php) -->
  <style>
    .notification-container { position: fixed; width: 350px; background-color: white; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 100; display: none; transition: opacity 0.2s ease; }
    .notification-container.visible { display: block; }
    .notification-header { display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #eee; }
    .notification-header h3 { margin: 0; font-size: 18px; color: #333; }
    #close-notification { cursor: pointer; color: #999; }
    #close-notification:hover { color: #333; }
    .notification-list { max-height: 400px; overflow-y: auto; }
    .notification-item { padding: 15px; border-bottom: 1px solid #eee; cursor: pointer; transition: background-color 0.2s; }
    .notification-item:hover { background-color: #f5f5f5; }
    .notification-content { display: flex; flex-direction: column; }
    .notification-text { font-size: 14px; color: #333; margin-bottom: 5px; }
    .notification-text strong { color: #047857; }
    .notification-time { font-size: 12px; color: #999; }
    .notification-list::-webkit-scrollbar { width: 6px; }
    .notification-list::-webkit-scrollbar-track { background: #f1f1f1; }
    .notification-list::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 3px; }
    .notification-list::-webkit-scrollbar-thumb:hover { background: #a1a1a1; }

    .header-chat-container { position: fixed; width: 320px; background-color: white; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.12); z-index: 100; display: none; transition: all 0.3s ease; max-height: 450px; border: 1px solid #e4e6ea; }
    .header-chat-container.visible { display: block; opacity: 1; transform: translateY(0); }
    .chat-header-popup { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid #e4e6ea; background-color: #f8f9fa; border-radius: 12px 12px 0 0; }
    .chat-header-popup h3 { margin: 0; font-size: 18px; color: #1c1e21; font-weight: 700; }
    #close-chat { cursor: pointer; color: #8a8d91; font-size: 20px; padding: 4px; border-radius: 50%; transition: all 0.2s ease; }
    #close-chat:hover { color: #1c1e21; background-color: #e4e6ea; }
    .active-users-popup { padding: 16px 20px 12px; border-bottom: 1px solid #e4e6ea; }
    .active-title-popup { font-weight: 600; font-size: 14px; color: #047857; margin-bottom: 12px; }
    .active-list-popup { display: flex; gap: 12px; overflow-x: auto; padding-bottom: 8px; }
    .active-user-popup { display: flex; flex-direction: column; align-items: center; cursor: pointer; min-width: 60px; }
    .user-status-popup { position: relative; display: inline-block; margin-bottom: 6px; }
    .user-status-popup img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #e4e6ea; transition: transform 0.2s ease; }
    .active-user-popup:hover .user-status-popup img { transform: scale(1.05); border-color: #047857; }
    .status-indicator-popup { position: absolute; bottom: 2px; right: 2px; width: 12px; height: 12px; background-color: #42b883; border-radius: 50%; border: 2px solid white; }
    .name-popup { font-size: 12px; color: #65676b; text-align: center; max-width: 50px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .conversations-title-popup { font-weight: 600; font-size: 14px; color: #047857; padding: 12px 20px 8px; }
    .chat-list-popup { max-height: 280px; overflow-y: auto; }
    .chat-list-popup::-webkit-scrollbar { width: 6px; }
    .chat-list-popup::-webkit-scrollbar-track { background: transparent; }
    .chat-list-popup::-webkit-scrollbar-thumb { background: #bcc0c4; border-radius: 3px; }
    .chat-list-popup::-webkit-scrollbar-thumb:hover { background: #8a8d91; }
  </style>

  <div class="notification-container" id="notification-container" style="position:fixed; top:0; right:0">
    <div class="notification-header"><h3>Notifications</h3><i class="material-symbols-outlined" id="close-notification">close</i></div>
    <div class="notification-list">
      <div class="notification-item"><div class="notification-content"><div class="notification-text"><strong>System</strong> Settings page loaded.</div><div class="notification-time">Just now</div></div></div>
    </div>
  </div>

  <div class="header-chat-container" id="header-chat-container" style="position:fixed; top:0; right:0">
    <div class="chat-header-popup"><h3>Chats</h3><i class="material-symbols-outlined" id="close-chat">close</i></div>
    <div class="active-users-popup">
      <div class="active-title-popup">Active Now</div>
      <div class="active-list-popup">
        <div class="active-user-popup" data-user='{"id":"ana-gonzales","name":"Ana Gonzales","avatar":"https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/ee4b5487-b1ea-40b6-9a76-0415e304de49.png"}'>
          <div class="user-status-popup">
            <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/ee4b5487-b1ea-40b6-9a76-0415e304de49.png" alt="Ana Gonzales" />
            <span class="status-indicator-popup"></span>
          </div>
          <span class="name-popup">Ana</span>
        </div>
        <div class="active-user-popup" data-user='{"id":"carlos-reyes","name":"Carlos Reyes","avatar":"https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/331059ac-f6bf-4684-b902-d37824bad8f5.png"}'>
          <div class="user-status-popup">
            <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/331059ac-f6bf-4684-b902-d37824bad8f5.png" alt="Carlos Reyes" />
            <span class="status-indicator-popup"></span>
          </div>
          <span class="name-popup">Carlos</span>
        </div>
        <div class="active-user-popup" data-user='{"id":"lorna-lim","name":"Lorna Lim","avatar":"https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/2ab478de-5d6d-4dce-8c67-baf47fd7ad8e.png"}'>
          <div class="user-status-popup">
            <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/2ab478de-5d6d-4dce-8c67-baf47fd7ad8e.png" alt="Lorna Lim" />
            <span class="status-indicator-popup"></span>
          </div>
          <span class="name-popup">Lorna</span>
        </div>
      </div>
    </div>
    <div class="conversations-title-popup">Conversations</div>
    <div class="chat-list-popup" id="chat-list-popup"></div>
  </div>
  
  <!-- Additional styles for floating chat windows (from homemain.php) -->
  <style>
    .chat-window { position: fixed; width: 280px; height: 420px; background: white; border-radius: 12px 12px 0 0; box-shadow: 0 -4px 25px rgba(0,0,0,0.2); z-index: 1000; border: 1px solid #e4e6ea; border-bottom: none; transition: all 0.3s ease; }
    .chat-window.minimized { height: 42px; }
    .chat-window-header { background: linear-gradient(135deg, #047857 0%, #059669 100%); color: white; padding: 14px 18px; border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center; cursor: pointer; box-shadow: 0 2px 8px rgba(4,120,87,0.3); }
    .chat-window-user { display: flex; align-items: center; gap: 10px; }
    .chat-window-avatar { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.3); }
    .chat-window-name { font-weight: 600; font-size: 15px; }
    .chat-window-controls { display: flex; gap: 6px; }
    .chat-minimize, .chat-close { font-size: 20px; cursor: pointer; padding: 4px; border-radius: 50%; transition: all 0.2s; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; }
    .chat-minimize:hover, .chat-close:hover { background-color: rgba(255,255,255,0.2); transform: scale(1.1); }
    .chat-window-messages { height: 320px; overflow-y: auto; padding: 16px; background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%); scroll-behavior: smooth; }
    .chat-empty-state { text-align: center; color: #65676b; padding: 60px 20px; }
    .chat-empty-state h4 { margin: 0 0 8px 0; color: #047857; font-size: 16px; }
    .chat-empty-state p { margin: 0; font-size: 14px; }
    .chat-message { margin-bottom: 16px; }
    .chat-message.sent { text-align: right; }
    .chat-message.received { text-align: left; }
    .message-content { display: inline-block; max-width: 85%; padding: 10px 14px; border-radius: 18px; font-size: 14px; line-height: 1.4; position: relative; word-wrap: break-word; }
    .chat-message.sent .message-content { background: linear-gradient(135deg, #047857 0%, #059669 100%); color: white; box-shadow: 0 2px 8px rgba(4,120,87,0.3); }
    .chat-message.received .message-content { background: #e4e6ea; color: #1c1e21; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .message-time { font-size: 11px; color: #65676b; margin-top: 6px; }
    .message-status { font-size: 10px; color: #65676b; margin-top: 4px; }
    .chat-message.sent .message-status { text-align: right; }
    .typing-indicator { display: flex; align-items: center; padding: 12px 16px; margin-bottom: 8px; }
    .typing-indicator .typing-avatar { width: 24px; height: 24px; border-radius: 50%; margin-right: 8px; }
    .typing-bubble { background: #e4e6ea; border-radius: 18px; padding: 8px 12px; display: flex; align-items: center; gap: 3px; }
    .typing-dot { width: 6px; height: 6px; border-radius: 50%; background: #65676b; animation: typingAnimation 1.4s infinite; }
    .typing-dot:nth-child(2) { animation-delay: 0.2s; }
    .typing-dot:nth-child(3) { animation-delay: 0.4s; }
    .chat-window-input { display: flex; padding: 14px; background: white; border-top: 1px solid #e4e6ea; align-items: center; gap: 10px; }
    .chat-window-input input { flex: 1; border: 1px solid #e4e6ea; border-radius: 22px; padding: 10px 16px; outline: none; font-size: 14px; transition: all 0.2s; }
    .chat-window-input input:focus { border-color: #047857; box-shadow: 0 0 0 3px rgba(4,120,87,0.1); }
    .chat-window-input button { background: linear-gradient(135deg, #047857 0%, #059669 100%); color: white; border: none; border-radius: 50%; width: 38px; height: 38px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; box-shadow: 0 2px 6px rgba(4,120,87,0.3); }
    .chat-window-input button:hover { background: linear-gradient(135deg, #065f46 0%, #047857 100%); transform: scale(1.05); }
    .chat-window-input button:disabled { background: #e4e6ea; color: #65676b; cursor: not-allowed; transform: none; }
    .chat-window-input button i { font-size: 18px; }
    @keyframes typingAnimation { 0%, 60%, 100% { transform: translateY(0); } 30% { transform: translateY(-10px); } }
  </style>

  <div id="floating-chats-root"></div>

  <script>
    (function(){
      function $(sel, root){ return (root||document).querySelector(sel); }
      // Normalize header icons: assign IDs to forum/notifications icons if missing
      try {
        (document.querySelectorAll('.material-symbols-outlined')||[]).forEach(sp => {
          const label = (sp.textContent||'').trim().toLowerCase();
          const parentIcon = sp.closest && sp.closest('.icon');
          if (!parentIcon) return;
          if (label === 'forum' && !parentIcon.id) parentIcon.id = 'chat-icon';
          if (label === 'notifications' && !parentIcon.id) parentIcon.id = 'notification-icon';
        });
      } catch (e) {}

      const notifIcon = $('#notification-icon');
      const notifCont = $('#notification-container');
      const chatIcon = $('#chat-icon');
      const chatCont = $('#header-chat-container');
      if (!notifIcon || !notifCont || !chatIcon || !chatCont) return;

      function positionNear(el, cont){
        const r = el.getBoundingClientRect();
        const top = r.bottom + 5;
        const right = Math.max(10, window.innerWidth - r.right - 190);
        cont.style.top = top + 'px';
        cont.style.right = right + 'px';
      }
      function hideAll(){ notifCont.classList.remove('visible'); chatCont.classList.remove('visible'); }

      notifIcon.addEventListener('click', (e)=>{
        positionNear(notifIcon, notifCont);
        chatCont.classList.remove('visible');
        notifCont.classList.toggle('visible');
        e.stopPropagation();
      });
      chatIcon.addEventListener('click', (e)=>{
        positionNear(chatIcon, chatCont);
        notifCont.classList.remove('visible');
        chatCont.classList.toggle('visible');
        e.stopPropagation();
      });
      document.addEventListener('click', (e)=>{
        if (notifCont.classList.contains('visible') && !notifCont.contains(e.target) && e.target !== notifIcon) notifCont.classList.remove('visible');
        if (chatCont.classList.contains('visible') && !chatCont.contains(e.target) && e.target !== chatIcon) chatCont.classList.remove('visible');
      });
      window.addEventListener('resize', ()=>{
        if (notifCont.classList.contains('visible')) positionNear(notifIcon, notifCont);
        if (chatCont.classList.contains('visible')) positionNear(chatIcon, chatCont);
      });
      window.addEventListener('scroll', ()=>{
        if (notifCont.classList.contains('visible')) positionNear(notifIcon, notifCont);
        if (chatCont.classList.contains('visible')) positionNear(chatIcon, chatCont);
      }, { passive: true });
      const closeN = $('#close-notification');
      const closeC = $('#close-chat');
      closeN && closeN.addEventListener('click', ()=> { notifCont.classList.remove('visible'); notifCont.style.display='none'; });
      closeC && closeC.addEventListener('click', ()=> { chatCont.classList.remove('visible'); chatCont.style.display='none'; });
      document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape') hideAll(); });

      // Profile container navigation (match homemain.php)
      const profileLink = $('#profile-link');
      if (profileLink) profileLink.addEventListener('click', ()=> { window.location.href = 'profile.php'; });
      document.querySelectorAll('.profile-menu li').forEach(li => {
        li.addEventListener('click', ()=> {
          const href = li.getAttribute('data-href');
          if (href) window.location.href = href;
        });
      });
      const logoutBtn = $('#logout-btn');
      if (logoutBtn) logoutBtn.addEventListener('click', ()=> { window.location.href = 'homemain.php'; });
    })();
  </script>

  <!-- Chat logic ported from homemain.php: chat list + floating chat windows -->
  <script>
    (function(){
      function $(sel, root=document){ return root.querySelector(sel); }
      function $all(sel, root=document){ return Array.from(root.querySelectorAll(sel)); }

      const state = {
        conversations: [
          { id: 'mang-jose', name: 'Mang Jose', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/70ed3f6c-a0ca-4490-8b6a-eaa245dffa33.png', lastMessage: 'Hi, about the manure delivery tomorrow...', time: '2:30 PM', messages: [ { id: 1, text: 'Hi, about the manure delivery tomorrow...', sender: 'them', time: '2:30 PM' }, { id: 2, text: 'What time should I expect the delivery?', sender: 'them', time: '2:31 PM' } ] },
          { id: 'farmers-coop', name: 'Farmers Cooperative', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/250bc031-e5eb-4467-9d71-49154b992c13.png', lastMessage: 'New bulk order of organic fertilizer available', time: 'Yesterday', messages: [ { id: 1, text: 'New bulk order of organic fertilizer available', sender: 'them', time: 'Yesterday' }, { id: 2, text: 'Would you be interested in placing an order?', sender: 'them', time: 'Yesterday' } ] }
        ],
        openChatWindows: [],
        typingUsers: {},
        messageInputs: {},
      };

      function renderChatList() {
        const list = $('#chat-list-popup');
        if (!list) return;
        list.innerHTML = '';
        state.conversations.forEach(conversation => {
          const item = document.createElement('div');
          item.className = 'chat-item-popup';
          item.innerHTML = `
            <img src="${conversation.avatar}" alt="${conversation.name}" />
            <div class="chat-info-popup">
              <div class="chat-name-popup">${conversation.name}</div>
              <div class="chat-preview-popup">${conversation.lastMessage}</div>
            </div>
            <div class="chat-time-popup">${conversation.time}</div>
          `;
          item.addEventListener('click', () => openChatWindow(conversation));
          list.appendChild(item);
        });
        const extra = document.createElement('div');
        extra.className = 'chat-item-popup';
        extra.innerHTML = `
          <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/fd7699da-33fe-4e49-a185-6f45171cafb8.png" alt="AgriTech PH" />
          <div class="chat-info-popup">
            <div class="chat-name-popup">AgriTech PH</div>
            <div class="chat-preview-popup">Workshop on sustainable farming this Saturday</div>
          </div>
          <div class="chat-time-popup">Monday</div>
        `;
        list.appendChild(extra);
      }

      function openChatWindow(user) {
        if (state.openChatWindows.find(c => c.id === user.id)) return;
        const existingConversation = state.conversations.find(c => c.id === user.id);
        const newChat = {
          id: user.id,
          name: user.name,
          avatar: user.avatar,
          messages: (existingConversation && existingConversation.messages) || user.messages || [],
          isMinimized: false,
        };
        if (!existingConversation) {
          state.conversations = [ { id: user.id, name: user.name, avatar: user.avatar, lastMessage: 'Start a conversation...', time: 'Now', messages: [] }, ...state.conversations ];
          renderChatList();
        }
        state.openChatWindows.push(newChat);
        renderFloatingChats();
        const chatCont = document.getElementById('header-chat-container');
        if (chatCont) chatCont.classList.remove('visible');
      }

      function closeChatWindow(chatId) {
        state.openChatWindows = state.openChatWindows.filter(c => c.id !== chatId);
        renderFloatingChats();
      }

      function minimizeChatWindow(chatId) {
        state.openChatWindows = state.openChatWindows.map(c => c.id === chatId ? { ...c, isMinimized: !c.isMinimized } : c);
        renderFloatingChats();
      }

      function sendMessage(chatId, message) {
        if (!message || !message.trim()) return;
        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const newMessage = { id: Date.now(), text: message, sender: 'me', time, timestamp: new Date(), status: 'sent' };
        state.openChatWindows = state.openChatWindows.map(c => c.id === chatId ? { ...c, messages: [...c.messages, newMessage] } : c);
        state.conversations = state.conversations.map(conv => conv.id === chatId ? { ...conv, lastMessage: message, time: 'Now', messages: [...conv.messages, newMessage] } : conv);
        state.messageInputs[chatId] = '';
        renderFloatingChats();
        simulateTypingAndResponse(chatId);
        setTimeout(() => autoScrollMessages(chatId), 100);
      }

      function simulateTypingAndResponse(chatId) {
        state.typingUsers[chatId] = true;
        renderFloatingChats();
        setTimeout(() => {
          state.typingUsers[chatId] = false;
          const responses = [
            "Thanks for your message! I'll get back to you soon.",
            "That sounds great! Let me check on that for you.",
            "I appreciate you reaching out. Let's discuss this further.",
            "Perfect! I'll have more details for you shortly.",
            "Got it! I'll look into this and respond soon."
          ];
          const randomResponse = responses[Math.floor(Math.random() * responses.length)];
          const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
          const responseMessage = { id: Date.now() + 1, text: randomResponse, sender: 'them', time, timestamp: new Date(), status: 'delivered' };
          state.openChatWindows = state.openChatWindows.map(c => c.id === chatId ? { ...c, messages: [...c.messages, responseMessage] } : c);
          state.conversations = state.conversations.map(conv => conv.id === chatId ? { ...conv, lastMessage: randomResponse, time: 'Now', messages: [...conv.messages, responseMessage] } : conv);
          renderFloatingChats();
          setTimeout(() => autoScrollMessages(chatId), 100);
        }, Math.random() * 2000 + 1500);
      }

      function autoScrollMessages(chatId) {
        const el = document.getElementById(`messages-${chatId}`);
        if (el) el.scrollTop = el.scrollHeight;
      }

      function renderFloatingChats() {
        const root = document.getElementById('floating-chats-root');
        if (!root) return;
        root.innerHTML = '';
        state.openChatWindows.forEach((chat, index) => {
          const container = document.createElement('div');
          container.className = `chat-window ${chat.isMinimized ? 'minimized' : ''}`;
          container.style.bottom = '0px';
          container.style.right = `${320 + (index * 280)}px`;
          container.innerHTML = `
            <div class="chat-window-header">
              <div class="chat-window-user">
                <img src="${chat.avatar}" alt="${chat.name}" class="chat-window-avatar" />
                <span class="chat-window-name">${chat.name}</span>
              </div>
              <div class="chat-window-controls">
                <i class="material-symbols-outlined chat-minimize">${chat.isMinimized ? 'expand_more' : 'expand_less'}</i>
                <i class="material-symbols-outlined chat-close">close</i>
              </div>
            </div>
            ${chat.isMinimized ? '' : `
            <div class="chat-window-messages" id="messages-${chat.id}">
              ${chat.messages.length === 0 ? `
                <div class="chat-empty-state">
                  <h4>👋 Hey there!</h4>
                  <p>Start a conversation with ${chat.name}</p>
                </div>
              ` : `
                ${chat.messages.map(m => `
                  <div class="chat-message ${m.sender === 'me' ? 'sent' : 'received'}">
                    <div class="message-content">${m.text}</div>
                    <div class="message-time">${m.time}</div>
                    ${m.sender === 'me' ? `<div class="message-status">${m.status === 'sent' ? '✓' : (m.status === 'delivered' ? '✓✓' : '')}</div>` : ''}
                  </div>
                `).join('')}
                ${state.typingUsers[chat.id] ? `
                  <div class="typing-indicator">
                    <img src="${chat.avatar}" alt="${chat.name}" class="typing-avatar" />
                    <div class="typing-bubble">
                      <div class="typing-dot"></div>
                      <div class="typing-dot"></div>
                      <div class="typing-dot"></div>
                    </div>
                  </div>
                ` : ''}
              `}
            </div>
            <div class="chat-window-input">
              <input type="text" placeholder="Message ${chat.name}..." value="${state.messageInputs[chat.id] || ''}" />
              <button ${!(state.messageInputs[chat.id] || '').trim() ? 'disabled' : ''}>
                <i class="material-symbols-outlined">send</i>
              </button>
            </div>`}
          `;
          container.querySelector('.chat-window-header').addEventListener('click', () => minimizeChatWindow(chat.id));
          container.querySelector('.chat-close').addEventListener('click', (e) => { e.stopPropagation(); closeChatWindow(chat.id); });
          if (!chat.isMinimized) {
            const input = container.querySelector('.chat-window-input input');
            const button = container.querySelector('.chat-window-input button');
            input.addEventListener('input', (e) => { state.messageInputs[chat.id] = e.target.value; });
            input.addEventListener('keypress', (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(chat.id, input.value); }});
            button.addEventListener('click', () => sendMessage(chat.id, input.value));
          }
          root.appendChild(container);
        });
      }

      // Bind quick open chat from active users
      $all('.active-user-popup').forEach(el => {
        el.addEventListener('click', () => {
          try {
            const user = JSON.parse(el.getAttribute('data-user'));
            openChatWindow({ ...user, messages: [] });
          } catch (_) {}
        });
      });

      renderChatList();
    })();
  </script>
HTML
;

$html = preg_replace('/<\/body\s*>/i', $injection . '</body>', $html, 1);
echo $html;
