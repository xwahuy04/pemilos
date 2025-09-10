<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

// Check permission for panitia
if ($_SESSION['admin_level'] === 'panitia') {
    checkPermission('panitia');
}

$page_title = 'Kelola Kandidat - PEMILU ONLINE';
require_once '../includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_kandidat'])) {
        $nama = sanitizeInput($_POST['nama']);
        $kelas = sanitizeInput($_POST['kelas']);
        $visi = sanitizeInput($_POST['visi']);
        $misi = sanitizeInput($_POST['misi']);
        
        // Handle file upload
        $foto = '';
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFile($_FILES['foto'], UPLOAD_KANDIDAT);
            if ($upload['success']) {
                $foto = $upload['filename'];
            } else {
                $_SESSION['error'] = $upload['message'];
                header("Location: kandidat.php");
                exit();
            }
        } else {
            $_SESSION['error'] = 'Foto kandidat wajib diupload.';
            header("Location: kandidat.php");
            exit();
        }
        
        // Insert to database
        $sql = "INSERT INTO kandidat (nama, kelas, foto, visi, misi) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssss', $nama, $kelas, $foto, $visi, $misi);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Kandidat berhasil ditambahkan.';
        } else {
            $_SESSION['error'] = 'Gagal menambahkan kandidat.';
        }
        
        header("Location: kandidat.php");
        exit();
    } elseif (isset($_POST['edit_kandidat'])) {
        $id = intval($_POST['id']);
        $nama = sanitizeInput($_POST['nama']);
        $kelas = sanitizeInput($_POST['kelas']);
        $visi = sanitizeInput($_POST['visi']);
        $misi = sanitizeInput($_POST['misi']);
        
        // Get current foto
        $current_foto = '';
        $sql = "SELECT foto FROM kandidat WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $current_foto = $result->fetch_assoc()['foto'];
        }
        
        // Handle file upload if new file is provided
        $foto = $current_foto;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFile($_FILES['foto'], UPLOAD_KANDIDAT);
            if ($upload['success']) {
                $foto = $upload['filename'];
                // Delete old foto if exists
                if (!empty($current_foto)) {
                    @unlink(UPLOAD_KANDIDAT . $current_foto);
                }
            } else {
                $_SESSION['error'] = $upload['message'];
                header("Location: kandidat.php");
                exit();
            }
        }
        
        // Update database
        $sql = "UPDATE kandidat SET nama = ?, kelas = ?, foto = ?, visi = ?, misi = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssi', $nama, $kelas, $foto, $visi, $misi, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Kandidat berhasil diperbarui.';
        } else {
            $_SESSION['error'] = 'Gagal memperbarui kandidat.';
        }
        
        header("Location: kandidat.php");
        exit();
   } elseif (isset($_POST['delete_kandidat'])) {
    $id = intval($_POST['id']);

    // Soft delete (tidak hapus permanen)
    $sql = "UPDATE kandidat SET deleted_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Kandidat berhasil dihapus (soft delete).';
    } else {
        $_SESSION['error'] = 'Gagal menghapus kandidat.';
    }

    header("Location: kandidat.php");
    exit();
    } elseif (isset($_POST['reset_suara'])) {
        // Reset all vote counts
        $sql = "UPDATE kandidat SET jumlah_suara = 0";
        if ($conn->query($sql)) {
            // Also reset pemilih data
            $sql = "UPDATE pemilih SET sudah_memilih = FALSE, waktu_memilih = NULL, pilihan = NULL";
            if ($conn->query($sql)) {
                $_SESSION['success'] = 'Semua data suara berhasil direset.';
            } else {
                $_SESSION['error'] = 'Gagal mereset data pemilih.';
            }
        } else {
            $_SESSION['error'] = 'Gagal mereset data suara.';
        }
        
        header("Location: kandidat.php");
        exit();
    }
}

// Get all kandidat (hanya yang belum dihapus)
$sql = "SELECT * FROM kandidat WHERE deleted_at IS NULL ORDER BY nama ASC";
$result = $conn->query($sql);
$kandidat = $result->fetch_all(MYSQLI_ASSOC);

