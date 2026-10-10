/**
 * =============================================================
 * voice.js — Voice Input Gejala dengan Fuzzy Matching
 * SIPAKAR-ISPA — Web Speech API
 * =============================================================
 */
(function () {
    'use strict';

    // ============================================
    // KAMUS SINONIM — kata alternatif (termasuk Jawa)
    // ============================================
    const SINONIM = {
        'demam':       ['demam','deman','panas','sumeng','meriang','hangat','gerah','mriyang'],
        'batuk kering':['batuk kering','batuk','batok','watuk','bathuk','tussis'],
        'batuk berdahak':['batuk berdahak','berdahak','dahak','lendir','berlendir','rek'],
        'pilek':       ['pilek','ingusan','meler','hidung tersumbat','mampet','selesma'],
        'sesak napas': ['sesak','sesek','napas berat','megap','megap-megap','susah napas','ngos-ngosan'],
        'nyeri tenggorokan':['nyeri tenggorokan','tenggorokan perih','sakit tenggorokan','radang','nyeri telan','perih'],
        'sakit kepala':['sakit kepala','pusing','nyeri kepala','cenut-cenut','kepala berat'],
        'nafsu makan menurun':['nafsu makan','tidak mau makan','males makan','mogok makan','susah makan'],
        'mengi':       ['mengi','wheezing','bunyi napas','ngik','ngek'],
        'nyeri dada':  ['nyeri dada','sakit dada','dada sakit','perih dada'],
        'suara serak': ['suara serak','serak','parau','sember'],
        'mata berair': ['mata berair','mata nangis','belekan','mata perih'],
        'muntah':      ['muntah','mutah','muuntah','ludah','mual','enek'],
        'lemas':       ['lemas','lemah','tidak bertenaga','lesu','ngantuk','lunglai'],
        'napas cepat': ['napas cepat','cepat napas','takipnea','napas pendek','megap'],
    };

    // ============================================
    // FUNGSI: Hitung kemiripan (Levenshtein Distance)
    // ============================================
    function kemiripan(a, b) {
        a = a.toLowerCase(); b = b.toLowerCase();
        if (a === b) return 1.0;
        if (a.length === 0 || b.length === 0) return 0;

        const matrix = [];
        for (let i = 0; i <= b.length; i++) matrix[i] = [i];
        for (let j = 0; j <= a.length; j++) matrix[0][j] = j;

        for (let i = 1; i <= b.length; i++) {
            for (let j = 1; j <= a.length; j++) {
                if (b.charAt(i - 1) === a.charAt(j - 1)) {
                    matrix[i][j] = matrix[i - 1][j - 1];
                } else {
                    matrix[i][j] = Math.min(
                        matrix[i - 1][j - 1] + 1,
                        matrix[i][j - 1] + 1,
                        matrix[i - 1][j] + 1
                    );
                }
            }
        }
        const jarak = matrix[b.length][a.length];
        const panjang = Math.max(a.length, b.length);
        return 1 - (jarak / panjang);
    }

    // ============================================
    // FUNGSI: Cek kecocokan kata dengan sinonim
    // ============================================
    function cekSinonim(teksUcapan, namaGejala) {
        const ucapan = teksUcapan.toLowerCase();
        const gejala = namaGejala.toLowerCase();

        // 1. Cek exact match
        if (ucapan.includes(gejala)) return 1.0;

        // 2. Cek sinonim
        for (const [kunci, daftarSinonim] of Object.entries(SINONIM)) {
            if (gejala.includes(kunci) || kunci.includes(gejala.split(' ')[0])) {
                for (const sin of daftarSinonim) {
                    if (ucapan.includes(sin)) return 0.95;
                }
            }
        }

        // 3. Cek fuzzy per kata
        const kataUcapan = ucapan.split(/\s+/);
        const kataGejala = gejala.split(/\s+/);
        let totalSkor = 0;
        let jumlah = 0;

        kataGejala.forEach(kG => {
            if (kG.length < 3) return;
            let skorTerbaik = 0;
            kataUcapan.forEach(kU => {
                const s = kemiripan(kU, kG);
                if (s > skorTerbaik) skorTerbaik = s;
            });
            totalSkor += skorTerbaik;
            jumlah++;
        });

        return jumlah > 0 ? (totalSkor / jumlah) : 0;
    }

    // ============================================
    // INISIALISASI
    // ============================================
    const btn       = document.getElementById('btnVoice');
    const statusEl  = document.getElementById('voiceStatus');
    const previewEl = document.getElementById('voicePreview');

    if (!btn) return;

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
        btn.style.display = 'none';
        if (statusEl) {
            statusEl.innerHTML = '⚠️ Browser tidak mendukung input suara.<br>Gunakan <strong>Google Chrome</strong> atau <strong>Microsoft Edge</strong>.';
            statusEl.style.color = '#dc3545';
        }
        return;
    }

    const recognition = new SpeechRecognition();
    recognition.lang = 'id-ID';
    recognition.interimResults = false;
    recognition.maxAlternatives = 3;
    recognition.continuous = false;

    let recording = false;

    btn.addEventListener('click', function () {
        if (recording) { recognition.stop(); return; }
        try { recognition.start(); } catch (e) { alert('Error: ' + e.message); }
    });

    recognition.onstart = function () {
        recording = true;
        btn.textContent = '🔴 Mendengarkan... (klik stop)';
        btn.style.background = '#dc3545';
        if (statusEl) {
            statusEl.innerHTML = '🎙️ Silakan ucapkan gejala...<br><em>Contoh: "demam dan batuk kering"</em>';
            statusEl.style.color = '#0d6efd';
        }
        if (previewEl) previewEl.innerHTML = '';
    };

    recognition.onend = function () {
        recording = false;
        btn.textContent = '🎙️ Input Gejala via Suara';
        btn.style.background = '#0d6efd';
    };

    recognition.onerror = function (event) {
        recording = false;
        btn.textContent = '🎙️ Input Gejala via Suara';
        btn.style.background = '#0d6efd';
        if (statusEl) {
            let pesan = '❌ Error: ' + event.error;
            if (event.error === 'no-speech') pesan = '⚠️ Tidak ada suara terdeteksi. Coba lagi.';
            if (event.error === 'not-allowed') pesan = '🚫 Izin microphone ditolak. Beri izin di browser.';
            if (event.error === 'network') pesan = '📡 Butuh koneksi internet untuk voice input.';
            statusEl.innerHTML = pesan;
            statusEl.style.color = '#dc3545';
        }
    };

    recognition.onresult = function (event) {
        // Gabungkan semua alternatif
        const semua = [];
        for (let i = 0; i < event.results.length; i++) {
            for (let j = 0; j < event.results[i].length; j++) {
                semua.push(event.results[i][j].transcript);
            }
        }
        const teks = semua.join(' ').toLowerCase();

        // Update status
        if (statusEl) {
            statusEl.innerHTML = '📢 Terdeteksi: <strong>"' + teks + '"</strong>';
            statusEl.style.color = '#0d6efd';
        }

        // Cocokkan dengan gejala di form
        const checkboxes = document.querySelectorAll('input[name="gejala[]"]');
        let hasilMatch = [];

        checkboxes.forEach(cb => {
            const label = cb.closest('label');
            if (!label) return;
            const teksLabel = label.textContent.trim();

            // Ambil nama gejala (tanpa kode G01)
            const namaGejala = teksLabel.replace(/^G\d+\s*[—-]?\s*/i, '').trim();

            // Hitung skor
            const skor = cekSinonim(teks, namaGejala);

            if (skor >= 0.65) {
                cb.checked = true;
                hasilMatch.push({ label, namaGejala, skor, cb });
            }
        });

        // Preview hasil
        if (previewEl) {
            if (hasilMatch.length === 0) {
                previewEl.innerHTML = `
                    <div style="background:#fff3cd; padding:12px; border-radius:8px; border-left:4px solid #ffc107; margin-top:10px;">
                        <strong>⚠️ Tidak ada gejala yang cocok</strong><br>
                        <small>Coba ucapkan lebih jelas. Contoh: "demam", "batuk", "pilek"</small>
                    </div>`;
            } else {
                let html = `
                    <div style="background:#d1e7dd; padding:12px; border-radius:8px; border-left:4px solid #198754; margin-top:10px;">
                        <strong>✅ ${hasilMatch.length} gejala terdeteksi otomatis:</strong>
                        <ul style="margin:8px 0 0 20px; padding:0;">`;
                hasilMatch
                    .sort((a, b) => b.skor - a.skor)
                    .forEach(m => {
                        const persen = Math.round(m.skor * 100);
                        const warna = persen >= 90 ? '#198754' : (persen >= 75 ? '#ffc107' : '#dc3545');
                        html += `<li style="font-size:13px; padding:3px 0;">
                            ${m.namaGejala}
                            <span style="color:${warna}; font-weight:bold; margin-left:6px;">(${persen}%)</span>
                        </li>`;
                    });
                html += `</ul>
                    <p style="font-size:12px; color:#666; margin-top:8px;">
                        💡 Cek ulang di bawah. Kalau ada yang salah, uncheck manual.
                    </p>
                </div>`;
                previewEl.innerHTML = html;

                // Highlight label gejala yang terpilih
                hasilMatch.forEach(m => {
                    m.label.style.background = '#d1e7dd';
                    m.label.style.borderColor = '#198754';
                    setTimeout(() => {
                        m.label.style.background = '';
                        m.label.style.borderColor = '';
                    }, 3000);
                });
            }
        }
    };
})();