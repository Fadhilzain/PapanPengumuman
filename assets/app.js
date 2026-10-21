// Papan Pengumuman — Kelompok 4 · S1 Informatika
// Logika sisi klien: AJAX (fetch), filter, pencarian, paginasi, dan validasi form.
// Daftar pengumuman diterima sebagai HTML yang SUDAH di-escape oleh server (htmlspecialchars).
const state = { halaman: 1, kategori: '', status: 'aktif', q: '', totalHalaman: 1 };
const $ = (s) => document.querySelector(s);
const $$ = (s) => document.querySelectorAll(s);
const HEADER_AJAX = { 'X-Requested-With': 'fetch' };
const KATEGORI = ['Akademik', 'Kegiatan', 'Layanan', 'Umum'];

/* ---------- Banner "Anda dilayani oleh" ---------- */
function perbaruiServedBy(sb) {
  if (!sb) return;
  $$('[data-served-nama]').forEach((n) => (n.textContent = sb.nama));
  $$('[data-served-nim]').forEach((n) => (n.textContent = sb.nim));
  $$('[data-served-route]').forEach((n) => (n.textContent = String(sb.route).toUpperCase()));
  const w = $('#waktu-respons');
  if (w) w.textContent = new Date().toLocaleTimeString('id-ID');
}

/* ---------- Muat daftar ---------- */
async function muat() {
  const q = new URLSearchParams({
    aksi: 'daftar', halaman: state.halaman, kategori: state.kategori, status: state.status, q: state.q,
  });
  const daftar = $('#daftar');
  try {
    const res = await fetch('api.php?' + q, { cache: 'no-store', headers: HEADER_AJAX });
    const json = await res.json();
    perbaruiServedBy(json.served_by);
    if (!res.ok) throw new Error(json.error || 'Gagal memuat');

    daftar.innerHTML = json.html; // aman: setiap nilai sudah melewati htmlspecialchars() di server
    $('#stat-aktif').textContent = json.total_aktif;
    state.halaman = json.halaman;
    state.totalHalaman = json.total_halaman;
    $('#info-halaman').textContent = `HALAMAN ${json.halaman} / ${json.total_halaman} · ${json.total} ENTRI`;
    $('#prev').disabled = json.halaman <= 1;
    $('#next').disabled = json.halaman >= json.total_halaman;
  } catch (e) {
    const p = document.createElement('p');
    p.className = 'empty';
    p.textContent = 'GAGAL MEMUAT: ' + e.message.toUpperCase();
    daftar.replaceChildren(p);
  }
}

/* ---------- Filter, pencarian, paginasi ---------- */
$$('[data-kategori]').forEach((b) => b.addEventListener('click', () => {
  $$('[data-kategori]').forEach((x) => x.classList.toggle('is-on', x === b));
  state.kategori = b.dataset.kategori; state.halaman = 1; muat();
}));
$$('[data-status]').forEach((b) => b.addEventListener('click', () => {
  $$('[data-status]').forEach((x) => x.classList.toggle('is-on', x === b));
  state.status = b.dataset.status; state.halaman = 1; muat();
}));

let jedaCari;
const inputCari = $('#cari');
if (inputCari) {
  inputCari.addEventListener('input', () => {
    clearTimeout(jedaCari);
    jedaCari = setTimeout(() => { state.q = inputCari.value.trim(); state.halaman = 1; muat(); }, 300);
  });
}

$('#prev').addEventListener('click', () => { if (state.halaman > 1) { state.halaman--; muat(); } });
$('#next').addEventListener('click', () => { if (state.halaman < state.totalHalaman) { state.halaman++; muat(); } });

/* ---------- Validasi sisi klien ---------- */
function hariIni() {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function validasi(form) {
  const galat = {};
  const judul = form.judul.value.trim();
  const isi = form.isi.value.trim();
  const tanggal = form.tanggal_kedaluwarsa.value;

  if (!judul) galat.judul = 'Judul wajib diisi.';
  else if (judul.length > 150) galat.judul = 'Judul maksimal 150 karakter.';

  if (!isi) galat.isi = 'Isi wajib diisi.';
  else if (isi.length > 2000) galat.isi = 'Isi maksimal 2000 karakter.';

  if (!KATEGORI.includes(form.kategori.value)) galat.kategori = 'Pilih kategori yang tersedia.';

  if (!/^\d{4}-\d{2}-\d{2}$/.test(tanggal)) galat.tanggal_kedaluwarsa = 'Tanggal kedaluwarsa wajib diisi.';
  else if (tanggal < hariIni()) galat.tanggal_kedaluwarsa = 'Tanggal tidak boleh di masa lalu.';

  return galat;
}

function tampilkanGalat(form, galat) {
  form.querySelectorAll('[data-err]').forEach((n) => {
    const pesan = galat[n.dataset.err] || '';
    n.textContent = pesan.toUpperCase();
    const field = form.elements[n.dataset.err];
    if (field) field.classList.toggle('is-invalid', Boolean(pesan));
  });
}

/* ---------- Kirim form ---------- */
const form = $('#form');
form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const pesan = $('#form-pesan');
  const tombol = form.querySelector('button[type="submit"]');
  pesan.classList.remove('is-err');

  const galat = validasi(form);
  tampilkanGalat(form, galat);
  if (Object.keys(galat).length) {
    pesan.textContent = 'PERIKSA KEMBALI ISIAN.';
    pesan.classList.add('is-err');
    return;
  }

  tombol.disabled = true; pesan.textContent = 'MENGIRIM…';
  try {
    const res = await fetch('api.php', { method: 'POST', body: new FormData(form), headers: HEADER_AJAX });
    const json = await res.json();
    perbaruiServedBy(json.served_by);
    if (!res.ok) {
      if (json.galat) tampilkanGalat(form, json.galat); // galat dari validasi sisi server
      throw new Error(json.error);
    }
    pesan.textContent = 'TERBIT VIA LAMAN ' + String(json.served_by.nama).toUpperCase() + '.';
    form.reset();
    state.halaman = 1; state.status = 'aktif';
    $$('[data-status]').forEach((x) => x.classList.toggle('is-on', x.dataset.status === 'aktif'));
    await muat();
    $('#pengumuman').scrollIntoView();
  } catch (err) {
    pesan.textContent = String(err.message || 'Gagal mengirim').toUpperCase();
    pesan.classList.add('is-err');
  } finally { tombol.disabled = false; }
});

// Hapus tanda galat begitu kolom diperbaiki
form.addEventListener('input', (e) => {
  const n = form.querySelector(`[data-err="${e.target.name}"]`);
  if (n && n.textContent) { n.textContent = ''; e.target.classList.remove('is-invalid'); }
});

/* ---------- Penanda menu aktif saat menggulir (halaman landing) ---------- */
const menu = $$('.nav__menu a[href^="#"]');
if (menu.length && 'IntersectionObserver' in window) {
  const obs = new IntersectionObserver((entri) => entri.forEach((en) => {
    if (en.isIntersecting) menu.forEach((a) => a.classList.toggle('is-active', a.getAttribute('href') === '#' + en.target.id));
  }), { threshold: 0.4 });
  $$('section[id]').forEach((s) => obs.observe(s));
}

muat();
setInterval(muat, 15000); // polling berkala: tiap request bisa dilayani server berbeda
