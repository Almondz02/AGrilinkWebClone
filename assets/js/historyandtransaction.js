// Minimal script to render some demo transactions and wire basic controls
(function(){
  const $ = (s, r=document)=> r.querySelector(s);
  const $$ = (s, r=document)=> Array.from(r.querySelectorAll(s));

  // Demo transactions data
  const transactions = [
    { id: 1, type: 'sale', status: 'completed', date: '2024-10-01', title: 'Sold 50kg Manure', amount: 2500 },
    { id: 2, type: 'purchase', status: 'pending', date: '2024-10-03', title: 'Bought 30kg Compost', amount: 1800 },
    { id: 3, type: 'delivery', status: 'completed', date: '2024-10-05', title: 'Delivered to Barangay 5', amount: 0 },
  ];

  function peso(n){ return '₱' + Number(n||0).toLocaleString(undefined, { minimumFractionDigits: 0 }); }

  function renderStats(list){
    const total = list.length;
    const earned = list.filter(t=>t.type==='sale').reduce((s,t)=> s + (t.amount||0), 0);
    const spent = list.filter(t=>t.type==='purchase').reduce((s,t)=> s + (t.amount||0), 0);
    const successRate = total ? Math.round((list.filter(t=>t.status==='completed').length / total) * 100) : 0;
    const byId = id => document.getElementById(id);
    const W = (id, val) => { const el = byId(id); if(el) el.textContent = val; };
    W('stat-total', String(total));
    W('stat-earned', peso(earned));
    W('stat-spent', peso(spent));
    W('stat-success', successRate + '%');
  }

  function renderTransactions(list){
    const root = $('#transactions-container');
    const empty = $('#transactions-empty');
    if(!root || !empty) return;
    if(list.length === 0){ root.innerHTML=''; empty.style.display='block'; return; }
    empty.style.display='none';
    root.innerHTML = list.map(t => `
      <div class="transaction-item" style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #eef2f7;">
        <div>
          <div style="font-weight:700;">${t.title}</div>
          <div style="font-size:12px; color:#64748b;">${t.date} • ${t.type} • ${t.status}</div>
        </div>
        <div style="font-weight:700; color:#047857;">${t.amount? peso(t.amount): ''}</div>
      </div>
    `).join('');
  }

  function applyFilters(){
    const type = $('#filter-type')?.value || 'all';
    const status = $('#filter-status')?.value || 'all';
    const from = $('#date-from')?.value;
    const to = $('#date-to')?.value;
    let list = [...transactions];
    if(type !== 'all') list = list.filter(t => ({sales:'sale', purchases:'purchase', exchanges:'exchange', deliveries:'delivery'}[type]) ? t.type === ({sales:'sale', purchases:'purchase', exchanges:'exchange', deliveries:'delivery'}[type]) : true);
    if(status !== 'all') list = list.filter(t => t.status === status);
    if(from) list = list.filter(t => t.date >= from);
    if(to) list = list.filter(t => t.date <= to);
    renderStats(list);
    renderTransactions(list);
  }

  // Wire controls
  ['filter-type','filter-status','date-from','date-to'].forEach(id => {
    const el = document.getElementById(id);
    if(el) el.addEventListener('change', applyFilters);
  });

  // Populate right sidebar listings placeholder
  (function renderListings(){
    const el = document.getElementById('listings-list');
    if(!el) return;
    const samples = [
      { title: 'Cattle Manure', detail: '₱50.00 • 500kg' },
      { title: 'Organic Compost', detail: '₱75.00 • 300kg' },
      { title: 'Goat Manure', detail: '₱60.00 • 200kg' },
    ];
    el.innerHTML = samples.map(s => `
      <div style="border:1px solid #e5e7eb; border-radius:10px; padding:10px;">
        <div style="font-weight:700;">${s.title}</div>
        <div style="font-size:12px; color:#64748b;">${s.detail}</div>
      </div>
    `).join('');
  })();

  // Initial render
  applyFilters();
})();
