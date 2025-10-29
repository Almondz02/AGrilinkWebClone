<?php
if (!function_exists('render_profile_container')) {
  function render_profile_container($options = []) {
    static $emitted = false;
    $brand = isset($options['brand']) ? $options['brand'] : 'Agrilink';
    $menu = isset($options['menu']) ? $options['menu'] : [
      ['href' => 'homemain.php', 'icon' => 'home', 'label' => 'Home'],
      ['href' => 'listing.php', 'icon' => 'storefront', 'label' => 'Listings'],
      ['href' => 'historyandtransaction.php', 'icon' => 'receipt_long', 'label' => 'Listing History'],
      ['href' => 'profile.php', 'icon' => 'person', 'label' => 'Profile'],
    ];
    if (!$emitted) {
      $emitted = true;
      echo '<style>
:root{ --sidebar-expanded: 280px; --surface:#ffffff; --surface-hover:#f8fafc; --border:#e5e7eb; --radius-sm:10px; --shadow-sm:0 1px 2px rgba(0,0,0,0.06); --shadow-lg:0 10px 25px rgba(0,0,0,0.1); }
.profile-container{ position:fixed; left:0; top:0; bottom:0; width:var(--sidebar-expanded) !important; background-color:var(--surface); padding:20px; z-index:90; box-shadow:var(--shadow-sm); overflow-y:auto; transition:width .3s ease; display:flex; flex-direction:column; font-family: "Inter", "Segoe UI", system-ui, -apple-system, sans-serif; }
.profile-container *{ font-family: inherit; }
.profile-header{ display:flex; align-items:center; margin-bottom:20px; }
.profile-pic{ width:50px; height:50px; border-radius:50%; object-fit:cover; margin-right:10px; cursor:pointer; }
.profile-name{ font-weight:600; }
.profile-container .profile-name,
.profile-container .profile-menu li span,
.profile-container .logout-btn span{ display:inline; }
.profile-menu{ list-style:none; padding:0; margin:0; }
.profile-menu li{ padding:14px 16px; margin:8px 0; border-radius:var(--radius-sm); display:flex; align-items:center; cursor:pointer; font-size:18px; }
.profile-menu li:hover{ background-color:var(--surface-hover); }
.profile-menu li i{ margin-right:14px; color:#FF9100; font-size:26px; }
.bottom-menu{ margin-top:auto; }
.sidebar-brand{ font-weight:800; color:#FF9100; margin:0 0 16px; font-size:28px; background:transparent; padding:0; border-radius:0; display:block; text-align:center; }
.sidebar-search{ display:flex; align-items:center; gap:10px; background:#f1f5f9; border:1px solid var(--border); border-radius:12px; padding:10px 12px; margin:6px 0 10px; }
.sidebar-search .material-symbols-outlined{ color:#64748b; font-size:22px; }
.sidebar-search input{ flex:1; border:none; outline:none; background:transparent; font-size:16px; color:#0f172a; }
.sidebar-search input::placeholder{ color:#94a3b8; }
.profile-menu-trigger{ margin-top:auto; display:inline-flex; align-items:center; gap:8px; border:1px solid var(--border); background:#fff; color:#111827; padding:10px 14px; border-radius:10px; cursor:pointer; box-shadow:var(--shadow-sm); }
.profile-menu-trigger .material-symbols-outlined{ color:#0f172a; }
.profile-menu-popover{ position:fixed; width:260px; background:#fff; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.15); border:1px solid #e5e7eb; padding:10px 0; display:none; z-index:120; max-height:calc(100vh - 16px); overflow-y:auto; }
.profile-menu-popover.visible{ display:block; }
.profile-menu-popover .menu-item{ display:flex; align-items:center; gap:12px; padding:10px 16px; color:#0f172a; cursor:pointer; }
.profile-menu-popover .menu-item:hover{ background:#f8fafc; }
.profile-menu-popover .menu-item .material-symbols-outlined{ color:#475569; }
.profile-menu-popover .menu-divider{ height:1px; background:#f1f5f9; margin:6px 0; }
.profile-menu-popover .logout-action{ margin:8px 12px 4px; padding:10px 14px; background:#ef4444; color:#fff; border-radius:10px; display:flex; align-items:center; gap:10px; font-weight:600; justify-content:center; }
.profile-menu-popover .logout-action .material-symbols-outlined{ color:#fff; }
/* Backdrop floater shown when menu is open */
.profile-menu-backdrop{ position:fixed; inset:0; background:rgba(0,0,0,0.2); backdrop-filter:saturate(120%) blur(0px); display:none; z-index:110; }
.profile-menu-backdrop.visible{ display:block; }
@media (max-width: 992px){ .profile-container{ transform:translateX(-100%); transition:transform .3s ease; width:var(--sidebar-expanded); } .profile-container.active{ transform:translateX(0); } }
</style>';
      echo '<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400" rel="stylesheet">';
      echo '<script>
(function(){
  function qs(s,r){return (r||document).querySelector(s)}
  function qsa(s,r){return Array.prototype.slice.call((r||document).querySelectorAll(s))}
  var trigger, pop, logoutBtn, bd;
  function position(){
    if(!trigger||!pop) return;
    var r = trigger.getBoundingClientRect();
    var gap = 16; var left = r.right + gap;
    var pc = qs(".profile-container");
    if(pc){ var cr = pc.getBoundingClientRect(); left = Math.max(left, cr.right + 8); }
    var prevDisplay = pop.style.display;
    if(!pop.classList.contains("visible")){ pop.style.visibility="hidden"; pop.style.display="block"; }
    var pw = pop.offsetWidth || 260; var ph = pop.offsetHeight || 200;
    if(!pop.classList.contains("visible")){ pop.style.display = prevDisplay; pop.style.visibility=""; }
    var margin = 8;
    if(left + pw > window.innerWidth - margin){ left = Math.max(margin, r.left - gap - pw); }
    var top = r.bottom - ph;
    top = Math.max(margin, Math.min(top, window.innerHeight - ph - margin));
    pop.style.top = Math.round(top) + "px";
    pop.style.left = Math.round(left) + "px";
  }
  function toggle(){ position(); pop.classList.toggle("visible"); }
  function hide(){ pop.classList.remove("visible"); if(bd) bd.classList.remove("visible"); }
  function bind(){
    trigger = document.getElementById("profile-menu-trigger");
    pop = document.getElementById("profile-menu-popover");
    logoutBtn = document.getElementById("logout-action");
    bd = document.getElementById("profile-menu-backdrop");
    if(!trigger||!pop) return;
    trigger.addEventListener("click", function(e){ e.stopPropagation(); position(); pop.classList.toggle("visible"); if(bd) bd.classList.toggle("visible"); });
    document.addEventListener("click", function(e){ if(pop.classList.contains("visible") && !pop.contains(e.target) && e.target !== trigger) hide(); });
    document.addEventListener("keydown", function(e){ if(e.key === "Escape") hide(); });
    if(bd){ bd.addEventListener("click", hide); }
    // Sidebar list items navigation
    qsa(".profile-menu li").forEach(function(li){ li.addEventListener("click", function(){ var href = li.getAttribute("data-href"); if(href) window.location.href = href; }); });
    // Popover menu items navigation (e.g., Settings, My Report)
    if(pop){
      qsa(".menu-item", pop).forEach(function(item){
        item.addEventListener("click", function(){
          var href = item.getAttribute("data-href");
          if(!href) return;
          if(href.charAt(0) === "#"){ hide(); return; }
          hide();
          window.location.href = href;
        });
      });
    }
    if(logoutBtn){ logoutBtn.addEventListener("click", function(){ window.location.href = "homemain.php"; }); }
    window.addEventListener("resize", function(){ if(pop.classList.contains("visible")) position(); });
    window.addEventListener("scroll", function(){ if(pop.classList.contains("visible")) position(); }, { passive:true });
    window.__positionProfileMenu = position;
  }
  if(document.readyState === "loading"){ document.addEventListener("DOMContentLoaded", bind); } else { bind(); }
})();
</script>';
    }

    echo '<div class="profile-container">';
    echo '<div class="sidebar-brand">'.htmlspecialchars($brand, ENT_QUOTES, 'UTF-8').'</div>';
    echo '<ul class="profile-menu">';
    $first = array_slice($menu, 0, 1);
    foreach ($first as $it) {
      echo '<li data-href="'.htmlspecialchars($it['href'], ENT_QUOTES, 'UTF-8').'"><i class="material-symbols-outlined">'.htmlspecialchars($it['icon'], ENT_QUOTES, 'UTF-8').'</i><span>'.htmlspecialchars($it['label'], ENT_QUOTES, 'UTF-8').'</span></li>';
    }
    echo '</ul>';
    echo '<div class="sidebar-search">';
    echo '<span class="material-symbols-outlined">search</span>';
    echo '<input type="text" id="sidebar-search-input" placeholder="Search" />';
    echo '</div>';
    echo '<ul class="profile-menu">';
    $rest = array_slice($menu, 1);
    foreach ($rest as $it) {
      echo '<li data-href="'.htmlspecialchars($it['href'], ENT_QUOTES, 'UTF-8').'"><i class="material-symbols-outlined">'.htmlspecialchars($it['icon'], ENT_QUOTES, 'UTF-8').'</i><span>'.htmlspecialchars($it['label'], ENT_QUOTES, 'UTF-8').'</span></li>';
    }
    echo '</ul>';
    echo '<button class="profile-menu-trigger" id="profile-menu-trigger" type="button">';
    echo '<span class="material-symbols-outlined">menu</span><span>Menu</span>';
    echo '</button>';
    echo '</div>';

    echo '<div class="profile-menu-popover" id="profile-menu-popover">';
    echo '<div class="menu-item" data-href="#change-role"><span class="material-symbols-outlined">manage_accounts</span><span>Change Role</span></div>';
    echo '<div class="menu-item" data-href="settings.php"><span class="material-symbols-outlined">settings</span><span>Settings</span></div>';
    echo '<div class="menu-item" data-href="report.php"><span class="material-symbols-outlined">analytics</span><span>My Report</span></div>';
    echo '<div class="menu-item" data-href="#switch-appearance"><span class="material-symbols-outlined">dark_mode</span><span>Switch Appearance</span></div>';
    echo '<div class="menu-divider"></div>';
    echo '<div class="logout-action" id="logout-action"><span class="material-symbols-outlined">logout</span><span>Logout</span></div>';
    echo '</div>';
    echo '<div class="profile-menu-backdrop" id="profile-menu-backdrop"></div>';
  }
}
?>
