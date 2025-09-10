<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();
checkPermission('super_admin');

$page_title = 'Pengaturan - PEMILU ONLINE';
require_once '../includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_pemilihan = sanitizeInput($_POST['nama_pemilihan']);
    $tahun_pemilihan = sanitizeInput($_POST['tahun_pemilihan']);
    $warna_utama = sanitizeInput($_POST['warna_utama']);
    $waktu_mulai = sanitizeInput($_POST['waktu_mulai']);
    $waktu_selesai = sanitizeInput($_POST['waktu_selesai']);
    $waktu_pemilihan_enabled = isset($_POST['waktu_pemilihan_enabled']) ? 1 : 0;
    
    // Handle logo upload
    $logo_sekolah = getSetting('logo_sekolah');
    if (isset($_FILES['logo_sekolah']) && $_FILES['logo_sekolah']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['logo_sekolah'], UPLOAD_LOGO);
        if ($upload['success']) {
            if (!empty($logo_sekolah)) {
                @unlink(UPLOAD_LOGO . $logo_sekolah);
            }
            $logo_sekolah = $upload['filename'];
        } else {
            $_SESSION['error'] = $upload['message'];
            header("Location: settings.php");
            exit();
        }
    }
    
    // Handle favicon upload
    $favicon = getSetting('favicon');
    if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['favicon'], UPLOAD_LOGO, ['ico', 'png', 'jpg', 'jpeg']);
        if ($upload['success']) {
            if (!empty($favicon)) {
                @unlink(UPLOAD_LOGO . $favicon);
            }
            $favicon = $upload['filename'];
        } else {
            $_SESSION['error'] = $upload['message'];
            header("Location: settings.php");
            exit();
        }
    }
    
    // Update settings
    $sql = "UPDATE settings SET 
            nama_pemilihan = ?, 
            tahun_pemilihan = ?, 
            warna_utama = ?, 
            logo_sekolah = ?, 
            favicon = ?,
            waktu_mulai = ?,
            waktu_selesai = ?,
            waktu_pemilihan_enabled = ?
            WHERE id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssssi', 
        $nama_pemilihan, 
        $tahun_pemilihan, 
        $warna_utama, 
        $logo_sekolah, 
        $favicon,
        $waktu_mulai,
        $waktu_selesai,
        $waktu_pemilihan_enabled
    );
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Pengaturan berhasil diperbarui.';
    } else {
        $_SESSION['error'] = 'Gagal memperbarui pengaturan: ' . $conn->error;
    }
    
    header("Location: settings.php");
    exit();
}

// Get current settings
$settings = [];
$sql = "SELECT * FROM settings WHERE id = 1";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $settings = $result->fetch_assoc();
} else {
    // Insert default settings
    $sql = "INSERT INTO settings (
            nama_pemilihan, 
            tahun_pemilihan, 
            warna_utama,
            waktu_mulai,
            waktu_selesai
        ) VALUES ('Pemilihan Ketua OSIS', ?, '#87CEEB', '2024-01-01 08:00:00', '2024-12-31 16:00:00')";
    $stmt = $conn->prepare($sql);
    $tahun = date('Y');
    $stmt->bind_param('s', $tahun);
    $stmt->execute();
    
    $settings = [
        'nama_pemilihan' => 'Pemilihan Ketua OSIS',
        'tahun_pemilihan' => $tahun,
        'warna_utama' => '#87CEEB',
        'logo_sekolah' => '',
        'favicon' => '',
        'waktu_mulai' => '2024-01-01 08:00:00',
        'waktu_selesai' => '2024-12-31 16:00:00',
        'waktu_pemilihan_enabled' => 1
    ];
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold mb-2 d-flex align-items-center dashboard-title">
            <!-- <i class="fas fa-tachometer-alt me-2"></i> Dashboard -->
        </h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb custom-breadcrumb">
                <li class="breadcrumb-item active text-black" aria-current="page">
                    <i class="fas fa-cog me-2"></i> Pengaturan Sistem
                </li>
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            </ol>
        </nav>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Pengaturan Umum</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nama_pemilihan" class="form-label">Nama Pemilihan</label>
                            <input type="text" class="form-control" id="nama_pemilihan" name="nama_pemilihan" 
                                   value="<?php echo htmlspecialchars($settings['nama_pemilihan']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tahun_pemilihan" class="form-label">Tahun Pemilihan</label>
                            <input type="text" class="form-control" id="tahun_pemilihan" name="tahun_pemilihan" 
                                   value="<?php echo htmlspecialchars($settings['tahun_pemilihan']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="warna_utama" class="form-label">Warna Utama</label>
                        <div class="input-group colorpicker">
                            <input type="text" class="form-control" id="warna_utama" name="warna_utama" 
                                   value="<?php echo htmlspecialchars($settings['warna_utama']); ?>" required>
                            <span class="input-group-text"><i class="fas fa-square" style="color: <?php echo $settings['warna_utama']; ?>"></i></span>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="logo_sekolah" class="form-label">Logo Sekolah</label>
                            <input type="file" class="form-control" id="logo_sekolah" name="logo_sekolah" accept="image/*">
                            <div class="form-text">Ukuran maksimal 2MB. Format: JPG, JPEG, PNG</div>
                            
                            <?php if (!empty($settings['logo_sekolah'])): ?>
                                <div class="mt-2">
                                    <img src="<?php echo BASE_URL; ?>/assets/uploads/logo/<?php echo $settings['logo_sekolah']; ?>" 
                                         alt="Logo Sekolah" class="img-thumbnail" width="150">
                                    <div class="form-text">Logo saat ini</div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="favicon" class="form-label">Favicon</label>
                            <input type="file" class="form-control" id="favicon" name="favicon" accept=".ico,image/*">
                            <div class="form-text">Ukuran maksimal 500KB. Format: ICO, PNG</div>
                            
                            <?php if (!empty($settings['favicon'])): ?>
                                <div class="mt-2">
                                    <img src="<?php echo BASE_URL; ?>/assets/uploads/logo/<?php echo $settings['favicon']; ?>" 
                                         alt="Favicon" class="img-thumbnail" width="50">
                                    <div class="form-text">Favicon saat ini</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                <div class="col-md-6">
                    <label for="waktu_mulai" class="form-label">Waktu Mulai Pemilihan</label>
                    <input type="datetime-local" class="form-control" id="waktu_mulai" name="waktu_mulai" 
                           value="<?php echo date('Y-m-d\TH:i', strtotime($settings['waktu_mulai'])); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="waktu_selesai" class="form-label">Waktu Selesai Pemilihan</label>
                    <input type="datetime-local" class="form-control" id="waktu_selesai" name="waktu_selesai" 
                           value="<?php echo date('Y-m-d\TH:i', strtotime($settings['waktu_selesai'])); ?>" required>
                </div>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="waktu_pemilihan_enabled" name="waktu_pemilihan_enabled" 
                       <?php echo $settings['waktu_pemilihan_enabled'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="waktu_pemilihan_enabled">Aktifkan Pembatasan Waktu Pemilihan</label>
            </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Color Picker -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/3.4.0/css/bootstrap-colorpicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/3.4.0/js/bootstrap-colorpicker.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize color picker
    $('.colorpicker').colorpicker({
        format: 'hex'
    });
    
    // Update color preview when color changes
    $('.colorpicker').on('colorpickerChange', function(event) {
        $(this).find('.fa-square').css('color', event.color.toString());
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>