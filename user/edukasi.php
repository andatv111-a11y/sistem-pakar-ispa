<?php
require_once __DIR__ . '/../includes/auth.php';
requireUser();

// Ambil artikel dari database (kalau ada)
$artikel = [];
try {
    $artikel = $pdo->query("SELECT * FROM artikel ORDER BY tanggal DESC")->fetchAll();
} catch (Throwable $e) {
    $artikel = [];
}

// Ambil pengaturan puskesmas (kalau ada)
$config = [];
try {
    $rows = $pdo->query("SELECT kunci, nilai FROM pengaturan")->fetchAll();
    foreach ($rows as $r) $config[$r['kunci']] = $r['nilai'];
} catch (Throwable $e) {
    $config = [];
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>📚 Edukasi ISPA pada Balita</h2>
    <p style="color:#666; font-size:14px; margin-bottom:16px;">
        Panduan lengkap tentang Infeksi Saluran Pernapasan Akut (ISPA) untuk orang tua &amp; kader posyandu
        — <strong>Puskesmas Kedungpring</strong>
    </p>

    <!-- ============================== -->
    <!-- NAVIGASI CEPAT -->
    <!-- ============================== -->
    <div class="container" style="max-width:100%; background:#e7f1ff;">
        <strong>🔗 Navigasi Cepat:</strong>
        <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:10px;">
            <a href="#apa-itu" class="btn btn-sm">🦠 Apa Itu ISPA</a>
            <a href="#jenis" class="btn btn-sm">📋 Jenis ISPA</a>
            <a href="#gejala" class="btn btn-sm">🌡️ Gejala</a>
            <a href="#penyebab" class="btn btn-sm">🔍 Penyebab</a>
            <a href="#pencegahan" class="btn btn-sm">🛡️ Pencegahan</a>
            <a href="#penanganan" class="btn btn-sm">💊 Penanganan</a>
            <a href="#kapan-rujuk" class="btn btn-sm btn-danger">🚨 Kapan Rujuk</a>
            <a href="#mitos" class="btn btn-sm">❌ Mitos vs Fakta</a>
            <a href="#faq" class="btn btn-sm">❓ FAQ</a>
            <a href="#kontak" class="btn btn-sm btn-success">📞 Kontak</a>
        </div>
    </div>

    <!-- ============================== -->
    <!-- KARTU RINGKASAN CEPAT -->
    <!-- ============================== -->
    <div class="stats-grid" style="margin-top:20px;">
        <div class="stat-card" style="border-left-color:#0d6efd;">
            <div style="font-size:32px;">🦠</div>
            <p><strong>Apa Itu ISPA</strong><br>Infeksi saluran napas akut</p>
        </div>
        <div class="stat-card" style="border-left-color:#198754;">
            <div style="font-size:32px;">💡</div>
            <p><strong>Kenali Gejala</strong><br>Demam, batuk, pilek, sesak</p>
        </div>
        <div class="stat-card" style="border-left-color:#ffc107;">
            <div style="font-size:32px;">🛡️</div>
            <p><strong>Pencegahan</strong><br>ASI, imunisasi, kebersihan</p>
        </div>
        <div class="stat-card" style="border-left-color:#dc3545;">
            <div style="font-size:32px;">🚨</div>
            <p><strong>Rujuk Segera</strong><br>Napas cepat, lemas, biru</p>
        </div>
    </div>

    <!-- ============================== -->
    <!-- 1. APA ITU ISPA -->
    <!-- ============================== -->
    <div class="container" id="apa-itu" style="max-width:100%; margin-top:24px;">
        <h3>🦠 1. Apa Itu ISPA?</h3>
        <p style="line-height:1.9; font-size:15px; margin-top:10px;">
            <strong>ISPA (Infeksi Saluran Pernapasan Akut)</strong> adalah infeksi yang menyerang
            saluran pernapasan, mulai dari <strong>hidung, tenggorokan, hingga paru-paru</strong>.
            Penyakit ini berlangsung <strong>kurang dari 14 hari</strong> dan dapat disebabkan oleh
            virus, bakteri, atau mikroorganisme lainnya.
        </p>
        <p style="line-height:1.9; font-size:15px; margin-top:10px;">
            Pada <strong>balita (usia 0–5 tahun)</strong>, ISPA sangat sering terjadi karena sistem
            imun tubuh mereka masih berkembang. ISPA menjadi salah satu penyebab utama
            <strong>kunjungan ke Puskesmas</strong> dan merupakan penyebab kematian balita
            tertinggi di Indonesia jika tidak ditangani dengan cepat.
        </p>

        <div style="background:#f8fbff; padding:16px; border-radius:8px; margin-top:16px; border-left:4px solid #0d6efd;">
            <strong style="color:#0d6efd;">📊 Fakta Penting:</strong>
            <ul style="line-height:1.9; font-size:14px; padding-left:20px; margin-top:8px;">
                <li>ISPA menyumbang <strong>± 20% kematian balita</strong> di dunia</li>
                <li>Di Indonesia, ISPA adalah <strong>penyebab kematian #1 pada balita</strong></li>
                <li>Puncak kasus ISPA terjadi pada <strong>musim hujan</strong></li>
                <li>Deteksi dini menyelamatkan nyawa — <strong>jangan tunda ke Puskesmas!</strong></li>
            </ul>
        </div>
    </div>

    <!-- ============================== -->
    <!-- 2. JENIS ISPA -->
    <!-- ============================== -->
    <div class="container" id="jenis" style="max-width:100%; margin-top:24px;">
        <h3>📋 2. Jenis-Jenis ISPA</h3>
        <p style="font-size:14px; color:#666; margin-bottom:12px;">
            Berdasarkan tingkat keparahan &amp; lokasi infeksi:
        </p>

        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:14px;">

            <div style="background:#e7f1ff; padding:16px; border-radius:10px; border-left:4px solid #0d6efd;">
                <strong style="color:#0d6efd; font-size:15px;">1. ISPA Ringan</strong>
                <p style="font-size:13px; color:#444; margin-top:6px;">
                    Contoh: <em>Common Cold, Faringitis</em>.<br>
                    Gejala ringan: pilek, batuk, demam ringan. Bisa sembuh sendiri dalam 5–7 hari.
                </p>
            </div>

            <div style="background:#fff3cd; padding:16px; border-radius:10px; border-left:4px solid #ffc107;">
                <strong style="color:#d39e00; font-size:15px;">2. ISPA Sedang</strong>
                <p style="font-size:13px; color:#444; margin-top:6px;">
                    Contoh: <em>Bronkitis Akut, Tonsilitis</em>.<br>
                    Batuk berdahak, nyeri tenggorokan. Perlu pengobatan dokter.
                </p>
            </div>

            <div style="background:#f8d7da; padding:16px; border-radius:10px; border-left:4px solid #dc3545;">
                <strong style="color:#dc3545; font-size:15px;">3. ISPA Berat (Pneumonia)</strong>
                <p style="font-size:13px; color:#444; margin-top:6px;">
                    <strong>Bahaya!</strong> Infeksi paru-paru serius.<br>
                    Napas cepat, tarikan dinding dada, biru. <strong>Rujuk SEGERA ke RS.</strong>
                </p>
            </div>

        </div>
    </div>

    <!-- ============================== -->
    <!-- 3. GEJALA -->
    <!-- ============================== -->
    <div class="container" id="gejala" style="max-width:100%; margin-top:24px;">
        <h3>🌡️ 3. Gejala ISPA pada Balita</h3>

        <h4 style="color:#198754; margin-top:12px;">✅ Gejala Umum:</h4>
        <ul style="line-height:1.9; font-size:14px; padding-left:20px;">
            <li><strong>Demam</strong> — suhu tubuh &gt; 37,5°C</li>
            <li><strong>Batuk</strong> — kering atau berdahak</li>
            <li><strong>Pilek / hidung tersumbat</strong></li>
            <li><strong>Nyeri tenggorokan</strong> — balita rewel saat menelan</li>
            <li><strong>Nafsu makan menurun</strong></li>
            <li><strong>Mata berair</strong></li>
            <li><strong>Sakit kepala</strong> — balita rewel, pegang kepala</li>
        </ul>

        <h4 style="color:#dc3545; margin-top:16px;">🚨 Gejala Bahaya (Rujuk Segera!):</h4>
        <ul style="line-height:1.9; font-size:14px; padding-left:20px; color:#842029;">
            <li><strong>Napas cepat</strong> (&gt;50×/menit untuk usia 2–12 bulan, &gt;40×/menit untuk usia 1–5 tahun)</li>
            <li><strong>Tarikan dinding dada</strong> bagian bawah saat bernapas</li>
            <li><strong>Bibir atau kulit membiru</strong></li>
            <li><strong>Lemas / tidak sadar</strong></li>
            <li><strong>Tidak mau makan / minum</strong></li>
            <li><strong>Demam tinggi &gt; 39°C</strong> atau &gt; 3 hari</li>
            <li><strong>Muntah terus-menerus</strong></li>
        </ul>
    </div>

    <!-- ============================== -->
    <!-- 4. PENYEBAB -->
    <!-- ============================== -->
    <div class="container" id="penyebab" style="max-width:100%; margin-top:24px;">
        <h3>🔍 4. Penyebab &amp; Faktor Risiko</h3>

        <h4 style="color:#0d6efd; margin-top:12px;">🦠 Penyebab Utama:</h4>
        <ul style="line-height:1.9; font-size:14px; padding-left:20px;">
            <li><strong>Virus</strong> — Rhinovirus, Influenza, RSV, Adenovirus (90% kasus)</li>
            <li><strong>Bakteri</strong> — Streptococcus pneumoniae, Haemophilus influenzae</li>
            <li><strong>Jamur</strong> — jarang, biasanya pada balita dengan imun lemah</li>
        </ul>

        <h4 style="color:#ffc107; margin-top:16px;">⚠️ Faktor Risiko:</h4>
        <ul style="line-height:1.9; font-size:14px; padding-left:20px;">
            <li>Usia &lt; 2 tahun (sistem imun belum matang)</li>
            <li>Bayi tidak mendapat ASI eksklusif</li>
            <li>Imunisasi tidak lengkap</li>
            <li>Paparan asap rokok (aktif/pasif)</li>
            <li>Lingkungan padat &amp; kurang ventilasi</li>
            <li>Gizi buruk</li>
            <li>Berat badan lahir rendah (BBLR)</li>
            <li>Musim hujan / perubahan cuaca</li>
            <li>Polusi udara</li>
        </ul>
    </div>

    <!-- ============================== -->
    <!-- 5. PENCEGAHAN -->
    <!-- ============================== -->
    <div class="container" id="pencegahan" style="max-width:100%; margin-top:24px;">
        <h3>🛡️ 5. Cara Mencegah ISPA pada Balita</h3>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px; margin-top:14px;">

            <div style="background:#e7f1ff; padding:16px; border-radius:10px; border-left:4px solid #0d6efd;">
                <div style="font-size:28px;">🍼</div>
                <strong>1. ASI Eksklusif</strong>
                <p style="font-size:13px; color:#444; margin-top:6px;">
                    Berikan ASI eksklusif minimal 6 bulan, lanjutkan hingga 2 tahun.
                </p>
            </div>

            <div style="background:#d1e7dd; padding:16px; border-radius:10px; border-left:4px solid #198754;">
                <div style="font-size:28px;">💉</div>
                <strong>2. Imunisasi Lengkap</strong>
                <p style="font-size:13px; color:#444; margin-top:6px;">
                    Lengkapi BCG, DPT, Polio, Campak, dan PCV sesuai jadwal.
                </p>
            </div>

            <div style="background:#fff3cd; padding:16px; border-radius:10px; border-left:4px solid #ffc107;">
                <div style="font-size:28px;">🧼</div>
                <strong>3. Cuci Tangan</strong>
                <p style="font-size:13px; color:#444; margin-top:6px;">
                    Cuci tangan sebelum menyentuh balita, setelah dari luar rumah.
                </p>
            </div>

            <div style="background:#f8d7da; padding:16px; border-radius:10px; border-left:4px solid #dc3545;">
                <div style="font-size:28px;">🚭</div>
                <strong>4. Bebas Asap Rokok</strong>
                <p style="font-size:13px; color:#444; margin-top:6px;">
                    Jauhkan balita dari asap rokok dan polusi udara.
                </p>
            </div>

            <div style="background:#e2e3e5; padding:16px; border-radius:10px; border-left:4px solid #6c757d;">
                <div style="font-size:28px;">🥗</div>
                <strong>5. Gizi Seimbang</strong>
                <p style="font-size:13px; color:#444; margin-top:6px;">
                    Berikan makanan bergizi: protein, sayur, buah, dan air putih cukup.
                </p>
            </div>

            <div style="background:#cfe2ff; padding:16px; border-radius:10px; border-left:4px solid #0a58ca;">
                <div style="font-size:28px;">🏠</div>
                <strong>6. Rumah Sehat</strong>
                <p style="font-size:13px; color:#444; margin-top:6px;">
                    Pastikan ventilasi baik, sinar matahari masuk, rumah bersih.
                </p>
            </div>

            <div style="background:#d1e7dd; padding:16px; border-radius:10px; border-left:4px solid #198754;">
                <div style="font-size:28px;">🤧</div>
                <strong>7. Etika Batuk</strong>
                <p style="font-size:13px; color:#444; margin-top:6px;">
                    Ajarkan tutup mulut saat batuk, gunakan masker jika sakit.
                </p>
            </div>

            <div style="background:#fff3cd; padding:16px; border-radius:10px; border-left:4px solid #ffc107;">
                <div style="font-size:28px;">👥</div>
                <strong>8. Hindari Keramaian</strong>
                <p style="font-size:13px; color:#444; margin-top:6px;">
                    Kurangi kunjungan ke tempat ramai saat musim ISPA.
                </p>
            </div>

        </div>
    </div>

    <!-- ============================== -->
    <!-- 6. PENANGANAN -->
    <!-- ============================== -->
    <div class="container" id="penanganan" style="max-width:100%; margin-top:24px;">
        <h3>💊 6. Penanganan Awal di Rumah</h3>
        <p style="font-size:14px; color:#666; margin-bottom:12px;">
            Untuk ISPA ringan, orang tua bisa melakukan pertolongan pertama:
        </p>

        <div style="background:#f0f7ff; padding:16px; border-radius:8px; border-left:4px solid #0d6efd;">
            <ol style="line-height:1.9; font-size:14px; padding-left:20px;">
                <li><strong>Istirahat cukup</strong> — jauhkan dari keramaian</li>
                <li><strong>Beri minum lebih banyak</strong> — air hangat, ASI lebih sering</li>
                <li><strong>Bersihkan hidung</strong> — pakai larutan garam fisiologis (NaCl 0,9%)</li>
                <li><strong>Beri paracetamol</strong> — sesuai dosis berat badan, bukan aspirin</li>
                <li><strong>Kompres hangat</strong> — jika demam</li>
                <li><strong>Jaga kehangatan</strong> — pakai baju hangat, hindari AC</li>
                <li><strong>Uap air hangat</strong> — untuk melonggarkan hidung tersumbat</li>
                <li><strong>Berikan makanan bergizi</strong> — sup hangat, bubur, buah</li>
            </ol>
        </div>

        <div style="background:#f8d7da; padding:16px; border-radius:8px; border-left:4px solid #dc3545; margin-top:16px;">
            <strong style="color:#842029;">❌ JANGAN lakukan:</strong>
            <ul style="line-height:1.9; font-size:14px; padding-left:20px; margin-top:8px;">
                <li>Memberi antibiotik tanpa resep dokter</li>
                <li>Memberi obat orang dewasa ke balita</li>
                <li>Memberi aspirin (bahaya untuk balita)</li>
                <li>Memaksa balita makan saat mual</li>
                <li>Menutup tubuh terlalu rapat saat demam tinggi</li>
            </ul>
        </div>
    </div>

    <!-- ============================== -->
    <!-- 7. KAPAN RUJUK -->
    <!-- ============================== -->
    <div class="container" id="kapan-rujuk" style="max-width:100%; margin-top:24px; background:#f8d7da; border:1px solid #f5c2c7;">
        <h3 style="color:#842029;">🚨 7. Kapan Harus Segera ke Puskesmas?</h3>
        <p style="font-size:14px; color:#842029;">
            <strong>SEGERA</strong> bawa balita ke <strong>Puskesmas Kedungpring</strong> jika:
        </p>
        <ul style="line-height:1.9; font-size:14px; padding-left:20px; color:#842029; margin-top:8px;">
            <li>Napas cepat atau sesak</li>
            <li>Tarikan dinding dada saat bernapas</li>
            <li>Bibir atau kulit membiru</li>
            <li>Demam tinggi &gt; 39°C atau &gt; 3 hari</li>
            <li>Lemas / tidak sadar</li>
            <li>Tidak mau makan / minum</li>
            <li>Muntah terus-menerus</li>
            <li>Kejang</li>
            <li>Gejala tidak membaik setelah 3 hari</li>
        </ul>
        <p style="margin-top:12px; font-weight:bold; color:#842029;">
            ⏰ Jangan tunda! Deteksi dini menyelamatkan nyawa balita Anda.
        </p>
    </div>

    <!-- ============================== -->
    <!-- 8. MITOS vs FAKTA -->
    <!-- ============================== -->
    <div class="container" id="mitos" style="max-width:100%; margin-top:24px;">
        <h3>❌ Mitos vs ✅ Fakta</h3>
        <table>
            <thead>
                <tr><th width="50%">❌ Mitos</th><th>✅ Fakta</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>ISPA hanya terjadi saat musim hujan</td>
                    <td>ISPA bisa terjadi kapan saja, terutama saat imun lemah</td>
                </tr>
                <tr>
                    <td>Balita batuk pilek tidak perlu ke Puskesmas</td>
                    <td>ISPA ringan bisa sembuh sendiri, tapi harus dipantau. Jika memburuk → segera ke Puskesmas</td>
                </tr>
                <tr>
                    <td>Diberi antibiotik pasti cepat sembuh</td>
                    <td>ISPA 90% disebabkan virus. Antibiotik tidak efektif untuk virus, malah bisa merugikan</td>
                </tr>
                <tr>
                    <td>Balita demam harus dibungkus selimut tebal</td>
                    <td>Justru berbahaya! Tubuh tidak bisa melepas panas. Pakai baju tipis, kompres hangat</td>
                </tr>
                <tr>
                    <td>Asap rokok tidak apa-apa kalau di luar rumah</td>
                    <td>Asap rokok menempel di baju &amp; rambut. Balita tetap bisa terpapar (third-hand smoke)</td>
                </tr>
                <tr>
                    <td>Imunisasi menyebabkan ISPA</td>
                    <td>Imunisasi justru MELINDUNGI balita dari ISPA berat seperti pneumonia</td>
                </tr>
                <tr>
                    <td>ASI tidak perlu kalau sudah makan</td>
                    <td>ASI tetap penting hingga 2 tahun. Antibodi ASI melindungi dari ISPA</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ============================== -->
    <!-- 9. FAQ -->
    <!-- ============================== -->
    <div class="container" id="faq" style="max-width:100%; margin-top:24px;">
        <h3>❓ Pertanyaan yang Sering Ditanyakan (FAQ)</h3>

        <details style="padding:12px; background:#f8f9fa; border-radius:6px; margin-bottom:8px;">
            <summary style="cursor:pointer; font-weight:bold; color:#0d6efd;">
                Berapa lama ISPA biasanya sembuh?
            </summary>
            <p style="margin-top:8px; font-size:14px; line-height:1.7;">
                ISPA ringan (common cold) biasanya sembuh dalam <strong>5–7 hari</strong>.
                Jika lebih dari 10 hari tidak membaik atau memburuk, segera ke Puskesmas.
            </p>
        </details>

        <details style="padding:12px; background:#f8f9fa; border-radius:6px; margin-bottom:8px;">
            <summary style="cursor:pointer; font-weight:bold; color:#0d6efd;">
                Apakah ISPA menular?
            </summary>
            <p style="margin-top:8px; font-size:14px; line-height:1.7;">
                <strong>Ya, sangat menular</strong> — melalui percikan ludah (droplet) saat batuk/bersin,
                atau menyentuh benda yang terkontaminasi. Jauhkan balita sakit dari balita sehat.
            </p>
        </details>

        <details style="padding:12px; background:#f8f9fa; border-radius:6px; margin-bottom:8px;">
            <summary style="cursor:pointer; font-weight:bold; color:#0d6efd;">
                Kapan boleh beri obat penurun panas?
            </summary>
            <p style="margin-top:8px; font-size:14px; line-height:1.7;">
                Beri paracetamol jika suhu &gt; 38°C dan balita rewel. Dosis sesuai
                <strong>berat badan</strong>, bukan usia. Jangan beri aspirin. Ikuti petunjuk dokter/apoteker.
            </p>
        </details>

        <details style="padding:12px; background:#f8f9fa; border-radius:6px; margin-bottom:8px;">
            <summary style="cursor:pointer; font-weight:bold; color:#0d6efd;">
                Apakah balita boleh dimandikan saat demam?
            </summary>
            <p style="margin-top:8px; font-size:14px; line-height:1.7;">
                <strong>Boleh</strong>, dengan air hangat (bukan dingin). Mandi air hangat membantu
                menurunkan suhu tubuh. Hindari air dingin karena bisa membuat menggigil.
            </p>
        </details>

        <details style="padding:12px; background:#f8f9fa; border-radius:6px; margin-bottom:8px;">
            <summary style="cursor:pointer; font-weight:bold; color:#0d6efd;">
                Kapan balita boleh dibawa ke Puskesmas?
            </summary>
            <p style="margin-top:8px; font-size:14px; line-height:1.7;">
                Segera jika ada <strong>tanda bahaya</strong> (napas cepat, tarikan dinding dada, biru,
                lemas, tidak mau makan/minum). Untuk gejala ringan, bisa 2–3 hari dulu di rumah
                dengan istirahat &amp; minum cukup.
            </p>
        </details>

        <details style="padding:12px; background:#f8f9fa; border-radius:6px; margin-bottom:8px;">
            <summary style="cursor:pointer; font-weight:bold; color:#0d6efd;">
                Apakah ISPA bisa dicegah?
            </summary>
            <p style="margin-top:8px; font-size:14px; line-height:1.7;">
                <strong>Ya!</strong> Dengan ASI eksklusif, imunisasi lengkap, gizi seimbang, kebersihan tangan,
                dan menghindari asap rokok, risiko ISPA bisa ditekan hingga 50%.
            </p>
        </details>
    </div>

    <!-- ============================== -->
    <!-- 10. KONTAK PUSKESMAS -->
    <!-- ============================== -->
    <div class="container" id="kontak" style="max-width:100%; margin-top:24px; background:#d1e7dd; border:1px solid #a3cfbb;">
        <h3 style="color:#0f5132;">📞 10. Kontak Puskesmas Kedungpring</h3>
        <table>
            <tr>
                <td width="160"><strong>Nama</strong></td>
                <td>: <?= htmlspecialchars($config['nama_puskesmas'] ?? 'Puskesmas Kedungpring') ?></td>
            </tr>
            <tr>
                <td><strong>Alamat</strong></td>
                <td>: <?= htmlspecialchars($config['alamat_puskesmas'] ?? 'Jl. Raya Kedungpring, Kabupaten Lamongan') ?></td>
            </tr>
            <tr>
                <td><strong>Telepon</strong></td>
                <td>: <?= htmlspecialchars($config['telepon_puskesmas'] ?? '(0322) 123456') ?></td>
            </tr>
            <tr>
                <td><strong>Jam Buka</strong></td>
                <td>: Senin–Jumat, 07.30–14.00 WIB</td>
            </tr>
            <tr>
                <td><strong>Layanan</strong></td>
                <td>: Pemeriksaan umum, imunisasi, KIA, gizi, kesehatan anak</td>
            </tr>
        </table>
        <p style="margin-top:12px; font-size:14px; color:#0f5132;">
            💡 <strong>Tips:</strong> Datang lebih awal (07.30) untuk menghindari antrean panjang.
        </p>
    </div>

    <!-- ============================== -->
    <!-- ARTIKEL DARI DATABASE -->
    <!-- ============================== -->
    <?php if (!empty($artikel)): ?>
        <div class="container" style="max-width:100%; margin-top:24px;">
            <h3>📖 Artikel Tambahan dari Admin</h3>
            <?php foreach ($artikel as $a): ?>
                <article style="margin-bottom:18px; padding-bottom:14px; border-bottom:1px solid #e3e3e3;">
                    <h4 style="color:#0d6efd;">
                        📰 <?= htmlspecialchars($a['judul']) ?>
                    </h4>
                    <?php if (!empty($a['kategori'])): ?>
                        <p style="font-size:12px; color:#888;">
                            <strong><?= htmlspecialchars($a['kategori']) ?></strong> |
                            <?= date('d/m/Y', strtotime($a['tanggal'])) ?>
                        </p>
                    <?php endif; ?>
                    <p style="line-height:1.8; white-space:pre-line; font-size:14px; margin-top:8px;">
                        <?= htmlspecialchars($a['konten']) ?>
                    </p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ============================== -->
    <!-- DISCLAIMER -->
    <!-- ============================== -->
    <div class="container" style="max-width:100%; margin-top:24px; background:#fff3cd; border:1px solid #ffd54f;">
        <h3 style="color:#664d03;">⚠️ Disclaimer</h3>
        <p style="font-size:14px; line-height:1.8; color:#664d03;">
            Informasi dalam halaman ini bersifat <strong>edukasi umum</strong> dan <strong>bukan pengganti
            konsultasi medis profesional</strong>. Untuk diagnosis &amp; penanganan yang tepat, selalu
            konsultasikan dengan <strong>dokter atau tenaga kesehatan</strong> di Puskesmas Kedungpring.
        </p>
    </div>

    <!-- ============================== -->
    <!-- AKSI -->
    <!-- ============================== -->
    <div class="container" style="max-width:100%; margin-top:24px; text-align:center;">
        <h3>Siap Lakukan Diagnosa Awal?</h3>
        <p style="font-size:14px; color:#666; margin-bottom:16px;">
            Gunakan sistem pakar untuk diagnosa awal ISPA pada balita Anda.
        </p>
        <a href="diagnosa.php" class="btn btn-success" style="font-size:15px; padding:12px 24px;">
            🩺 Mulai Diagnosa Sekarang
        </a>
        <a href="dashboard.php" class="btn btn-warning" style="font-size:15px; padding:12px 24px;">
            🏠 Kembali ke Dashboard
        </a>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>