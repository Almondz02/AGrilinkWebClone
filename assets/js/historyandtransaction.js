// Minimal script to render some demo transactions and wire basic controls
(function(){
  const $ = (s, r=document)=> r.querySelector(s);
  const $$ = (s, r=document)=> Array.from(r.querySelectorAll(s));

  // Demo transactions data
  const transactions = [
    { id: 1, type: 'sale', status: 'completed', date: '2024-10-01', title: 'Sold 50kg Manure', amount: 2500 },
    { id: 2, type: 'purchase', status: 'pending', date: '2024-10-03', title: 'Bought 30kg Compost', amount: 1800 },
    { id: 3, type: 'delivery', status: 'completed', date: '2024-10-05', title: 'Delivered to Barangay 5', amount: 0 },
  ];

  function peso(n){ return '₱' + Number(n||0).toLocaleString(undefined, { minimumFractionDigits: 0 }); }

  function renderStats(list){
    const total = list.length;
    const earned = list.filter(t=>t.type==='sale').reduce((s,t)=> s + (t.amount||0), 0);
    const spent = list.filter(t=>t.type==='purchase').reduce((s,t)=> s + (t.amount||0), 0);
    const successRate = total ? Math.round((list.filter(t=>t.status==='completed').length / total) * 100) : 0;
    const byId = id => document.getElementById(id);
    const W = (id, val) => { const el = byId(id); if(el) el.textContent = val; };
    W('stat-total', String(total));
    W('stat-earned', peso(earned));
    W('stat-spent', peso(spent));
    W('stat-success', successRate + '%');
  }

  function renderTransactions(list){
    const root = $('#transactions-container');
    const empty = $('#transactions-empty');
    if(!root || !empty) return;
    if(list.length === 0){ root.innerHTML=''; empty.style.display='block'; return; }
    empty.style.display='none';
    root.innerHTML = list.map(t => `
      <div class="transaction-item" style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #eef2f7;">
        <div>
          <div style="font-weight:700;">${t.title}</div>
          <div style="font-size:12px; color:#64748b;">${t.date} • ${t.type} • ${t.status}</div>
        </div>
        <div style="font-weight:700; color:#047857;">${t.amount? peso(t.amount): ''}</div>
      </div>
    `).join('');
  }

  function applyFilters(){
    const type = $('#filter-type')?.value || 'all';
    const status = $('#filter-status')?.value || 'all';
    const from = $('#date-from')?.value;
    const to = $('#date-to')?.value;
    let list = [...transactions];
    if(type !== 'all') list = list.filter(t => ({sales:'sale', purchases:'purchase', exchanges:'exchange', deliveries:'delivery'}[type]) ? t.type === ({sales:'sale', purchases:'purchase', exchanges:'exchange', deliveries:'delivery'}[type]) : true);
    if(status !== 'all') list = list.filter(t => t.status === status);
    if(from) list = list.filter(t => t.date >= from);
    if(to) list = list.filter(t => t.date <= to);
    renderStats(list);
    renderTransactions(list);
  }

  // Wire controls
  ['filter-type','filter-status','date-from','date-to'].forEach(id => {
    const el = document.getElementById(id);
    if(el) el.addEventListener('change', applyFilters);
  });

  // Populate right sidebar listings placeholder
  (function renderListings(){
    const el = document.getElementById('listings-list');
    if(!el) return;
    const samples = [
      { title: 'Cattle Manure', detail: '₱50.00 • 500kg' },
      { title: 'Organic Compost', detail: '₱75.00 • 300kg' },
      { title: 'Goat Manure', detail: '₱60.00 • 200kg' },
    ];
    el.innerHTML = samples.map(s => `
      <div style="border:1px solid #e5e7eb; border-radius:10px; padding:10px;">
        <div style="font-weight:700;">${s.title}</div>
        <div style="font-size:12px; color:#64748b;">${s.detail}</div>
      </div>
    `).join('');
  })();

  // Initial render
  applyFilters();

  // ===== Header Notifications (aligned with homemain.html) =====
  const notificationIcon = $('#notification-icon');
  const notificationContainer = $('#notification-container');
  const closeNotif = $('#close-notification');
  if (notificationIcon && notificationContainer) {
    notificationIcon.addEventListener('click', (e) => {
      const rect = notificationIcon.getBoundingClientRect();
      notificationContainer.style.top = (rect.bottom + 5) + 'px';
      notificationContainer.style.right = (window.innerWidth - rect.right - 190) + 'px';
      notificationContainer.classList.toggle('visible');
      e.stopPropagation();
    });
    document.addEventListener('click', (e) => {
      if (notificationContainer.classList.contains('visible') && !notificationContainer.contains(e.target) && e.target !== notificationIcon) {
        notificationContainer.classList.remove('visible');
      }
    });
    if (closeNotif) closeNotif.addEventListener('click', () => notificationContainer.classList.remove('visible'));
  }

  // ===== Header Chat (aligned with homemain.html) =====
  const chatIcon = $('#chat-icon');
  const chatContainer = $('#header-chat-container');
  const closeChat = $('#close-chat');

  const initialConversations = [
    { id: 'mang-jose', name: 'Mang Jose', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/70ed3f6c-a0ca-4490-8b6a-eaa245dffa33.png', lastMessage: 'Hi, about the manure delivery tomorrow...', time: '2:30 PM', messages: [ { id: 1, text: 'Hi, about the manure delivery tomorrow...', sender: 'them', time: '2:30 PM' }, { id: 2, text: 'What time should I expect the delivery?', sender: 'them', time: '2:31 PM' } ] },
    { id: 'farmers-coop', name: 'Farmers Cooperative', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/250bc031-e5eb-4467-9d71-49154b992c13.png', lastMessage: 'New bulk order of organic fertilizer available', time: 'Yesterday', messages: [ { id: 1, text: 'New bulk order of organic fertilizer available', sender: 'them', time: 'Yesterday' }, { id: 2, text: 'Would you be interested in placing an order?', sender: 'them', time: 'Yesterday' } ] }
  ];

  const chatState = {
    conversations: [...initialConversations],
    openChatWindows: [],
    typingUsers: {},
    messageInputs: {},
  };

  function renderActiveUsers(){
    const host = $('#active-users');
    if(!host) return;
    const users = [
      { id:'ana-gonzales', name:'Ana Gonzales', avatar:'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/ee4b5487-b1ea-40b6-9a76-0415e304de49.png' },
      { id:'carlos-reyes', name:'Carlos Reyes', avatar:'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/331059ac-f6bf-4684-b902-d37824bad8f5.png' },
      { id:'lorna-lim', name:'Lorna Lim', avatar:'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/2ab478de-5d6d-4dce-8c67-baf47fd7ad8e.png' },
    ];
    host.innerHTML = users.map(u => `
      <div class="active-user-popup" data-user='${JSON.stringify(u)}'>
        <div class="user-status-popup">
          <img src="${u.avatar}" alt="${u.name}" />
          <span class="status-indicator-popup"></span>
        </div>
        <span class="name-popup">${u.name.split(' ')[0]}</span>
      </div>
    `).join('');
    $$('.active-user-popup', host).forEach(el => {
      el.addEventListener('click', () => {
        const user = JSON.parse(el.getAttribute('data-user'));
        openChatWindow({ ...user, messages: [] });
        if (chatContainer) chatContainer.classList.remove('visible');
      });
    });
  }

  function renderChatList(){
    const list = $('#chat-list-popup');
    if(!list) return;
    list.innerHTML = '';
    chatState.conversations.forEach(c => {
      const item = document.createElement('div');
      item.className = 'chat-item-popup';
      item.innerHTML = `
        <img src="${c.avatar}" alt="${c.name}" />
        <div class="chat-info-popup">
          <div class="chat-name-popup">${c.name}</div>
          <div class="chat-preview-popup">${c.lastMessage}</div>
        </div>
        <div class="chat-time-popup">${c.time}</div>
      `;
      item.addEventListener('click', () => openChatWindow(c));
      list.appendChild(item);
    });
  }

  function openChatWindow(user){
    if (chatState.openChatWindows.find(c => c.id === user.id)) return;
    const existing = chatState.conversations.find(c => c.id === user.id);
    const newChat = { id:user.id, name:user.name, avatar:user.avatar, messages: existing?.messages || user.messages || [], isMinimized:false };
    if (!existing) {
      chatState.conversations = [ { id:user.id, name:user.name, avatar:user.avatar, lastMessage:'Start a conversation...', time:'Now', messages:[] }, ...chatState.conversations ];
      renderChatList();
    }
    chatState.openChatWindows.push(newChat);
    renderFloatingChats();
  }

  function closeChatWindow(chatId){
    chatState.openChatWindows = chatState.openChatWindows.filter(c => c.id !== chatId);
    renderFloatingChats();
  }
  function minimizeChatWindow(chatId){
    chatState.openChatWindows = chatState.openChatWindows.map(c => c.id === chatId ? { ...c, isMinimized: !c.isMinimized } : c);
    renderFloatingChats();
  }
  function autoScrollMessages(chatId){
    const el = document.getElementById(`messages-${chatId}`);
    if (el) el.scrollTop = el.scrollHeight;
  }
  function sendMessage(chatId, message){
    if(!message || !message.trim()) return;
    const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const m = { id: Date.now(), text: message, sender:'me', time, timestamp:new Date(), status:'sent' };
    chatState.openChatWindows = chatState.openChatWindows.map(c => c.id === chatId ? { ...c, messages:[...c.messages, m] } : c);
    chatState.conversations = chatState.conversations.map(conv => conv.id === chatId ? { ...conv, lastMessage: message, time:'Now', messages:[...conv.messages, m] } : conv);
    chatState.messageInputs[chatId] = '';
    renderFloatingChats();
    simulateTypingAndResponse(chatId);
    setTimeout(() => autoScrollMessages(chatId), 100);
  }
  function simulateTypingAndResponse(chatId){
    chatState.typingUsers[chatId] = true;
    renderFloatingChats();
    setTimeout(() => {
      chatState.typingUsers[chatId] = false;
      const responses = [
        "Thanks for your message! I'll get back to you soon.",
        "That sounds great! Let me check on that for you.",
        "I appreciate you reaching out. Let's discuss this further.",
        "Perfect! I'll have more details for you shortly.",
        "Got it! I'll look into this and respond soon."
      ];
      const r = responses[Math.floor(Math.random() * responses.length)];
      const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      const m = { id: Date.now()+1, text: r, sender:'them', time, timestamp:new Date(), status:'delivered' };
      chatState.openChatWindows = chatState.openChatWindows.map(c => c.id === chatId ? { ...c, messages:[...c.messages, m] } : c);
      chatState.conversations = chatState.conversations.map(conv => conv.id === chatId ? { ...conv, lastMessage: r, time:'Now', messages:[...conv.messages, m] } : conv);
      renderFloatingChats();
      setTimeout(() => autoScrollMessages(chatId), 100);
    }, Math.random()*2000 + 1500);
  }

  function renderFloatingChats(){
    const root = document.getElementById('floating-chats-root');
    if(!root) return;
    root.innerHTML = '';
    chatState.openChatWindows.forEach((chat, index) => {
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
            ${chatState.typingUsers[chat.id] ? `
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
          <input type="text" placeholder="Message ${chat.name}..." value="${chatState.messageInputs[chat.id] || ''}" />
          <button ${!(chatState.messageInputs[chat.id] || '').trim() ? 'disabled' : ''}>
            <i class="material-symbols-outlined">send</i>
          </button>
        </div>`}
      `;
      container.querySelector('.chat-window-header').addEventListener('click', () => minimizeChatWindow(chat.id));
      container.querySelector('.chat-close').addEventListener('click', (e) => { e.stopPropagation(); closeChatWindow(chat.id); });
      if (!chat.isMinimized) {
        const input = container.querySelector('.chat-window-input input');
        const button = container.querySelector('.chat-window-input button');
        input.addEventListener('input', (e) => { chatState.messageInputs[chat.id] = e.target.value; });
        input.addEventListener('keypress', (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(chat.id, input.value); } });
        button.addEventListener('click', () => sendMessage(chat.id, input.value));
      }
      root.appendChild(container);
    });
  }

  // Toggle chat container and outside click
  if (chatIcon && chatContainer) {
    chatIcon.addEventListener('click', (e) => {
      const rect = chatIcon.getBoundingClientRect();
      chatContainer.style.top = (rect.bottom + 5) + 'px';
      chatContainer.style.right = (window.innerWidth - rect.right - 190) + 'px';
      chatContainer.classList.toggle('visible');
      if (notificationContainer) notificationContainer.classList.remove('visible');
      e.stopPropagation();
    });
    document.addEventListener('click', (e) => {
      if (chatContainer.classList.contains('visible') && !chatContainer.contains(e.target) && e.target !== chatIcon) {
        chatContainer.classList.remove('visible');
      }
    });
    if (closeChat) closeChat.addEventListener('click', () => chatContainer.classList.remove('visible'));
  }

  // Initial chat UI render
  renderActiveUsers();
  renderChatList();

  // ===== Profile sidebar navigation (aligned with homemain.html) =====
  const profileLink = $('#profile-link');
  if (profileLink) profileLink.addEventListener('click', () => { window.location.href = 'profile.html'; });
  $$('.profile-menu li').forEach(li => li.addEventListener('click', () => {
    const href = li.getAttribute('data-href');
    if (href) window.location.href = href;
  }));
  const logoutBtn = $('#logout-btn');
  if (logoutBtn) logoutBtn.addEventListener('click', () => { window.location.href = 'homemain.html'; });
})();
