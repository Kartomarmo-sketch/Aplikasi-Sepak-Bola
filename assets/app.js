(function () {
    'use strict';

    var body = document.body;
    var root = document.documentElement;

    // ----- Buka / tutup sidebar (selalu tertutup saat halaman dibuka) -----
    var tombolMenu = document.getElementById('tombolMenu');
    function aturMenu(tutup) {
        body.classList.toggle('menu-tutup', tutup);
        tombolMenu.setAttribute('aria-expanded', tutup ? 'false' : 'true');
    }
    aturMenu(true);
    tombolMenu.addEventListener('click', function () {
        aturMenu(!body.classList.contains('menu-tutup'));
    });
    document.getElementById('latar').addEventListener('click', function () { aturMenu(true); });

    // Tautan ke bagian di halaman yang sama (#jadwal, dst.): tandai aktif.
    // Di layar kecil, sidebar ditutup setelah memilih menu.
    var tautan = document.querySelectorAll('#sidebar a');
    document.getElementById('sidebar').addEventListener('click', function (ev) {
        var a = ev.target.closest('a');
        if (!a) { return; }
        var h = a.getAttribute('href') || '';
        if (h !== '#' && h.indexOf('#') !== -1) {
            tautan.forEach(function (t) { t.classList.remove('aktif'); t.removeAttribute('aria-current'); });
            a.classList.add('aktif');
            a.setAttribute('aria-current', 'true');
        }
        if (window.innerWidth <= 800) { aturMenu(true); }
    });

    // ----- Grup menu (submenu bisa dibuka / ditutup) -----
    document.querySelectorAll('#sidebar .kepala-grup').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            var buka = tombol.getAttribute('aria-expanded') !== 'true';
            tombol.setAttribute('aria-expanded', buka ? 'true' : 'false');
            document.getElementById(tombol.getAttribute('aria-controls')).hidden = !buka;
            tombol.parentNode.classList.toggle('terbuka', buka);
        });
    });

    // ----- Ganti tema gelap / terang (disimpan di browser, sama dengan login) -----
    var tombolTema = document.getElementById('tombolTema');
    function perbaruiLabel() {
        var gelap = root.getAttribute('data-tema') === 'gelap';
        tombolTema.setAttribute('aria-label', gelap ? 'Ganti ke tema terang' : 'Ganti ke tema gelap');
        tombolTema.title = gelap ? 'Tema terang' : 'Tema gelap';
    }
    tombolTema.addEventListener('click', function () {
        var baru = root.getAttribute('data-tema') === 'gelap' ? 'terang' : 'gelap';
        root.setAttribute('data-tema', baru);
        try { localStorage.setItem('tema', baru); } catch (e) {}
        perbaruiLabel();
    });
    perbaruiLabel();

    // ----- Menu akun -----
    var tombolAkun = document.getElementById('tombolAkun');
    var menuAkun = document.getElementById('menuAkun');
    function tampilAkun(buka) {
        menuAkun.hidden = !buka;
        tombolAkun.setAttribute('aria-expanded', buka ? 'true' : 'false');
    }
    tombolAkun.addEventListener('click', function () { tampilAkun(menuAkun.hidden); });
    document.addEventListener('click', function (ev) {
        if (!ev.target.closest('.akun')) { tampilAkun(false); }
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && !menuAkun.hidden) { tampilAkun(false); tombolAkun.focus(); }
    });

    // ----- Notifikasi: panel lonceng, tab, aksi tanpa muat ulang, lencana berkala -----
    var tombolNotif = document.getElementById('tombolNotif');
    var panelNotif = document.getElementById('panelNotif');
    if (tombolNotif && panelNotif) {
        var judulAsli = document.title;
        var diHalamanNotif = /notifikasi\.php$/.test(location.pathname);
        var jumlahKini = function () { return parseInt(tombolNotif.getAttribute('data-jumlah') || '0', 10) || 0; };

        var terapkanTabNotif = function () {
            var tab = panelNotif.getAttribute('data-tab') || 'semua';
            panelNotif.querySelectorAll('[data-pn-tab]').forEach(function (b) {
                b.setAttribute('aria-selected', b.getAttribute('data-pn-tab') === tab ? 'true' : 'false');
            });
            var adaSemua = !!panelNotif.querySelector('.notif-item');
            var adaBelum = !!panelNotif.querySelector('.notif-item.belum');
            panelNotif.querySelectorAll('[data-pn-kosong]').forEach(function (k) {
                var jenis = k.getAttribute('data-pn-kosong');
                k.hidden = jenis !== tab || (jenis === 'semua' ? adaSemua : adaBelum);
            });
        };
        var tampilNotif = function (buka) {
            panelNotif.hidden = !buka;
            tombolNotif.setAttribute('aria-expanded', buka ? 'true' : 'false');
            if (buka) { tampilAkun(false); terapkanTabNotif(); }
        };
        tombolNotif.addEventListener('click', function () { tampilNotif(panelNotif.hidden); });
        tombolAkun.addEventListener('click', function () { tampilNotif(false); });
        document.addEventListener('click', function (ev) {
            if (!ev.target.closest('.notif')) { tampilNotif(false); }
        });
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && !panelNotif.hidden) { tampilNotif(false); tombolNotif.focus(); }
        });
        panelNotif.addEventListener('click', function (ev) {
            var b = ev.target.closest('[data-pn-tab]');
            if (b) { panelNotif.setAttribute('data-tab', b.getAttribute('data-pn-tab')); terapkanTabNotif(); }
        });

        var perbaruiLencana = function (jumlah) {
            var lama = jumlahKini();
            var lencana = tombolNotif.querySelector('.lencana');
            if (jumlah > 0) {
                if (!lencana) {
                    lencana = document.createElement('span');
                    lencana.className = 'lencana';
                    tombolNotif.appendChild(lencana);
                }
                lencana.textContent = jumlah > 99 ? '99+' : String(jumlah);
                if (jumlah > lama) { lencana.classList.remove('denyut'); void lencana.offsetWidth; lencana.classList.add('denyut'); }
            } else if (lencana) {
                lencana.parentNode.removeChild(lencana);
            }
            tombolNotif.setAttribute('data-jumlah', String(jumlah));
            tombolNotif.setAttribute('aria-label', 'Notifikasi' + (jumlah > 0 ? ', ' + jumlah + ' belum dibaca' : ''));
            document.title = (jumlah > 0 ? '(' + jumlah + ') ' : '') + judulAsli;
        };
        var minta = function (url, opsi) {
            opsi = opsi || {};
            opsi.credentials = 'same-origin';
            opsi.headers = { 'X-Requested-With': 'fetch' };
            return fetch(url, opsi).then(function (r) {
                if (!r.ok && r.status !== 400) { throw new Error('HTTP ' + r.status); }
                return r;
            });
        };
        var muatPanel = function () {
            return minta('notifikasi.php?panel=1')
                .then(function (r) { return r.text(); })
                .then(function (html) { panelNotif.innerHTML = html; terapkanTabNotif(); });
        };

        // Aksi di panel (tandai dibaca/belum, tandai semua, urungkan) tanpa meninggalkan halaman.
        panelNotif.addEventListener('submit', function (ev) {
            var form = ev.target.closest('form[data-notif-aksi]');
            if (!form || !window.fetch || !window.FormData) { return; }
            ev.preventDefault();
            var tombol = form.querySelector('button');
            if (tombol) { tombol.disabled = true; }
            minta(form.getAttribute('action'), { method: 'POST', body: new FormData(form) })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    perbaruiLencana(d.jumlah || 0);
                    if (diHalamanNotif) { location.reload(); return; }
                    return muatPanel().then(function () {
                        var fokus = panelNotif.querySelector('.pn-urung button') || panelNotif.querySelector('[data-pn-tab][aria-selected="true"]');
                        if (fokus) { fokus.focus(); }
                    });
                })
                .catch(function () { form.submit(); });
        });

        // Lencana diperbarui tiap 60 detik saat tab terlihat (dan saat kembali ke tab).
        var cekStatusNotif = function () {
            if (document.visibilityState !== 'visible' || !window.fetch) { return; }
            minta('notifikasi.php?status=1')
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (typeof d.jumlah !== 'number' || d.jumlah === jumlahKini()) { return; }
                    perbaruiLencana(d.jumlah);
                    if (panelNotif.hidden) { muatPanel().catch(function () {}); }
                })
                .catch(function () {});
        };
        setInterval(cekStatusNotif, 60000);
        document.addEventListener('visibilitychange', cekStatusNotif);
        if (jumlahKini() > 0) { document.title = '(' + jumlahKini() + ') ' + judulAsli; }
    }

    // ----- Pemberitahuan (toast): tutup manual atau otomatis setelah 6 detik -----
    function tutupToast(t) {
        t.classList.add('hilang');
        setTimeout(function () { if (t.parentNode) { t.parentNode.removeChild(t); } }, 320);
    }
    function pasangToast(t) {
        var tutup = t.querySelector('.tutup-toast');
        if (tutup) { tutup.addEventListener('click', function () { tutupToast(t); }); }
        if (t.hasAttribute('data-otomatis')) { setTimeout(function () { tutupToast(t); }, 6000); }
    }
    document.querySelectorAll('.toast').forEach(pasangToast);

    function tampilkanToast(pesan, tipe) {
        var t = document.createElement('div');
        t.className = 'toast ' + (tipe || 'galat');
        t.setAttribute('role', 'status');
        t.setAttribute('data-otomatis', '');
        var s = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        s.setAttribute('class', 'ik'); s.setAttribute('viewBox', '0 0 24 24');
        var p = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        p.setAttribute('d', tipe === 'sukses' ? 'm5 12.5 4.5 4.5L19 7.5'
            : 'M12 8v5M12 16.5v.01M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z');
        s.appendChild(p);
        var m = document.createElement('span'); m.textContent = pesan;
        var x = document.createElement('button');
        x.type = 'button'; x.className = 'tutup-toast'; x.setAttribute('aria-label', 'Tutup pesan'); x.innerHTML = '&times;';
        t.appendChild(s); t.appendChild(m); t.appendChild(x);
        document.body.appendChild(t);
        pasangToast(t);
    }

    // ----- Tampil / sembunyikan password -----
    document.querySelectorAll('[data-tampil-password]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            var kolom = tombol.parentNode.querySelector('input');
            var terlihat = kolom.type === 'text';
            kolom.type = terlihat ? 'password' : 'text';
            tombol.setAttribute('aria-pressed', terlihat ? 'false' : 'true');
            tombol.setAttribute('aria-label', terlihat ? 'Tampilkan password' : 'Sembunyikan password');
            tombol.querySelector('.mata-buka').style.display = terlihat ? '' : 'none';
            tombol.querySelector('.mata-tutup').style.display = terlihat ? 'none' : '';
        });
    });

    // ----- Formulir: tandai perubahan yang belum disimpan -----
    var sedangKirim = false;
    var adaPerubahan = false;
    document.querySelectorAll('form[data-pantau]').forEach(function (form) {
        var status = form.querySelector('.status-ubah');
        var simpan = form.querySelector('[data-simpan]');
        var awal = {};
        function ambil() {
            var h = {};
            Array.prototype.forEach.call(form.elements, function (el) {
                if (el.name && el.name !== 'csrf') { h[el.name] = el.value; }
            });
            return h;
        }
        awal = ambil();
        function periksa() {
            var kini = ambil(), kotor = false;
            Object.keys(kini).forEach(function (k) { if (kini[k] !== awal[k]) { kotor = true; } });
            adaPerubahan = kotor;
            if (simpan) { simpan.disabled = !kotor; }
            if (status) {
                status.classList.toggle('kotor', kotor);
                status.textContent = kotor ? 'Ada perubahan yang belum disimpan' : 'Tidak ada perubahan';
            }
        }
        // Setelah galat validasi dari server, isian yang dikirim ulang dianggap belum tersimpan.
        if (form.hasAttribute('data-mulai-kotor')) { awal = {}; }
        form.addEventListener('input', periksa);
        form.addEventListener('reset', function () { setTimeout(periksa, 0); });
        form.addEventListener('submit', function () {
            sedangKirim = true;
            if (simpan) { simpan.disabled = true; simpan.textContent = 'Menyimpan...'; }
        });
        periksa();
    });
    window.addEventListener('beforeunload', function (ev) {
        if (adaPerubahan && !sedangKirim) { ev.preventDefault(); ev.returnValue = ''; }
    });
    window.addEventListener('pageshow', function (ev) { if (ev.persisted) { sedangKirim = false; } });

    // ----- Unggah foto: periksa jenis & ukuran, lalu kirim otomatis -----
    document.querySelectorAll('input[data-foto-otomatis]').forEach(function (input) {
        input.addEventListener('change', function () {
            var f = input.files && input.files[0];
            if (!f) { return; }
            if (['image/jpeg', 'image/png', 'image/webp'].indexOf(f.type) === -1) {
                tampilkanToast('Foto harus berformat JPG, PNG, atau WebP.', 'galat'); input.value = ''; return;
            }
            if (f.size > 2 * 1024 * 1024) {
                tampilkanToast('Ukuran foto maksimal 2 MB.', 'galat'); input.value = ''; return;
            }
            sedangKirim = true;
            input.form.submit();
        });
    });

    // ----- Dialog -----
    document.querySelectorAll('[data-buka-dialog]').forEach(function (b) {
        b.addEventListener('click', function () {
            var d = document.getElementById(b.getAttribute('data-buka-dialog'));
            if (d && d.showModal) { d.showModal(); }
        });
    });
    document.querySelectorAll('dialog.modal').forEach(function (d) {
        d.addEventListener('click', function (ev) { if (ev.target === d) { d.close(); } });
        d.querySelectorAll('[data-tutup-dialog]').forEach(function (b) {
            b.addEventListener('click', function () { d.close(); });
        });
        if (d.hasAttribute('data-buka-otomatis') && d.showModal) { d.showModal(); }
    });

    // ----- Filter GET: kirim otomatis saat pilihan berubah -----
    document.querySelectorAll('form[data-kirim-otomatis]').forEach(function (form) {
        form.addEventListener('change', function () { form.submit(); });
    });

    // ----- Tabel data: cari, filter, urutkan, halaman (tanpa muat ulang) -----
    var IKON_KIRI = '<svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>';
    var IKON_KANAN = '<svg class="ik" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>';

    function pasangTabel(akar) {
        var tabel = akar.querySelector('table');
        if (!tabel) { return; }
        var tbody = tabel.tBodies[0];
        var baris = Array.prototype.slice.call(tbody.rows);
        var cari = akar.querySelector('[data-cari]');
        var saring = akar.querySelectorAll('[data-saring]');
        var perHal = akar.querySelector('[data-per-halaman]');
        var info = akar.querySelector('[data-info]');
        var nav = akar.querySelector('[data-halaman]');
        var kosong = akar.querySelector('[data-kosong]');
        var kepala = Array.prototype.slice.call(tabel.tHead.rows[0].cells);
        var teksBaris = baris.map(function (b) { return b.textContent.replace(/\s+/g, ' ').toLowerCase(); });
        var keadaan = { kolom: -1, arah: 1, hal: 1 };

        function nilaiSel(b, i, jenis) {
            var sel = b.cells[i];
            if (!sel) { return ''; }
            var v = sel.hasAttribute('data-nilai') ? sel.getAttribute('data-nilai') : sel.textContent.trim();
            return jenis === 'angka' ? (parseFloat(v) || 0) : v.toLowerCase();
        }

        function gambar() {
            var q = cari ? cari.value.trim().toLowerCase() : '';
            var cocok = baris.filter(function (b, i) {
                if (q && teksBaris[i].indexOf(q) === -1) { return false; }
                for (var s = 0; s < saring.length; s++) {
                    var v = saring[s].value;
                    if (v && b.getAttribute('data-' + saring[s].getAttribute('data-saring')) !== v) { return false; }
                }
                return true;
            });
            if (keadaan.kolom >= 0) {
                var jenis = kepala[keadaan.kolom].getAttribute('data-urut');
                cocok.sort(function (a, b) {
                    var x = nilaiSel(a, keadaan.kolom, jenis), y = nilaiSel(b, keadaan.kolom, jenis);
                    if (jenis === 'angka') { return (x - y) * keadaan.arah; }
                    return x.localeCompare(y, 'id') * keadaan.arah;
                });
            }
            var per = perHal ? parseInt(perHal.value, 10) : 10;
            var jumlahHal = Math.max(1, Math.ceil(cocok.length / per));
            keadaan.hal = Math.min(keadaan.hal, jumlahHal);
            var awal = (keadaan.hal - 1) * per;
            var tampil = cocok.slice(awal, awal + per);

            var frag = document.createDocumentFragment();
            tampil.forEach(function (b) { frag.appendChild(b); });
            tbody.innerHTML = '';
            tbody.appendChild(frag);

            if (kosong) { kosong.hidden = cocok.length > 0; }
            if (info) {
                info.textContent = cocok.length ? (awal + 1) + '–' + (awal + tampil.length) + ' dari ' + cocok.length : '0 dari ' + baris.length;
            }
            if (nav) {
                var html = '<button type="button" data-ke="' + (keadaan.hal - 1) + '" aria-label="Halaman sebelumnya"' + (keadaan.hal === 1 ? ' disabled' : '') + '>' + IKON_KIRI + '</button>';
                var mulai = Math.max(1, Math.min(keadaan.hal - 2, jumlahHal - 4));
                for (var h = mulai; h <= Math.min(jumlahHal, mulai + 4); h++) {
                    html += '<button type="button" data-ke="' + h + '"' + (h === keadaan.hal ? ' aria-current="page"' : '') + ' aria-label="Halaman ' + h + '">' + h + '</button>';
                }
                html += '<button type="button" data-ke="' + (keadaan.hal + 1) + '" aria-label="Halaman berikutnya"' + (keadaan.hal === jumlahHal ? ' disabled' : '') + '>' + IKON_KANAN + '</button>';
                nav.innerHTML = html;
            }
        }

        function urutkan(i, arah) {
            keadaan.arah = arah;
            keadaan.kolom = i;
            kepala.forEach(function (th, j) {
                if (th.hasAttribute('data-urut')) {
                    th.setAttribute('aria-sort', j === i ? (arah === 1 ? 'ascending' : 'descending') : 'none');
                }
            });
        }

        kepala.forEach(function (th, i) {
            var tombol = th.querySelector('button');
            if (!tombol || !th.hasAttribute('data-urut')) { return; }
            if (th.hasAttribute('data-urut-awal')) { urutkan(i, th.getAttribute('data-urut-awal') === 'desc' ? -1 : 1); }
            tombol.addEventListener('click', function () {
                // Klik pertama: angka besar ke kecil, teks A ke Z; klik berikutnya membalik.
                var arah = keadaan.kolom === i ? -keadaan.arah : (th.getAttribute('data-urut') === 'angka' ? -1 : 1);
                urutkan(i, arah);
                keadaan.hal = 1;
                gambar();
            });
        });
        if (cari) { cari.addEventListener('input', function () { keadaan.hal = 1; gambar(); }); }
        saring.forEach(function (s) { s.addEventListener('change', function () { keadaan.hal = 1; gambar(); }); });
        if (perHal) { perHal.addEventListener('change', function () { keadaan.hal = 1; gambar(); }); }
        if (nav) {
            nav.addEventListener('click', function (ev) {
                var b = ev.target.closest('button[data-ke]');
                if (!b || b.disabled) { return; }
                keadaan.hal = parseInt(b.getAttribute('data-ke'), 10);
                gambar();
            });
        }
        gambar();
    }
    document.querySelectorAll('[data-tabel]').forEach(pasangTabel);

    // ----- Grafik garis (satu seri) dengan garis bantu dan tooltip -----
    var NS = 'http://www.w3.org/2000/svg';
    function el(nama, atribut) {
        var n = document.createElementNS(NS, nama);
        Object.keys(atribut || {}).forEach(function (k) { n.setAttribute(k, atribut[k]); });
        return n;
    }
    function angkaId(v, digit) {
        return Number(v).toLocaleString('id-ID', { minimumFractionDigits: digit, maximumFractionDigits: digit });
    }

    function gambarGrafik(wadah) {
        var d;
        try { d = JSON.parse(wadah.getAttribute('data-grafik')); } catch (e) { return; }
        var titik = d.titik || [];
        var lebar = wadah.clientWidth, tinggi = wadah.clientHeight;
        if (!lebar || !tinggi || !titik.length) { return; }
        var tepi = { kiri: 34, kanan: 14, atas: 12, bawah: 28 };
        var w = lebar - tepi.kiri - tepi.kanan, h = tinggi - tepi.atas - tepi.bawah;
        var rentang = (d.max - d.min) || 1;
        var X = function (i) { return tepi.kiri + (titik.length === 1 ? w / 2 : (i * w) / (titik.length - 1)); };
        var Y = function (v) { return tepi.atas + h - ((Math.max(d.min, Math.min(d.max, v)) - d.min) / rentang) * h; };

        wadah.innerHTML = '';
        var svg = el('svg', { width: lebar, height: tinggi, 'aria-hidden': 'true' });

        (d.bantu || []).forEach(function (v) {
            svg.appendChild(el('line', { 'class': 'g-bantu', x1: tepi.kiri, x2: lebar - tepi.kanan, y1: Y(v), y2: Y(v) }));
            var t = el('text', { 'class': 'g-teks', x: tepi.kiri - 8, y: Y(v) + 4, 'text-anchor': 'end' });
            t.textContent = v;
            svg.appendChild(t);
        });

        // Label sumbu X: semua bila muat, jika tidak hanya awal, tengah, akhir.
        var muat = Math.floor(w / 70);
        var langkah = titik.length <= muat ? 1 : Math.ceil((titik.length - 1) / 2);
        titik.forEach(function (p, i) {
            if (i % langkah !== 0 && i !== titik.length - 1) { return; }
            var jangkar = i === 0 && titik.length > 1 ? 'start' : (i === titik.length - 1 && titik.length > 1 ? 'end' : 'middle');
            var t = el('text', { 'class': 'g-teks', x: X(i), y: tinggi - 6, 'text-anchor': jangkar });
            t.textContent = p.x;
            svg.appendChild(t);
        });

        var jalur = titik.map(function (p, i) { return (i ? 'L' : 'M') + X(i).toFixed(1) + ' ' + Y(p.y).toFixed(1); }).join(' ');
        if (titik.length > 1) {
            svg.appendChild(el('path', { 'class': 'g-area', d: jalur + ' L' + X(titik.length - 1).toFixed(1) + ' ' + (tepi.atas + h) + ' L' + X(0).toFixed(1) + ' ' + (tepi.atas + h) + ' Z' }));
            svg.appendChild(el('path', { 'class': 'g-garis', d: jalur }));
        }
        var silang = el('line', { 'class': 'g-silang', y1: tepi.atas, y2: tepi.atas + h });
        svg.appendChild(silang);
        var bulatan = titik.map(function (p, i) {
            var c = el('circle', { 'class': 'g-titik', cx: X(i), cy: Y(p.y), r: 4.5 });
            svg.appendChild(c);
            return c;
        });
        wadah.appendChild(svg);

        var tip = document.createElement('div');
        tip.className = 'tip-grafik';
        wadah.appendChild(tip);

        function sorot(i) {
            var p = titik[i];
            bulatan.forEach(function (c, j) { c.setAttribute('r', j === i ? 7 : 4.5); });
            silang.setAttribute('x1', X(i)); silang.setAttribute('x2', X(i));
            tip.innerHTML = '';
            var b = document.createElement('b'); b.textContent = angkaId(p.y, d.digit || 0);
            var s1 = document.createElement('small'); s1.textContent = p.x;
            tip.appendChild(b); tip.appendChild(s1);
            if (p.t) { var s2 = document.createElement('small'); s2.textContent = p.t; tip.appendChild(s2); }
            var kiri = Math.max(90, Math.min(lebar - 90, X(i)));
            tip.style.left = kiri + 'px';
            tip.style.top = Y(p.y) + 'px';
            wadah.classList.add('hover');
            wadah.setAttribute('data-indeks', i);
        }
        function lepas() {
            wadah.classList.remove('hover');
            bulatan.forEach(function (c) { c.setAttribute('r', 4.5); });
        }
        wadah.onpointermove = function (ev) {
            var kotak = wadah.getBoundingClientRect();
            var x = ev.clientX - kotak.left, terdekat = 0, jarak = Infinity;
            titik.forEach(function (p, i) { var dx = Math.abs(X(i) - x); if (dx < jarak) { jarak = dx; terdekat = i; } });
            sorot(terdekat);
        };
        wadah.onpointerleave = lepas;
        wadah.onblur = lepas;
        wadah.onfocus = function () { sorot(titik.length - 1); };
        wadah.onkeydown = function (ev) {
            var i = parseInt(wadah.getAttribute('data-indeks') || String(titik.length - 1), 10);
            if (ev.key === 'ArrowLeft') { sorot(Math.max(0, i - 1)); ev.preventDefault(); }
            if (ev.key === 'ArrowRight') { sorot(Math.min(titik.length - 1, i + 1)); ev.preventDefault(); }
        };
    }
    var grafik = document.querySelectorAll('[data-grafik]');
    grafik.forEach(gambarGrafik);
    if (grafik.length && window.ResizeObserver) {
        var tundaGambar = null;
        var pengamat = new ResizeObserver(function (entri) {
            clearTimeout(tundaGambar);
            tundaGambar = setTimeout(function () { entri.forEach(function (e) { gambarGrafik(e.target); }); }, 80);
        });
        grafik.forEach(function (g) { pengamat.observe(g); });
    }

    // ----- Menu aksi baris (⋯): posisi tetap agar tidak terpotong tabel yang bisa digeser -----
    function tutupMenuBaris(kecuali) {
        document.querySelectorAll('details.menu-baris[open]').forEach(function (d) { if (d !== kecuali) { d.open = false; } });
    }
    document.querySelectorAll('details.menu-baris').forEach(function (d) {
        d.addEventListener('toggle', function () {
            if (!d.open) { return; }
            tutupMenuBaris(d);
            var isi = d.querySelector('.isi-menu-baris');
            var r = d.querySelector('summary').getBoundingClientRect();
            isi.style.top = Math.min(r.bottom + 6, window.innerHeight - isi.offsetHeight - 8) + 'px';
            isi.style.left = Math.max(8, r.right - isi.offsetWidth) + 'px';
        });
    });
    document.addEventListener('click', function (ev) { if (!ev.target.closest('details.menu-baris')) { tutupMenuBaris(null); } });
    document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') { tutupMenuBaris(null); } });
    window.addEventListener('scroll', function () { tutupMenuBaris(null); }, true);
    window.addEventListener('resize', function () { tutupMenuBaris(null); });

    // ----- Isi dialog (formulir / detail) dari data JSON pada tombol -----
    document.addEventListener('click', function (ev) {
        var b = ev.target.closest('[data-isi-dialog]');
        if (!b) { return; }
        var d = document.getElementById(b.getAttribute('data-isi-dialog'));
        var data = {};
        try { data = JSON.parse(b.getAttribute('data-json') || '{}'); } catch (e) {}
        if (!d) { return; }
        var form = d.querySelector('form');
        if (form) {
            Array.prototype.forEach.call(form.elements, function (el) {
                if (!el.name || el.name === 'csrf' || el.name === 'aksi' || el.type === 'submit' || el.type === 'button') { return; }
                // Kotak centang grup (peserta[]): dicentang bila nilainya ada di data.peserta.
                if (el.type === 'checkbox' && /\[\]$/.test(el.name)) {
                    var grup = data[el.name.slice(0, -2)];
                    el.checked = Array.isArray(grup) && grup.map(String).indexOf(el.value) !== -1;
                    return;
                }
                // Baris bertingkat (sesi[0][judul]): ambil dari data.sesi[0].judul, atau nilai bawaan.
                var m = /^(\w+)\[(\d+)\]\[(\w+)\]$/.exec(el.name);
                if (m) {
                    var baris = Array.isArray(data[m[1]]) ? data[m[1]][parseInt(m[2], 10)] : null;
                    var bawaan = { hari: 'Senin', durasi: '60', intensitas: 'sedang' };
                    var isi = baris && baris[m[3]] !== undefined && baris[m[3]] !== null ? String(baris[m[3]]) : (bawaan[m[3]] || '');
                    el.value = isi;
                    if (el.tagName === 'SELECT' && el.selectedIndex === -1) { el.selectedIndex = 0; }
                    return;
                }
                var v = Object.prototype.hasOwnProperty.call(data, el.name) && data[el.name] !== null ? String(data[el.name]) : '';
                if (el.tagName === 'SELECT') {
                    el.value = v;
                    if (el.selectedIndex === -1) { el.selectedIndex = 0; }
                } else {
                    el.value = v;
                }
            });
            form.querySelectorAll('.medan.salah').forEach(function (m) { m.classList.remove('salah'); });
            form.querySelectorAll('.pesan-salah').forEach(function (p) { p.remove(); });
            var baru = !data.id || String(data.id) === '0';
            form.querySelectorAll('[data-hanya-baru]').forEach(function (el) { el.hidden = !baru; });
            form.dispatchEvent(new Event('input'));
            form.dispatchEvent(new Event('change'));
        }
        var judul = d.querySelector('[data-judul-dialog]');
        if (judul && b.getAttribute('data-judul')) { judul.textContent = b.getAttribute('data-judul'); }
        d.querySelectorAll('[data-isi]').forEach(function (el) {
            var k = el.getAttribute('data-isi');
            el.textContent = data[k] !== undefined && data[k] !== null && data[k] !== '' ? String(data[k]) : '-';
        });
        // HTML disusun & di-escape di server (bukan masukan pengguna mentah).
        d.querySelectorAll('[data-isi-html]').forEach(function (el) {
            el.innerHTML = data[el.getAttribute('data-isi-html')] || '';
        });
        tutupMenuBaris(null);
        if (d.showModal && !d.open) { d.showModal(); }
    });

    // ----- Tombol ganti tampilan (Grafik / Tabel, Nilai / Gaji) -----
    document.querySelectorAll('[data-grup-ganti]').forEach(function (grup) {
        var tombol = grup.querySelectorAll('[data-ganti]');
        tombol.forEach(function (b) {
            b.addEventListener('click', function () {
                tombol.forEach(function (x) {
                    var aktif = x === b;
                    x.classList.toggle('aktif', aktif);
                    x.setAttribute('aria-pressed', aktif ? 'true' : 'false');
                    var t = document.getElementById(x.getAttribute('data-ganti'));
                    if (t) { t.hidden = !aktif; }
                });
                window.dispatchEvent(new Event('resize'));
            });
        });
    });

    // ----- Formulir kontrak: jenis menyesuaikan peran & pratinjau nilai -----
    var formKontrak = document.querySelector('#dialogKontrak form');
    if (formKontrak) {
        var pilihOrang = formKontrak.querySelector('[name="user_id"]');
        var pilihJenis = formKontrak.querySelector('[name="jenis"]');
        var ringkas = formKontrak.querySelector('[data-ringkas-kontrak]');
        pilihOrang.addEventListener('change', function () {
            var opt = pilihOrang.options[pilihOrang.selectedIndex];
            var peran = opt ? opt.getAttribute('data-peran') : '';
            if (peran === 'staf') { pilihJenis.value = 'staf'; } else if (peran === 'pemain' && pilihJenis.value === 'staf') { pilihJenis.value = 'profesional'; }
        });
        var hitungKontrak = function () {
            var m = formKontrak.querySelector('[name="tanggal_mulai"]').value;
            var s = formKontrak.querySelector('[name="tanggal_selesai"]').value;
            var g = parseFloat(formKontrak.querySelector('[name="gaji_pokok"]').value) || 0;
            var t = parseFloat(formKontrak.querySelector('[name="bonus_tanda_tangan"]').value) || 0;
            if (!m || !s || s <= m || !g) { ringkas.textContent = ''; return; }
            var a = new Date(m), z = new Date(s);
            z.setDate(z.getDate() + 1);
            var bulan = (z.getFullYear() - a.getFullYear()) * 12 + (z.getMonth() - a.getMonth()) + (z.getDate() - a.getDate() >= 15 ? 1 : 0);
            ringkas.textContent = 'Lama ' + bulan + ' bulan · nilai kontrak ≈ Rp ' + Math.round(g * bulan + t).toLocaleString('id-ID');
        };
        formKontrak.addEventListener('input', hitungKontrak);
        hitungKontrak();
    }

    // ----- Formulir cedera: tanggal pulih hanya untuk status "Pulih" -----
    document.querySelectorAll('form[data-form-cedera]').forEach(function (f) {
        var status = f.querySelector('[name="status"]');
        var blok = f.querySelector('[data-hanya-pulih]');
        var atur = function () { blok.hidden = status.value !== 'pulih'; };
        f.addEventListener('change', atur);
        atur();
    });

    // ----- Formulir tes kebugaran: pratinjau skor otomatis (rumus sama dengan server) -----
    document.querySelectorAll('form[data-form-tes]').forEach(function (f) {
        var info = f.querySelector('[data-pratinjau-skor]');
        var ambilAngka = function (n) { var v = parseFloat(String(f.querySelector('[name="' + n + '"]').value).replace(',', '.')); return isNaN(v) ? null : v; };
        var jepit = function (x) { return Math.max(0, Math.min(1, x)); };
        var hitung = function () {
            var vo2 = ambilAngka('vo2max'), sprint = ambilAngka('sprint_30m_detik'), yoyo = ambilAngka('yoyo_level');
            var k = [];
            if (vo2 !== null) { k.push([0.4, jepit((vo2 - 45) / 21)]); }
            if (yoyo !== null) { k.push([0.3, jepit((yoyo - 15) / 8)]); }
            if (sprint !== null) { k.push([0.3, jepit((4.5 - sprint) / 0.7)]); }
            var manual = ambilAngka('skor_kebugaran');
            if (!k.length) { info.textContent = manual === null ? 'Isi VO₂max/sprint/Yo-Yo untuk skor otomatis.' : ''; return; }
            var bobot = k.reduce(function (s, x) { return s + x[0]; }, 0);
            var skor = Math.round(100 * k.reduce(function (s, x) { return s + x[0] * x[1]; }, 0) / bobot);
            var level = skor >= 85 ? 'Sangat baik' : (skor >= 70 ? 'Baik' : (skor >= 55 ? 'Cukup' : 'Kurang'));
            info.textContent = manual === null ? 'Skor otomatis: ' + skor + ' (' + level + ')' : 'Skor manual dipakai (otomatis: ' + skor + ')';
        };
        f.addEventListener('input', hitung);
        hitung();
    });

    // ----- Cari kartu (program latihan) -----
    document.querySelectorAll('[data-cari-kartu]').forEach(function (input) {
        var wadah = document.getElementById(input.getAttribute('data-cari-kartu'));
        if (!wadah) { return; }
        var kosong = wadah.querySelector('[data-kosong-kartu]');
        input.addEventListener('input', function () {
            var q = input.value.trim().toLowerCase(), ada = 0;
            wadah.querySelectorAll('.kartu').forEach(function (k) {
                var cocok = !q || k.textContent.toLowerCase().indexOf(q) !== -1;
                k.hidden = !cocok;
                if (cocok) { ada++; }
            });
            if (kosong) { kosong.hidden = ada > 0; }
        });
    });

    // ----- Grafik batang bertumpuk (mis. pemain vs staf per bulan) -----
    function ringkasRp(v) {
        if (v >= 1e9) { return (v / 1e9).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' M'; }
        if (v >= 1e6) { return (v / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 0 }) + ' jt'; }
        return Math.round(v).toLocaleString('id-ID');
    }
    function gambarBatang(wadah) {
        var d;
        try { d = JSON.parse(wadah.getAttribute('data-grafik-batang')); } catch (e) { return; }
        var lebar = wadah.clientWidth, tinggi = wadah.clientHeight;
        if (!lebar || !tinggi || !d.label.length) { return; }
        var tepi = { kiri: 56, kanan: 10, atas: 10, bawah: 26 };
        var w = lebar - tepi.kiri - tepi.kanan, h = tinggi - tepi.atas - tepi.bawah;
        var total = d.label.map(function (_, i) { return d.seri.reduce(function (s, x) { return s + (x.nilai[i] || 0); }, 0); });
        var maks = Math.max.apply(null, total) || 1;
        var langkah = Math.pow(10, Math.floor(Math.log10(maks / 4)));
        [1, 2, 2.5, 5, 10].some(function (k) { if (maks / (langkah * k) <= 5) { langkah *= k; return true; } return false; });
        var atas = Math.ceil(maks / langkah) * langkah;
        // format "angka" (peringkat gol/rating/menit) atau rupiah (bawaan).
        var angkaSaja = d.format === "angka";
        var fmt = function (x) { return angkaSaja ? Number(x).toLocaleString("id-ID", { maximumFractionDigits: d.digit || 0 }) : "Rp " + Math.round(x).toLocaleString("id-ID"); };
        if (angkaSaja && (d.digit || 0) === 0) { langkah = Math.max(1, Math.round(langkah)); atas = Math.ceil(maks / langkah) * langkah; }
        var Y = function (v) { return tepi.atas + h - (v / atas) * h; };
        var slot = w / d.label.length, lebarBatang = Math.min(64, slot * 0.56);
        wadah.innerHTML = '';
        var svg = el('svg', { width: lebar, height: tinggi, 'aria-hidden': 'true' });
        for (var v = 0; v <= atas + 1e-6; v += langkah) {
            svg.appendChild(el('line', { 'class': 'g-bantu', x1: tepi.kiri, x2: lebar - tepi.kanan, y1: Y(v), y2: Y(v) }));
            var t = el('text', { 'class': 'g-teks', x: tepi.kiri - 8, y: Y(v) + 4, 'text-anchor': 'end' });
            t.textContent = angkaSaja ? fmt(v) : (v === 0 ? 'Rp 0' : ringkasRp(v));
            svg.appendChild(t);
        }
        var sorot = el('rect', { 'class': 'g-sorot', y: tepi.atas, height: h, width: slot, x: -999 });
        svg.appendChild(sorot);
        d.label.forEach(function (lbl, i) {
            var x = tepi.kiri + slot * i + (slot - lebarBatang) / 2, dasar = 0;
            d.seri.forEach(function (s, j) {
                var nilai = s.nilai[i] || 0;
                if (nilai <= 0) { return; }
                var y1 = Y(dasar + nilai), y0 = Y(dasar);
                var teratas = j === d.seri.length - 1;
                // Celah 2px antarsegmen; ujung atas membulat.
                var hh = Math.max(0, y0 - y1 - (dasar > 0 ? 2 : 0));
                svg.appendChild(el('rect', { 'class': 'g-batang ' + s.kelas, x: x, y: y1, width: lebarBatang, height: hh, rx: teratas ? 4 : 0 }));
                if (teratas && hh > 4) { svg.appendChild(el('rect', { 'class': 'g-batang ' + s.kelas, x: x, y: y1 + 4, width: lebarBatang, height: hh - 4 })); }
                dasar += nilai;
            });
            var tl = el('text', { 'class': 'g-teks', x: tepi.kiri + slot * i + slot / 2, y: tinggi - 6, 'text-anchor': 'middle' });
            tl.textContent = lbl;
            svg.appendChild(tl);
        });
        wadah.appendChild(svg);
        var tip = document.createElement('div');
        tip.className = 'tip-grafik';
        wadah.appendChild(tip);
        function tunjuk(i) {
            sorot.setAttribute('x', tepi.kiri + slot * i);
            tip.innerHTML = '';
            var b = document.createElement('b'); b.textContent = d.judul ? d.judul[i] : d.label[i]; tip.appendChild(b);
            d.seri.forEach(function (s) {
                var r = document.createElement('small');
                r.innerHTML = '<i class="titik-seri ' + s.kelas + '"></i>';
                r.appendChild(document.createTextNode(s.nama + ': ' + fmt(s.nilai[i] || 0)));
                tip.appendChild(r);
            });
            if (d.seri.length > 1) { var tot = document.createElement('small'); tot.textContent = 'Total: ' + fmt(total[i]); tip.appendChild(tot); }
            tip.style.left = Math.max(110, Math.min(lebar - 110, tepi.kiri + slot * i + slot / 2)) + 'px';
            tip.style.top = Y(total[i]) + 'px';
            wadah.classList.add('hover');
            wadah.setAttribute('data-indeks', i);
        }
        wadah.onpointermove = function (ev) {
            var x = ev.clientX - wadah.getBoundingClientRect().left - tepi.kiri;
            tunjuk(Math.max(0, Math.min(d.label.length - 1, Math.floor(x / slot))));
        };
        wadah.onpointerleave = function () { wadah.classList.remove('hover'); sorot.setAttribute('x', -999); };
        wadah.onblur = wadah.onpointerleave;
        wadah.onfocus = function () { tunjuk(d.label.length - 1); };
        wadah.onkeydown = function (ev) {
            var i = parseInt(wadah.getAttribute('data-indeks') || String(d.label.length - 1), 10);
            if (ev.key === 'ArrowLeft') { tunjuk(Math.max(0, i - 1)); ev.preventDefault(); }
            if (ev.key === 'ArrowRight') { tunjuk(Math.min(d.label.length - 1, i + 1)); ev.preventDefault(); }
        };
    }
    var batang = document.querySelectorAll('[data-grafik-batang]');
    batang.forEach(gambarBatang);
    if (batang.length) {
        var tundaBatang = null;
        window.addEventListener('resize', function () {
            clearTimeout(tundaBatang);
            tundaBatang = setTimeout(function () { batang.forEach(gambarBatang); }, 80);
        });
    }

    // ----- Hitung mundur menuju kick-off -----
    document.querySelectorAll('[data-hitung-mundur]').forEach(function (kotak) {
        var target = Date.parse(kotak.getAttribute('data-hitung-mundur'));
        if (isNaN(target)) { return; }
        function perbarui() {
            var sisa = Math.max(0, target - Date.now());
            var menit = Math.floor(sisa / 60000);
            var nilai = { hari: Math.floor(menit / 1440), jam: Math.floor((menit % 1440) / 60), menit: menit % 60 };
            Object.keys(nilai).forEach(function (k) {
                var el = kotak.querySelector('[data-satuan="' + k + '"]');
                if (el) { el.textContent = nilai[k]; }
            });
        }
        perbarui();
        setInterval(perbarui, 30000);
    });

    // ----- Konfirmasi sebelum aksi yang tidak bisa dibatalkan -----
    document.querySelectorAll('form[data-konfirmasi]').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            if (!window.confirm(form.getAttribute('data-konfirmasi'))) { ev.preventDefault(); }
        });
    });

    // ----- Formulir pengajuan: jumlah hari & sisa cuti langsung terlihat -----
    var formAju = document.querySelector('form[data-form-pengajuan]');
    if (formAju) {
        var jenisAju = formAju.querySelector('[name="jenis"]');
        var mulaiAju = formAju.querySelector('[name="tanggal_mulai"]');
        var selesaiAju = formAju.querySelector('[name="tanggal_selesai"]');
        var infoAju = formAju.querySelector('[data-info-hari]');
        var sisaCuti = parseInt(formAju.getAttribute('data-sisa-cuti'), 10);
        function hitungHari() {
            if (mulaiAju.value && (!selesaiAju.value || selesaiAju.value < mulaiAju.value)) {
                selesaiAju.value = mulaiAju.value;
            }
            selesaiAju.min = mulaiAju.value || '';
            if (!mulaiAju.value) { infoAju.textContent = ''; return; }
            var hari = Math.round((Date.parse(selesaiAju.value) - Date.parse(mulaiAju.value)) / 86400000) + 1;
            var teks = hari + ' hari';
            var lewat = false;
            if (jenisAju.value === 'cuti' && !isNaN(sisaCuti)) {
                lewat = hari > sisaCuti;
                teks += lewat ? ' — melebihi sisa cuti (' + sisaCuti + ' hari)' : ' · sisa cuti setelah ini ' + (sisaCuti - hari) + ' hari';
            }
            infoAju.textContent = teks;
            infoAju.classList.toggle('lewat', lewat);
        }
        [jenisAju, mulaiAju, selesaiAju].forEach(function (el) { el.addEventListener('change', hitungHari); });
        hitungHari();
    }

    // ----- Ganti password: syarat langsung + pengukur kekuatan -----
    var formPw = document.querySelector('form[data-form-password]');
    if (formPw) {
        var lama = formPw.querySelector('[name="password_lama"]');
        var baru = formPw.querySelector('[name="password_baru"]');
        var ulang = formPw.querySelector('[name="password_ulang"]');
        var meter = document.getElementById('meterPassword');
        var labelMeter = document.getElementById('labelMeter');
        var tombolKirim = formPw.querySelector('[data-kirim-password]');
        var teks = ['Belum diisi', 'Lemah', 'Cukup', 'Kuat', 'Sangat kuat'];

        function atur(nama, ok) {
            var li = document.querySelector('[data-syarat="' + nama + '"]');
            if (li) { li.classList.toggle('ok', ok); }
            return ok;
        }
        function periksaPw() {
            var v = baru.value;
            var panjang = atur('panjang', v.length >= 8);
            var huruf = atur('huruf', /[A-Za-z]/.test(v));
            var angka = atur('angka', /[0-9]/.test(v));
            var beda = atur('beda', v !== '' && v !== lama.value);
            var cocok = atur('cocok', v !== '' && v === ulang.value);

            var skor = 0;
            if (v.length >= 8) { skor++; }
            if (v.length >= 12) { skor++; }
            if (/[a-z]/.test(v) && /[A-Z]/.test(v) && /[0-9]/.test(v)) { skor++; }
            if (/[^A-Za-z0-9]/.test(v)) { skor++; }
            if (v === '') { skor = 0; } else if (skor === 0) { skor = 1; }
            meter.setAttribute('data-level', String(skor));
            labelMeter.textContent = 'Kekuatan password: ' + teks[skor];

            tombolKirim.disabled = !(panjang && huruf && angka && beda && cocok && lama.value !== '');
        }
        [lama, baru, ulang].forEach(function (el) { el.addEventListener('input', periksaPw); });
        periksaPw();
        formPw.addEventListener('submit', function () {
            tombolKirim.disabled = true;
            tombolKirim.lastChild.textContent = 'Menyimpan...';
        });
    }
})();
