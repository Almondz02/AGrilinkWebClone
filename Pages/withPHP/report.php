<?php
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
    :root { --brand:#047857; --brand-light:#059669; --brand-dark:#065f46; --bg:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 50%,#f0f2f5 100%); --surface:#ffffff; --surface-2:#f8fafc; --surface-hover:#f1f5f9; --text:#0f172a; --text-muted:#64748b; --text-light:#94a3b8; --border:#e2e8f0; --border-light:#f1f5f9; --ring:rgba(4,120,87,0.12); --shadow-sm:0 1px 2px rgba(0,0,0,0.05); --shadow-md:0 4px 6px -1px rgba(0,0,0,0.1),0 2px 4px -1px rgba(0,0,0,0.06); --shadow-lg:0 10px 15px -3px rgba(0,0,0,0.1),0 4px 6px -2px rgba(0,0,0,0.05); --radius:12px; --radius-sm:8px; --sidebar-expanded:280px; --sidebar-collapsed:80px; }
    body { background: var(--bg); color: var(--text); line-height: 1.6; overflow-x: hidden; min-height: 100vh; padding-top: 60px; }
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

    .profile-container { position:fixed; left:0; top:60px; bottom:0; width:var(--sidebar-expanded); background:#fff; padding:20px; z-index:90; box-shadow:1px 0 5px rgba(0,0,0,0.1); overflow-y:auto; transition: width .3s ease; display:flex; flex-direction:column; }
    .profile-header { display:flex; align-items:center; margin-bottom:20px; }
    .profile-pic { width:50px; height:50px; border-radius:50%; object-fit:cover; margin-right:10px; cursor:pointer; }
    .profile-name { font-weight:600; }
    .profile-menu { list-style:none; }
    .profile-menu li { padding:10px; margin:5px 0; border-radius:8px; display:flex; align-items:center; cursor:pointer; }
    .profile-menu li:hover { background:#f0f2f5; }
    .profile-menu li i { margin-right:10px; color:#FF9100; }
    .bottom-menu { margin-top:auto; }
    .profile-container .profile-name, .profile-container .profile-menu li span, .profile-container .logout-btn span { display: inline; }

    .main-content { margin-left: calc(var(--sidebar-expanded) + 20px); padding: 20px 24px 24px; transition: margin-left .3s ease; }
    .report-container { max-width: 900px; margin: 0 auto; }
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

    @media (max-width: 992px) { .main-content { margin-left: 0; } .profile-container { transform: translateX(-100%); transition: transform .3s; width: var(--sidebar-expanded); } .profile-container.active { transform: translateX(0); } }
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
    </ul>
    <ul class="profile-menu bottom-menu">
      <li data-href="#change-role"><i class="material-symbols-outlined">manage_accounts</i><span>Change Role</span></li>
      <li data-href="settings.php"><i class="material-symbols-outlined">settings</i><span>Settings</span></li>
      <li data-href="report.php"><i class="material-symbols-outlined">analytics</i><span>My Report</span></li>
      <li data-href="#switch-appearance"><i class="material-symbols-outlined">dark_mode</i><span>Switch Appearance</span></li>
    </ul>
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

  <script>
    function $(sel, root=document){ return root.querySelector(sel); }
    function $all(sel, root=document){ return Array.from(root.querySelectorAll(sel)); }
    function showToast(msg){ const t = $('#toast'); if(!t) return; t.textContent = msg; t.style.display='block'; setTimeout(()=>{ t.style.display='none'; }, 4000); }

    // Sidebar nav
    $('#profile-link')?.addEventListener('click', ()=>{ window.location.href = 'profile.php'; });
    $all('.profile-menu li').forEach(li=> li.addEventListener('click', ()=>{ const href=li.getAttribute('data-href'); if(href) window.location.href = href; }));
    $('#logout-btn')?.addEventListener('click', ()=>{ window.location.href = 'homemain.php'; });

    // If server signaled success, also show a toast for UX
    <?php if ($success): ?>
    showToast('Report submitted successfully!');
    <?php endif; ?>

    // Header popups retained from HTML version would go here if needed
  </script>
</body>
</html>
