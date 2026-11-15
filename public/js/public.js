document.addEventListener('DOMContentLoaded', () => {
  const header = document.querySelector('.public-site > .header');
  const toggle = document.querySelector('.public-menu-toggle');
  const menu = document.querySelector('#public-navigation');
  if (!header || !toggle || !menu) return;
  const mobile = matchMedia('(max-width: 1023px)');
  const setOpen = (open, restoreFocus = false) => {
    header.classList.toggle('menu-open', open);
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Tutup menu navigasi' : 'Buka menu navigasi');
    menu.inert = mobile.matches && !open;
    if (restoreFocus) toggle.focus();
  };
  setOpen(false);
  toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
  menu.querySelectorAll('a').forEach(link => link.addEventListener('click', () => setOpen(false)));
  document.addEventListener('click', event => { if (!header.contains(event.target)) setOpen(false); });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') setOpen(false, true);
  });
  mobile.addEventListener('change', () => setOpen(false));
});
