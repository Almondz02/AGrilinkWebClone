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
    html { scrollbar-gutter: stable both-edges; }
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif; }
    :root {
      --brand: #047857;
      --brand-light: #059669;
      --brand-dark: #065f46;
      --bg: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #f0f2f5 100%);
      --surface: #ffffff;
      --surface-2: #f8fafc;
      --surface-hover: #f1f5f9;
      --text: #0f172a;
      --text-muted: #64748b;
      --text-light: #94a3b8;
      --border: #e2e8f0;
      --border-light: #f1f5f9;
      --ring: rgba(4, 120, 87, 0.12);
      --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
      --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      --radius: 12px;
      --radius-sm: 8px;
      --sidebar-expanded: 280px;
      --sidebar-collapsed: 80px;
      --top-gap: 0; /* keep body and notification top gaps in sync */
    }
    body { background: var(--bg); color: var(--text); min-height: 100vh; padding-top: var(--top-gap); }
    .header-container { display: none; }
    
    .main-content { margin: 0px auto 24px calc(var(--sidebar-expanded) + 96px) !important; padding-top: 24px; flex: 1; display: flex; flex-direction: column; gap: 24px; transition: margin-left .3s ease; max-width: 820px; width: 100%; }
    .cover-section { margin-bottom: 20px; }
    .cover-container { position: relative; border-radius: 12px; overflow: hidden; box-shadow: var(--shadow-md); background: white; }
    .cover-photo { height: 220px; position: relative; }
    .cover-image { width: 100%; height: 100%; object-fit: cover; display: block; }
    .cover-overlay { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(0,0,0,0.15), rgba(0,0,0,0.25)); }
    .profile-header-section { display: flex; flex-direction: column; align-items: center; gap: 12px; padding: 0 24px 16px; margin-top: -60px; }
    .profile-picture-row { display: flex; align-items: center; justify-content: center; gap: 10px; }
    .profile-picture-container { position: relative; width: 120px; height: 120px; border-radius: 50%; overflow: hidden; border: 4px solid white; box-shadow: 0 6px 16px rgba(0,0,0,0.2); background: #fff; }
    .profile-picture { width: 100%; height: 100%; object-fit: cover; display: block; }
    .profile-edit-overlay { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.35); opacity: 0; color: #fff; cursor: pointer; transition: opacity .2s; }
    .profile-picture-container:hover .profile-edit-overlay { opacity: 1; }
    .profile-info-header { flex: 1; color: #0f172a; text-align: center; display: flex; flex-direction: column; align-items: center; }
    .profile-name { font-size: 28px; font-weight: 700; }
    .profile-role { color: #047857; font-weight: 600; margin: 4px 0; }
    .profile-bio { color: #334155; margin-top: 6px; }
    #profile-static { text-align: center; max-width: 820px; margin: 0 auto; }
    #profile-edit { max-width: 640px; margin: 0 auto; }
    .profile-edit-actions { display: flex; gap: 10px; align-items: center; justify-content: center; }
    .profile-meta { display: flex; gap: 16px; margin-top: 10px; color: #475569; align-items: center; justify-content: center; flex-wrap: wrap; }
    .profile-meta .material-symbols-outlined { font-size: 18px; vertical-align: middle; margin-right: 4px; }
    .rating-stars-inline { display: inline-flex; gap: 2px; vertical-align: middle; }
    .rating-stars-inline .material-symbols-outlined { color: #f59e0b; font-size: 20px; }
    .alert-error { background: #fff1f2; color: #991b1b; border: 1px solid #fecaca; padding: 12px 14px; border-radius: 8px; margin: 12px auto; max-width: 640px; }
    .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 12px 14px; border-radius: 8px; margin: 12px auto; max-width: 640px; }
    @media (max-width: 992px) { .main-content { margin-left: 0; margin-right: 0; } .profile-container { transform: translateX(-100%); transition: transform .3s; width: var(--sidebar-expanded); } .profile-container.active { transform: translateX(0); } }

    /* ===== FLOATING ACTION BUTTONS (from homemain.php) ===== */
    .fab-notif { position: fixed; top: 24px; right: 24px; width: 76px; height: 76px; border-radius: 9999px; background: #ffffff; border: 1px solid #e5e7eb; box-shadow: 0 14px 34px rgba(0,0,0,0.18); display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 1000; }
    .fab-notif .material-symbols-outlined { color: #111827; font-size: 34px; }
    .fab-notif:hover { background: #f8fafc; }
    .fab-chat { position: fixed; bottom: 24px; right: 24px; border-radius: 9999px; background: #ffffff; border: 1px solid #e5e7eb; box-shadow: 0 16px 36px rgba(0,0,0,0.18); display: inline-flex; align-items: center; gap: 16px; padding: 18px 32px; cursor: pointer; z-index: 120; }
    .fab-chat .material-symbols-outlined { color: #111827; font-size: 30px; }
    .fab-chat .fab-chat-label { color: #111827; font-weight: 800; font-size: 17px; }
    .fab-chat:hover { background: #f8fafc; }

    /* ===== NOTIFICATION POPUP (from homemain.php) ===== */
    .notification-container { position: fixed; top: var(--top-gap, 0); right: 0; width: 460px; background-color: #fff; border-radius: 16px; box-shadow: 0 18px 40px rgba(0,0,0,0.18); z-index: 130; display: none; border: 1px solid #e5e7eb; overflow: hidden; }
    .notification-container.visible { display: block; }
    .notification-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #e5e7eb; background: #fff; }
    .notification-header h3 { margin: 0; font-size: 20px; color: #111827; font-weight: 700; }
    #close-notification { cursor: pointer; color: #999; }
    #close-notification:hover { color: #333; }
    .notification-list { max-height: 540px; overflow-y: auto; padding: 24px; background: #fff; }
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

    /* ===== CHAT POPUP (from homemain.php) ===== */
    .header-chat-container { position: fixed; top: var(--top-gap, 0); right: 0; width: 460px; background-color: white; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.12); z-index: 100; display: none; transition: all 0.3s ease; max-height: 540px; border: 1px solid #e4e6ea; }
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

    /* ===== CHAT WINDOWS (from homemain.php) ===== */
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
  </style>
</head>
<body>
  <?php require_once __DIR__ . '/partials/profile_container.php'; ?>
  <?php render_profile_container(); ?>

  <!-- Main content -->
  <div class="main-content">
    <div class="cover-section">
      <div class="cover-container">
        <div class="cover-photo">
          <img src="https://images.unsplash.com/photo-1500937386664-56d1dfef3854?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80" alt="Cover" class="cover-image" />
          <div class="cover-overlay"></div>
        </div>
        <div class="profile-header-section">
          <div class="profile-picture-row">
            <div class="profile-picture-container">
              <img id="profile-picture" src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/0ec0940b-23d9-4b1f-8e72-2a8bd35584e4.png" alt="Profile" class="profile-picture" />
              <div class="profile-edit-overlay" id="edit-profile-trigger"></div>
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

  <!-- Floating buttons: match homemain.php triggers -->
  <button class="fab-notif" id="notification-icon" type="button" aria-label="Notifications">
    <span class="material-symbols-outlined">notifications</span>
  </button>
  <button class="fab-chat" id="chat-icon" type="button" aria-label="Messages">
    <span class="material-symbols-outlined">forum</span>
    <span class="fab-chat-label">Messages</span>
  </button>

  <!-- ===== NOTIFICATION POPUP ===== -->
  <div class="notification-container" id="notification-container">
    <div class="notification-header"><h3>Notifications</h3><i class="material-symbols-outlined" id="close-notification">close</i></div>
    <div class="notification-list">
      <div class="notification-item"><div class="notification-content"><div class="notification-text"><strong>System</strong> Profile page loaded.</div><div class="notification-time">Just now</div></div></div>
    </div>
  </div>

  <!-- ===== CHAT POPUP ===== -->
  <div class="header-chat-container" id="header-chat-container">
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

  <!-- Floating chat windows root -->
  <div id="floating-chats-root"></div>

  <script>
    // Helpers
    function $(sel, root=document){ return root.querySelector(sel); }
    function $all(sel, root=document){ return Array.from(root.querySelectorAll(sel)); }

    // Rating (demo)
    function calculateRating(){ const products=2, transactions=5, total=products+transactions; let r = total<=2?1: total<=5?2: total<=10?3: total<=20?4:5; if ([2,4,8,15].includes(total)) r += 0.5; return Math.min(5,r); }
    function renderStars(rating){ const root = $('#rating-stars'); if(!root) return; root.innerHTML=''; const full=Math.floor(rating), half = rating%1!==0; for(let i=0;i<full;i++){ root.innerHTML += '<span class="material-symbols-outlined">star</span>'; } if(half){ root.innerHTML += '<span class="material-symbols-outlined">star_half</span>'; } const empties = 5 - Math.ceil(rating); for(let i=0;i<empties;i++){ root.innerHTML += '<span class="material-symbols-outlined">star</span>'; } }
    renderStars(calculateRating());

    

    // ===== CHAT LIST RENDER =====
    const chatState = {
      conversations: [
        { id: 'mang-jose', name: 'Mang Jose', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/70ed3f6c-a0ca-4490-8b6a-eaa245dffa33.png', lastMessage: 'Hi, about the manure delivery tomorrow...', time: '2:30 PM', messages: [ { id: 1, text: 'Hi, about the manure delivery tomorrow...', sender: 'them', time: '2:30 PM' }, { id: 2, text: 'What time should I expect the delivery?', sender: 'them', time: '2:31 PM' } ] },
        { id: 'farmers-coop', name: 'Farmers Cooperative', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/250bc031-e5eb-4467-9d71-49154b992c13.png', lastMessage: 'New bulk order of organic fertilizer available', time: 'Yesterday', messages: [ { id: 1, text: 'New bulk order of organic fertilizer available', sender: 'them', time: 'Yesterday' }, { id: 2, text: 'Would you be interested in placing an order?', sender: 'them', time: 'Yesterday' } ] }
      ],
      openChatWindows: [],
      typingUsers: {},
      messageInputs: {},
    };

    function renderChatList() {
      const list = document.getElementById('chat-list-popup');
      if (!list) return;
      list.innerHTML = '';
      chatState.conversations.forEach(conversation => {
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
    }

    // Active users quick-open
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
      if (chatState.openChatWindows.find(c => c.id === user.id)) return;
      const existingConversation = chatState.conversations.find(c => c.id === user.id);
      const newChat = { id: user.id, name: user.name, avatar: user.avatar, messages: existingConversation?.messages || user.messages || [], isMinimized: false };
      if (!existingConversation) {
        chatState.conversations = [ { id: user.id, name: user.name, avatar: user.avatar, lastMessage: 'Start a conversation...', time: 'Now', messages: [] }, ...chatState.conversations ];
        renderChatList();
      }
      chatState.openChatWindows.push(newChat);
      renderFloatingChats();
    }

    function closeChatWindow(chatId) {
      chatState.openChatWindows = chatState.openChatWindows.filter(c => c.id !== chatId);
      renderFloatingChats();
    }

    function minimizeChatWindow(chatId) {
      chatState.openChatWindows = chatState.openChatWindows.map(c => c.id === chatId ? { ...c, isMinimized: !c.isMinimized } : c);
      renderFloatingChats();
    }

    function sendMessage(chatId, message) {
      if (!message || !message.trim()) return;
      const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      const newMessage = { id: Date.now(), text: message, sender: 'me', time, timestamp: new Date(), status: 'sent' };
      chatState.openChatWindows = chatState.openChatWindows.map(c => c.id === chatId ? { ...c, messages: [...c.messages, newMessage] } : c);
      chatState.conversations = chatState.conversations.map(conv => conv.id === chatId ? { ...conv, lastMessage: message, time: 'Now', messages: [...conv.messages, newMessage] } : conv);
      chatState.messageInputs[chatId] = '';
      renderFloatingChats();
      simulateTypingAndResponse(chatId);
      setTimeout(() => autoScrollMessages(chatId), 100);
    }

    function simulateTypingAndResponse(chatId) {
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
        const randomResponse = responses[Math.floor(Math.random() * responses.length)];
        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const responseMessage = { id: Date.now() + 1, text: randomResponse, sender: 'them', time, timestamp: new Date(), status: 'delivered' };
        chatState.openChatWindows = chatState.openChatWindows.map(c => c.id === chatId ? { ...c, messages: [...c.messages, responseMessage] } : c);
        chatState.conversations = chatState.conversations.map(conv => conv.id === chatId ? { ...conv, lastMessage: randomResponse, time: 'Now', messages: [...conv.messages, responseMessage] } : conv);
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
          input.addEventListener('keypress', (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(chat.id, input.value); }});
          button.addEventListener('click', () => sendMessage(chat.id, input.value));
        }
        root.appendChild(container);
      });
    }

    // Initialize chat list
    renderChatList();

    // ===== HEADER POPUPS (homemain aligned) =====
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
        const anchorRect = (cont.id === 'header-chat-container' && notifIcon) ? notifIcon.getBoundingClientRect() : r;
        const top = anchorRect.bottom + 8;
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
