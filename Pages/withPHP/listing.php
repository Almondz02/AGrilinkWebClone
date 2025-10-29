<?php
session_start();
// CSRF token
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf_token'];

$errors = [];
$success = false;

// Sticky fields
$sticky = [
  'wasteType' => '',
  'animalType' => '',
  'weight' => '',
  'collectionDate' => '',
  'price' => '',
  'description' => ''
];

$allowedWaste = ['manure','bedding','slurry','compost','other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF
  if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $errors[] = 'Invalid request. Please refresh the page and try again.';
  }

  // Collect input
  $sticky['wasteType'] = isset($_POST['wasteType']) ? (string)$_POST['wasteType'] : '';
  $sticky['animalType'] = isset($_POST['animalType']) ? trim((string)$_POST['animalType']) : '';
  $sticky['weight'] = isset($_POST['weight']) ? (string)$_POST['weight'] : '';
  $sticky['collectionDate'] = isset($_POST['collectionDate']) ? (string)$_POST['collectionDate'] : '';
  $sticky['price'] = isset($_POST['price']) ? (string)$_POST['price'] : '';
  $sticky['description'] = isset($_POST['description']) ? trim((string)$_POST['description']) : '';

  // Validate required fields
  if (!$sticky['wasteType'] || !in_array($sticky['wasteType'], $allowedWaste, true)) {
    $errors[] = 'Please select a valid waste type.';
  }
  if ($sticky['wasteType'] === 'manure' && $sticky['animalType'] === '') {
    $errors[] = 'Animal Type is required for manure.';
  }
  if ($sticky['weight'] === '') {
    $errors[] = 'Weight is required.';
  } else {
    if (!is_numeric($sticky['weight']) || (float)$sticky['weight'] <= 0) {
      $errors[] = 'Weight must be a number greater than 0.';
    }
  }
  if ($sticky['collectionDate'] === '') {
    $errors[] = 'Collection date is required.';
  } else {
    $cd = DateTime::createFromFormat('Y-m-d', $sticky['collectionDate']);
    if (!$cd || $cd->format('Y-m-d') !== $sticky['collectionDate']) {
      $errors[] = 'Invalid collection date. Use YYYY-MM-DD.';
    }
  }

  // Optional price
  if ($sticky['price'] !== '') {
    if (!is_numeric($sticky['price']) || (float)$sticky['price'] < 0) {
      $errors[] = 'Price must be a number 0 or greater.';
    }
  }

  // Optional description length limit
  if ($sticky['description'] !== '' && mb_strlen($sticky['description']) > 500) {
    $errors[] = 'Description must be 500 characters or fewer.';
  }

  // Optional image
  $hasFile = isset($_FILES['photo']) && is_array($_FILES['photo']) && (int)$_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE;
  if ($hasFile) {
    if ((int)$_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
      $errors[] = 'There was an error uploading the image.';
    } else {
      $maxBytes = 5 * 1024 * 1024; // 5MB
      if ((int)$_FILES['photo']['size'] > $maxBytes) {
        $errors[] = 'Image must be 5MB or smaller.';
      }
      $tmp = $_FILES['photo']['tmp_name'];
      $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
      $mime = $finfo ? finfo_file($finfo, $tmp) : (mime_content_type($tmp) ?: null);
      if ($finfo) { finfo_close($finfo); }
      $allowed = ['image/jpeg','image/png','image/webp'];
      if (!$mime || !in_array($mime, $allowed, true)) {
        $errors[] = 'Only JPEG, PNG, or WEBP images are allowed.';
      }
    }
  }

  if (empty($errors)) {
    $success = true;
    // Rotate CSRF on success
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_token'];
    // Demo only: not persisting to storage
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AgriLink - Listings</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
  <style>
    html { scrollbar-gutter: stable both-edges; }
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter','Segoe UI',system-ui,-apple-system,sans-serif; }
    :root { --brand:#047857; --brand-light:#059669; --brand-dark:#065f46; --bg:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 50%,#f0f2f5 100%); --surface:#ffffff; --surface-2:#f8fafc; --surface-hover:#f1f5f9; --text:#0f172a; --text-muted:#64748b; --text-light:#94a3b8; --border:#e2e8f0; --border-light:#f1f5f9; --ring:rgba(4,120,87,.12); --shadow-sm:0 1px 2px rgba(0,0,0,.05); --shadow-md:0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -1px rgba(0,0,0,.06); --shadow-lg:0 10px 15px -3px rgba(0,0,0,.1),0 4px 6px -2px rgba(0,0,0,.05); --radius:12px; --radius-sm:8px; --sidebar-expanded:280px; --sidebar-collapsed:80px; --top-gap: 0; }
    body { background: var(--bg); color: var(--text); line-height: 1.6; overflow-x: hidden; min-height: 100vh; padding-top: var(--top-gap); }
    .header-container { position: fixed; top: 0; left: 0; right: 0; height: 60px; background-color: #FF9100; display: flex; align-items: center; padding: 0 20px; z-index: 100; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .header-left { flex: 1; display: flex; align-items: center; gap: 20px; }
    .header-center { flex: 2; display: flex; justify-content: center; align-items: center; }
    .header-right { flex: 1; display: flex; justify-content: flex-end; align-items: center; }
    .logo { font-size: 24px; font-weight: bold; color: white; }
    .search-container { display: flex; align-items: center; background-color: rgba(255,255,255,0.2); border-radius: 12px; padding: 8px 16px; gap: 12px; transition: all 0.2s ease; }
    .search-container:hover { background-color: rgba(255,255,255,0.25); }
    .search-container input { background: transparent; border: none; color: white; outline: none; width: 250px; font-size: 14px; }
    .search-container input::placeholder { color: rgba(255,255,255,0.7); }
    .search-container .material-symbols-outlined { color: white; font-size: 24px; transition: transform 0.2s ease; }
    .search-container:hover .material-symbols-outlined { transform: scale(1.1); }
    .material-symbols-outlined { font-family: 'Material Symbols Outlined'; }
    .nav-icons { display: flex; gap: 20px; }
    .nav-icons .icon { width: 40px; height: 40px; border-radius: 50%; background-color: rgba(255,255,255,0.2); display: flex; justify-content: center; align-items: center; color: white; cursor: pointer; transition: background-color 0.3s; text-decoration: none; }
    .nav-icons .icon:hover { background-color: rgba(255,255,255,0.3); }
    .nav-icons .icon .material-symbols-outlined { font-size: 24px; font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    .profile-container { position: fixed; top: 0; left: 0; bottom: 0; width: var(--sidebar-expanded); background-color: white; padding: 20px; z-index: 90; box-shadow: 1px 0 5px rgba(0,0,0,0.1); overflow-y:auto; transition: width .3s ease; display:flex; flex-direction:column; }
    .profile-header { display: flex; align-items: center; margin-bottom: 20px; }
    .profile-pic { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; margin-right: 10px; cursor: pointer; }
    .profile-name { font-weight: 600; }
    .profile-menu { list-style: none; }
    .profile-menu li { padding: 14px 16px; margin: 8px 0; border-radius: var(--radius-sm); display: flex; align-items: center; cursor: pointer; font-size: 18px; }
    .profile-menu li:hover { background-color: var(--surface-hover); }
    .profile-menu li i { margin-right: 14px; color: #FF9100; font-size: 26px; }
    .bottom-menu { margin-top: auto; }
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
    .profile-container .profile-name, .profile-container .profile-menu li span, .profile-container .logout-btn span { display:inline; }
    .main-container { display: flex; margin-top: 0px; padding-top: 0px; min-height: 100vh; }
    .main-content { margin: 0px auto 24px calc(var(--sidebar-expanded) + 96px); padding-top: 24px; flex: 1; display: flex; flex-direction: column; gap: 24px; transition: margin-left 0.3s ease; max-width: 820px; width: 100%; }
    .container { width: 100%; }
    header { text-align: center; margin-bottom: 30px; }
    h1 { color: #2c6b4a; }
    .form-container { background-color: white; padding: 25px; border-radius: var(--radius); box-shadow: var(--shadow-md); margin-bottom: 30px; }
    .form-group { margin-bottom: 15px; }
    .form-row { display: flex; gap: 15px; }
    .form-row .form-group { flex: 1; }
    label { display: block; margin-bottom: 5px; font-weight: bold; }
    input, textarea, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
    button.primary { background-color: #047857; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
    button.primary:hover { background-color: #065f46; }
    .main-listings-container { display: grid; grid-template-columns: repeat(2, 1fr); grid-template-rows: repeat(2, 1fr); gap: 25px; margin-top: 30px; }
    .listing-card { background-color: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); position: relative; display: flex; flex-direction: column; overflow: hidden; transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .listing-card:hover { transform: translateY(-5px); box-shadow: 0 6px 16px rgba(0,0,0,0.12); }
    .listing-header { background-color: #047857; color: white; padding: 20px; text-align: center; }
    .listing-title { margin: 0; font-size: 22px; font-weight: 600; }
    .listing-subtitle { font-size: 16px; opacity: 0.9; margin-top: 5px; }
    .listing-photo { max-width: 100%; height: 200px; object-fit: cover; width: 100%; border-radius: 8px 8px 0 0; }
    .listing-content { padding: 25px; flex: 1; }
    .listing-details { margin-bottom: 20px; }
    .detail-item { display: flex; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0; }
    .detail-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .detail-label { font-weight: 600; color: #555; min-width: 140px; display: inline-block; }
    .detail-value { flex: 1; color: #333; }
    .price-tag { background-color: #047857; color: white; padding: 5px 12px; border-radius: 20px; font-weight: 600; display: inline-block; margin-top: 10px; }
    .actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; }
    .edit-btn { padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 14px; background-color: #ffc107; color: #000; border: none; }
    .edit-btn:hover { background-color: #e0a800; }
    .delete-btn { padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 14px; background-color: #dc3545; color: white; border: none; }
    .delete-btn:hover { background-color: #c82333; }
    .edit-form { background-color: #f8f9fa; padding: 15px; border-radius: 4px; margin-top: 15px; }
    .notification { padding: 10px; margin: 10px 0; border-radius: 4px; }
    .notification.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .notification.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    /* ===== NOTIFICATION POPUP (match homemain) ===== */
    .notification-container { position: fixed; top: var(--top-gap); right: 0; width: 460px; background-color: #fff; border-radius: 16px; box-shadow: 0 18px 40px rgba(0,0,0,0.18); z-index: 130; display: none; border: 1px solid #e5e7eb; overflow: hidden; }
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

    /* ===== HEADER CHAT POPUP (match homemain) ===== */
    .header-chat-container { position: fixed; top: var(--top-gap); right: 0; width: 460px; background-color: white; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.12); z-index: 100; display: none; transition: opacity 0.2s ease; max-height: 540px; border: 1px solid #e4e6ea; }
    /* ===== NOTIFICATION FAB ===== */
    .fab-notif { position: fixed; top: 24px; right: 24px; width: 76px; height: 76px; border-radius: 9999px; background: #fff; border: 1px solid #e5e7eb; box-shadow: 0 14px 34px rgba(0,0,0,0.18); display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 1000; }
    .fab-notif .material-symbols-outlined { color: #111827; font-size: 34px; }
    .fab-notif:hover { background: #f8fafc; }
    /* ===== CHAT FAB ===== */
    .fab-chat { position: fixed; bottom: 24px; right: 24px; border-radius: 9999px; background: #ffffff; border: 1px solid #e5e7eb; box-shadow: 0 16px 36px rgba(0,0,0,0.18); display: inline-flex; align-items: center; gap: 16px; padding: 18px 32px; cursor: pointer; z-index: 120; }
    .fab-chat .material-symbols-outlined { color: #111827; font-size: 30px; }
    .fab-chat .fab-chat-label { color: #111827; font-weight: 800; font-size: 17px; }
    .fab-chat:hover { background: #f8fafc; }
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
    @media (max-width: 992px) { .profile-container { transform: translateX(-100%); transition: transform 0.3s ease; width: var(--sidebar-expanded); } .profile-container.active { transform: translateX(0); } .main-content { margin-left: 0; margin-right: 0; } }
    /* ===== FLOATING CHAT WINDOWS (match homemain) ===== */
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

  <div class="main-container">
    <div class="main-content">
      <div class="container">
        <header><h1>Livestock Waste Listings</h1></header>

        <?php if (!empty($errors)): ?>
          <div class="notification error">
            <ul style="padding-left:18px;">
              <?php foreach ($errors as $e): ?>
                <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php elseif ($success): ?>
          <div class="notification success">Listing validated successfully. (Demo: not persisted.)</div>
        <?php endif; ?>

        <form id="listing-form" class="form-container" method="post" action="listing.php" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>" />
          <div class="form-row">
            <div class="form-group">
              <label for="wasteType">Waste Type *</label>
              <select id="wasteType" name="wasteType" required>
                <option value="">Select type</option>
                <option value="manure" <?php echo $sticky['wasteType']==='manure'?'selected':''; ?>>Manure</option>
                <option value="bedding" <?php echo $sticky['wasteType']==='bedding'?'selected':''; ?>>Bedding</option>
                <option value="slurry" <?php echo $sticky['wasteType']==='slurry'?'selected':''; ?>>Slurry</option>
                <option value="compost" <?php echo $sticky['wasteType']==='compost'?'selected':''; ?>>Compost</option>
                <option value="other" <?php echo $sticky['wasteType']==='other'?'selected':''; ?>>Other</option>
              </select>
            </div>
            <div class="form-group">
              <label for="animalType">Animal Type (for Manure)</label>
              <input type="text" id="animalType" name="animalType" placeholder="e.g., Cattle" value="<?php echo htmlspecialchars($sticky['animalType'], ENT_QUOTES, 'UTF-8'); ?>" />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="weight">Weight (kg) *</label>
              <input type="number" id="weight" name="weight" placeholder="e.g., 500" required value="<?php echo htmlspecialchars($sticky['weight'], ENT_QUOTES, 'UTF-8'); ?>" />
            </div>
            <div class="form-group">
              <label for="collectionDate">Collection Date *</label>
              <input type="date" id="collectionDate" name="collectionDate" required value="<?php echo htmlspecialchars($sticky['collectionDate'], ENT_QUOTES, 'UTF-8'); ?>" />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="price">Price (₱)</label>
              <input type="number" step="0.01" id="price" name="price" placeholder="e.g., 75.00" value="<?php echo htmlspecialchars($sticky['price'], ENT_QUOTES, 'UTF-8'); ?>" />
            </div>
            <div class="form-group">
              <label for="photo">Photo</label>
              <input type="file" id="photo" name="photo" accept="image/*" />
            </div>
          </div>

          <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3" placeholder="Describe the listing"><?php echo htmlspecialchars($sticky['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
          </div>

          <button type="submit" class="primary">Add Listing</button>
        </form>

        <div id="listings-grid" class="main-listings-container"></div>
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

  <div class="notification-container" id="notification-container">
    <div class="notification-header"><h3>Notifications</h3><i class="material-symbols-outlined" id="close-notification">close</i></div>
    <div class="notification-list">
      <div class="notification-item"><div class="notification-content"><div class="notification-text"><strong>Maria Santos</strong> commented on your listing.</div><div class="notification-time">2 hrs ago</div></div></div>
      <div class="notification-item"><div class="notification-content"><div class="notification-text"><strong>Pedro Bautista</strong> liked your compost listing.</div><div class="notification-time">5 hrs ago</div></div></div>
      <div class="notification-item"><div class="notification-content"><div class="notification-text"><strong>AgriTech PH</strong> shared a new article: "Benefits of Organic Waste in Farming"</div><div class="notification-time">1 day ago</div></div></div>
      <div class="notification-item"><div class="notification-content"><div class="notification-text"><strong>Farmers Cooperative</strong> added a new listing for compost materials.</div><div class="notification-time">2 days ago</div></div></div>
    </div>
  </div>

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
    // ===== DATA & STATE =====
    const initialListings = [
      { id: 1, wasteType: 'manure', weight: 500, collectionDate: '2023-06-15', description: 'Well-aged cattle manure, excellent for fertilizer', animalType: 'Cattle', price: 50.0, photo: null },
      { id: 2, wasteType: 'compost', weight: 300, collectionDate: '2023-06-18', description: 'Organic compost from mixed livestock waste', price: 75.0, photo: null },
    ];
    const state = { listings: [...initialListings], conversations: [
      { id: 'mang-jose', name: 'Mang Jose', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/70ed3f6c-a0ca-4490-8b6a-eaa245dffa33.png', lastMessage: 'Hi, about the manure delivery tomorrow...', time: '2:30 PM', messages: [ { id: 1, text: 'Hi, about the manure delivery tomorrow...', sender: 'them', time: '2:30 PM' }, { id: 2, text: 'What time should I expect the delivery?', sender: 'them', time: '2:31 PM' } ] },
      { id: 'farmers-coop', name: 'Farmers Cooperative', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/250bc031-e5eb-4467-9d71-49154b992c13.png', lastMessage: 'New bulk order of organic fertilizer available', time: 'Yesterday', messages: [ { id: 1, text: 'New bulk order of organic fertilizer available', sender: 'them', time: 'Yesterday' }, { id: 2, text: 'Would you be interested in placing an order?', sender: 'them', time: 'Yesterday' } ] }
    ], openChatWindows: [], typingUsers: {}, messageInputs: {}, notificationStyle: { top: 0, right: 0 }, chatStyle: { top: 0, right: 0 } };

    // ===== HELPERS =====
    function $(sel, root=document){ return root.querySelector(sel); }
    function $all(sel, root=document){ return Array.from(root.querySelectorAll(sel)); }
    function getWasteTypeLabel(type){ const map={manure:'Manure', bedding:'Bedding', slurry:'Slurry', compost:'Compost', other:'Other'}; return map[type]||type||'Unknown'; }
    function formatDate(dateString){ if(!dateString) return 'No date provided'; try{ return new Date(dateString).toLocaleDateString(undefined,{year:'numeric',month:'long',day:'numeric'});}catch(e){ return dateString; } }

    // ===== NAV =====
    (function(){ const pl = document.getElementById('profile-link'); if (pl) { pl.addEventListener('click', () => { window.location.href = 'profile.php'; }); } })();
    $all('.profile-menu li').forEach(li => li.addEventListener('click', () => { const href = li.getAttribute('data-href'); if (href) window.location.href = href; }));
    $('#logout-btn')?.addEventListener('click', () => { window.location.href = 'homemain.php'; });

    // Sidebar bottom menu popover (synced with homemain)
    (function(){
      const trigger = document.getElementById('profile-menu-trigger');
      const pop = document.getElementById('profile-menu-popover');
      const logout = document.getElementById('logout-action');
      if (!trigger || !pop) return;
      function position(){
        const r = trigger.getBoundingClientRect();
        const gap = 16;
        let left = r.right + gap;
        const pc = document.querySelector('.profile-container');
        if (pc) {
          const cr = pc.getBoundingClientRect();
          left = Math.max(left, cr.right + 8);
        }
        const prevDisplay = pop.style.display;
        if (!pop.classList.contains('visible')) { pop.style.visibility='hidden'; pop.style.display='block'; }
        const pw = pop.offsetWidth || 260; const ph = pop.offsetHeight || 200;
        if (!pop.classList.contains('visible')) { pop.style.display = prevDisplay; pop.style.visibility=''; }
        const margin = 8;
        if (left + pw > window.innerWidth - margin) { left = Math.max(margin, r.left - gap - pw); }
        let top = r.bottom - ph;
        top = Math.max(margin, Math.min(top, window.innerHeight - ph - margin));
        pop.style.top = Math.round(top)+'px';
        pop.style.left = Math.round(left)+'px';
      }
      function toggle(){ position(); pop.classList.toggle('visible'); }
      function hide(){ pop.classList.remove('visible'); }
      trigger.addEventListener('click', (e)=>{ e.stopPropagation(); toggle(); });
      document.addEventListener('click', (e)=>{ if (pop.classList.contains('visible') && !pop.contains(e.target) && e.target !== trigger) hide(); });
      document.addEventListener('keydown', (e)=>{ if (e.key==='Escape') hide(); });
      pop.querySelectorAll('.menu-item').forEach(it=> it.addEventListener('click', ()=>{
        const href = it.getAttribute('data-href');
        if (!href) return;
        if (href === '#switch-appearance') { return; }
        if (href.startsWith('#')) { hide(); return; }
        window.location.href = href;
      }));
      logout && logout.addEventListener('click', ()=>{ window.location.href = 'homemain.php'; });
      window.addEventListener('resize', ()=>{ if (pop.classList.contains('visible')) position(); });
      window.addEventListener('scroll', ()=>{ if (pop.classList.contains('visible')) position(); }, { passive:true });
      window.__positionProfileMenu = position;
    })();

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
            <div class="chat-preview-popup">${conversation.lastMessage}</div>
          </div>
          <div class="chat-time-popup">${conversation.time}</div>
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

    // ===== HEADER POPUPS (standardized) =====
    (function(){
      const notifIcon = $('#notification-icon');
      const notifCont = $('#notification-container');
      const chatIcon = $('#chat-icon');
      const chatCont = $('#header-chat-container');
      if (!notifIcon || !notifCont || !chatIcon || !chatCont) return;

      function positionNear(el, cont){
        const r = el.getBoundingClientRect();
        const margin = 10;
        // Measure container width even if hidden
        const prevDisplay = cont.style.display;
        const prevVisibility = cont.style.visibility;
        if (!cont.classList.contains('visible')) { cont.style.visibility = 'hidden'; cont.style.display = 'block'; }
        const cw = cont.offsetWidth || 400;
        if (!cont.classList.contains('visible')) { cont.style.display = prevDisplay; cont.style.visibility = prevVisibility; }
        // For chat floater, align vertically under the notification bell; else use the triggering icon
        const anchorRect = (cont.id === 'header-chat-container' && notifIcon) ? notifIcon.getBoundingClientRect() : r;
        const top = anchorRect.bottom + 8;
        const centerLeft = (window.innerWidth - cw) / 2;
        const bias = 780; // horizontal nudge to the right
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

      document.addEventListener('keydown', (e)=>{
        if (e.key === 'Escape') hideAll();
      });
    })();

    // ===== DARK MODE TOGGLE (match homemain) =====
    (function(){
      const STORAGE_KEY = 'theme';
      function applyTheme(theme){ document.body.classList.toggle('dark', theme === 'dark'); }
      const saved = localStorage.getItem(STORAGE_KEY);
      if (saved === 'dark' || saved === 'light') { applyTheme(saved); }
      const switchItem = document.querySelector('.profile-menu-popover .menu-item[data-href="#switch-appearance"]');
      if (switchItem) {
        switchItem.addEventListener('click', (e)=>{
          e.stopPropagation();
          const willBeDark = !document.body.classList.contains('dark');
          document.body.classList.toggle('dark', willBeDark);
          localStorage.setItem(STORAGE_KEY, willBeDark ? 'dark' : 'light');
          const pop = document.getElementById('profile-menu-popover');
          if (pop) {
            pop.classList.add('visible');
            if (window.__positionProfileMenu) { window.__positionProfileMenu(); }
          }
        });
      }
    })();

    // ===== LISTINGS RENDER =====
    function renderListings(){
      const grid = document.getElementById('listings-grid');
      if (!grid) return;
      grid.innerHTML = '';
      state.listings.forEach(listing => {
        const card = document.createElement('div');
        card.className = 'listing-card';
        const title = listing.animalType ? `${listing.animalType} ${getWasteTypeLabel(listing.wasteType)}` : getWasteTypeLabel(listing.wasteType);
        card.innerHTML = `
          ${listing.photo ? `<img class="listing-photo" src="${listing.photo}" alt="${title}" />` : ''}
          <div class="listing-header">
            <h2 class="listing-title">${title}</h2>
            <div class="listing-subtitle">${formatDate(listing.collectionDate)}</div>
          </div>
          <div class="listing-content">
            <div class="listing-details">
              <div class="detail-item"><span class="detail-label">Waste Type:</span><span class="detail-value">${getWasteTypeLabel(listing.wasteType)}</span></div>
              <div class="detail-item"><span class="detail-label">Weight:</span><span class="detail-value">${listing.weight} kg</span></div>
              <div class="detail-item"><span class="detail-label">Description:</span><span class="detail-value">${listing.description || '—'}</span></div>
              ${listing.price!=null? `<div class="price-tag">₱${Number(listing.price).toFixed(2)}</div>` : ''}
            </div>
          </div>
        `;
        grid.appendChild(card);
      });
    }

    // Initial render (demo data only)
    renderChatList();
    renderListings();
  </script>
</body>
</html>
