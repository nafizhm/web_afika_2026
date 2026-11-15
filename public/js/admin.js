document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('.sidebar-toggle');
  const backdrop = document.querySelector('.sidebar-backdrop');
  const sidebar = document.querySelector('.admin-sidebar');
  const mobile = window.matchMedia('(max-width: 760px)');
  const storage = {
    get(key) { try { return localStorage.getItem(key); } catch { return null; } },
    set(key,value) { try { localStorage.setItem(key,value); } catch {} }
  };
  let desktopHidden = storage.get('afika.sidebar-hidden') === 'true';
  const applySidebar = () => {
    const visible = mobile.matches ? document.body.classList.contains('sidebar-open') : !desktopHidden;
    document.documentElement.dataset.sidebarHidden = String(desktopHidden);
    toggle?.setAttribute('aria-expanded', String(visible));
    toggle?.setAttribute('aria-label', visible ? 'Sembunyikan menu sidebar' : 'Tampilkan menu sidebar');
    if (backdrop) backdrop.hidden = !mobile.matches || !visible;
    if (sidebar) sidebar.inert = !visible;
    window.dispatchEvent(new Event('resize'));
  };
  const closeSidebar = () => {
    if (mobile.matches) document.body.classList.remove('sidebar-open');
    else { desktopHidden = true; storage.set('afika.sidebar-hidden','true'); }
    applySidebar(); toggle?.focus();
  };
  toggle?.addEventListener('click', () => {
    if (mobile.matches) document.body.classList.toggle('sidebar-open');
    else { desktopHidden = !desktopHidden; storage.set('afika.sidebar-hidden',String(desktopHidden)); }
    applySidebar();
  });
  applySidebar();
  mobile.addEventListener('change', () => { document.body.classList.remove('sidebar-open'); applySidebar(); });
  document.querySelectorAll('[data-sidebar-close]').forEach(el => el.addEventListener('click', closeSidebar));

  const themeToggle = document.querySelector('.theme-toggle');
  const updateTheme = theme => {
    document.documentElement.dataset.theme = theme;
    themeToggle?.setAttribute('aria-label',theme === 'dark' ? 'Aktifkan mode terang' : 'Aktifkan mode gelap');
    themeToggle?.setAttribute('aria-pressed',String(theme === 'dark'));
  };
  updateTheme(document.documentElement.dataset.theme || 'light');
  themeToggle?.addEventListener('click', () => {
    const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    storage.set('afika.theme',theme); updateTheme(theme);
  });

  const profile = document.querySelector('.profile-menu');
  const dialog = document.querySelector('#logout-confirm');
  document.addEventListener('click', event => { if (profile && !profile.contains(event.target)) profile.open = false; });
  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    if (mobile.matches && document.body.classList.contains('sidebar-open')) closeSidebar();
    if (profile?.open) { profile.open = false; profile.querySelector('summary')?.focus(); }
  });
  document.querySelectorAll('[data-logout-form]').forEach(form => {
    form.onsubmit = null;
    form.addEventListener('submit', event => {
      if (dialog && typeof dialog.showModal === 'function') {
        event.preventDefault(); if (profile) profile.open = false; dialog.showModal();
        dialog.querySelector('[data-dialog-close]')?.focus();
      } else if (!window.confirm('Yakin ingin keluar dari akun?')) event.preventDefault();
    });
  });
  document.querySelector('[data-dialog-close]')?.addEventListener('click', () => dialog.close());
  dialog?.addEventListener('close', () => profile?.querySelector('summary')?.focus());
  if (!window.DataTable) return;
  DataTable.ext.errMode = 'none';
  const text = DataTable.render.text();
  const status = value => {
    const node = document.createElement('span');
    node.className = 'table-badge ' + (['Aktif','Tayang'].includes(value) ? 'positive' : 'neutral');
    node.textContent = value;
    return node;
  };
  const detail = (value, type) => {
    if (type !== 'display') return value;
    const node = document.createElement('details');
    node.className = 'table-detail';
    const summary = document.createElement('summary');
    summary.textContent = value.length > 110 ? value.slice(0, 110) + '…' : value;
    const body = document.createElement('p'); body.className = 'preserve'; body.textContent = value;
    node.append(summary, body);
    return node;
  };
  const badge = (value, type) => type === 'display' ? status(value) : value;
  document.querySelectorAll('table[data-table-kind]').forEach(table => {
    const kind = table.dataset.tableKind;
    const columns = {
      properties: [{data:'name',render:text},{data:'location',render:text},{data:'price',render:text},{data:'status',render:badge},{data:'actions',orderable:false,searchable:false}],
      faqs: [{data:'sort_order',render:text},{data:'question',render:text},{data:'answer',render:detail},{data:'status',render:badge},{data:'actions',orderable:false,searchable:false}],
      messages: [{data:'sender_name',render:text},{data:'sender_phone',render:text},{data:'body',render:detail},{data:'message_type',render:text},{data:'sent_at',render:text}]
    }[kind];
    const error = document.createElement('p');
    error.className = 'table-error'; error.setAttribute('role', 'alert'); error.hidden = true;
    table.closest('.panel').append(error);
    window.jQuery(table).on('dt-error.dt', () => {
      error.textContent = 'Data belum dapat dimuat. Periksa koneksi atau masuk ulang jika sesi berakhir.';
      error.hidden = false;
    }).on('xhr.dt', (_event, _settings, json) => { if (json) error.hidden = true; });
    new DataTable(table, {
      processing: true, serverSide: true, ajax: table.dataset.source,
      columns, order: [[kind === 'messages' ? 4 : 0, kind === 'messages' ? 'desc' : 'asc']],
      pageLength: 10, lengthMenu: [10,25,50,100], searchDelay: 350,
      search: { search: table.dataset.initialSearch || '' },
      autoWidth: false,
      language: {
        search:'Cari:',searchPlaceholder:'Cari data…',lengthMenu:'Tampilkan _MENU_ data',
        info:'Menampilkan _START_–_END_ dari _TOTAL_ data',infoEmpty:'Belum ada data',
        infoFiltered:'(dari _MAX_ total data)',zeroRecords:'Data tidak ditemukan.',
        emptyTable:'Belum ada data tersedia.',processing:'Memuat data…',loadingRecords:'Memuat…',
        paginate:{first:'Awal',last:'Akhir',next:'›',previous:'‹'}
      }
    });
  });
});

