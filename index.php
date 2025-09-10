<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = 'Beranda - PEMILU ONLINE SMKN 1 LUMAJANG';
require_once 'includes/header.php';

// Get all candidates
$sql = "SELECT * FROM kandidat ORDER BY nama ASC";
$result = $conn->query($sql);
$kandidat = $result->fetch_all(MYSQLI_ASSOC);
?>

<link rel="icon" href="assets/uploads/logo/IMG_20230612_125630(1).png" type="image/x-icon">

<style>

      :root {
            --primary-color: <?php echo getSetting('warna_utama') ?: '#87CEEB'; ?>;
            --primary-hover: <?php echo adjustBrightness(getSetting('warna_utama') ?: '#87CEEB', -20); ?>;
            --header-height: <?php echo (isAdminLoggedIn() ? '60px' : '70px'); ?>;
        }
    body {
                padding-top: 120px; /* Lebih besar untuk mobile */
            }
</style>
<div class="row justify-content-center animate-on-scroll">
    <div class="col-lg-8 text-center mb-4">
        <h1 class="display-4 fw-bold text-primary"><?php echo getSetting('nama_pemilihan') ?: 'PEMILU ONLINE'; ?></h1>
        <h2 class="h4 text-muted">SMKN 1 LUMAJANG</h2>
        <p class="lead mt-3"><?php echo getSetting('tahun_pemilihan') ?: date('Y'); ?></p>
        
        <div class="d-flex justify-content-center gap-3 mt-4">
            <a href="vote.php" class="btn btn-primary btn-lg px-4">
                <i class="fas fa-vote-yea me-2"></i>VOTE SEKARANG
            </a>
            <a href="hasil.php" class="btn btn-outline-primary btn-lg px-4">
                <i class="fas fa-chart-bar me-2"></i>LIHAT HASIL
            </a>
        </div>
    </div>
</div>

<div class="row mt-5 animate-on-scroll">
    <div class="col-12">
        <h3 class="text-center mb-4">KANDIDAT KETUA OSIS</h3>
        
        <div class="row justify-content-center">
            <?php if (count($kandidat) > 0): ?>
                <?php foreach ($kandidat as $k): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm h-100 animate-on-scroll">
                            <img src="<?php echo BASE_URL; ?>/assets/uploads/kandidat/<?php echo $k['foto']; ?>" class="card-img-top" alt="<?php echo $k['nama']; ?>" style="height: 100%; object-fit: cover; border-radius: 8px 8px 0 0;">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $k['nama']; ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted"><?php echo $k['kelas']; ?></h6>
                                <button class="btn btn-sm btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#visiMisiModal<?php echo $k['id']; ?>">
                                    Lihat Visi & Misi
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Visi & Misi Modal -->
                    <div class="modal fade" id="visiMisiModal<?php echo $k['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?php echo $k['nama']; ?> - <?php echo $k['kelas']; ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <h6>Visi:</h6>
                                        <p><?php echo nl2br($k['visi']); ?></p>
                                    </div>
                                    <div>
                                        <h6>Misi:</h6>
                                        <p><?php echo nl2br($k['misi']); ?></p>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <!-- <a href="vote.php" class="btn btn-primary">VOTE SEKARANG</a> -->
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Belum ada kandidat yang terdaftar.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row mt-5 animate-on-scroll">
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h4 class="card-title"><i class="fas fa-info-circle text-primary me-2"></i> Tentang PEMILU ONLINE</h4>
                <p class="card-text">
                    PEMILOS ONLINE adalah sistem pemilihan ketua OSIS secara digital yang memungkinkan seluruh siswa PKL SMKN 1 Lumajang 
                    untuk memilih kandidat secara online dengan mudah, cepat, dan transparan.
                </p>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h4 class="card-title"><i class="fas fa-question-circle text-primary me-2"></i> Cara Memilih</h4>
                <ol class="card-text">
                    <li>Klik tombol "VOTE SEKARANG"</li>
                    <li>Pilih salah satu kandidat</li>
                    <li>Masukkan NIS Anda</li>
                    <li>Tanda Tangan</li>
                    <li>Konfirmasi pilihan Anda</li>
                    <li>Selesai! Suara Anda telah tercatat</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>