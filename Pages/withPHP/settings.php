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
];
$html = str_replace(array_keys($replacements), array_values($replacements), $html);

// Inject Notification and Chat containers + standardized header handlers before </body>
$injection = <<<'HTML'
  
  <!-- Injected: Notification & Chat containers for Settings page -->
  <div class="notification-container" id="notification-container" style="position:fixed; top:0; right:0; display:none">
    <div class="notification-header" style="display:flex;justify-content:space-between;align-items:center;padding:15px;border-bottom:1px solid #eee;">
      <h3 style="margin:0;font-size:18px;color:#333">Notifications</h3>
      <i class="material-symbols-outlined" id="close-notification" style="cursor:pointer;color:#999">close</i>
    </div>
    <div class="notification-list" style="max-height:400px;overflow-y:auto">
      <div class="notification-item" style="padding:15px;border-bottom:1px solid #eee;cursor:pointer">
        <div class="notification-content">
          <div class="notification-text"><strong>System</strong> Settings page loaded.</div>
          <div class="notification-time">Just now</div>
        </div>
      </div>
    </div>
  </div>

  <div class="header-chat-container" id="header-chat-container" style="position:fixed; top:0; right:0; display:none; width:320px; background:#fff; border-radius:12px; box-shadow:0 8px 30px rgba(0,0,0,0.12); max-height:450px; border:1px solid #e4e6ea;">
    <div class="chat-header-popup" style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid #e4e6ea;background:#f8f9fa;border-radius:12px 12px 0 0;">
      <h3 style="margin:0;font-size:18px;color:#1c1e21;font-weight:700">Chats</h3>
      <i class="material-symbols-outlined" id="close-chat" style="cursor:pointer;color:#8a8d91;font-size:20px;padding:4px;border-radius:50%">close</i>
    </div>
    <div class="active-users-popup" style="padding:16px 20px 12px;border-bottom:1px solid #e4e6ea;">
      <div class="active-title-popup" style="font-weight:600;font-size:14px;color:#047857;margin-bottom:12px;">Active Now</div>
    </div>
    <div class="conversations-title-popup" style="font-weight:600;font-size:14px;color:#047857;padding:12px 20px 8px;">Conversations</div>
    <div class="chat-list-popup" id="chat-list-popup" style="max-height:280px; overflow-y:auto;"></div>
  </div>

  <script>
    (function(){
      function $(sel, root){ return (root||document).querySelector(sel); }
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
      function hideAll(){ notifCont.classList.remove('visible'); notifCont.style.display='none'; chatCont.classList.remove('visible'); chatCont.style.display='none'; }

      notifIcon.addEventListener('click', (e)=>{
        positionNear(notifIcon, notifCont);
        chatCont.classList.remove('visible'); chatCont.style.display='none';
        const show = !notifCont.classList.contains('visible');
        notifCont.classList.toggle('visible', show);
        notifCont.style.display = show ? 'block' : 'none';
        e.stopPropagation();
      });
      chatIcon.addEventListener('click', (e)=>{
        positionNear(chatIcon, chatCont);
        notifCont.classList.remove('visible'); notifCont.style.display='none';
        const show = !chatCont.classList.contains('visible');
        chatCont.classList.toggle('visible', show);
        chatCont.style.display = show ? 'block' : 'none';
        e.stopPropagation();
      });
      document.addEventListener('click', (e)=>{
        if (notifCont.classList.contains('visible') && !notifCont.contains(e.target) && e.target !== notifIcon) { notifCont.classList.remove('visible'); notifCont.style.display='none'; }
        if (chatCont.classList.contains('visible') && !chatCont.contains(e.target) && e.target !== chatIcon) { chatCont.classList.remove('visible'); chatCont.style.display='none'; }
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
    })();
  </script>
HTML;

$html = preg_replace('/<\/body\s*>/i', $injection . '</body>', $html, 1);
echo $html;
