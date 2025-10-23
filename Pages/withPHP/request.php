<?php
// No server-side form on this page; converted to PHP for consistency and future extensibility.
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AgriLink - Livestock Waste Requests (Enhanced)</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif; }
    :root { --brand:#047857; --brand-light:#059669; --brand-dark:#065f46; --bg:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 50%,#f0f2f5 100%); --surface:#ffffff; --surface-2:#f8fafc; --surface-hover:#f1f5f9; --text:#0f172a; --text-muted:#64748b; --text-light:#94a3b8; --border:#e2e8f0; --border-light:#f1f5f9; --ring:rgba(4,120,87,.12); --shadow-sm:0 1px 2px rgba(0,0,0,.05); --shadow-md:0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -1px rgba(0,0,0,.06); --shadow-lg:0 10px 15px -3px rgba(0,0,0,0.1),0 4px 6px -2px rgba(0,0,0,0.05); --radius:12px; --radius-sm:8px; --sidebar-expanded:280px; --sidebar-collapsed:80px; }
    body { background: var(--bg); color: var(--text); line-height: 1.6; overflow-x: hidden; min-height: 100vh; padding-top: 60px; }

    .header-container { position: fixed; top: 0; left: 0; right: 0; height: 60px; background-color: #FF9100; display: flex; align-items: center; padding: 0 20px; z-index: 100; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .header-left { flex: 1; display: flex; align-items: center; gap: 20px; }
    .header-center { flex: 2; display: flex; justify-content: center; align-items: center; }
    .header-right { flex: 1; display: flex; justify-content: flex-end; align-items: center; }
    .logo { font-size: 24px; font-weight: bold; color: white; text-decoration:none; }
    .search-container { display: flex; align-items: center; background-color: rgba(255,255,255,0.2); border-radius: 12px; padding: 8px 16px; gap: 12px; transition: all 0.2s ease; }
    .search-container:hover { background-color: rgba(255,255,255,0.25); }
    .search-container input { background: transparent; border: none; color: white; outline: none; width: 250px; font-size: 14px; }
    .search-container input::placeholder { color: rgba(255,255,255,0.7); }
    .search-container .material-symbols-outlined { color: white; font-size: 24px; transition: transform 0.2s ease; }
    .search-container:hover .material-symbols-outlined { transform: scale(1.1); }
    .nav-icons { display: flex; gap: 20px; }
    .nav-icons .icon { width: 40px; height: 40px; border-radius: 50%; background-color: rgba(255,255,255,0.2); display: flex; justify-content: center; align-items: center; color: white; cursor: pointer; transition: background-color 0.3s; text-decoration: none; }
    .nav-icons .icon:hover { background-color: rgba(255,255,255,0.3); }

    .profile-container { position: fixed; left: 0; top: 60px; bottom: 0; width: var(--sidebar-collapsed); background-color: var(--surface); padding: 20px; z-index: 90; box-shadow: var(--shadow-sm); overflow: hidden; transition: width .3s ease; display:flex; flex-direction:column; }
    .profile-container:hover { width: var(--sidebar-expanded); }
    .profile-container:hover { overflow-y: auto; }
    .profile-header { display: flex; align-items: center; margin-bottom: 20px; }
    .profile-pic { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; margin-right: 10px; cursor: pointer; }
    .profile-name { font-weight: 600; }
    .profile-menu { list-style: none; }
    .profile-menu li { padding: 10px; margin: 5px 0; border-radius: var(--radius-sm); display: flex; align-items: center; cursor: pointer; }
    .profile-menu li:hover { background-color: var(--surface-hover); }
    .profile-menu li i { margin-right: 0; color: #FF9100; }
    .profile-container:hover .profile-menu li i { margin-right: 10px; }
    .logout-btn { margin-top: auto; margin-bottom: 16px; padding: 10px 15px; background-color: #ef4444; color: var(--surface); border: none; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: flex-start; gap: 8px; cursor: pointer; width: 100%; }
    .logout-btn i { margin-right: 0; line-height: 1; display: inline-flex; align-items: center; }
    .logout-btn span { line-height: 1; display: inline-flex; align-items: center; }
    .profile-container .profile-name, .profile-container .profile-menu li span, .profile-container .logout-btn span { display: none; }
    .profile-container:hover .profile-name, .profile-container:hover .profile-menu li span, .profile-container:hover .logout-btn span { display: inline; }

    .main-container { display: flex; min-height: 100vh; }
    .main-content { flex: 1; margin-left: calc(var(--sidebar-collapsed) + 20px); padding: 24px; transition: margin-left .3s ease; }
    .profile-container:hover ~ .main-container .main-content { margin-left: calc(var(--sidebar-expanded) + 20px); }

    .page-header { background: var(--surface); border-radius: var(--radius); padding: 32px; margin-bottom: 32px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-light); position: relative; overflow: hidden; }
    .page-header::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #047857 0%, #10b981 50%, #34d399 100%); }
    .header-content { display: flex; align-items: center; gap: 24px; margin-bottom: 24px; }
    .header-icon-wrapper { position: relative; display: flex; align-items: center; justify-content: center; }
    .header-icon { width: 80px; height: 80px; background: linear-gradient(135deg, #047857 0%, #10b981 100%); border-radius: 20px; display: flex; align-items: center; justify-content: center; color: white; font-size: 36px; box-shadow: 0 8px 25px rgba(4,120,87,0.3); position: relative; z-index: 2; }
    .icon-glow { position:absolute; width:100px; height:100px; background: radial-gradient(circle, rgba(4,120,87,0.2) 0%, transparent 70%); border-radius:50%; animation: pulse 2s infinite; }
    @keyframes pulse { 0%,100%{ transform: scale(1); opacity:.7 } 50%{ transform: scale(1.1); opacity:.3 } }
    .header-text h1 { font-size: 28px; font-weight: 700; color: #111827; margin: 0 0 8px 0; }
    .header-text p { font-size: 15px; color: #6b7280; margin: 0; }

    .requests-overview { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 16px; }
    .overview-card { background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); border-radius: 16px; padding: 20px; display:flex; align-items:center; gap:14px; border:2px solid #047857; box-shadow: 0 4px 20px rgba(0,0,0,0.05); position:relative; overflow:hidden; transition: all .3s; }
    .overview-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background: linear-gradient(90deg, #047857 0%, #10b981 100%); transform: scaleX(0); transition: transform .3s; }
    .overview-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,0.1); }
    .overview-card:hover::before { transform: scaleX(1); }
    .card-icon { width:48px; height:48px; background: linear-gradient(135deg, #047857 0%, #10b981 100%); border-radius: 12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:24px; box-shadow: 0 4px 15px rgba(4,120,87,.3); }
    .card-number { font-size: 22px; font-weight: 700; color: #111827; line-height: 1; }
    .card-label { font-size: 13px; color:#6b7280; font-weight: 500; }

    .requests-section { background:#fff; border-radius:20px; padding: 24px; box-shadow: 0 4px 25px rgba(0,0,0,.08); margin-top: 24px; border:1px solid rgba(229,231,235,.8); }
    .section-header { display:flex; justify-content: space-between; align-items:center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid #f3f4f6; }
    .section-title-wrapper { display:flex; align-items:center; gap: 10px; }
    .section-title-wrapper h2 { font-size: 22px; font-weight: 700; color:#111827; margin:0; }
    .requests-count { background: linear-gradient(135deg, #047857 0%, #10b981 100%); color:#fff; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; box-shadow: 0 2px 8px rgba(4,120,87,.3); }
    .section-filters { display:flex; gap:8px; }
    .filter-btn { display:flex; align-items:center; gap:8px; padding:10px 16px; border:2px solid #e5e7eb; background:#fff; border-radius:12px; font-size:14px; font-weight:500; color:#6b7280; cursor:pointer; transition: all .2s; }
    .filter-btn:hover { border-color:#047857; color:#047857; background:#f0fdf4; }

    .requests-grid { display:grid; gap:20px; grid-template-columns: 1fr 1fr; }
    .request-card { background:#fff; border:2px solid #f3f4f6; border-radius:20px; overflow:hidden; transition: all .3s; position:relative; }
    .request-card::before { content:''; position:absolute; top:0; left:0; right:0; height:4px; background: linear-gradient(90deg, #047857 0%, #10b981 50%, #34d399 100%); transform: scaleX(0); transition: transform .3s; }
    .request-card:hover { border-color:#047857; box-shadow: 0 12px 40px rgba(0,0,0,.1); transform: translateY(-4px); }
    .request-card:hover::before { transform: scaleX(1); }
    .card-header { padding: 20px 20px 0 20px; display:flex; justify-content: space-between; align-items: flex-start; }
    .requester-profile { display:flex; align-items:center; gap: 14px; }
    .avatar-wrapper { position:relative; }
    .requester-avatar { width:56px; height:56px; border-radius:16px; object-fit:cover; border:3px solid #f3f4f6; }
    .status-indicator { position:absolute; bottom:-2px; right:-2px; width:16px; height:16px; border-radius:50%; border:3px solid #fff; background:#10b981; }
    .requester-name { font-size:18px; font-weight:700; color:#111827; }
    .request-time { display:flex; align-items:center; gap:6px; font-size:14px; color:#6b7280; }
    .priority-badge { padding:6px 12px; border-radius:20px; font-size:12px; font-weight:600; text-transform:uppercase; color:#fff; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); box-shadow: 0 2px 8px rgba(239,68,68,.3); }
    .card-body { padding: 16px 20px; }
    .waste-type-badge { display:inline-flex; align-items:center; gap:8px; background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); color:#166534; padding:8px 16px; border-radius:20px; font-size:14px; font-weight:600; margin-bottom:12px; border:1px solid #a7f3d0; }
    .request-description { color:#374151; line-height:1.6; margin-bottom: 16px; font-size: 15px; background:#f8fafc; padding: 12px; border-radius: 12px; border-left: 4px solid #047857; }
    .spec-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(150px,1fr)); gap: 12px; }
    .spec-item { display:flex; align-items:center; gap: 10px; padding: 10px; background:#f9fafb; border-radius: 12px; border:1px solid #e5e7eb; }
    .spec-icon { width: 32px; height:32px; background: linear-gradient(135deg, #047857 0%, #10b981 100%); border-radius:10px; display:flex; align-items:center; justify-content:center; color:#fff; }
    .spec-details { display:flex; flex-direction:column; }
    .spec-label { font-size:12px; color:#6b7280; text-transform: uppercase; letter-spacing:.5px; }
    .spec-value { font-weight:600; color:#111827; }
    .card-footer { padding: 0 20px 20px 20px; border-top: 1px solid #f3f4f6; margin-top: 12px; padding-top: 12px; }
    .request-actions { display:flex; gap: 10px; flex-wrap: wrap; }
    .btn { display:inline-flex; align-items:center; gap:8px; padding: 10px 16px; border-radius: 12px; font-size:14px; font-weight:600; cursor:pointer; border:2px solid transparent; text-decoration:none; position:relative; overflow:hidden; transition: all .2s; }
    .btn::before { content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,.2), transparent); transition: left .5s; }
    .btn:hover::before { left: 100%; }
    .btn-primary { background: linear-gradient(135deg, #047857 0%, #10b981 100%); color:#fff; box-shadow: 0 4px 15px rgba(4,120,87,.3); }
    .btn-primary:hover { background: linear-gradient(135deg, #065f46 0%, #059669 100%); }
    .btn-outline { background:#fff; color:#6b7280; border-color:#e5e7eb; }

    .notification-toast { position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); background: #047857; color: white; padding: 10px 16px; border-radius: 8px; box-shadow: var(--shadow-md); z-index: 150; display:none; }

    /* ===== NOTIFICATION POPUP (match homemain/listing) ===== */
    .notification-container { position: fixed; width: 350px; background-color: #fff; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 100; display: none; transition: opacity 0.2s ease; }
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

    /* ===== HEADER CHAT POPUP (match homemain/listing) ===== */
    .header-chat-container { position: fixed; width: 320px; background-color: #fff; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.12); z-index: 100; display: none; transition: all 0.3s ease; max-height: 450px; border: 1px solid #e4e6ea; }
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
    .conversations-title-popup { font-weight: 600; font-size: 14px; color: #047857; padding: 12px 20px 8px; }
    .chat-list-popup { max-height: 280px; overflow-y: auto; }
    .chat-item-popup { display: flex; align-items: center; padding: 12px 20px; cursor: pointer; transition: background-color 0.2s; position: relative; }
    .chat-item-popup:hover { background-color: #f2f3f5; }
    .chat-item-popup img { width: 44px; height: 44px; border-radius: 50%; margin-right: 12px; object-fit: cover; }
    .chat-info-popup { flex: 1; min-width: 0; }
    .chat-name-popup { font-weight: 600; font-size: 14px; margin-bottom: 2px; color: #1c1e21; }
    .chat-preview-popup { font-size: 13px; color: #65676b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .chat-time-popup { font-size: 12px; color: #8a8d91; position: absolute; top: 12px; right: 20px; }
    .chat-list-popup::-webkit-scrollbar { width: 6px; }
    .chat-list-popup::-webkit-scrollbar-track { background: transparent; }
    .chat-list-popup::-webkit-scrollbar-thumb { background: #bcc0c4; border-radius: 3px; }
    .chat-list-popup::-webkit-scrollbar-thumb:hover { background: #8a8d91; }

    @media (max-width: 992px) { .profile-container { transform: translateX(-100%); transition: transform .3s; width: var(--sidebar-expanded); } .profile-container.active { transform: translateX(0); } .main-content { margin-left: 0; } .requests-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <!-- Header -->
  <div class="header-container">
    <div class="header-left">
      <a href="homemain.php" class="logo">AgriLink</a>
      <div class="search-container">
        <span class="material-symbols-outlined">search</span>
        <input type="text" placeholder="Search Agri" />
      </div>
    </div>
    <div class="header-center">
      <div class="nav-icons">
        <a href="homemain.php" class="icon"><span class="material-symbols-outlined">home</span></a>
        <a href="listing.php" class="icon"><span class="material-symbols-outlined">storefront</span></a>
        <div class="icon" id="chat-icon"><span class="material-symbols-outlined">forum</span></div>
        <div class="icon" id="notification-icon"><span class="material-symbols-outlined">notifications</span></div>
      </div>
    </div>
    <div class="header-right"></div>
  </div>

  <!-- Sidebar -->
  <div class="profile-container">
    <div class="profile-header">
      <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/0ec0940b-23d9-4b1f-8e72-2a8bd35584e4.png" alt="Farmer" class="profile-pic" id="profile-link" />
      <div class="profile-name">Juan Dela Cruz</div>
    </div>
    <ul class="profile-menu">
      <li data-href="request.php"><i class="material-symbols-outlined">request_quote</i><span>Request</span></li>
      <li data-href="historyandtransaction.php"><i class="material-symbols-outlined">receipt_long</i><span>History and Transactions</span></li>
      <li data-href="settings.php"><i class="material-symbols-outlined">privacy_tip</i><span>Settings and Privacy</span></li>
      <li data-href="report.php"><i class="material-symbols-outlined">analytics</i><span>Reports</span></li>
    </ul>
    <button class="logout-btn" id="logout-btn"><i class="material-symbols-outlined">logout</i><span>Logout</span></button>
  </div>

  <div class="main-container">
    <div class="main-content">
      <div class="page-header">
        <div class="header-content">
          <div class="header-icon-wrapper">
            <div class="header-icon"><span class="material-symbols-outlined">request_quote</span></div>
            <div class="icon-glow"></div>
          </div>
          <div class="header-text">
            <h1>Livestock Waste Requests</h1>
            <p>View and manage requests from farmers and buyers looking for livestock waste products.</p>
          </div>
        </div>
        <div class="requests-overview" id="overview-cards"></div>
      </div>

      <div class="requests-section">
        <div class="section-header">
          <div class="section-title-wrapper">
            <h2>Active Requests</h2>
            <span class="requests-count" id="requests-count">0</span>
          </div>
          <div class="section-filters">
            <div class="search-container" style="background:#fff; border:1px solid #e5e7eb; color:#0f172a;">
              <span class="material-symbols-outlined" style="color:#64748b;">search</span>
              <input id="search" type="text" placeholder="Search requests..." style="color:#0f172a;" />
            </div>
          </div>
        </div>
        <div class="requests-grid" id="requests-grid"></div>
      </div>
    </div>
  </div>

  <div id="toast" class="notification-toast"></div>

  <div class="notification-container" id="notification-container" style="top:0; right:0;">
    <div class="notification-header"><h3>Notifications</h3><i class="material-symbols-outlined" id="close-notification">close</i></div>
    <div class="notification-list">
      <div class="notification-item"><div class="notification-content"><div class="notification-text"><strong>Maria Santos</strong> commented on your post: "This looks great! Would love to buy some."</div><div class="notification-time">2 hrs ago</div></div></div>
      <div class="notification-item"><div class="notification-content"><div class="notification-text"><strong>Pedro Bautista</strong> liked your post about organic fertilizer.</div><div class="notification-time">5 hrs ago</div></div></div>
      <div class="notification-item"><div class="notification-content"><div class="notification-text"><strong>AgriTech PH</strong> shared a new article: "Benefits of Organic Waste in Farming"</div><div class="notification-time">1 day ago</div></div></div>
      <div class="notification-item"><div class="notification-content"><div class="notification-text"><strong>Farmers Cooperative</strong> added a new listing for compost materials.</div><div class="notification-time">2 days ago</div></div></div>
    </div>
  </div>

  <div class="header-chat-container" id="header-chat-container" style="top:0; right:0;">
    <div class="chat-header-popup"><h3>Chats</h3><i class="material-symbols-outlined" id="close-chat">close</i></div>
    <div class="active-users-popup">
      <div class="active-title-popup">Active Now</div>
      <div class="active-list-popup">
        <div class="active-user-popup" data-user='{"id":"ana-gonzales","name":"Ana Gonzales","avatar":"https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/ee4b5487-b1ea-40b6-9a76-0415e304de49.png"}'>
          <div class="user-status-popup"><img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/ee4b5487-b1ea-40b6-9a76-0415e304de49.png" alt="Ana Gonzales" /><span class="status-indicator-popup"></span></div>
          <span class="name-popup">Ana</span>
        </div>
        <div class="active-user-popup" data-user='{"id":"carlos-reyes","name":"Carlos Reyes","avatar":"https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/331059ac-f6bf-4684-b902-d37824bad8f5.png"}'>
          <div class="user-status-popup"><img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/331059ac-f6bf-4684-b902-d37824bad8f5.png" alt="Carlos Reyes" /><span class="status-indicator-popup"></span></div>
          <span class="name-popup">Carlos</span>
        </div>
        <div class="active-user-popup" data-user='{"id":"lorna-lim","name":"Lorna Lim","avatar":"https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/2ab478de-5d6d-4dce-8c67-baf47fd7ad8e.png"}'>
          <div class="user-status-popup"><img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/2ab478de-5d6d-4dce-8c67-baf47fd7ad8e.png" alt="Lorna Lim" /><span class="status-indicator-popup"></span></div>
          <span class="name-popup">Lorna</span>
        </div>
      </div>
    </div>
    <div class="conversations-title-popup">Conversations</div>
    <div class="chat-list-popup" id="chat-list-popup"></div>
  </div>

  <div id="floating-chats-root"></div>

  <script>
    // Helpers
    function $(sel, root=document){ return root.querySelector(sel); }
    function $all(sel, root=document){ return Array.from(root.querySelectorAll(sel)); }
    function showToast(msg){ const t = $('#toast'); t.textContent = msg; t.style.display='block'; setTimeout(()=>{ t.style.display='none'; }, 3000); }

    // Sidebar and nav
    $('#profile-link')?.addEventListener('click', ()=>{ window.location.href = 'profile.php'; });
    $all('.profile-menu li').forEach(li=> li.addEventListener('click', ()=>{ const href=li.getAttribute('data-href'); if(href) window.location.href = href; }));
    $('#logout-btn')?.addEventListener('click', ()=>{ window.location.href = 'homemain.php'; });

    // State
    const state = {
      searchTerm: '',
      requests: [
        { id: 'maria-santos', name: 'Maria Santos', time: '2 hours ago', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/e9739672-d0ca-45ac-857b-5ee49303f563.png', wasteType: 'Chicken Manure', description: 'Looking for fresh chicken manure for my vegetable garden. Need about 100kg for organic farming. Can pick up within Nueva Ecija area.', specs: { weight: '100kg', location: 'Nueva Ecija', timeframe: 'Within 1 week' } },
        { id: 'pedro-reyes', name: 'Pedro Reyes', time: '5 hours ago', avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&h=100&fit=crop&crop=face', wasteType: 'Pig Manure', description: 'Need pig manure for my rice field. Looking for well-composted material. Can transport myself if needed.', specs: { weight: '200kg', location: 'Bulacan', timeframe: 'Within 2 weeks' } },
        { id: 'ana-garcia', name: 'Ana Garcia', time: '1 day ago', avatar: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=100&h=100&fit=crop&crop=face', wasteType: 'Cow Manure', description: 'Seeking cow manure for my flower farm. Need organic material for soil enrichment. Prefer fresh but can handle aged manure.', specs: { weight: '150kg', location: 'Laguna', timeframe: 'Within 3 days' } },
        { id: 'carlos-mendoza', name: 'Carlos Mendoza', time: '2 days ago', avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=100&h=100&fit=crop&crop=face', wasteType: 'Mixed Livestock Waste', description: 'Looking for mixed livestock waste for composting project. Need various types for research on organic fertilizer production.', specs: { weight: '300kg', location: 'Metro Manila', timeframe: 'Within 1 month' } }
      ],
      conversations: [
        { id: 'mang-jose', name: 'Mang Jose', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/70ed3f6c-a0ca-4490-8b6a-eaa245dffa33.png', lastMessage: 'Hi, about the manure delivery tomorrow...', time: '2:30 PM', messages: [ { id: 1, text: 'Hi, about the manure delivery tomorrow...', sender: 'them', time: '2:30 PM' }, { id: 2, text: 'What time should I expect the delivery?', sender: 'them', time: '2:31 PM' } ] },
        { id: 'farmers-coop', name: 'Farmers Cooperative', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/250bc031-e5eb-4467-9d71-49154b992c13.png', lastMessage: 'New bulk order of organic fertilizer available', time: 'Yesterday', messages: [ { id: 1, text: 'New bulk order of organic fertilizer available', sender: 'them', time: 'Yesterday' }, { id: 2, text: 'Would you be interested in placing an order?', sender: 'them', time: 'Yesterday' } ] }
      ],
      openChatWindows: [],
      typingUsers: {},
      messageInputs: {},
    };

    function renderOverview(){
      const el = $('#overview-cards');
      const total = state.requests.length;
      const urgent = 1;
      const today = state.requests.filter(r=> r.time.includes('hour')).length;
      el.innerHTML = ''+
        `<div class="overview-card"><div class="card-icon"><span class="material-symbols-outlined">list_alt</span></div><div><div class="card-number">${total}</div><div class="card-label">Total Requests</div></div></div>`+
        `<div class="overview-card"><div class="card-icon"><span class="material-symbols-outlined">priority_high</span></div><div><div class="card-number">${urgent}</div><div class="card-label">High Priority</div></div></div>`+
        `<div class="overview-card"><div class="card-icon"><span class="material-symbols-outlined">today</span></div><div><div class="card-number">${today}</div><div class="card-label">Requested Today</div></div></div>`;
    }

    function filtered(){ const s=(state.searchTerm||'').toLowerCase(); return state.requests.filter(r=> r.name.toLowerCase().includes(s) || r.wasteType.toLowerCase().includes(s) || r.description.toLowerCase().includes(s)); }

    function acceptRequest(id){ state.requests = state.requests.filter(r=> r.id!==id); showToast('Request accepted successfully!'); renderAll(); }
    function cancelRequest(id){ state.requests = state.requests.filter(r=> r.id!==id); showToast('Request cancelled successfully!'); renderAll(); }

    function renderRequests(){
      const grid = $('#requests-grid');
      const list = filtered();
      $('#requests-count').textContent = list.length;
      grid.innerHTML = list.map(r=>`
        <div class="request-card">
          <div class="card-header">
            <div class="requester-profile">
              <div class="avatar-wrapper">
                <img class="requester-avatar" src="${r.avatar}" alt="${r.name}" />
                <span class="status-indicator online"></span>
              </div>
              <div>
                <div class="requester-name">${r.name}</div>
                <div class="request-time"><span class="material-symbols-outlined">schedule</span>${r.time}</div>
              </div>
            </div>
            <div class="priority-badge">High</div>
          </div>
          <div class="card-body">
            <div class="waste-type-badge"><span class="material-symbols-outlined">compost</span>${r.wasteType}</div>
            <div class="request-description">${r.description}</div>
            <div class="spec-grid">
              <div class="spec-item"><div class="spec-icon"><span class="material-symbols-outlined">scale</span></div><div class="spec-details"><div class="spec-label">Weight</div><div class="spec-value">${r.specs.weight}</div></div></div>
              <div class="spec-item"><div class="spec-icon"><span class="material-symbols-outlined">location_on</span></div><div class="spec-details"><div class="spec-label">Location</div><div class="spec-value">${r.specs.location}</div></div></div>
              <div class="spec-item"><div class="spec-icon"><span class="material-symbols-outlined">schedule</span></div><div class="spec-details"><div class="spec-label">Timeframe</div><div class="spec-value">${r.specs.timeframe}</div></div></div>
            </div>
          </div>
          <div class="card-footer">
            <div class="request-actions">
              <button class="btn btn-primary" data-action="accept" data-id="${r.id}"><span class="material-symbols-outlined">check</span>Accept</button>
              <button class="btn btn-outline" data-action="cancel" data-id="${r.id}"><span class="material-symbols-outlined">close</span>Cancel</button>
              <button class="btn btn-outline" data-action="chat" data-id="${r.id}"><span class="material-symbols-outlined">chat</span>Chat</button>
            </div>
          </div>
        </div>
      `).join('');

      $all('[data-action="accept"]').forEach(b=> b.addEventListener('click', ()=> acceptRequest(b.getAttribute('data-id'))));
      $all('[data-action="cancel"]').forEach(b=> b.addEventListener('click', ()=> cancelRequest(b.getAttribute('data-id'))));
      $all('[data-action="chat"]').forEach(b=> b.addEventListener('click', ()=>{
        const r = state.requests.find(x=>x.id===b.getAttribute('data-id'));
        if(!r) return;
        openChatWindow({ id: r.id, name: r.name, avatar: r.avatar, messages: [] });
      }));
    }

    $('#search')?.addEventListener('input', (e)=>{ state.searchTerm = e.target.value; renderRequests(); renderOverview(); });

    // Header popups (standardized)
    (function(){
      const notifIcon = $('#notification-icon');
      const notifCont = $('#notification-container');
      const chatIcon = $('#chat-icon');
      const chatCont = $('#header-chat-container');
      if (!notifIcon || !notifCont || !chatIcon || !chatCont) return;

      function positionNear(el, cont){
        const r = el.getBoundingClientRect();
        cont.style.top = (r.bottom + 5) + 'px';
        cont.style.right = Math.max(10, window.innerWidth - r.right - 190) + 'px';
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
      $('#close-notification')?.addEventListener('click', ()=> notifCont.classList.remove('visible'));
      $('#close-chat')?.addEventListener('click', ()=> chatCont.classList.remove('visible'));
      document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape') hideAll(); });
    })();

    // Floating chats
    function renderChatList(){ const list = $('#chat-list-popup'); if(!list) return; list.innerHTML=''; state.conversations.forEach(c=>{ const item=document.createElement('div'); item.className='chat-item-popup'; item.innerHTML = `<img src="${c.avatar}" alt="${c.name}" /><div class="chat-info-popup"><div class="chat-name-popup">${c.name}</div><div class="chat-preview-popup">${c.lastMessage}</div></div><div class="chat-time-popup">${c.time}</div>`; item.addEventListener('click', ()=> openChatWindow(c)); list.appendChild(item); }); }
    renderChatList();

    function openChatWindow(user){ if (state.openChatWindows.find(c=>c.id===user.id)) return; const existing = state.conversations.find(c=>c.id===user.id); const newChat = { id:user.id, name:user.name, avatar:user.avatar, messages: existing?.messages || user.messages || [], isMinimized:false }; if(!existing){ state.conversations=[{ id:user.id, name:user.name, avatar:user.avatar, lastMessage:'Start a conversation...', time:'Now', messages:[] }, ...state.conversations]; renderChatList(); } state.openChatWindows.push(newChat); renderFloatingChats(); }
    function closeChatWindow(chatId){ state.openChatWindows = state.openChatWindows.filter(c=>c.id!==chatId); renderFloatingChats(); }
    function minimizeChatWindow(chatId){ state.openChatWindows = state.openChatWindows.map(c=> c.id===chatId ? { ...c, isMinimized: !c.isMinimized } : c); renderFloatingChats(); }
    function sendMessage(chatId, message){ if(!message||!message.trim()) return; const time=new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}); const m={id:Date.now(), text:message, sender:'me', time, timestamp:new Date(), status:'sent'}; state.openChatWindows=state.openChatWindows.map(c=>c.id===chatId?{...c,messages:[...c.messages,m]}:c); state.conversations=state.conversations.map(conv=>conv.id===chatId?{...conv,lastMessage:message,time:'Now',messages:[...conv.messages,m]}:conv); state.messageInputs[chatId]=''; renderFloatingChats(); setTimeout(()=>autoScrollMessages(chatId),100); }

    function simulateTypingAndResponse(chatId){ state.typingUsers[chatId]=true; renderFloatingChats(); setTimeout(()=>{ state.typingUsers[chatId]=false; const responses=["Thanks for your message! I'll get back to you soon.","That sounds great! Let me check on that for you.","I appreciate you reaching out. Let's discuss this further.","Perfect! I'll have more details for you shortly.","Got it! I'll look into this and respond soon."]; const r=responses[Math.floor(Math.random()*responses.length)]; const time=new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}); const m={id:Date.now()+1, text:r, sender:'them', time, timestamp:new Date(), status:'delivered'}; state.openChatWindows=state.openChatWindows.map(c=>c.id===chatId?{...c,messages:[...c.messages,m]}:c); state.conversations=state.conversations.map(conv=>conv.id===chatId?{...conv,lastMessage:r,time:'Now',messages:[...conv.messages,m]}:conv); renderFloatingChats(); setTimeout(()=>autoScrollMessages(chatId),100); }, Math.random()*2000+1500); }

    function autoScrollMessages(chatId){ const el=document.getElementById(`messages-${chatId}`); if(el) el.scrollTop=el.scrollHeight; }

    function renderFloatingChats(){ const root=document.getElementById('floating-chats-root'); if(!root) return; root.innerHTML=''; state.openChatWindows.forEach((chat,idx)=>{ const c=document.createElement('div'); c.className=`chat-window ${chat.isMinimized?'minimized':''}`; c.style.bottom='0px'; c.style.right = `${20 + idx*300}px`; c.innerHTML = `<div class=\"chat-window-header\"><div class=\"chat-window-user\"><img src=\"${chat.avatar}\" alt=\"${chat.name}\" class=\"chat-window-avatar\" /><span class=\"chat-window-name\">${chat.name}</span></div><div class=\"chat-window-controls\"><i class=\"material-symbols-outlined chat-minimize\">${chat.isMinimized?'expand_more':'expand_less'}</i><i class=\"material-symbols-outlined chat-close\">close</i></div></div>${chat.isMinimized?'':`<div class=\"chat-window-messages\" id=\"messages-${chat.id}\">${chat.messages.length===0?`<div class=\"chat-empty-state\"><h4>👋 Hey there!</h4><p>Start a conversation with ${chat.name}</p></div>`:chat.messages.map(m=>`<div class=\"chat-message ${m.sender==='me'?'sent':'received'}\"><div class=\"message-content\">${m.text}</div><div class=\"message-time\">${m.time}</div>${m.sender==='me'?`<div class=\"message-status\">${m.status==='sent'?'✓':(m.status==='delivered'?'✓✓':'')}</div>`:''}</div>`).join('')}</div><div class=\"chat-window-input\"><input type=\"text\" placeholder=\"Message ${chat.name}...\" value=\"${state.messageInputs[chat.id]||''}\" /><button ${!(state.messageInputs[chat.id]||'').trim()?'disabled':''}><i class=\"material-symbols-outlined\">send</i></button></div>`}`; c.querySelector('.chat-window-header').addEventListener('click', ()=> minimizeChatWindow(chat.id)); c.querySelector('.chat-close').addEventListener('click', (e)=>{ e.stopPropagation(); closeChatWindow(chat.id); }); if(!chat.isMinimized){ const input=c.querySelector('.chat-window-input input'); const btn=c.querySelector('.chat-window-input button'); input.addEventListener('input',(e)=>{ state.messageInputs[chat.id]=e.target.value; btn.disabled=!e.target.value.trim(); }); btn.addEventListener('click',(e)=>{ e.stopPropagation(); sendMessage(chat.id, input.value); state.messageInputs[chat.id]=''; renderFloatingChats(); simulateTypingAndResponse(chat.id); }); input.addEventListener('keydown',(e)=>{ if(e.key==='Enter'){ sendMessage(chat.id, input.value); state.messageInputs[chat.id]=''; renderFloatingChats(); simulateTypingAndResponse(chat.id); } }); setTimeout(()=>autoScrollMessages(chat.id), 50); }
      root.appendChild(c); }); }

    function renderAll(){ renderRequests(); renderOverview(); }

    renderAll();
  </script>
</body>
</html>
