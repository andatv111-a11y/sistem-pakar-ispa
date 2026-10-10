<?php
/**
 * =============================================================
 * Engine Perhitungan Sistem Pakar Diagnosa Awal ISPA
 * Metode: Naive Bayes + Certainty Factor
 * Studi Kasus: Puskesmas Kedungpring
 * =============================================================
 */

class DiagnosaISPA
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * ---------------------------------------------------------
     * 1. NAIVE BAYES
     * Menghitung probabilitas posterior P(C|G) untuk setiap penyakit
     * berdasarkan gejala yang dipilih, lalu dinormalisasi.
     *
     * @param  array $gejalaIds  daftar id_gejala yang dipilih user
     * @return array [id_penyakit => nilai_nb_ternormalisasi]
     * ---------------------------------------------------------
     */
    public function hitungNaiveBayes(array $gejalaIds): array
    {
        $gejalaIds = array_values(array_unique(array_map('intval', $gejalaIds)));

        $penyakitList = $this->pdo->query("SELECT * FROM penyakit ORDER BY id_penyakit")->fetchAll();

        // Total kasus (data training). Jika kosong, fallback ke jumlah penyakit.
        $totalKasus = (int) $this->pdo->query("SELECT COUNT(*) FROM kasus")->fetchColumn();
        if ($totalKasus <= 0) {
            $totalKasus = max(1, (int) $this->pdo->query("SELECT COUNT(DISTINCT id_penyakit) FROM rule_basis")->fetchColumn());
        }

        $hasil = [];
        foreach ($penyakitList as $p) {
            $idP = (int) $p['id_penyakit'];

            // Prior P(C) = jumlah kasus kelas / total kasus
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM kasus WHERE id_penyakit = ?");
            $stmt->execute([$idP]);
            $jmlKasus = (int) $stmt->fetchColumn();

            $prior = $jmlKasus > 0 ? ($jmlKasus / $totalKasus) : (1 / $totalKasus);

            // Likelihood ∏ P(Gi|C)
            $prob = $prior;
            foreach ($gejalaIds as $idG) {
                $stmt = $this->pdo->prepare("SELECT probabilitas FROM rule_basis WHERE id_penyakit = ? AND id_gejala = ?");
                $stmt->execute([$idP, $idG]);
                $pG = $stmt->fetchColumn();

                // Laplace smoothing kecil kalau rule tidak ada
                $prob *= ($pG !== false ? (float) $pG : 0.01);
            }
            $hasil[$idP] = $prob;
        }

        // Normalisasi P(C|G) = P(C)·∏P(G|C) / Σ ...
        $sum = array_sum($hasil);
        if ($sum > 0) {
            foreach ($hasil as $id => $v) {
                $hasil[$id] = $v / $sum;
            }
        }
        return $hasil;
    }

    /**
     * ---------------------------------------------------------
     * 2. CERTAINTY FACTOR
     * CF_pakar = MB - MD
     * Kombinasi: CF_combine = CF1 + CF2 × (1 - CF1)
     *
     * @param  array $gejalaIds  daftar id_gejala yang dipilih user
     * @return array [id_penyakit => nilai_cf]
     * ---------------------------------------------------------
     */
    public function hitungCertaintyFactor(array $gejalaIds): array
    {
        $gejalaIds = array_values(array_unique(array_map('intval', $gejalaIds)));
        $penyakitList = $this->pdo->query("SELECT * FROM penyakit ORDER BY id_penyakit")->fetchAll();

        $hasil = [];
        foreach ($penyakitList as $p) {
            $idP = (int) $p['id_penyakit'];
            $cfGabungan = 0;
            $pertama = true;

            foreach ($gejalaIds as $idG) {
                $stmt = $this->pdo->prepare("SELECT mb, md FROM rule_basis WHERE id_penyakit = ? AND id_gejala = ?");
                $stmt->execute([$idP, $idG]);
                $rule = $stmt->fetch();
                if (!$rule) continue;

                // CF dari pakar
                $cfPakar = (float) $rule['mb'] - (float) $rule['md'];

                if ($pertama) {
                    $cfGabungan = $cfPakar;
                    $pertama = false;
                } else {
                    $cfGabungan = $cfGabungan + $cfPakar * (1 - $cfGabungan);
                }
            }
            $hasil[$idP] = max($cfGabungan, 0);
        }
        return $hasil;
    }

    /**
     * ---------------------------------------------------------
     * 3. KOMBINASI AKHIR
     * Nilai Akhir = (NB × bobotNB) + (CF × bobotCF)
     * Default: 60% Naive Bayes + 40% Certainty Factor
     * ---------------------------------------------------------
     */
    public function hitungKombinasi(array $nb, array $cf, float $bobotNB = 0.6, float $bobotCF = 0.4): array
    {
        $hasil = [];
        foreach ($nb as $id => $v) {
            $nilaiCF = $cf[$id] ?? 0;
            $hasil[$id] = ($v * $bobotNB) + ($nilaiCF * $bobotCF);
        }
        arsort($hasil);
        return $hasil;
    }

    /**
     * ---------------------------------------------------------
     * 4. AMBIL DIAGNOSA TERBAIK
     * Mengembalikan id_penyakit pemenang + nilai NB, CF, Akhir
     * ---------------------------------------------------------
     */
    public function diagnosa(array $gejalaIds): array
    {
        $nb    = $this->hitungNaiveBayes($gejalaIds);
        $cf    = $this->hitungCertaintyFactor($gejalaIds);
        $akhir = $this->hitungKombinasi($nb, $cf);

        $idTerbaik = !empty($akhir) ? array_key_first($akhir) : null;

        return [
            'id_penyakit'  => $idTerbaik,
            'nilai_nb'     => $idTerbaik !== null ? ($nb[$idTerbaik] ?? 0) : 0,
            'nilai_cf'     => $idTerbaik !== null ? ($cf[$idTerbaik] ?? 0) : 0,
            'nilai_akhir'  => $idTerbaik !== null ? ($akhir[$idTerbaik] ?? 0) : 0,
            'detail_nb'    => $nb,
            'detail_cf'    => $cf,
            'ranking'      => $akhir
        ];
    }
}    