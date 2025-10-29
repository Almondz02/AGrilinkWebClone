<?php
$sessionDir = __DIR__ . '/tmp/sessions';
if (!is_dir($sessionDir)) { mkdir($sessionDir, 0777, true); }
session_save_path($sessionDir);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);
session_start();
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf_token'];

$errors = [];
$success = false;

$allowedTypes = ['spam','inappropriate','fraud','harassment','technical','other'];
$allowedPriority = ['low','medium','high','urgent'];

$sticky = [
  'reportType' => '',
  'priority' => 'medium',
  'description' => '',
  'location' => '',
  'contactInfo' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $errors[] = 'Invalid request. Please refresh the page and try again.';
  }
  $sticky['reportType'] = isset($_POST['reportType']) ? (string)$_POST['reportType'] : '';
  $sticky['priority'] = isset($_POST['priority']) ? (string)$_POST['priority'] : 'medium';
  $sticky['description'] = isset($_POST['description']) ? trim((string)$_POST['description']) : '';
  $sticky['location'] = isset($_POST['location']) ? trim((string)$_POST['location']) : '';
  $sticky['contactInfo'] = isset($_POST['contactInfo']) ? trim((string)$_POST['contactInfo']) : '';

  if ($sticky['reportType'] === '' || !in_array($sticky['reportType'], $allowedTypes, true)) {
    $errors[] = 'Please select a valid report type.';
  }
  if (!in_array($sticky['priority'], $allowedPriority, true)) {
    $errors[] = 'Invalid priority value.';
  }
  if ($sticky['description'] === '') {
    $errors[] = 'Description is required.';
  } elseif (mb_strlen($sticky['description']) < 10) {
    $errors[] = 'Description must be at least 10 characters.';
  } elseif (mb_strlen($sticky['description']) > 2000) {
    $errors[] = 'Description must be 2000 characters or fewer.';
  }
  if ($sticky['location'] !== '' && mb_strlen($sticky['location']) > 200) {
    $errors[] = 'Location must be 200 characters or fewer.';
  }
  if ($sticky['contactInfo'] !== '' && mb_strlen($sticky['contactInfo']) > 150) {
    $errors[] = 'Contact information must be 150 characters or fewer.';
  }

  if (empty($errors)) {
    $success = true;
    // Rotate CSRF token on successful post
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_token'];
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AgriLink - Report</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif; }
    :root { --brand:#047857; --brand-light:#059669; --brand-dark:#065f46; --bg:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 50%,#f0f2f5 100%); --surface:#ffffff; --surface-2:#f8fafc; --surface-hover:#f1f5f9; --text:#0f172a; --text-muted:#64748b; --text-light:#94a3b8; --border:#e2e8f0; --border-light:#f1f5f9; --ring:rgba(4,120,87,0.12); --shadow-sm:0 1px 2px rgba(0,0,0,0.05); --shadow-md:0 4px 6px -1px rgba(0,0,0,0.1),0 2px 4px -1px rgba(0,0,0,0.06); --shadow-lg:0 10px 15px -3px rgba(0,0,0,0.1),0 4px 6px -2px rgba(0,0,0,0.05); --radius:12px; --radius-sm:8px; --sidebar-expanded:280px; --sidebar-collapsed:80px; --top-gap:0; }
    body { background: var(--bg); color: var(--text); line-height: 1.6; overflow-x: hidden; min-height: 100vh; padding-top: 0; }
    .header-container { position: fixed; top:0; left:0; right:0; height:60px; background:#FF9100; display:flex; align-items:center; padding:0 20px; z-index:100; box-shadow:0 2px 4px rgba(0,0,0,0.1); }
    .header-left { flex:1; display:flex; align-items:center; gap:20px; }
    .header-center { flex:2; display:flex; justify-content:center; align-items:center; }
    .header-right { flex:1; display:flex; justify-content:flex-end; align-items:center; }
    .logo { font-size:24px; font-weight:bold; color:#fff; text-decoration:none; }
    .search-container { display:flex; align-items:center; background:rgba(255,255,255,0.2); border-radius:12px; padding:8px 16px; gap:12px; }
    .search-container input { background:transparent; border:none; color:#fff; outline:none; width:250px; font-size:14px; }
    .search-container .material-symbols-outlined { color:#fff; font-size:24px; }
    .nav-icons { display:flex; gap:20px; }
    .nav-icons .icon { width:40px; height:40px; border-radius:50%; background:rgba(255,255,255,0.2); display:flex; justify-content:center; align-items:center; color:#fff; cursor:pointer; text-decoration:none; }

    .profile-container { position:fixed; left:0; top:0; bottom:0; width:var(--sidebar-expanded); background:#fff; padding:20px; z-index:90; box-shadow:1px 0 5px rgba(0,0,0,0.1); overflow-y:auto; transition: width .3s ease; display:flex; flex-direction:column; }
    .profile-header { display:flex; align-items:center; margin-bottom:20px; }
    .profile-pic { width:50px; height:50px; border-radius:50%; object-fit:cover; margin-right:10px; cursor:pointer; }
    .profile-name { font-weight:600; }
    .profile-menu { list-style:none; }
    .profile-menu li { padding:14px 16px; margin:8px 0; border-radius: var(--radius-sm); display:flex; align-items:center; cursor:pointer; font-size: 18px; }
    .profile-menu li:hover { background:#f0f2f5; }
    .profile-menu li i { margin-right:14px; color:#FF9100; font-size: 26px; }
    .bottom-menu { margin-top:auto; }
    .sidebar-brand { font-weight: 800; color: #FF9100; margin: 0 0 16px; font-size: 28px; background: transparent; padding: 0; border-radius: 0; display: block; text-align: center; }
    .sidebar-search { display: flex; align-items: center; gap: 10px; background: #f1f5f9; border: 1px solid var(--border); border-radius: 12px; padding: 10px 12px; margin: 6px 0 10px; }
    .sidebar-search .material-symbols-outlined { color: #64748b; font-size: 22px; }
    .sidebar-search input { flex: 1; border: none; outline: none; background: transparent; font-size: 16px; color: #0f172a; }
    .sidebar-search input::placeholder { color: #94a3b8; }
    /* Menu trigger + popover (match homemain) */
    .profile-menu-trigger { margin-top: auto; display: inline-flex; align-items: center; gap: 8px; border: 1px solid var(--border); background: #fff; color: #111827; padding: 10px 14px; border-radius: 10px; cursor: pointer; box-shadow: var(--shadow-sm); }
    .profile-menu-trigger .material-symbols-outlined { color: #0f172a; }
    .profile-menu-popover { position: fixed; width: 260px; background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); border: 1px solid #e5e7eb; padding: 10px 0; display: none; z-index: 120; max-height: calc(100vh - 16px); overflow-y: auto; }
    .profile-menu-popover.visible { display: block; }
    .profile-menu-popover .menu-item { display: flex; align-items: center; gap: 12px; padding: 10px 16px; color: #0f172a; cursor: pointer; }
    .profile-menu-popover .menu-item:hover { background: #f8fafc; }
    .profile-menu-popover .menu-item .material-symbols-outlined { color: #475569; }
    .profile-menu-popover .menu-divider { height: 1px; background: #f1f5f9; margin: 6px 0; }
    .profile-menu-popover .logout-action { margin: 8px 12px 4px; padding: 10px 14px; background: #ef4444; color: #fff; border-radius: 10px; display: flex; align-items: center; gap: 10px; font-weight: 600; justify-content: center; }
    .profile-container .profile-name, .profile-container .profile-menu li span, .profile-container .logout-btn span { display: inline; }

    .main-content { margin: 0px auto 24px calc(var(--sidebar-expanded) + 96px); padding-top: 24px; transition: margin-left .3s ease; max-width: 820px; width: 100%; }
    .report-container { max-width: 100%; margin: 0 auto; }
    .report-header { background:#fff; border-radius:12px; box-shadow:var(--shadow-md); padding:20px 24px; margin-bottom:16px; }
    .report-header h1 { margin:0; color:#0f172a; }
    .report-header p { margin-top:4px; color:#64748b; }

    .report-form-container { background:#fff; border-radius:12px; box-shadow:var(--shadow-md); padding:20px 24px; margin-bottom:16px; }
    .report-form .form-group { margin-bottom:16px; }
    .report-form label { display:block; margin-bottom:6px; font-weight:600; color:#0f172a; }
    .report-form input, .report-form select, .report-form textarea { width:100%; padding:10px 12px; border:1px solid #e2e8f0; border-radius:8px; outline:none; }
    .report-form textarea { resize:vertical; }
    .submit-btn { background:#047857; color:#fff; border:none; border-radius:8px; padding:10px 16px; cursor:pointer; font-weight:600; }
    .submit-btn:hover { background:#065f46; }

    .report-info { background:#fff; border-radius:12px; box-shadow:var(--shadow-md); padding:20px 24px; }
    .report-info h3 { margin:0 0 8px 0; }
    .report-info ul { padding-left: 18px; color:#334155; }

    .notification-toast { position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); background: #047857; color: white; padding: 10px 16px; border-radius: 8px; box-shadow: var(--shadow-md); z-index: 150; display:none; }
    .alert-error { background: #fff1f2; color: #991b1b; border: 1px solid #fecaca; padding: 12px 14px; border-radius: 8px; margin-bottom: 12px; }
    .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 12px 14px; border-radius: 8px; margin-bottom: 12px; }

    /* ===== FLOATING ACTION BUTTONS ===== */
    .fab-notif { position: fixed; top: 24px; right: 24px; width: 76px; height: 76px; border-radius: 9999px; background: #ffffff; border: 1px solid #e5e7eb; box-shadow: 0 14px 34px rgba(0,0,0,0.18); display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 1000; }
    .fab-notif .material-symbols-outlined { color: #111827; font-size: 34px; }
    .fab-notif:hover { background: #f8fafc; }
    .fab-chat { position: fixed; bottom: 24px; right: 24px; border-radius: 9999px; background: #ffffff; border: 1px solid #e5e7eb; box-shadow: 0 16px 36px rgba(0,0,0,0.18); display: inline-flex; align-items: center; gap: 16px; padding: 18px 32px; cursor: pointer; z-index: 120; }
    .fab-chat .material-symbols-outlined { color: #111827; font-size: 30px; }
    .fab-chat .fab-chat-label { color: #111827; font-weight: 800; font-size: 17px; }
    .fab-chat:hover { background: #f8fafc; }

    /* ===== NOTIFICATION POPUP ===== */
    .notification-container { position: fixed; top: var(--top-gap); right: 0; width: 460px; background-color: #fff; border-radius: 16px; box-shadow: 0 18px 40px rgba(0,0,0,0.18); z-index: 130; display: none; border: 1px solid #e5e7eb; overflow: hidden; }
    .notification-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #e5e7eb; background: #fff; }
    .notification-header h3 { margin: 0; font-size: 20px; color: #111827; font-weight: 700; }
    #close-notification { cursor: pointer; color: #999; }
    #close-notification:hover { color: #333; }
    .notification-list { max-height: 540px; overflow-y: auto; padding: 24px; background: #fff; }
    .notification-empty { display: flex; align-items: center; justify-content: center; color: #6b7280; font-size: 15px; padding: 40px 0; }
    .notification-container.visible { display: block; }
    .notification-list::-webkit-scrollbar { width: 6px; }
    .notification-list::-webkit-scrollbar-track { background: #f1f1f1; }
    .notification-list::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 3px; }
    .notification-list::-webkit-scrollbar-thumb:hover { background: #a1a1a1; }

    /* ===== CHAT POPUP ===== */
    .header-chat-container { position: fixed; top: var(--top-gap); right: 0; width: 460px; background-color: white; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.12); z-index: 100; display: none; transition: all 0.3s ease; max-height: 540px; border: 1px solid #e4e6ea; }
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

    /* ===== FLOATING CHAT WINDOWS ===== */
    .chat-window { position: fixed; width: 280px; height: 420px; background: white; border-radius: 12px 12px 0 0; box-shadow: 0 -4px 25px rgba(0,0,0,0.2); z-index: 1000; border: 1px solid #e4e6ea; border-bottom: none; transition: all 0.3s ease; }
    .chat-window.minimized { height: 42px; }
    .chat-window-header { background: linear-gradient(135deg, #047857 0%, #059669 100%); color: white; padding: 14px 18px; border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center; cursor: pointer; box-shadow: 0 2px 8px rgba(4,120,87,0.3); }
    .chat-window-user { display: flex; align-items: center; gap: 10px; }
    .chat-window-avatar { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.3); }
    .chat-window-name { font-weight: 600; font-size: 15px; }
    .chat-window-status { font-size: 12px; opacity: 0.8; margin-top: 2px; }
    .chat-window-controls { display: flex; gap: 6px; }
    .chat-minimize, .chat-close { font-size: 20px; cursor: pointer; padding: 4px; border-radius: 50%; transition: all 0.2s; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; }
    .chat-minimize:hover, .chat-close:hover { background-color: rgba(255,255,255,0.2); transform: scale(1.1); }
    .chat-window-messages { height: 320px; overflow-y: auto; padding: 16px; background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%); scroll-behavior: smooth; }
    .chat-empty-state { text-align: center; color: #65676b; padding: 60px 20px; }
    .chat-empty-state h4 { margin: 0 0 8px 0; color: #047857; font-size: 16px; }
    .chat-empty-state p { margin: 0; font-size: 14px; }
    .chat-message { margin-bottom: 16px; animation: messageSlideIn 0.3s ease-out; }
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

    @keyframes messageSlideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes typingAnimation { 0%, 60%, 100% { transform: translateY(0); } 30% { transform: translateY(-10px); } }

    @media (max-width: 992px) { .main-content { margin-left: 0; margin-right: 0; } .profile-container { transform: translateX(-100%); transition: transform .3s; width: var(--sidebar-expanded); } .profile-container.active { transform: translateX(0); } }
  </style>
</head>
<body>
  

  <!-- Sidebar -->
  <div class="profile-container">
    <div class="sidebar-brand">Agrilink</div>
    <ul class="profile-menu">
      <li data-href="homemain.php"><i class="material-symbols-outlined">home</i><span>Home</span></li>
    </ul>
    <div class="sidebar-search">
      <span class="material-symbols-outlined">search</span>
      <input type="text" id="sidebar-search-input" placeholder="Search" />
    </div>
    <ul class="profile-menu">
      <li data-href="listing.php"><i class="material-symbols-outlined">storefront</i><span>Listings</span></li>
      <li data-href="historyandtransaction.php"><i class="material-symbols-outlined">receipt_long</i><span>Listing History</span></li>
      <li data-href="profile.php"><i class="material-symbols-outlined">person</i><span>Profile</span></li>
    </ul>
    <!-- Bottom popup menu trigger -->
    <button class="profile-menu-trigger" id="profile-menu-trigger" type="button">
      <span class="material-symbols-outlined">menu</span>
      <span>Menu</span>
    </button>
  </div>
  <!-- Popover moved outside sidebar -->
  <div class="profile-menu-popover" id="profile-menu-popover">
    <div class="menu-item" data-href="#change-role"><span class="material-symbols-outlined">manage_accounts</span><span>Change Role</span></div>
    <div class="menu-item" data-href="settings.php"><span class="material-symbols-outlined">settings</span><span>Settings</span></div>
    <div class="menu-item" data-href="report.php"><span class="material-symbols-outlined">analytics</span><span>My Report</span></div>
    <div class="menu-item" data-href="#switch-appearance"><span class="material-symbols-outlined">dark_mode</span><span>Switch Appearance</span></div>
    <div class="menu-divider"></div>
    <div class="logout-action" id="logout-action"><span class="material-symbols-outlined">logout</span><span>Logout</span></div>
  </div>

  <!-- Main content -->
  <div class="main-content">
    <div class="report-container">
      <header class="report-header">
        <h1>Submit a Report</h1>
        <p>Help us improve AgriLink by reporting issues, violations, or providing feedback</p>
      </header>

      <div id="toast" class="notification-toast"></div>

      <div class="report-form-container">
        <?php if (!empty($errors)): ?>
          <div class="alert-error">
            <ul style="padding-left:18px;">
              <?php foreach ($errors as $e): ?>
                <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php elseif ($success): ?>
          <div class="alert-success">Report submitted successfully! We will review it within 24-48 hours. (Demo)</div>
        <?php endif; ?>

        <form id="report-form" class="report-form" method="post" action="report.php" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>" />
          <div class="form-group">
            <label for="reportType">Report Type *</label>
            <select id="reportType" name="reportType" required>
              <option value="">Select report type</option>
              <option value="spam" <?php echo $sticky['reportType']==='spam'?'selected':''; ?>>Spam Content</option>
              <option value="inappropriate" <?php echo $sticky['reportType']==='inappropriate'?'selected':''; ?>>Inappropriate Content</option>
              <option value="fraud" <?php echo $sticky['reportType']==='fraud'?'selected':''; ?>>Fraudulent Activity</option>
              <option value="harassment" <?php echo $sticky['reportType']==='harassment'?'selected':''; ?>>Harassment</option>
              <option value="technical" <?php echo $sticky['reportType']==='technical'?'selected':''; ?>>Technical Issue</option>
              <option value="other" <?php echo $sticky['reportType']==='other'?'selected':''; ?>>Other</option>
            </select>
          </div>

          <div class="form-group">
            <label for="priority">Priority Level</label>
            <select id="priority" name="priority">
              <option value="low" <?php echo $sticky['priority']==='low'?'selected':''; ?>>Low</option>
              <option value="medium" <?php echo $sticky['priority']==='medium'?'selected':''; ?>>Medium</option>
              <option value="high" <?php echo $sticky['priority']==='high'?'selected':''; ?>>High</option>
              <option value="urgent" <?php echo $sticky['priority']==='urgent'?'selected':''; ?>>Urgent</option>
            </select>
          </div>

          <div class="form-group">
            <label for="description">Description *</label>
            <textarea id="description" name="description" rows="6" placeholder="Please provide detailed information about the issue..." required><?php echo htmlspecialchars($sticky['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
          </div>

          <div class="form-group">
            <label for="location">Location (Optional)</label>
            <input type="text" id="location" name="location" placeholder="Where did this occur? (e.g., specific post, user profile, etc.)" value="<?php echo htmlspecialchars($sticky['location'], ENT_QUOTES, 'UTF-8'); ?>" />
          </div>

          <div class="form-group">
            <label for="contactInfo">Contact Information (Optional)</label>
            <input type="text" id="contactInfo" name="contactInfo" placeholder="Email or phone number for follow-up" value="<?php echo htmlspecialchars($sticky['contactInfo'], ENT_QUOTES, 'UTF-8'); ?>" />
          </div>

          <button type="submit" class="submit-btn">Submit Report</button>
        </form>
      </div>

      <div class="report-info">
        <h3>Reporting Guidelines</h3>
        <ul>
          <li>Provide as much detail as possible to help us understand the issue</li>
          <li>Include screenshots or links if relevant</li>
          <li>Reports are reviewed within 24-48 hours</li>
          <li>False reports may result in account restrictions</li>
          <li>For urgent safety concerns, contact local authorities first</li>
        </ul>
      </div>
    </div>
  </div>
  
  <!-- Floating buttons: match homemain.php triggers -->
  <button class="fab-notif" id="notification-icon" type="button" aria-label="Notifications">
    <span class="material-symbols-outlined">notifications</span>
  </button>
  <button class="fab-chat" id="chat-icon" type="button" aria-label="Messages">
    <span class="material-symbols-outlined">forum</span>
    <span class="fab-chat-label">Messages</span>
  </button>

  <!-- Notification popup -->
  <div class="notification-container" id="notification-container">
    <div class="notification-header"><h3>Notifications</h3><i class="material-symbols-outlined" id="close-notification">close</i></div>
    <div class="notification-list">
      <div class="notification-empty">No notifications yet</div>
    </div>
  </div>

  <!-- Chat popup -->
  <div class="header-chat-container" id="header-chat-container">
    <div class="chat-header-popup">
      <h3>Chats</h3>
      <i class="material-symbols-outlined" id="close-chat">close</i>
    </div>
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

  <div id="floating-chats-root"></div>

  <script>
    function $(sel, root=document){ return root.querySelector(sel); }
    function $all(sel, root=document){ return Array.from(root.querySelectorAll(sel)); }
    function showToast(msg){ const t = $('#toast'); if(!t) return; t.textContent = msg; t.style.display='block'; setTimeout(()=>{ t.style.display='none'; }, 4000); }

    // Sidebar nav
    $('#profile-link')?.addEventListener('click', ()=>{ window.location.href = 'profile.php'; });
    $all('.profile-menu li').forEach(li=> li.addEventListener('click', ()=>{ const href=li.getAttribute('data-href'); if(href) window.location.href = href; }));
    $('#logout-btn')?.addEventListener('click', ()=>{ window.location.href = 'homemain.php'; });

    // Sidebar bottom menu popover (match homemain)
    (function(){
      const trigger = document.getElementById('profile-menu-trigger');
      const pop = document.getElementById('profile-menu-popover');
      const logout = document.getElementById('logout-action');
      if (!trigger || !pop) return;
      function position(){
        const r = trigger.getBoundingClientRect();
        const gap = 16; const topOffset = -6; let left = r.right + gap; let top = Math.max(8, r.top + topOffset);
        const pc = document.querySelector('.profile-container'); if (pc) { const cr = pc.getBoundingClientRect(); left = Math.max(left, cr.right + 8); }
        const prevDisplay = pop.style.display; if (!pop.classList.contains('visible')) { pop.style.visibility='hidden'; pop.style.display='block'; }
        const pw = pop.offsetWidth || 260; const ph = pop.offsetHeight || 200; if (!pop.classList.contains('visible')) { pop.style.display = prevDisplay; pop.style.visibility=''; }
        const margin = 8; if (left + pw > window.innerWidth - margin) left = Math.max(margin, r.left - gap - pw);
        top = Math.max(margin, Math.min(top, window.innerHeight - ph - margin));
        pop.style.top = Math.round(top)+'px'; pop.style.left = Math.round(left)+'px';
      }
      function toggle(){ position(); pop.classList.toggle('visible'); }
      function hide(){ pop.classList.remove('visible'); }
      trigger.addEventListener('click', (e)=>{ e.stopPropagation(); toggle(); });
      document.addEventListener('click', (e)=>{ if (pop.classList.contains('visible') && !pop.contains(e.target) && e.target !== trigger) hide(); });
      document.addEventListener('keydown', (e)=>{ if (e.key==='Escape') hide(); });
      pop.querySelectorAll('.menu-item').forEach(it=> it.addEventListener('click', ()=>{ const href=it.getAttribute('data-href'); if(href && href.startsWith('#')) { hide(); return; } if(href){ window.location.href = href; } }));
      logout && logout.addEventListener('click', ()=>{ window.location.href = 'homemain.php'; });
      window.addEventListener('resize', ()=>{ if (pop.classList.contains('visible')) position(); });
      window.addEventListener('scroll', ()=>{ if (pop.classList.contains('visible')) position(); }, { passive:true });
    })();

    // If server signaled success, also show a toast for UX
    <?php if ($success): ?>
    showToast('Report submitted successfully!');
    <?php endif; ?>

    // ===== DATA & STATE (for chat) =====
    const state = { conversations: [], openChatWindows: [], typingUsers: {}, messageInputs: {} };

    // ===== CHAT LIST RENDER =====
    function renderChatList() {
      const list = document.getElementById('chat-list-popup');
      if (!list) return;
      list.innerHTML = '';
      state.conversations.forEach(conversation => {
        const item = document.createElement('div');
        item.className = 'chat-item-popup';
        item.innerHTML = `
          <img src="${conversation.avatar}" alt="${conversation.name}" />
          <div class="chat-info-popup">
            <div class="chat-name-popup">${conversation.name}</div>
            <div class="chat-preview-popup">${conversation.lastMessage||''}</div>
          </div>
          <div class="chat-time-popup">${conversation.time||''}</div>
        `;
        item.addEventListener('click', () => openChatWindow(conversation));
        list.appendChild(item);
      });
    }

    // Quick open chat from active users
    (function(){
      const chatCont = document.getElementById('header-chat-container');
      Array.from(document.querySelectorAll('.active-user-popup')).forEach(el => {
        el.addEventListener('click', () => {
          const user = JSON.parse(el.getAttribute('data-user'));
          openChatWindow({ ...user, messages: [] });
          chatCont && chatCont.classList.remove('visible');
        });
      });
    })();

    // ===== FLOATING CHAT WINDOWS =====
    function openChatWindow(user) {
      if (state.openChatWindows.find(c => c.id === user.id)) return;
      const existingConversation = state.conversations.find(c => c.id === user.id);
      const newChat = { id: user.id, name: user.name, avatar: user.avatar, messages: existingConversation?.messages || user.messages || [], isMinimized: false };
      if (!existingConversation) {
        state.conversations = [ { id: user.id, name: user.name, avatar: user.avatar, lastMessage: 'Start a conversation...', time: 'Now', messages: [] }, ...state.conversations ];
        renderChatList();
      }
      state.openChatWindows.push(newChat);
      renderFloatingChats();
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

    // ===== HEADER POPUPS (Notification + Chat) =====
    (function(){
      const notifIcon = $('#notification-icon');
      const notifCont = $('#notification-container');
      const chatIcon = $('#chat-icon');
      const chatCont = $('#header-chat-container');
      if (!notifIcon || !notifCont || !chatIcon || !chatCont) return;

      function positionNear(el, cont){
        const r = el.getBoundingClientRect();
        const margin = 10;
        const prevDisplay = cont.style.display;
        const prevVisibility = cont.style.visibility;
        if (!cont.classList.contains('visible')) { cont.style.visibility = 'hidden'; cont.style.display = 'block'; }
        const cw = cont.offsetWidth || 400;
        if (!cont.classList.contains('visible')) { cont.style.display = prevDisplay; cont.style.visibility = prevVisibility; }
        const top = r.bottom + 8;
        const centerLeft = (window.innerWidth - cw) / 2;
        const bias = 780;
        const left = Math.max(margin, Math.min(window.innerWidth - cw - margin, centerLeft + bias));
        cont.style.top = Math.round(top) + 'px';
        cont.style.left = Math.round(left) + 'px';
        cont.style.right = 'auto';
      }

      function hideAll(){
        notifCont.classList.remove('visible');
        chatCont.classList.remove('visible');
      }

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
      closeN && closeN.addEventListener('click', ()=> notifCont.classList.remove('visible'));
      closeC && closeC.addEventListener('click', ()=> chatCont.classList.remove('visible'));

      document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape') hideAll(); });
    })();

    // Initial render
    renderChatList();
  </script>
</body>
</html>