?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold mb-2 d-flex align-items-center dashboard-title">
            <!-- <i class="fas fa-tachometer-alt me-2"></i> Dashboard -->
        </h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb custom-breadcrumb">
                <li class="breadcrumb-item active text-black" aria-current="page">
                    <i class="fas fa-users me-2"></i> Kelola Kandidat
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
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Kandidat</h5>
                    <div>
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addKandidatModal">
                            <i class="fas fa-plus me-1"></i>Tambah
                        </button>
                        <?php if ($_SESSION['admin_level'] === 'super_admin'): ?>
                            <button class="btn btn-warning btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#resetModal">
                                <i class="fas fa-sync-alt me-1"></i>Reset Suara
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (count($kandidat) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">No</th>
                                    <th>Foto</th>
                                    <th>Nama</th>
                                    <th>Kelas</th>
                                    <th>Visi</th>
                                    <th>Misi</th>
                                    <th>Suara</th>
                                    <th width="150">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kandidat as $index => $k): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <img src="<?php echo BASE_URL; ?>/assets/uploads/kandidat/<?php echo $k['foto']; ?>" 
                                                 alt="<?php echo $k['nama']; ?>" 
                                                 class="img-thumbnail" 
                                                 width="80">
                                        </td>
                                        <td><?php echo $k['nama']; ?></td>
                                        <td><?php echo $k['kelas']; ?></td>
                                        <td><?php echo nl2br(substr($k['visi'], 0, 50) . (strlen($k['visi']) > 50 ? '...' : '')); ?></td>
                                        <td><?php echo nl2br(substr($k['misi'], 0, 50) . (strlen($k['misi']) > 50 ? '...' : '')); ?></td>
                                        <td><?php echo $k['jumlah_suara']; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info detail-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#detailKandidatModal"
                                                    data-id="<?= $k['id']; ?>"
                                                    data-nama="<?= htmlspecialchars($k['nama'], ENT_QUOTES); ?>"
                                                    data-kelas="<?= htmlspecialchars($k['kelas'], ENT_QUOTES); ?>"
                                                    data-visi="<?= htmlspecialchars($k['visi'], ENT_QUOTES); ?>"
                                                    data-misi="<?= htmlspecialchars($k['misi'], ENT_QUOTES); ?>"
                                                    data-foto="<?php echo BASE_URL; ?>/assets/uploads/kandidat/<?php echo $k['foto']; ?>"
                                                    data-suara="<?= $k['jumlah_suara']; ?>">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                            <button class="btn btn-sm btn-primary edit-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editKandidatModal"
                                                    data-id="<?= $k['id']; ?>"
                                                    data-nama="<?= htmlspecialchars($k['nama'], ENT_QUOTES); ?>"
                                                    data-kelas="<?= htmlspecialchars($k['kelas'], ENT_QUOTES); ?>"
                                                    data-visi="<?= htmlspecialchars($k['visi'], ENT_QUOTES); ?>"
                                                    data-misi="<?= htmlspecialchars($k['misi'], ENT_QUOTES); ?>"
                                                    data-foto="<?php echo BASE_URL; ?>/assets/uploads/kandidat/<?php echo $k['foto']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteKandidatModal" 
                                                    data-id="<?php echo $k['id']; ?>"
                                                    data-nama="<?php echo htmlspecialchars($k['nama']); ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center mb-0">
                        <i class="fas fa-info-circle me-2"></i> Belum ada kandidat yang terdaftar.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Kandidat Modal -->
<div class="modal fade" id="addKandidatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Tambah Kandidat</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nama" class="form-label">Nama Kandidat</label>
                            <input type="text" class="form-control" id="nama" name="nama" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="kelas" class="form-label">Kelas</label>
                            <input type="text" class="form-control" id="kelas" name="kelas" required>
                        </div>
                    </div>
                    
                   <div class="mb-3">
                        <label for="edit_foto" class="form-label">Foto Kandidat</label>
                        <div class="mb-2">
                            <img id="edit_preview" src=""
                                alt="Foto kandidat"
                                class="img-thumbnail"
                                width="120"
                                style="display:none;">
                        </div>
                        <input type="file" class="form-control" id="edit_foto" name="foto" accept="image/*">
                        <div class="form-text">Ukuran maksimal 2MB. Format: JPG, JPEG, PNG.</div>
                    </div>


                    
                    <div class="mb-3">
                        <label for="visi" class="form-label">Visi</label>
                        <textarea class="form-control" id="visi" name="visi" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="misi" class="form-label">Misi</label>
                        <textarea class="form-control" id="misi" name="misi" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" name="add_kandidat">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Kandidat Modal -->
<div class="modal fade" id="editKandidatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Kandidat</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_nama" class="form-label">Nama Kandidat</label>
                            <input type="text" class="form-control" id="edit_nama" name="nama" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_kelas" class="form-label">Kelas</label>
                            <input type="text" class="form-control" id="edit_kelas" name="kelas" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_foto" class="form-label">Foto Kandidat</label>
                        <input type="file" class="form-control" id="edit_foto" name="foto" accept="image/*">
                        <div class="form-text">Ukuran maksimal 2MB. Format: JPG, JPEG, PNG. Tambahkan Foto jika ingin mengedit foto</div>
                    </div>
                   <div class="mb-3">
                        <label class="form-label">Foto Lama:</label><br>
                        <img id="edit_preview_old" src="" alt="Foto Lama" class="img-thumbnail mb-2" style="max-width: 150px; display: none;">
                    </div>

                    
                    <div class="mb-3">
                        <label for="edit_visi" class="form-label">Visi</label>
                        <textarea class="form-control" id="edit_visi" name="visi" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_misi" class="form-label">Misi</label>
                        <textarea class="form-control" id="edit_misi" name="misi" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" name="edit_kandidat">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Kandidat Modal -->
