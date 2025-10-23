<?php
session_start();
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf_token'];
$errors = [];$success=false;
$sticky = ['filter_type'=>'all','filter_status'=>'all','date_from'=>'','date_to'=>''];
$allowedTypes=['all','sales','purchases','exchanges','deliveries'];
$allowedStatus=['all','completed','pending','cancelled'];
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!isset($_POST['csrf_token'])||!hash_equals($_SESSION['csrf_token'],$_POST['csrf_token'])){$errors[]='Invalid request. Please refresh the page and try again.';}
  $sticky['filter_type']=(string)($_POST['filter_type']??'all');
  $sticky['filter_status']=(string)($_POST['filter_status']??'all');
  $sticky['date_from']=(string)($_POST['date_from']??'');
  $sticky['date_to']=(string)($_POST['date_to']??'');
  if(!in_array($sticky['filter_type'],$allowedTypes,true)){$errors[]='Invalid transaction type filter.';}
  if(!in_array($sticky['filter_status'],$allowedStatus,true)){$errors[]='Invalid status filter.';}
  $df=$sticky['date_from']!==''?DateTime::createFromFormat('Y-m-d',$sticky['date_from']):null;
  if($sticky['date_from']!==''&&(!$df||$df->format('Y-m-d')!==$sticky['date_from'])){$errors[]='Invalid From date. Use YYYY-MM-DD.';}
  $dt=$sticky['date_to']!==''?DateTime::createFromFormat('Y-m-d',$sticky['date_to']):null;
  if($sticky['date_to']!==''&&(!$dt||$dt->format('Y-m-d')!==$sticky['date_to'])){$errors[]='Invalid To date. Use YYYY-MM-DD.';}
  if($df&&$dt&&$df>$dt){$errors[]='From date must be earlier than or equal to To date.';}
  if(empty($errors)){$success=true; $_SESSION['csrf_token']=bin2hex(random_bytes(32)); $csrf=$_SESSION['csrf_token'];}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Transaction History - AgriLink</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
  <style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Inter','Segoe UI',system-ui,-apple-system,sans-serif}
    :root{--brand:#047857;--brand-light:#059669;--brand-dark:#065f46;--bg:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 50%,#f0f2f5 100%);--surface:#fff;--surface-hover:#f1f5f9;--text:#0f172a;--shadow-sm:0 1px 2px rgba(0,0,0,.05);--radius-sm:8px;--sidebar-expanded:280px;--sidebar-collapsed:80px}
    body{margin:0;padding:60px 0 0;background:var(--bg);color:var(--text);line-height:1.6;overflow-x:hidden;min-height:100vh}
    .profile-container{position:fixed;left:0;top:60px;bottom:0;width:var(--sidebar-expanded);background:var(--surface);padding:20px;z-index:90;box-shadow:var(--shadow-sm);overflow-y:auto;transition:width .3s;display:flex;flex-direction:column}
    .profile-container:hover{width:var(--sidebar-expanded)}.profile-container:hover{overflow-y:auto}
    .profile-header{display:flex;align-items:center;margin-bottom:20px}.profile-pic{width:50px;height:50px;border-radius:50%;object-fit:cover;margin-right:10px;cursor:pointer}.profile-name{font-weight:600}
    .profile-container .profile-name,.profile-container .profile-menu li span,.profile-container .logout-btn span{display:inline}
    .profile-container:hover .profile-name,.profile-container:hover .profile-menu li span,.profile-container:hover .logout-btn span{display:inline}
    .profile-menu{list-style:none}.profile-menu li{padding:10px;margin:5px 0;border-radius:var(--radius-sm);display:flex;align-items:center;cursor:pointer}
    .profile-menu li:hover{background:var(--surface-hover)}.profile-menu li i{margin-right:10px;color:#FF9100}
    .bottom-menu{margin-top:auto}
    .header-container{position:fixed;top:0;left:0;right:0;height:60px;background:#FF9100;display:flex;align-items:center;padding:0 20px;z-index:100;box-shadow:0 2px 4px rgba(0,0,0,.1)}
    .header-left{flex:1;display:flex;align-items:center;gap:20px}.header-center{flex:2;display:flex;justify-content:center;align-items:center}.header-right{flex:1;display:flex;justify-content:flex-end;align-items:center}
    .logo{font-size:24px;font-weight:bold;color:white;text-decoration:none}
    .search-container{display:flex;align-items:center;background:rgba(255,255,255,.2);border-radius:12px;padding:8px 16px;gap:12px}
    .search-container input{background:transparent;border:none;color:white;outline:none;width:250px;font-size:14px}
    .nav-icons{display:flex;gap:20px}.nav-icons .icon{width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;justify-content:center;align-items:center;color:white;cursor:pointer;text-decoration:none}
    .main-container{display:flex;min-height:100vh}.main-content{margin:0 320px 20px calc(var(--sidebar-expanded) + 20px);padding-top:20px;flex:1;display:flex;flex-direction:column;gap:20px}
    .notification-container{position:fixed;width:350px;background:#fff;border-radius:8px;box-shadow:0 5px 15px rgba(0,0,0,.2);z-index:100;display:none;transition:opacity .2s ease}
    .notification-container.visible{display:block}
    .notification-header{display:flex;justify-content:space-between;align-items:center;padding:15px;border-bottom:1px solid #eee}
    .notification-header h3{margin:0;font-size:18px;color:#333}
    #close-notification{cursor:pointer;color:#999}
    #close-notification:hover{color:#333}
    .notification-list{max-height:400px;overflow-y:auto}
    .notification-item{padding:15px;border-bottom:1px solid #eee;cursor:pointer;transition:background-color .2s}
    .notification-item:hover{background-color:#f5f5f5}
    .notification-content{display:flex;flex-direction:column}
    .notification-text{font-size:14px;color:#333;margin-bottom:5px}
    .notification-text strong{color:#047857}
    .notification-time{font-size:12px;color:#999}
    .notification-list::-webkit-scrollbar{width:6px}
    .notification-list::-webkit-scrollbar-track{background:#f1f1f1}
    .notification-list::-webkit-scrollbar-thumb{background:#c1c1c1;border-radius:3px}
    .notification-list::-webkit-scrollbar-thumb:hover{background:#a1a1a1}

    .header-chat-container{position:fixed;width:320px;background:#fff;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.12);z-index:100;display:none;transition:all .3s ease;max-height:450px;border:1px solid #e4e6ea}
    .header-chat-container.visible{display:block;opacity:1;transform:translateY(0)}
    .chat-header-popup{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid #e4e6ea;background-color:#f8f9fa;border-radius:12px 12px 0 0}
    .chat-header-popup h3{margin:0;font-size:18px;color:#1c1e21;font-weight:700}
    #close-chat{cursor:pointer;color:#8a8d91;font-size:20px;padding:4px;border-radius:50%;transition:all .2s ease}
    #close-chat:hover{color:#1c1e21;background-color:#e4e6ea}
    .active-users-popup{padding:16px 20px 12px; border-bottom: 1px solid #e4e6ea}
    .active-title-popup{font-weight:600;font-size:14px;color:#047857;margin-bottom:12px}
    .active-list-popup{display:flex;gap:12px;overflow-x:auto;padding-bottom:8px}
    .active-list-popup::-webkit-scrollbar{height:6px}
    .active-list-popup::-webkit-scrollbar-thumb{background:#e4e6ea;border-radius:3px}
    .active-user-popup{display:flex;flex-direction:column;align-items:center;gap:6px;min-width:54px;cursor:pointer}
    .user-status-popup{position:relative;width:44px;height:44px}
    .user-status-popup img{width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.08)}
    .status-indicator-popup{position:absolute;bottom:0;right:0;width:12px;height:12px;background:#31a24c;border:2px solid #fff;border-radius:50%}
    .name-popup{font-size:12px;color:#1c1e21}
    .conversations-title-popup{font-weight:600;font-size:14px;color:#047857;padding:12px 20px 8px}
    .chat-list-popup{max-height:280px;overflow-y:auto}
    .chat-list-popup::-webkit-scrollbar{width:6px}
    .chat-list-popup::-webkit-scrollbar-thumb{background:#e4e6ea;border-radius:3px}
    .listings-container{position:fixed;right:0;top:60px;bottom:0;width:300px;background:#fff;padding:20px;overflow-y:auto;z-index:90;box-shadow:-1px 0 5px rgba(0,0,0,.1)}
    .listings-header{display:flex;justify-content:flex-start;margin-bottom:15px}
    .listings-header h3{margin:0;font-size:18px;color:#047857;font-weight:600}
    .listings-list{display:flex;flex-direction:column;gap:15px}
    .listing-item{display:flex;align-items:center;padding:12px;border-radius:8px;cursor:pointer;transition:background-color .2s;border:1px solid #e4e6ea}
    .listing-item:hover{background-color:#f0f2f5;border-color:#047857}
    .listing-image{width:60px;height:60px;border-radius:8px;object-fit:cover;margin-right:12px}
    .listing-info{flex:1}
    .listing-title{font-weight:600;font-size:14px;color:#1c1e21;margin-bottom:4px}
    .listing-price{font-size:13px;color:#047857;font-weight:600;margin-bottom:2px}
    .listing-weight{font-size:12px;color:#65676b;margin-bottom:2px}
    .listing-description{font-size:11px;color:#65676b;line-height:1.3;margin-top:2px}
    .listings-container::-webkit-scrollbar{width:6px}
    .listings-container::-webkit-scrollbar-track{background:#f1f1f1}
    .listings-container::-webkit-scrollbar-thumb{background:#c1c1c1;border-radius:3px}
    .listings-container::-webkit-scrollbar-thumb:hover{background:#a1a1a1}
    .page-header{background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 2px rgba(0,0,0,.06);margin-bottom:12px}
    .filter-controls{background:#fff;border-radius:12px;padding:12px;box-shadow:0 1px 2px rgba(0,0,0,.06);margin-bottom:16px}
    .filter-row{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end}.filter-group{display:flex;flex-direction:column;gap:6px}
    .filter-select,.date-input{padding:8px 10px;border:1px solid #e5e7eb;border-radius:8px}
    .stats-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin:12px 0}
    .stat-card{background:#fff;border-radius:12px;padding:16px;text-align:center;box-shadow:0 1px 2px rgba(0,0,0,.06)}.stat-value{font-weight:800;color:var(--brand)}
    .transaction-list{background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 2px rgba(0,0,0,.06)}
    .action-button{border:1px solid #e5e7eb;background:#fff;border-radius:8px;padding:8px 12px;cursor:pointer}
    .empty-state{text-align:center;color:#64748b;padding:24px 0}
    .alert-error{background:#fff1f2;color:#991b1b;border:1px solid #fecaca;padding:12px 14px;border-radius:8px;margin-bottom:12px}
    .alert-success{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;padding:12px 14px;border-radius:8px;margin-bottom:12px}
  </style>
</head>
<body>
  <div class="header-container">
    <div class="header-left">
      <a href="homemain.php" class="logo">AgriLink</a>
      <div class="search-container"><span class="material-symbols-outlined">search</span><input type="text" placeholder="Search Agri" /></div>
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

  <div class="profile-container">
    <div class="profile-header">
      <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/0ec0940b-23d9-4b1f-8e72-2a8bd35584e4.png" alt="profile" class="profile-pic" id="profile-link" />
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

  <div class="main-container">
    <div class="main-content">
      <div class="filter-controls">
        <?php if(!empty($errors)):?>
          <div class="alert-error"><ul style="padding-left:18px;">
            <?php foreach($errors as $e):?><li><?php echo htmlspecialchars($e,ENT_QUOTES,'UTF-8');?></li><?php endforeach;?>
          </ul></div>
        <?php elseif($success):?>
          <div class="alert-success">Filters validated and applied. (Demo)</div>
        <?php endif; ?>

        <form method="post" action="historyandtransaction.php" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf,ENT_QUOTES,'UTF-8');?>" />
          <div class="filter-row">
            <div class="filter-group">
              <label class="filter-label">All Transactions</label>
              <select id="filter-type" name="filter_type" class="filter-select">
                <option value="all" <?php echo $sticky['filter_type']==='all'?'selected':'';?>>All Transactions</option>
                <option value="sales" <?php echo $sticky['filter_type']==='sales'?'selected':'';?>>Sales</option>
                <option value="purchases" <?php echo $sticky['filter_type']==='purchases'?'selected':'';?>>Purchases</option>
                <option value="exchanges" <?php echo $sticky['filter_type']==='exchanges'?'selected':'';?>>Exchanges</option>
                <option value="deliveries" <?php echo $sticky['filter_type']==='deliveries'?'selected':'';?>>Deliveries</option>
              </select>
            </div>
            <div class="filter-group">
              <label class="filter-label">Filter by Status</label>
              <select id="filter-status" name="filter_status" class="filter-select">
                <option value="all" <?php echo $sticky['filter_status']==='all'?'selected':'';?>>All Status</option>
                <option value="completed" <?php echo $sticky['filter_status']==='completed'?'selected':'';?>>Completed</option>
                <option value="pending" <?php echo $sticky['filter_status']==='pending'?'selected':'';?>>Pending</option>
                <option value="cancelled" <?php echo $sticky['filter_status']==='cancelled'?'selected':'';?>>Cancelled</option>
              </select>
            </div>
            <div class="filter-group">
              <label class="filter-label">Date Range - From</label>
              <input id="date-from" name="date_from" type="date" class="date-input" value="<?php echo htmlspecialchars($sticky['date_from'],ENT_QUOTES,'UTF-8');?>" />
            </div>
            <div class="filter-group">
              <label class="filter-label">Date Range - To</label>
              <input id="date-to" name="date_to" type="date" class="date-input" value="<?php echo htmlspecialchars($sticky['date_to'],ENT_QUOTES,'UTF-8');?>" />
            </div>
            <div class="filter-group">
              <button type="submit" class="action-button"><span class="material-symbols-outlined" style="font-size:16px">check</span>&nbsp;Apply</button>
            </div>
          </div>
        </form>
      </div>

      <div class="page-header"><h1 class="page-title">Transaction History</h1><p class="page-subtitle">Track all your agricultural waste exchange activities</p></div>

      <div class="stats-grid">
        <div class="stat-card"><div id="stat-total" class="stat-value">0</div><div class="stat-label">Total Transactions</div></div>
        <div class="stat-card"><div id="stat-earned" class="stat-value">₱0</div><div class="stat-label">Total Earnings</div></div>
        <div class="stat-card"><div id="stat-spent" class="stat-value">₱0</div><div class="stat-label">Total Spent</div></div>
      </div>

      <div class="transaction-list">
        <div class="list-header">
          <h3 class="list-title">Recent Transactions</h3>
          <div class="list-actions">
            <button class="action-button" id="btn-export"><span class="material-symbols-outlined" style="font-size:16px">download</span>&nbsp;Export</button>
            <button class="action-button" id="btn-sort"><span class="material-symbols-outlined" style="font-size:16px">filter_list</span>&nbsp;Sort</button>
          </div>
        </div>
        <div id="transactions-container"></div>
        <div id="transactions-empty" class="empty-state" style="display:none">
          <span class="material-symbols-outlined">search_off</span>
          <h3>No transactions found</h3>
          <p>Try adjusting your filters to see more results.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="notification-container" id="notification-container" style="position:fixed; top:0; right:0">
    <div class="notification-header"><h3>Notifications</h3><i class="material-symbols-outlined" id="close-notification">close</i></div>
    <div class="notification-list">
      <div class="notification-item"><div class="notification-content"><div class="notification-text"><strong>Maria Santos</strong> commented on your post: "This looks great! Would love to buy some."</div><div class="notification-time">2 hrs ago</div></div></div>
      <div class="notification-item"><div class="notification-content"><div class="notification-text"><strong>Pedro Bautista</strong> liked your post about organic fertilizer.</div><div class="notification-time">5 hrs ago</div></div></div>
      <div class="notification-item"><div class="notification-content"><div class="notification-text"><strong>AgriTech PH</strong> shared a new article: "Benefits of Organic Waste in Farming"</div><div class="notification-time">1 day ago</div></div></div>
      <div class="notification-item"><div class="notification-content"><div class="notification-text"><strong>Farmers Cooperative</strong> added a new listing for compost materials.</div><div class="notification-time">2 days ago</div></div></div>
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

  <div class="listings-container"><div class="listings-header"><h3>Current Listings</h3></div><div class="listings-list" id="listings-list"></div></div>
  <div id="floating-chats-root"></div>

  <script>
  (function(){
    const $=(s,r=document)=>r.querySelector(s); const $$=(s,r=document)=>Array.from(r.querySelectorAll(s));
    // Nav handlers (match homemain.php)
    const profileLink = $('#profile-link');
    profileLink && profileLink.addEventListener('click', () => { window.location.href = 'profile.php'; });
    $$('.profile-menu li').forEach(li => li.addEventListener('click', () => {
      const href = li.getAttribute('data-href');
      if (href) window.location.href = href;
    }));
    const logoutBtn = $('#logout-btn');
    logoutBtn && logoutBtn.addEventListener('click', () => { window.location.href = 'homemain.php'; });
    const transactions=[
      {id:1,type:'sale',status:'completed',date:'2024-10-01',title:'Sold 50kg Manure',amount:2500},
      {id:2,type:'purchase',status:'pending',date:'2024-10-03',title:'Bought 30kg Compost',amount:1800},
      {id:3,type:'delivery',status:'completed',date:'2024-10-05',title:'Delivered to Barangay 5',amount:0},
    ];
    function peso(n){return '₱'+Number(n||0).toLocaleString(undefined,{minimumFractionDigits:0});}
    function renderStats(list){const total=list.length;const earned=list.filter(t=>t.type==='sale').reduce((s,t)=>s+(t.amount||0),0);const spent=list.filter(t=>t.type==='purchase').reduce((s,t)=>s+(t.amount||0),0);$('#stat-total').textContent=String(total);$('#stat-earned').textContent=peso(earned);$('#stat-spent').textContent=peso(spent);}    
    function renderTransactions(list){const root=$('#transactions-container');const empty=$('#transactions-empty');if(!root||!empty)return; if(list.length===0){root.innerHTML='';empty.style.display='block';return;} empty.style.display='none'; root.innerHTML=list.map(t=>`<div class="transaction-item" style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #eef2f7;"><div><div style="font-weight:700;">${t.title}</div><div style="font-size:12px;color:#64748b;">${t.date} • ${t.type} • ${t.status}</div></div><div style="font-weight:700;color:#047857;">${t.amount?peso(t.amount):''}</div></div>`).join('');}
    function applyFilters(){const type=$('#filter-type')?.value||'all';const status=$('#filter-status')?.value||'all';const from=$('#date-from')?.value;const to=$('#date-to')?.value;let list=[...transactions];const map={sales:'sale',purchases:'purchase',exchanges:'exchange',deliveries:'delivery'}; if(type!=='all'){list=list.filter(t=>map[type]?t.type===map[type]:true);} if(status!=='all'){list=list.filter(t=>t.status===status);} if(from){list=list.filter(t=>t.date>=from);} if(to){list=list.filter(t=>t.date<=to);} renderStats(list);renderTransactions(list);}    
    ['filter-type','filter-status','date-from','date-to'].forEach(id=>{const el=document.getElementById(id); if(el) el.addEventListener('change',applyFilters);});
    (function(){
      const el=$('#listings-list');
      if(!el)return;
      const items=[
        {title:'Cattle manure',price:'₱50.00',weight:'500kg available',description:'Well-aged cattle manure, excellent for fertilizer', photo:null},
        {title:'compost',price:'₱75.00',weight:'300kg available',description:'Organic compost from mixed livestock waste', photo:null},
      ];
      el.innerHTML = items.map(s=>`
        <div class="listing-item">
          ${s.photo ? `<img class="listing-image" src="${s.photo}" alt="${s.title}" />` : ''}
          <div class="listing-info">
            <div class="listing-title">${s.title}</div>
            <div class="listing-price">${s.price}</div>
            <div class="listing-weight">${s.weight}</div>
            <div class="listing-description">${s.description}</div>
          </div>
        </div>
      `).join('');
    })();
    applyFilters();
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
      const closeN = $('#close-notification');
      const closeC = $('#close-chat');
      closeN && closeN.addEventListener('click', ()=> notifCont.classList.remove('visible'));
      closeC && closeC.addEventListener('click', ()=> chatCont.classList.remove('visible'));
      document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape') hideAll(); });
    })();
  })();
  </script>
</body>
</html>
