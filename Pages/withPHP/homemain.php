<?php
session_start();

// CSRF token setup
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$errors = [];
$success = false;
$sticky_text = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF validation
  if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $errors[] = 'Invalid request. Please refresh the page and try again.';
  }

  // Text validation
  $post_text = isset($_POST['post_text']) ? trim((string)$_POST['post_text']) : '';
  $sticky_text = $post_text;
  if ($post_text !== '' && mb_strlen($post_text) > 500) {
    $errors[] = 'Text must be 500 characters or fewer.';
  }

  // File validation
  $hasFile = isset($_FILES['photo']) && is_array($_FILES['photo']) && (int)$_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE;
  if ($hasFile) {
    $fileError = (int)$_FILES['photo']['error'];
    if ($fileError !== UPLOAD_ERR_OK) {
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
      $allowed = ['image/jpeg', 'image/png', 'image/webp'];
      if (!$mime || !in_array($mime, $allowed, true)) {
        $errors[] = 'Only JPEG, PNG, or WEBP images are allowed.';
      }
    }
  }

  // Require at least one of text or image
  if ($post_text === '' && !$hasFile) {
    $errors[] = 'Please enter text or select an image to post.';
  }

  if (empty($errors)) {
    $success = true;
    // Regenerate CSRF token after successful POST (double-submit protection)
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $csrf_token = $_SESSION['csrf_token'];
    // Note: Not persisting uploads in this demo. If needed, implement safe storage later.
    $sticky_text = '';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AgriLink - Livestock Waste Exchange</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
  <style>
    html { scrollbar-gutter: stable both-edges; }
    /* ===== GLOBAL STYLES AND VARIABLES ===== */
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
    body { margin: 0; padding: 0; background: var(--bg); color: var(--text); line-height: 1.6; overflow-x: hidden; min-height: 100vh; padding-top: var(--top-gap); }

    /* ===== PROFILE CONTAINER SIDEBAR ===== */
    .profile-container { position: fixed; left: 0; top: 0; bottom: 0; width: var(--sidebar-expanded); background-color: var(--surface); padding: 20px; z-index: 90; box-shadow: var(--shadow-sm); overflow-y: auto; transition: width 0.3s ease; display:flex; flex-direction:column; }
    .profile-header { display: flex; align-items: center; margin-bottom: 20px; }
    .profile-pic { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; margin-right: 10px; cursor: pointer; }
    .profile-name { font-weight: 600; }
    .profile-container .profile-name,
    .profile-container .profile-menu li span,
    .profile-container .logout-btn span { display: inline; }
    .profile-menu { list-style: none; }
    .profile-menu li { padding: 14px 16px; margin: 8px 0; border-radius: var(--radius-sm); display: flex; align-items: center; cursor: pointer; font-size: 18px; }
    .profile-menu li:hover { background-color: var(--surface-hover); }
    .profile-menu li i { margin-right: 14px; color: #FF9100; font-size: 26px; }
    .bottom-menu { margin-top: auto; }
    .sidebar-brand { font-weight: 800; color: #FF9100; margin: 0 0 16px; font-size: 28px; background: transparent; padding: 0; border-radius: 0; display: block; text-align: center; }

    /* Sidebar search bar */
    .sidebar-search { display: flex; align-items: center; gap: 10px; background: #f1f5f9; border: 1px solid var(--border); border-radius: 12px; padding: 10px 12px; margin: 6px 0 10px; }
    .sidebar-search .material-symbols-outlined { color: #64748b; font-size: 22px; }
    .sidebar-search input { flex: 1; border: none; outline: none; background: transparent; font-size: 16px; color: #0f172a; }
    .sidebar-search input::placeholder { color: #94a3b8; }

    /* ===== PROFILE BOTTOM MENU (popover) ===== */
    .profile-menu-trigger { margin-top: auto; display: inline-flex; align-items: center; gap: 8px; border: 1px solid var(--border); background: #fff; color: #111827; padding: 10px 14px; border-radius: 10px; cursor: pointer; box-shadow: var(--shadow-sm); }
    .profile-menu-trigger .material-symbols-outlined { color: #0f172a; }
    .profile-menu-popover { position: fixed; width: 260px; background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); border: 1px solid #e5e7eb; padding: 10px 0; display: none; z-index: 120; max-height: calc(100vh - 16px); overflow-y: auto; }
    .profile-menu-popover.visible { display: block; }
    .profile-menu-popover .menu-item { display: flex; align-items: center; gap: 12px; padding: 10px 16px; color: #0f172a; cursor: pointer; }
    .profile-menu-popover .menu-item:hover { background: #f8fafc; }
    .profile-menu-popover .menu-item .material-symbols-outlined { color: #475569; }
    .profile-menu-popover .menu-divider { height: 1px; background: #f1f5f9; margin: 6px 0; }
    .profile-menu-popover .logout-action { margin: 8px 12px 4px; padding: 10px 14px; background: #ef4444; color: #fff; border-radius: 10px; display: flex; align-items: center; gap: 10px; font-weight: 600; justify-content: center; }
    .profile-menu-popover .logout-action .material-symbols-outlined { color: #fff; }

    /* ===== HEADER CONTAINER STYLES ===== */
    .header-container { position: fixed; top: 0; left: 0; right: 0; height: 60px; background-color: #FF9100; display: flex; align-items: center; padding: 0 20px; z-index: 100; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .header-left { flex: 1; display: flex; align-items: center; gap: 20px; }
    .header-center { flex: 2; display: flex; justify-content: center; align-items: center; }
    .header-right { flex: 1; display: flex; justify-content: flex-end; align-items: center; }
    .logo { font-size: 24px; font-weight: bold; color: white; }

    /* Search bar removed */

    /* ===== NAVIGATION ICONS STYLES ===== */
    .nav-icons { display: flex; gap: 20px; }
    .nav-icons .icon { width: 40px; height: 40px; border-radius: 50%; background-color: rgba(255,255,255,0.2); display: flex; justify-content: center; align-items: center; color: white; cursor: pointer; transition: background-color 0.3s; text-decoration: none; }
    .nav-icons .icon:hover { background-color: rgba(255,255,255,0.3); }
    .nav-icons .icon .material-symbols-outlined { font-size: 24px; font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }

    /* ===== FLOATING ACTION BUTTONS ===== */
    .fab-notif { position: fixed; top: 24px; right: 24px; width: 76px; height: 76px; border-radius: 9999px; background: #ffffff; border: 1px solid #e5e7eb; box-shadow: 0 14px 34px rgba(0,0,0,0.18); display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 1000; }
    .fab-notif .material-symbols-outlined { color: #111827; font-size: 34px; }
    .fab-notif:hover { background: #f8fafc; }
    .fab-chat { position: fixed; bottom: 24px; right: 24px; border-radius: 9999px; background: #ffffff; border: 1px solid #e5e7eb; box-shadow: 0 16px 36px rgba(0,0,0,0.18); display: inline-flex; align-items: center; gap: 16px; padding: 18px 32px; cursor: pointer; z-index: 120; }
    .fab-chat .material-symbols-outlined { color: #111827; font-size: 30px; }
    .fab-chat .fab-chat-label { color: #111827; font-weight: 800; font-size: 17px; }
    .fab-chat:hover { background: #f8fafc; }

    /* ===== MAIN CONTAINER AND CONTENT ===== */
    .main-container { display: flex; margin-top: 0px; padding-top: 0px; min-height: 100vh; }
    .main-content { margin: 0px auto 24px calc(var(--sidebar-expanded) + 96px); padding-top: 24px; flex: 1; display: flex; flex-direction: column; gap: 24px; transition: margin-left 0.3s ease; max-width: 820px; width: 100%; }

    /* ===== POST CREATION BOX STYLES ===== */
    .post-box { background-color: white; border-radius: 16px; padding: 20px; box-shadow: var(--shadow-lg); border: 1px solid #e5e7eb; }
    .post-input { display: flex; margin-bottom: 15px; }
    .post-input img { width: 40px; height: 40px; border-radius: 50%; margin-right: 10px; }
    .post-input input { flex: 1; padding: 10px 15px; border-radius: 20px; border: 1px solid #ddd; outline: none; }
    .post-input input::placeholder { color: #999; }
    .post-actions { display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #eee; }
    .post-actions-left { display: flex; gap: 10px; }
    .post-action { display: flex; align-items: center; padding: 8px 15px; border-radius: 8px; cursor: pointer; }
    .post-action:hover { background-color: #f0f0f0; }
    .post-action i { margin-right: 8px; color: #666; }
    .post-btn { padding: 8px 15px; background-color: #ef4444; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
    .post-btn:hover { background-color: #dc2626; }

    /* ===== VALIDATION UI ===== */
    .alert-error { background: #fff1f2; color: #991b1b; border: 1px solid #fecaca; padding: 12px 14px; border-radius: 8px; margin-bottom: 12px; }
    .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 12px 14px; border-radius: 8px; margin-bottom: 12px; }
    .field-error { color: #b91c1c; font-size: 12px; margin-top: 6px; margin-left: 50px; }

    /* ===== PHOTO PREVIEW STYLES ===== */
    .photo-preview { position: relative; margin: 15px 0; border-radius: 8px; overflow: hidden; background-color: #f8f9fa; border: 2px dashed #dee2e6; }
    .preview-image { width: 100%; max-height: 300px; object-fit: cover; display: block; }
    .remove-photo-btn { position: absolute; top: 10px; right: 10px; width: 30px; height: 30px; border-radius: 50%; background-color: rgba(0,0,0,0.7); color: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background-color 0.2s ease; }
    .remove-photo-btn:hover { background-color: rgba(0,0,0,0.9); }
    .remove-photo-btn .material-symbols-outlined { font-size: 18px; }

    /* ===== POST DISPLAY STYLES ===== */
    .post { background-color: white; border-radius: 16px; padding: 20px; box-shadow: var(--shadow-lg); border: 1px solid #e5e7eb; }
    .post-header { display: flex; align-items: center; margin-bottom: 10px; }
    .post-header img { width: 40px; height: 40px; border-radius: 50%; margin-right: 10px; }
    .post-user { font-weight: 600; margin-bottom: 2px; }
    .post-time { font-size: 12px; color: #777; }
    .post-content { margin-bottom: 15px; }
    .post-image { width: 100%; max-height: 400px; object-fit: cover; border-radius: 8px; margin-bottom: 15px; }
    #posts-feed { display: flex; flex-direction: column; gap: 24px; }

    /* ===== POST INTERACTIONS AND REACTIONS ===== */
    .post-stats { display: flex; justify-content: space-between; padding: 10px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee; margin-bottom: 10px; }
    .post-reactions { display: flex; align-items: center; }
    .post-reactions i.liked { color: #1877f2; }
    .reaction-count { margin-left: 5px; font-size: 14px; color: #666; }
    .reaction-count.liked { color: #1877f2; font-weight: 600; }
    .reaction-count:hover { text-decoration: underline; cursor: pointer; }
    .post-comments-share { font-size: 14px; color: #666; }
    .post-interactions { display: flex; justify-content: space-around; margin-top: 10px; }
    .interaction-btn { display: flex; align-items: center; padding: 8px 20px; border-radius: 8px; cursor: pointer; transition: all 0.2s ease; position: relative; }
    .interaction-btn:hover { background-color: #f0f2f5; transform: scale(1.02); }
    .interaction-btn:active { transform: scale(0.95); }
    .interaction-btn i { margin-right: 8px; color: #666; transition: all 0.2s ease; }
    .interaction-btn.liked { color: #1877f2; }
    .interaction-btn.liked i { color: #1877f2; }
    .interaction-btn.liked:hover { background-color: rgba(24, 119, 242, 0.1); }
    .material-symbols-outlined.filled { font-variation-settings: 'FILL' 1; }
    .like-animation { animation: likeAnimation 0.3s ease; }
    @keyframes likeAnimation { 0% { transform: scale(1); } 50% { transform: scale(1.2); } 100% { transform: scale(1); } }

    /* ===== COMMENTS ===== */
    .comments-section { margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; }
    .comments-list { margin-bottom: 15px; }
    .comment { display: flex; margin-bottom: 12px; }
    .comment-avatar { width: 32px; height: 32px; border-radius: 50%; margin-right: 8px; object-fit: cover; }
    .comment-content { flex: 1; }
    .comment-bubble { background-color: #f0f2f5; border-radius: 16px; padding: 8px 12px; display: inline-block; max-width: 100%; }
    .comment-user { font-weight: 600; font-size: 13px; margin-bottom: 2px; }
    .comment-text { font-size: 14px; line-height: 1.3; }
    .comment-time { font-size: 12px; color: #65676b; margin-top: 4px; margin-left: 12px; }
    .comment-input-section { display: flex; align-items: flex-start; gap: 8px; }
    .comment-input-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; }
    .comment-input-container { flex: 1; display: flex; align-items: center; background-color: #f0f2f5; border-radius: 20px; padding: 8px 12px; }
    .comment-input { flex: 1; border: none; background: transparent; outline: none; font-size: 14px; }
    .comment-input::placeholder { color: #65676b; }
    .comment-post-btn { background-color: #1877f2; color: white; border: none; border-radius: 16px; padding: 6px 12px; font-size: 13px; font-weight: 600; cursor: pointer; margin-left: 8px; }
    .comment-post-btn:hover { background-color: #166fe5; }
    .comment-post-btn:disabled { background-color: #e4e6ea; color: #bcc0c4; cursor: not-allowed; }

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

    /* Listings sidebar removed */

    /* ===== CHAT WINDOWS ===== */
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

    /* ===== RESPONSIVE ===== */
    @media (max-width: 1200px) { }
    @media (max-width: 992px) { .profile-container { transform: translateX(-100%); transition: transform 0.3s ease; width: var(--sidebar-expanded); } .profile-container.active { transform: translateX(0); } .main-content { margin-left: 0; margin-right: 0; } }
    
    /* ===== DARK MODE (Quick invert approach) ===== */
    body.dark {
      filter: invert(1) hue-rotate(180deg);
      background-color: #000;
    }
    /* Re-invert media so images/videos look normal */
    body.dark img,
    body.dark video,
    body.dark iframe,
    body.dark canvas { filter: invert(1) hue-rotate(180deg); }
    /* Avoid double-inverting icons inside buttons that use background images (none currently) */
    /* Optional: fine-tune borders that may look too bright */
    body.dark * { border-color: inherit; }
    /* In dark mode, keep layout identical to light mode for profile container and popover */
    body.dark .profile-container,
    body.dark #profile-menu-popover {
      /* Cancel global invert so visuals match light mode */
      filter: invert(1) hue-rotate(180deg);
    }
    /* Position the menu trigger at the bottom in dark mode */
    body.dark .profile-menu-trigger { margin-top: auto; }
    /* Inside canceled areas, avoid re-inverting media */
    body.dark .profile-container img,
    body.dark .profile-container video,
    body.dark .profile-container iframe,
    body.dark .profile-container canvas,
    body.dark #profile-menu-popover img,
    body.dark #profile-menu-popover video,
    body.dark #profile-menu-popover iframe,
    body.dark #profile-menu-popover canvas {
      filter: none;
    }
  </style>
</head>
<body>
  

  <!-- ===== PROFILE CONTAINER SIDEBAR ===== -->
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

  <!-- Popover moved OUTSIDE sidebar to avoid clipping and align beside trigger -->
  <div class="profile-menu-popover" id="profile-menu-popover">
    <div class="menu-item" data-href="#change-role"><span class="material-symbols-outlined">manage_accounts</span><span>Change Role</span></div>
    <div class="menu-item" data-href="settings.php"><span class="material-symbols-outlined">settings</span><span>Settings</span></div>
    <div class="menu-item" data-href="report.php"><span class="material-symbols-outlined">analytics</span><span>My Report</span></div>
    <div class="menu-item" data-href="#switch-appearance"><span class="material-symbols-outlined">dark_mode</span><span>Switch Appearance</span></div>
    <div class="menu-divider"></div>
    <div class="logout-action" id="logout-action"><span class="material-symbols-outlined">logout</span><span>Logout</span></div>
  </div>

  <!-- ===== MAIN CONTAINER AND CONTENT AREA ===== -->
  <div class="main-container">
    <div class="main-content">
      <!-- ===== POST CREATION BOX ===== -->
      <div class="post-box">
        <?php if (!empty($errors)): ?>
          <div class="alert-error">
            <ul style="padding-left: 18px;">
              <?php foreach ($errors as $e): ?>
                <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php elseif ($success): ?>
          <div class="alert-success">Your post passed validation. (Demo: not persisted.)</div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" action="homemain.php" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
          <div class="post-input">
            <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/0ec0940b-23d9-4b1f-8e72-2a8bd35584e4.png" alt="Your profile picture" />
            <input id="post-text" name="post_text" type="text" maxlength="500" placeholder="What's on your mind?" value="<?php echo htmlspecialchars($sticky_text, ENT_QUOTES, 'UTF-8'); ?>" />
          </div>

          <div id="photo-preview" class="photo-preview" style="display:none">
            <img id="preview-image" src="" alt="Preview" class="preview-image" />
            <button type="button" id="remove-photo" class="remove-photo-btn"><span class="material-symbols-outlined">close</span></button>
          </div>
          <div class="post-actions">
            <div class="post-actions-left">
              <div class="post-action" id="photo-action"><i class="material-symbols-outlined">photo_library</i><span>Photo/Video</span></div>
              <input type="file" id="photo-input" name="photo" accept="image/*" style="display:none" />
            </div>
            <button class="post-btn" id="post-button" type="submit">Post</button>
          </div>
        </form>
      </div>

      <!-- ===== POSTS FEED SECTION ===== -->
      <div id="posts-feed"></div>
    </div>
  </div>

  <!-- Floating buttons: reuse existing IDs for JS hooks -->
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
      <div class="notification-empty">No notifications yet</div>
    </div>
  </div>

  <!-- ===== CHAT POPUP ===== -->
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
    // ===== DATA =====
    const predefinedPosts = [
      {
        id: 'maria-post',
        user: 'Maria Santos',
        time: '2 hrs ago',
        avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/e9739672-d0ca-45ac-857b-5ee49303f563.png',
        text: 'Fresh batch of organic fertilizer available in Nueva Ecija! Perfect for rice fields. Made from chicken manure and rice hull compost. 50kg sacks available at ₱500 each. Message me for bulk orders!',
        image: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/0375c96d-d5b5-446c-8940-9e595ce18767.png',
        likes: 24,
        liked: false,
        comments: [
          { id: 1, user: 'Carlos Reyes', text: 'This looks great! Would love to buy some.', time: '1 hr ago', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/331059ac-f6bf-4684-b902-d37824bad8f5.png' },
          { id: 2, user: 'Ana Gonzales', text: 'How much for 10 sacks?', time: '45 min ago', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/ee4b5487-b1ea-40b6-9a76-0415e304de49.png' }
        ]
      },
      {
        id: 'pedro-post',
        user: 'Pedro Bautista',
        time: 'Yesterday at 3:45 PM',
        avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/1d3ad3bc-bb65-4c02-a8a1-15f40bee376e.png',
        text: 'Looking for livestock manure for my vegetable farm in Benguet. Preferably cattle or goat manure. Willing to trade with fresh vegetables or pay cash. Transport can be arranged.',
        image: '',
        likes: 15,
        liked: false,
        comments: [
          { id: 1, user: 'Maria Santos', text: 'I have cattle manure available. Let me know!', time: '30 min ago', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/e9739672-d0ca-45ac-857b-5ee49303f563.png' }
        ]
      },
      {
        id: 'carlos-post',
        user: 'Carlos Reyes',
        time: '3 hrs ago',
        avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/331059ac-f6bf-4684-b902-d37824bad8f5.png',
        text: 'Excellent quality goat manure from my farm in Laguna! Well-composted and ready for use. Perfect for vegetable gardens and fruit trees. Available in 25kg and 50kg sacks. Free delivery within 20km radius. Contact me for bulk orders!',
        image: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/f5a2c8d1-9e4b-4c3a-8f7e-1d2b3c4e5f6a.png',
        likes: 18,
        liked: false,
        comments: []
      },
      {
        id: 'ana-post',
        user: 'Ana Gonzales',
        time: '5 hrs ago',
        avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/ee4b5487-b1ea-40b6-9a76-0415e304de49.png',
        text: 'Starting a community composting program in our barangay! Looking for partners who can supply animal manure regularly. We\'ll process it into high-quality organic fertilizer and share the profits. This is a great opportunity for sustainable farming. Who\'s interested?',
        image: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/a8b9c1d2-3e4f-5g6h-7i8j-9k0l1m2n3o4p.png',
        likes: 12,
        liked: false,
        comments: [
          { id: 1, user: 'Mang Jose', text: "I can supply pig manure. Let's discuss!", time: '1 hr ago', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/70ed3f6c-a0ca-4490-8b6a-eaa245dffa33.png' },
          { id: 2, user: 'Lorna Lim', text: 'Great initiative! Supporting local farmers.', time: '45 min ago', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/2ab478de-5d6d-4dce-8c67-baf47fd7ad8e.png' }
        ]
      }
    ];

    const postLikers = {
      'maria-post': [
        { name: 'Carlos Reyes', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/331059ac-f6bf-4684-b902-d37824bad8f5.png' },
        { name: 'Ana Gonzales', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/ee4b5487-b1ea-40b6-9a76-0415e304de49.png' },
        { name: 'Lorna Lim', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/2ab478de-5d6d-4dce-8c67-baf47fd7ad8e.png' },
        { name: 'Mang Jose', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/70ed3f6c-a0ca-4490-8b6a-eaa245dffa33.png' }
      ],
      'pedro-post': [
        { name: 'Ana Gonzales', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/ee4b5487-b1ea-40b6-9a76-0415e304de49.png' },
        { name: 'Carlos Reyes', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/331059ac-f6bf-4684-b902-d37824bad8f5.png' },
        { name: 'Maria Santos', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/e9739672-d0ca-45ac-857b-5ee49303f563.png' }
      ],
      'carlos-post': [
        { name: 'Pedro Bautista', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/1d3ad3bc-bb65-4c02-a8a1-15f40bee376e.png' },
        { name: 'Maria Santos', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/e9739672-d0ca-45ac-857b-5ee49303f563.png' },
        { name: 'Ana Gonzales', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/ee4b5487-b1ea-40b6-9a76-0415e304de49.png' }
      ],
      'ana-post': [
        { name: 'Mang Jose', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/70ed3f6c-a0ca-4490-8b6a-eaa245dffa33.png' },
        { name: 'Lorna Lim', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/2ab478de-5d6d-4dce-8c67-baf47fd7ad8e.png' }
      ]
    };

    // ===== STATE =====
    const state = {
      posts: [...predefinedPosts],
      userPosts: [],
      showComments: {},
      commentInputs: {},
      notificationVisible: false,
      chatVisible: false,
      notificationStyle: { top: 0, right: 0 },
      chatStyle: { top: 0, right: 0 },
      conversations: [
        { id: 'mang-jose', name: 'Mang Jose', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/70ed3f6c-a0ca-4490-8b6a-eaa245dffa33.png', lastMessage: 'Hi, about the manure delivery tomorrow...', time: '2:30 PM', messages: [ { id: 1, text: 'Hi, about the manure delivery tomorrow...', sender: 'them', time: '2:30 PM' }, { id: 2, text: 'What time should I expect the delivery?', sender: 'them', time: '2:31 PM' } ] },
        { id: 'farmers-coop', name: 'Farmers Cooperative', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/250bc031-e5eb-4467-9d71-49154b992c13.png', lastMessage: 'New bulk order of organic fertilizer available', time: 'Yesterday', messages: [ { id: 1, text: 'New bulk order of organic fertilizer available', sender: 'them', time: 'Yesterday' }, { id: 2, text: 'Would you be interested in placing an order?', sender: 'them', time: 'Yesterday' } ] }
      ],
      openChatWindows: [],
      typingUsers: {},
      messageInputs: {},
      likePopup: { visible: false, postId: null, users: [] },
      previewSrc: '',
      showPhotoPreview: false,
    };

    // ===== HELPERS =====
    function $(sel, root=document){ return root.querySelector(sel); }
    function $all(sel, root=document){ return Array.from(root.querySelectorAll(sel)); }

    // ===== NAVIGATION BINDINGS =====
    $('#profile-link')?.addEventListener('click', () => { window.location.href = 'profile.php'; });
    $all('.profile-menu li').forEach(li => li.addEventListener('click', () => {
      const href = li.getAttribute('data-href');
      if (!href) return;
      window.location.href = href;
    }));
    $('#logout-btn')?.addEventListener('click', () => { window.location.href = 'homemain.php'; });

    // Bottom popup menu (outside profile sidebar; positioned beside trigger)
    (function(){
      const trigger = document.getElementById('profile-menu-trigger');
      const pop = document.getElementById('profile-menu-popover');
      const logout = document.getElementById('logout-action');
      if (!trigger || !pop) return;
      function position(){
        const r = trigger.getBoundingClientRect();
        const gap = 16;
        // Start to the right of the trigger
        let left = r.right + gap;
        const pc = document.querySelector('.profile-container');
        if (pc) {
          const cr = pc.getBoundingClientRect();
          // Ensure it's at least outside the sidebar
          left = Math.max(left, cr.right + 8);
        }
        const prevDisplay = pop.style.display;
        if (!pop.classList.contains('visible')) { pop.style.visibility='hidden'; pop.style.display='block'; }
        const pw = pop.offsetWidth || 260; const ph = pop.offsetHeight || 200;
        if (!pop.classList.contains('visible')) { pop.style.display = prevDisplay; pop.style.visibility=''; }
        const margin = 8;
        // Flip to the left of the trigger if overflowing right edge
        if (left + pw > window.innerWidth - margin) { left = Math.max(margin, r.left - gap - pw); }
        // Bottom-align the popover beside the floater trigger
        let top = r.bottom - ph;
        // Constrain within viewport
        top = Math.max(margin, Math.min(top, window.innerHeight - ph - margin));
        pop.style.top = Math.round(top)+'px';
        pop.style.left = Math.round(left)+'px';
      }
      function toggle(){ position(); pop.classList.toggle('visible'); }
      function hide(){ pop.classList.remove('visible'); }
      trigger.addEventListener('click', (e)=>{ e.stopPropagation(); toggle(); });
      document.addEventListener('click', (e)=>{ if (pop.classList.contains('visible') && !pop.contains(e.target) && e.target !== trigger) hide(); });
      document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape') hide(); });
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
      // Expose for other scripts (e.g., theme toggle) to reposition after UI changes
      window.__positionProfileMenu = position;
    })();

    // ===== NOTIFICATION & CHAT HEADER ICONS =====
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
        // If we're positioning the chat floater, align it vertically under the notification bell
        const anchorRect = (cont.id === 'header-chat-container' && notifIcon) ? notifIcon.getBoundingClientRect() : r;
        const top = anchorRect.bottom + 8;
        let left;
        // Center on viewport for both notification and chat floaters
        const centerLeft = (window.innerWidth - cw) / 2;
        const bias = 780; // same right nudge used for notification
        left = Math.max(margin, Math.min(window.innerWidth - cw - margin, centerLeft + bias));
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

    // ===== DARK MODE TOGGLE =====
    (function(){
      const STORAGE_KEY = 'theme';
      function applyTheme(theme){
        const useDark = theme === 'dark';
        document.body.classList.toggle('dark', useDark);
      }
      // Initial load: apply ONLY if user has an explicit saved choice
      const saved = localStorage.getItem(STORAGE_KEY);
      if (saved === 'dark' || saved === 'light') {
        applyTheme(saved);
      }
      // Toggle on click of the "Switch Appearance" menu item
      const switchItem = document.querySelector('.profile-menu-popover .menu-item[data-href="#switch-appearance"]');
      if (switchItem) {
        switchItem.addEventListener('click', (e)=>{
          e.stopPropagation();
          const willBeDark = !document.body.classList.contains('dark');
          document.body.classList.toggle('dark', willBeDark);
          localStorage.setItem(STORAGE_KEY, willBeDark ? 'dark' : 'light');
          // Keep the menu visible after switching
          const pop = document.getElementById('profile-menu-popover');
          if (pop) {
            pop.classList.add('visible');
            // Reposition beside the floater after theme/layout changes
            if (window.__positionProfileMenu) { window.__positionProfileMenu(); }
          }
        });
      }
    })();

    // ===== CHAT LIST RENDER =====
    function renderChatList() {
      const list = $('#chat-list-popup');
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

    // Quick open chat from active users
    $all('.active-user-popup').forEach(el => {
      el.addEventListener('click', () => {
        const user = JSON.parse(el.getAttribute('data-user'));
        openChatWindow({ ...user, messages: [] });
        chatContainer.classList.remove('visible');
      });
    });

    // ===== FLOATING CHAT WINDOWS =====
    function openChatWindow(user) {
      if (state.openChatWindows.find(c => c.id === user.id)) return;
      const existingConversation = state.conversations.find(c => c.id === user.id);
      const newChat = {
        id: user.id,
        name: user.name,
        avatar: user.avatar,
        messages: existingConversation?.messages || user.messages || [],
        isMinimized: false,
      };
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
          input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(chat.id, input.value); }
          });
          button.addEventListener('click', () => sendMessage(chat.id, input.value));
        }
        root.appendChild(container);
      });
    }

    // ===== POSTS RENDERING =====
    function renderPosts() {
      const feed = document.getElementById('posts-feed');
      feed.innerHTML = '';

      [...state.posts, ...state.userPosts].forEach((p) => {
        const post = document.createElement('div');
        post.className = 'post';
        post.dataset.postId = p.id;
        post.innerHTML = `
          <div class="post-header">
            <img src="${p.avatar}" alt="${p.user}" />
            <div>
              <div class="post-user">${p.user}</div>
              <div class="post-time">${p.time}</div>
            </div>
          </div>
          <div class="post-content">${p.text || ''}</div>
          ${p.image ? `<img src="${p.image}" alt="Post image" class="post-image" />` : ''}
          <div class="post-stats">
            <div class="post-reactions">
              <i class="material-symbols-outlined ${p.liked ? 'liked' : ''}">thumb_up</i>
              <span class="reaction-count ${p.liked ? 'liked' : ''}">${p.likes}</span>
            </div>
            <div class="post-comments-share">
              <span>${(p.comments || []).length} comments</span>
            </div>
          </div>
          <div class="post-interactions">
            <div class="interaction-btn like-btn ${p.liked ? 'liked' : ''}"><i class="material-symbols-outlined ${p.liked ? 'filled' : ''}">thumb_up</i><span>Like</span></div>
            <div class="interaction-btn comment-btn"><i class="material-symbols-outlined">chat_bubble</i><span>Comment</span></div>
          </div>
          <div class="comments-section" style="display:none">
            <div class="comments-list">
              ${(p.comments || []).map(c => `
                <div class="comment">
                  <img src="${c.avatar}" alt="${c.user}" class="comment-avatar" />
                  <div class="comment-content">
                    <div class="comment-bubble">
                      <div class="comment-user">${c.user}</div>
                      <div class="comment-text">${c.text}</div>
                    </div>
                    <div class="comment-time">${c.time}</div>
                  </div>
                </div>
              `).join('')}
            </div>
            <div class="comment-input-section">
              <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/70ed3f6c-a0ca-4490-8b6a-eaa245dffa33.png" alt="Your avatar" class="comment-input-avatar" />
              <div class="comment-input-container">
                <input type="text" class="comment-input" placeholder="Write a comment..." value="${state.commentInputs[p.id] || ''}" />
                <button class="comment-post-btn" ${!(state.commentInputs[p.id]||'').trim() ? 'disabled' : ''}>Post</button>
              </div>
            </div>
          </div>
        `;

        post.querySelector('.like-btn').addEventListener('click', () => {
          p.liked = !p.liked;
          p.likes = p.liked ? (p.likes + 1) : (p.likes - 1);
          renderPosts();
        });
        post.querySelector('.reaction-count').addEventListener('click', () => {});
        const commentsSection = post.querySelector('.comments-section');
        post.querySelector('.comment-btn').addEventListener('click', () => {
          const visible = commentsSection.style.display !== 'none';
          commentsSection.style.display = visible ? 'none' : 'block';
        });
        const commentInput = post.querySelector('.comment-input');
        const commentButton = post.querySelector('.comment-post-btn');
        commentInput.addEventListener('input', (e) => { state.commentInputs[p.id] = e.target.value; commentButton.disabled = !(state.commentInputs[p.id]||'').trim(); });
        commentInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') { e.preventDefault(); addComment(p.id); }});
        commentButton.addEventListener('click', () => addComment(p.id));

        const feed = document.getElementById('posts-feed');
        feed.appendChild(post);
      });
    }

    function addComment(postId) {
      const text = (state.commentInputs[postId] || '').trim();
      if (!text) return;
      const newComment = { id: Date.now(), user: 'Juan Dela Cruz', text, time: 'Just now', avatar: 'https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/70ed3f6c-a0ca-4490-8b6a-eaa245dffa33.png' };
      const post = state.posts.find(p => p.id === postId) || state.userPosts.find(p => p.id === postId);
      if (!post.comments) post.comments = [];
      post.comments.push(newComment);
      state.commentInputs[postId] = '';
      renderPosts();
    }

    // ===== POST CREATION (Client-side preview only; submission handled by PHP) =====
    const photoAction = document.getElementById('photo-action');
    const photoInput = document.getElementById('photo-input');
    const photoPreview = document.getElementById('photo-preview');
    const previewImage = document.getElementById('preview-image');
    const removePhotoBtn = document.getElementById('remove-photo');
    const postText = document.getElementById('post-text');

    photoAction.addEventListener('click', () => photoInput.click());
    photoInput.addEventListener('change', (e) => {
      const file = e.target.files && e.target.files[0];
      if (!file) return;
      if (!file.type.startsWith('image/')) { alert('Please select an image file.'); return; }
      const reader = new FileReader();
      reader.onload = (ev) => {
        const src = String(ev.target && ev.target.result || '');
        previewImage.src = src;
        photoPreview.style.display = 'block';
      };
      reader.readAsDataURL(file);
    });
    removePhotoBtn.addEventListener('click', () => {
      previewImage.src = '';
      photoPreview.style.display = 'none';
      photoInput.value = '';
    });

    // ===== INIT =====
    renderChatList();
    renderPosts();
  </script>
</body>
</html>