<div class="modal fade" id="deleteKandidatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-trash-alt me-2"></i>Hapus Kandidat</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus kandidat <strong id="delete_nama"></strong>?</p>
                    <p class="text-danger">Semua data terkait kandidat ini akan dihapus dan tidak dapat dikembalikan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger" name="delete_kandidat">Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Suara Modal -->
<div class="modal fade" id="resetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title"><i class="fas fa-sync-alt me-2"></i>Reset Data Suara</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin mereset semua data suara?</p>
                    <p class="text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Semua data suara akan dihapus dan tidak dapat dikembalikan. 
                        Pemilih dapat memilih kembali setelah reset.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning" name="reset_suara">Reset Suara</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Detail Kandidat Modal -->
<div class="modal fade" id="detailKandidatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Detail Kandidat</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 text-center mb-3">
                        <img id="detail_foto" src="" alt="Foto Kandidat" class="img-thumbnail" width="150">
                    </div>
                    <div class="col-md-12">
                        <table class="table table-borderless">
                            <tr>
                                <th>Nama</th>
                                <td id="detail_nama"></td>
                            </tr>
                            <tr>
                                <th>Kelas</th>
                                <td id="detail_kelas"></td>
                            </tr>
                            <tr>
                                <th>Visi</th>
                                <td id="detail_visi"></td>
                            </tr>
                            <tr>
                                <th>Misi</th>
                                <td id="detail_misi"></td>
                            </tr>
                            <tr>
                                <th class="w-25" style="white-space: nowrap;">Jumlah Suara</th>
                                <td id="detail_suara"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Tangkap modal delete
    const deleteModalEl = document.getElementById("deleteKandidatModal");

    // Event show.bs.modal (pakai Bootstrap 5 native)
    deleteModalEl.addEventListener("show.bs.modal", function (event) {
        // Tombol yang memicu modal
        const button = event.relatedTarget;

        // Ambil data dari tombol
        const id = button.getAttribute("data-id");
        const nama = button.getAttribute("data-nama");

        // Set nilai ke dalam modal
        deleteModalEl.querySelector("#delete_id").value = id;
        deleteModalEl.querySelector("#delete_nama").textContent = nama;
    });
});
</script>


<script>
document.addEventListener('DOMContentLoaded', function() {
    var editModal = document.getElementById('editKandidatModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;

        var id = button.getAttribute('data-id');
        var nama = button.getAttribute('data-nama');
        var kelas = button.getAttribute('data-kelas');
        var visi = button.getAttribute('data-visi');
        var misi = button.getAttribute('data-misi');
        var foto = button.getAttribute('data-foto');

        editModal.querySelector('#edit_id').value = id;
        editModal.querySelector('#edit_nama').value = nama;
        editModal.querySelector('#edit_kelas').value = kelas;
        editModal.querySelector('#edit_visi').value = visi;
        editModal.querySelector('#edit_misi').value = misi;

        // Tampilkan foto lama
        var previewOld = editModal.querySelector('#edit_preview_old');
        if (foto && foto !== '') {
            previewOld.src = foto;
            previewOld.style.display = 'block';
        } else {
            previewOld.style.display = 'none';
        }

        // // Reset preview foto baru
        // var previewNew = editModal.querySelector('#edit_preview_new');
        // previewNew.style.display = 'none';
        // previewNew.src = '';

        // Reset input file
        var inputFoto = editModal.querySelector('#edit_foto');
        inputFoto.value = '';
    });

    // Preview foto baru saat file dipilih
    var inputFoto = document.getElementById('edit_foto');
    var previewNew = document.getElementById('edit_preview_new');
    inputFoto.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                previewNew.src = e.target.result;
                previewNew.style.display = 'block';
            }
            reader.readAsDataURL(this.files[0]);
        } else {
            previewNew.style.display = 'none';
            previewNew.src = '';
        }
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var detailModal = document.getElementById('detailKandidatModal');
    detailModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        detailModal.querySelector('#detail_nama').textContent = button.getAttribute('data-nama');
        detailModal.querySelector('#detail_kelas').textContent = button.getAttribute('data-kelas');
        detailModal.querySelector('#detail_visi').innerHTML = button.getAttribute('data-visi').replace(/\n/g, '<br>');
        detailModal.querySelector('#detail_misi').innerHTML = button.getAttribute('data-misi').replace(/\n/g, '<br>');
        detailModal.querySelector('#detail_suara').textContent = button.getAttribute('data-suara');
        detailModal.querySelector('#detail_foto').src = button.getAttribute('data-foto');
    });
});
</script>


<?php require_once '../includes/footer.php'; ?>