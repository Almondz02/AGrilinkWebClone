<?php
session_start();
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf_token'];

$errors = [];
$success = false;

// Sticky initial values (could be loaded from DB in real app)
$sticky = [
  'name' => 'Juan Dela Cruz',
  'role' => 'Crop Farmer',
  'bio' => 'Organic farmer specializing in rice and vegetable production. Passionate about sustainable agriculture and waste management.',
  'location' => 'Cabanatuan City, Nueva Ecija'
];

$allowedRoles = ['Crop Farmer','Livestock Waste Owner'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF
  if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $errors[] = 'Invalid request. Please refresh the page and try again.';
  }

  // Collect
  $sticky['name'] = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
  $sticky['role'] = isset($_POST['role']) ? (string)$_POST['role'] : '';
  $sticky['bio'] = isset($_POST['bio']) ? trim((string)$_POST['bio']) : '';
  $sticky['location'] = isset($_POST['location']) ? trim((string)$_POST['location']) : '';

  // Validate
  if ($sticky['name'] === '' || mb_strlen($sticky['name']) > 100) {
    $errors[] = 'Name is required and must be 100 characters or fewer.';
  }
  if (!in_array($sticky['role'], $allowedRoles, true)) {
    $errors[] = 'Please select a valid role.';
  }
  if ($sticky['bio'] !== '' && mb_strlen($sticky['bio']) > 500) {
    $errors[] = 'Bio must be 500 characters or fewer.';
  }
  if ($sticky['location'] !== '' && mb_strlen($sticky['location']) > 150) {
    $errors[] = 'Location must be 150 characters or fewer.';
  }

  // Optional profile photo upload
  $hasFile = isset($_FILES['profile_photo']) && is_array($_FILES['profile_photo']) && (int)$_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE;
  if ($hasFile) {
    if ((int)$_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
      $errors[] = 'There was an error uploading the profile image.';
    } else {
      $maxBytes = 5 * 1024 * 1024; // 5MB
      if ((int)$_FILES['profile_photo']['size'] > $maxBytes) {
        $errors[] = 'Profile image must be 5MB or smaller.';
      }
      $tmp = $_FILES['profile_photo']['tmp_name'];
      $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
      $mime = $finfo ? finfo_file($finfo, $tmp) : (mime_content_type($tmp) ?: null);
      if ($finfo) { finfo_close($finfo); }
      $allowed = ['image/jpeg','image/png','image/webp'];
      if (!$mime || !in_array($mime, $allowed, true)) {
        $errors[] = 'Only JPEG, PNG, or WEBP images are allowed for profile photo.';
      }
    }
  }

  if (empty($errors)) {
    $success = true;
    // Rotate CSRF
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_token'];
    // Demo: not persisting to storage
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AgriLink - Profile</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif; }
    :root { --brand:#047857; --brand-light:#059669; --brand-dark:#065f46; --bg:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 50%,#f0f2f5 100%); --surface:#ffffff; --surface-2:#f8fafc; --surface-hover:#f1f5f9; --text:#0f172a; --border:#e2e8f0; --shadow-md:0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -1px rgba(0,0,0,.06); --radius:12px; --sidebar-expanded:280px; --sidebar-collapsed:80px; }
    body { background: var(--bg); color: var(--text); min-height: 100vh; padding-top: 60px; }
    .header-container { position: fixed; top: 0; left: 0; right: 0; height: 60px; background-color: #FF9100; display: flex; align-items: center; padding: 0 20px; z-index: 100; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .header-left { flex: 1; display: flex; align-items: center; gap: 20px; }
    .header-center { flex: 2; display: flex; justify-content: center; align-items: center; }
    .header-right { flex: 1; display: flex; justify-content: flex-end; align-items: center; }
    .logo { font-size: 24px; font-weight: bold; color: white; text-decoration:none; }
    .search-container { display: flex; align-items: center; background-color: rgba(255,255,255,0.2); border-radius: 12px; padding: 8px 16px; gap: 12px; }
    .search-container input { background: transparent; border: none; color: white; outline: none; width: 250px; font-size: 14px; }
    .search-container .material-symbols-outlined { color: white; font-size: 24px; }
    .nav-icons { display: flex; gap: 20px; }
    .nav-icons .icon { width: 40px; height: 40px; border-radius: 50%; background-color: rgba(255,255,255,0.2); display: flex; justify-content: center; align-items: center; color: white; cursor: pointer; text-decoration:none; }
    .profile-container { position: fixed; left: 0; top: 60px; bottom: 0; width: var(--sidebar-collapsed); background-color: white; padding: 20px; z-index: 90; box-shadow: 1px 0 5px rgba(0,0,0,0.1); overflow:hidden; transition: width .3s ease; display:flex; flex-direction:column; }
    .profile-container:hover { width: var(--sidebar-expanded); }
    .profile-container:hover { overflow-y:auto; }
    .profile-header { display: flex; align-items: center; margin-bottom: 20px; }
    .profile-pic { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; margin-right: 10px; cursor: pointer; }
    .profile-name { font-weight: 600; }
    .profile-menu { list-style: none; }
    .profile-menu li { padding: 10px; margin: 5px 0; border-radius: 8px; display: flex; align-items: center; cursor: pointer; }
    .profile-menu li:hover { background-color: #f0f2f5; }
    .profile-menu li i { margin-right: 0; color: #FF9100; }
    .profile-container:hover .profile-menu li i { margin-right: 10px; }
    .logout-btn { margin-top: auto; padding: 10px 15px; background-color: #ef4444; color: white; border: none; border-radius: 8px; display: flex; align-items: center; justify-content: flex-start; gap: 8px; cursor: pointer; width: 100%; }
    .logout-btn i { margin-right: 0; line-height: 1; display: inline-flex; align-items: center; }
    .logout-btn span { line-height: 1; display: inline-flex; align-items: center; }
    .profile-container .profile-name, .profile-container .profile-menu li span, .profile-container .logout-btn span { display:none; }
    .profile-container:hover .profile-name, .profile-container:hover .profile-menu li span, .profile-container:hover .logout-btn span { display:inline; }
    .main-content { margin-left: calc(var(--sidebar-collapsed) + 20px); padding: 20px 24px 24px; transition: margin-left .3s ease; }
    .cover-section { margin-bottom: 20px; }
    .cover-container { position: relative; border-radius: 12px; overflow: hidden; box-shadow: var(--shadow-md); background: white; }
    .cover-photo { height: 220px; position: relative; }
    .cover-image { width: 100%; height: 100%; object-fit: cover; display: block; }
    .cover-overlay { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(0,0,0,0.15), rgba(0,0,0,0.25)); }
    .profile-header-section { display: flex; gap: 16px; align-items: flex-end; padding: 0 24px 16px; margin-top: -48px; }
    .profile-picture-container { position: relative; width: 120px; height: 120px; border-radius: 50%; overflow: hidden; border: 4px solid white; box-shadow: 0 6px 16px rgba(0,0,0,0.2); background: #fff; }
    .profile-picture { width: 100%; height: 100%; object-fit: cover; display: block; }
    .profile-edit-overlay { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.35); opacity: 0; color: #fff; cursor: pointer; transition: opacity .2s; }
    .profile-picture-container:hover .profile-edit-overlay { opacity: 1; }
    .profile-info-header { flex: 1; color: #0f172a; }
    .profile-name { font-size: 28px; font-weight: 700; }
    .profile-role { color: #047857; font-weight: 600; margin: 4px 0; }
    .profile-bio { color: #334155; margin-top: 6px; }
    .profile-meta { display: flex; gap: 16px; margin-top: 10px; color: #475569; align-items: center; flex-wrap: wrap; }
    .profile-meta .material-symbols-outlined { font-size: 18px; vertical-align: middle; margin-right: 4px; }
    .rating-stars-inline { display: inline-flex; gap: 2px; vertical-align: middle; }
    .rating-stars-inline .material-symbols-outlined { color: #f59e0b; font-size: 20px; }
    .alert-error { background: #fff1f2; color: #991b1b; border: 1px solid #fecaca; padding: 12px 14px; border-radius: 8px; margin: 12px 0; }
    .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 12px 14px; border-radius: 8px; margin: 12px 0; }
    @media (max-width: 992px) { .main-content { margin-left: 0; } .profile-container { transform: translateX(-100%); transition: transform .3s; width: var(--sidebar-expanded); } .profile-container.active { transform: translateX(0); } }

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

  <!-- Main content -->
  <div class="main-content">
    <div class="cover-section">
      <div class="cover-container">
        <div class="cover-photo">
          <img src="https://images.unsplash.com/photo-1500937386664-56d1dfef3854?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80" alt="Cover" class="cover-image" />
          <div class="cover-overlay"></div>
        </div>
        <div class="profile-header-section">
          <div class="profile-picture-container">
            <img id="profile-picture" src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/0ec0940b-23d9-4b1f-8e72-2a8bd35584e4.png" alt="Profile" class="profile-picture" />
            <div class="profile-edit-overlay" id="edit-profile-trigger">
              <span class="material-symbols-outlined">edit</span>
            </div>
          </div>
          <div class="profile-info-header">
            <?php if (!empty($errors)): ?>
              <div class="alert-error">
                <ul style="padding-left:18px;">
                  <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php elseif ($success): ?>
              <div class="alert-success">Profile details validated successfully. (Demo)</div>
            <?php endif; ?>

            <div id="profile-static" style="<?php echo $_SERVER['REQUEST_METHOD']==='POST' && !empty($errors) ? 'display:none' : '';?>">
              <h1 class="profile-name" id="name-text"><?php echo htmlspecialchars($sticky['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
              <p class="profile-role" id="role-text"><?php echo htmlspecialchars($sticky['role'], ENT_QUOTES, 'UTF-8'); ?></p>
              <p class="profile-bio" id="bio-text"><?php echo htmlspecialchars($sticky['bio'], ENT_QUOTES, 'UTF-8'); ?></p>
              <div class="profile-meta">
                <span class="location"><span class="material-symbols-outlined">location_on</span><span id="location-text"><?php echo htmlspecialchars($sticky['location'], ENT_QUOTES, 'UTF-8'); ?></span></span>
                <span class="joined-date"><span class="material-symbols-outlined">calendar_today</span>Joined March 2023</span>
                <span class="rating-meta"><span class="rating-text">Rating:</span> <span class="rating-stars-inline" id="rating-stars"></span></span>
              </div>
              <div style="margin-top:10px"><button class="save-btn" id="start-edit" type="button"><span class="material-symbols-outlined">edit</span>Edit Profile</button></div>
            </div>

            <form id="profile-edit" style="display:<?php echo $_SERVER['REQUEST_METHOD']==='POST' && !empty($errors) ? 'grid' : 'none';?>" class="profile-edit-form" method="post" action="profile.php" enctype="multipart/form-data" novalidate>
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="text" name="name" id="name-input" class="profile-name-input" placeholder="Full Name" value="<?php echo htmlspecialchars($sticky['name'], ENT_QUOTES, 'UTF-8'); ?>" />
              <select name="role" id="role-input" class="profile-role-input">
                <option value="Crop Farmer" <?php echo $sticky['role']==='Crop Farmer'?'selected':''; ?>>Crop Farmer</option>
                <option value="Livestock Waste Owner" <?php echo $sticky['role']==='Livestock Waste Owner'?'selected':''; ?>>Livestock Waste Owner</option>
              </select>
              <textarea name="bio" id="bio-input" class="profile-bio-input" rows="3" placeholder="Bio"><?php echo htmlspecialchars($sticky['bio'], ENT_QUOTES, 'UTF-8'); ?></textarea>
              <input type="text" name="location" id="location-input" class="profile-location-input" placeholder="Location" value="<?php echo htmlspecialchars($sticky['location'], ENT_QUOTES, 'UTF-8'); ?>" />
              <div class="profile-edit-actions">
                <input type="file" name="profile_photo" id="profile-file" accept="image/*" />
                <button class="save-btn" id="save-profile" type="submit"><span class="material-symbols-outlined">check</span>Save</button>
                <button class="cancel-btn" id="cancel-edit" type="button"><span class="material-symbols-outlined">close</span>Cancel</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== NOTIFICATION POPUP ===== -->
  <div class="notification-container" id="notification-container" style="position:fixed; top:0; right:0">
    <div class="notification-header"><h3>Notifications</h3><i class="material-symbols-outlined" id="close-notification">close</i></div>
    <div class="notification-list">
      <div class="notification-item"><div class="notification-content"><div class="notification-text"><strong>System</strong> Profile page loaded.</div><div class="notification-time">Just now</div></div></div>
    </div>
  </div>

  <!-- ===== CHAT POPUP ===== -->
  <div class="header-chat-container" id="header-chat-container" style="position:fixed; top:0; right:0">
    <div class="chat-header-popup"><h3>Chats</h3><i class="material-symbols-outlined" id="close-chat">close</i></div>
    <div class="active-users-popup">
      <div class="active-title-popup">Active Now</div>
      <div class="active-list-popup"></div>
    </div>
    <div class="conversations-title-popup">Conversations</div>
    <div class="chat-list-popup" id="chat-list-popup"></div>
  </div>

  <script>
    // Helpers
    function $(sel, root=document){ return root.querySelector(sel); }
    function $all(sel, root=document){ return Array.from(root.querySelectorAll(sel)); }

    // Rating (demo)
    function calculateRating(){ const products=2, transactions=5, total=products+transactions; let r = total<=2?1: total<=5?2: total<=10?3: total<=20?4:5; if ([2,4,8,15].includes(total)) r += 0.5; return Math.min(5,r); }
    function renderStars(rating){ const root = $('#rating-stars'); if(!root) return; root.innerHTML=''; const full=Math.floor(rating), half = rating%1!==0; for(let i=0;i<full;i++){ root.innerHTML += '<span class="material-symbols-outlined">star</span>'; } if(half){ root.innerHTML += '<span class="material-symbols-outlined">star_half</span>'; } const empties = 5 - Math.ceil(rating); for(let i=0;i<empties;i++){ root.innerHTML += '<span class="material-symbols-outlined">star</span>'; } }
    renderStars(calculateRating());

    // Sidebar nav
    $('#profile-link')?.addEventListener('click', ()=>{ window.location.href = 'profile.php'; });
    $all('.profile-menu li').forEach(li=> li.addEventListener('click', ()=>{ const href=li.getAttribute('data-href'); if(href) window.location.href = href; }));
    $('#logout-btn')?.addEventListener('click', ()=>{ window.location.href = 'homemain.php'; });

    // ===== HEADER POPUPS (standardized) =====
    (function(){
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
      closeN && closeN.addEventListener('click', ()=> notifCont.classList.remove('visible'));
      closeC && closeC.addEventListener('click', ()=> chatCont.classList.remove('visible'));
      document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape') hideAll(); });
    })();

    // Edit toggle (client-side convenience; server validates on submit)
    const startEdit = $('#start-edit');
    const editPanel = $('#profile-edit');
    const staticPanel = $('#profile-static');
    startEdit?.addEventListener('click', ()=>{ if(staticPanel&&editPanel){ staticPanel.style.display='none'; editPanel.style.display='grid'; }});
    $('#cancel-edit')?.addEventListener('click', ()=>{ if(staticPanel&&editPanel){ editPanel.style.display='none'; staticPanel.style.display='block'; }});

    // Profile picture click to open file input
    $('#edit-profile-trigger')?.addEventListener('click', ()=>{ $('#profile-file')?.click(); });
  </script>
</body>
</html>
