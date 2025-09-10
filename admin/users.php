<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../vendor/autoload.php';

redirectIfNotLoggedIn();

// Check permission for panitia
if ($_SESSION['admin_level'] === 'panitia') {
    checkPermission('panitia');
}

$page_title = 'Kelola Pemilih - PEMILU ONLINE';
require_once '../includes/header.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$error_import = '';
$success_import = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file']['tmp_name'];
    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Asumsi baris pertama adalah header
        foreach (array_slice($rows, 1) as $row) {
            $nis   = isset($row[0]) ? trim($row[0]) : '';
            $nama  = isset($row[1]) ? trim($row[1]) : '';
            $kelas = isset($row[2]) ? trim($row[2]) : '';

            if ($nis && $nama && $kelas) {
                // Cek duplikat NIS
                $cek = $conn->prepare("SELECT id FROM pemilih WHERE nis=?");
                $cek->bind_param('s', $nis);
                $cek->execute();
                $cek->store_result();
                if ($cek->num_rows == 0) {
                    $stmt = $conn->prepare("INSERT INTO pemilih (nis, nama, kelas) VALUES (?, ?, ?)");
                    $stmt->bind_param('sss', $nis, $nama, $kelas);
                    $stmt->execute();
                }
                $cek->close();
            }
        }
        $success_import = "Import berhasil!";
    } catch (Exception $e) {
        $error_import = "Gagal import: " . $e->getMessage();
    }
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        if (isset($_POST['add_pemilih'])) {
            $nis = isset($_POST['nis']) ? formatNIS($_POST['nis']) : '';
            $nama = isset($_POST['nama']) ? sanitizeInput($_POST['nama']) : '';
            $kelas = isset($_POST['kelas']) ? sanitizeInput($_POST['kelas']) : '';

            if (empty($nis) || empty($nama) || empty($kelas)) {
                throw new Exception('Semua field harus diisi');
            }

            // Check if NIS exists
            $sql = "SELECT id FROM pemilih WHERE nis = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $nis);
            $stmt->execute();
            $stmt->store_result();  // Store the result

            if ($stmt->num_rows > 0) {
                throw new Exception('NIS sudah terdaftar');
            }

            // Insert to database
            $sql = "INSERT INTO pemilih (nis, nama, kelas) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sss', $nis, $nama, $kelas);

            if (!$stmt->execute()) {
                throw new Exception('Gagal menambahkan pemilih');
            }

            $_SESSION['success'] = 'Pemilih berhasil ditambahkan';
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }

    header("Location: users.php");
    exit();
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$daftar_pemilih = getPemilihData($filter);

// Build query based on filter
$where = '';
if ($filter === 'sudah') {
    $where = 'WHERE sudah_memilih = TRUE';
} elseif ($filter === 'belum') {
    $where = 'WHERE sudah_memilih = FALSE';
}

// Get all pemilih
$sql = "SELECT * FROM pemilih $where ORDER BY nama ASC";
$result = $conn->query($sql);
$pemilih = $result->fetch_all(MYSQLI_ASSOC);

// Get counts
$sql_all = "SELECT COUNT(*) AS total FROM pemilih";
$total_pemilih = $conn->query($sql_all)->fetch_assoc()['total'];

$sql_sudah = "SELECT COUNT(*) AS total FROM pemilih WHERE sudah_memilih = TRUE";
$sudah_memilih = $conn->query($sql_sudah)->fetch_assoc()['total'];

$sql_belum = "SELECT COUNT(*) AS total FROM pemilih WHERE sudah_memilih = FALSE";
$belum_memilih = $conn->query($sql_belum)->fetch_assoc()['total'];
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold mb-2 d-flex align-items-center dashboard-title">
            <!-- <i class="fas fa-tachometer-alt me-2"></i> Dashboard -->
        </h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb custom-breadcrumb">
                <li class="breadcrumb-item active text-black" aria-current="page">
                    <i class="fas fa-user-friends me-2"></i> Kelola Pemilih
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
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Pemilih</h5>
                    <div>
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addPemilihModal">
                            <i class="fas fa-plus me-1"></i>Tambah
                        </button>
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#importPemilihModal">
                            <i class="fas fa-file-import me-1"></i>Import
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="btn-group" role="group">
                        <a href="?filter=all" class="btn btn-outline-primary <?= $filter === 'all' ? 'active' : '' ?>">
                            Semua <span class="badge bg-primary"><?= $total_pemilih ?></span>
                        </a>
                        <a href="?filter=sudah" class="btn btn-outline-success <?= $filter === 'sudah' ? 'active' : '' ?>">
                            Sudah Memilih <span class="badge bg-success"><?= $sudah_memilih ?></span>
                        </a>
                        <a href="?filter=belum" class="btn btn-outline-warning <?= $filter === 'belum' ? 'active' : '' ?>">
                            Belum Memilih <span class="badge bg-warning"><?= $belum_memilih ?></span>
                        </a>
                    </div>
                </div>

                <?php if (count($daftar_pemilih) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">No</th>
                                    <th>NIS</th>
                                    <th>Nama</th>
                                    <th>Kelas</th>
                                    <th>Status</th>
                                    <th>Pilihan</th>
                                    <th>Tanda Tangan</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($daftar_pemilih as $index => $p): ?>
                                    <tr class="<?= $p['sudah_memilih'] ? 'table-success' : 'table-warning' ?>">
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($p['nis']) ?></td>
                                        <td><?= htmlspecialchars($p['nama']) ?></td>
                                        <td><?= htmlspecialchars($p['kelas']) ?></td>
                                        <td>
                                            <?php if ($p['sudah_memilih']): ?>
                                                <span class="badge bg-success">Sudah</span>
                                                <small class="text-muted d-block"><?= date('d/m/Y H:i', strtotime($p['waktu_memilih'])) ?></small>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Belum</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $p['nama_kandidat'] ?? '-' ?></td>
                                        <td>
                                            <?php if (!empty($p['tanda_tangan'])): ?>
                                                <img src="../assets/uploads/signatures/<?= $p['tanda_tangan'] ?>"
                                                    alt="Tanda Tangan" class="img-thumbnail" width="80">
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($p['sudah_memilih']): ?>
                                                <a href="export_pdf.php?id=<?= $p['id'] ?>"
                                                    class="btn btn-sm btn-info" target="_blank">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($_SESSION['admin_level'] === 'super_admin' && $p['sudah_memilih']): ?>
                                                <!-- <button class="btn btn-sm btn-warning reset-pemilih"
                                                    data-id="<?= $p['id'] ?>"
                                                    data-name="<?= htmlspecialchars($p['nama']) ?>">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button> -->
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center mb-0">
                        <i class="fas fa-info-circle me-2"></i> Tidak ada data pemilih yang ditemukan.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Pemilih Modal -->
<div class="modal fade" id="addPemilihModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Tambah Pemilih</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nis" class="form-label">NIS</label>
                        <input type="text" class="form-control" id="nis" name="nis"
                            placeholder="Contoh: 19680/041.063" required>
                        <div class="form-text">Format NIS: 19680/041.063 (tanpa spasi)</div>
                    </div>
                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama</label>
                        <input type="text" class="form-control" id="nama" name="nama" required>
                    </div>
                    <div class="mb-3">
                        <label for="kelas" class="form-label">Kelas</label>
                        <input type="text" class="form-control" id="kelas" name="kelas" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" name="add_pemilih">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Edit Pemilih Modal -->
<div class="modal fade" id="editPemilihModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Pemilih</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_nis" class="form-label">NIS</label>
                        <input type="text" class="form-control" id="edit_nis" name="nis" required>
                        <div class="form-text">Format NIS: 19680/041.063 (tanpa spasi)</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_nama" class="form-label">Nama</label>
                        <input type="text" class="form-control" id="edit_nama" name="nama" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_kelas" class="form-label">Kelas</label>
                        <input type="text" class="form-control" id="edit_kelas" name="kelas" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" name="edit_pemilih">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Pemilih Modal -->
<div class="modal fade" id="deletePemilihModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-trash-alt me-2"></i>Hapus Pemilih</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus pemilih <strong id="delete_nama"></strong>?</p>
                    <p class="text-danger">Data akan dihapus permanen dan tidak dapat dikembalikan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger" name="delete_pemilih">Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Pemilih Modal -->
<div class="modal fade" id="importPemilihModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-file-import me-2"></i>Import Data Pemilih (Excel)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if ($error_import): ?>
                        <div class="alert alert-danger"><?= $error_import ?></div>
                    <?php endif; ?>
                    <?php if ($success_import): ?>
                        <div class="alert alert-success"><?= $success_import ?></div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="excel_file" class="form-label">File Excel</label>
                        <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                        <div class="form-text">
                            Format file: <b>NIS</b>, <b>Nama</b>, <b>Kelas</b> (baris pertama header).
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" name="import_pemilih">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Pemilih Modal -->
<div class="modal fade" id="resetPemilihModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="id" id="reset_id">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title"><i class="fas fa-sync-alt me-2"></i>Reset Status Pemilih</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin mereset status pemilih <strong id="reset_nama"></strong>?</p>
                    <p class="text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Pemilih ini akan dapat memilih kembali setelah reset.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning" name="reset_pemilih">Reset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Pemilih Modal -->
<div class="modal fade" id="importPemilihModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-file-import me-2"></i>Import Data Pemilih</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file_csv" class="form-label">File CSV</label>
                        <input type="file" class="form-control" id="file_csv" name="file_csv" accept=".csv" required>
                        <div class="form-text">
                            Format file CSV dengan kolom: NIS, Nama, Kelas.
                            <a href="sample_pemilih.csv" download class="text-primary">Download contoh file</a>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" name="import_pemilih">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Paste Pemilih Modal -->
<div class="modal fade" id="pastePemilihModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-paste me-2"></i>Paste Data Pemilih</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="paste_data" class="form-label">Data Pemilih</label>
                        <textarea class="form-control" id="paste_data" name="paste_data" rows="10" required></textarea>
                        <div class="form-text">
                            Format: NIS [tab] Nama [tab] Kelas (per baris). Contoh:<br>
                            <code>19680/041.063 John Doe XII TKJ 1</code><br>
                            Atau NIS,Nama,Kelas (format CSV). Contoh:<br>
                            <code>19680/041.063,John Doe,XII TKJ 1</code>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" name="paste_pemilih">Proses</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Handle edit modal
        $('#editPemilihModal').on('show.bs.modal', function(event) {
            const button = $(event.relatedTarget);
            const id = button.data('id');
            const nis = button.data('nis');
            const nama = button.data('nama');
            const kelas = button.data('kelas');

            const modal = $(this);
            modal.find('#edit_id').val(id);
            modal.find('#edit_nis').val(nis);
            modal.find('#edit_nama').val(nama);
            modal.find('#edit_kelas').val(kelas);
        });

        // Handle delete modal
        $('#deletePemilihModal').on('show.bs.modal', function(event) {
            const button = $(event.relatedTarget);
            const id = button.data('id');
            const nama = button.data('nama');

            const modal = $(this);
            modal.find('#delete_id').val(id);
            modal.find('#delete_nama').text(nama);
        });

        // Handle reset modal
        $('#resetPemilihModal').on('show.bs.modal', function(event) {
            const button = $(event.relatedTarget);
            const id = button.data('id');
            const nama = button.data('nama');

            const modal = $(this);
            modal.find('#reset_id').val(id);
            modal.find('#reset_nama').text(nama);
        });

        // Format NIS input (remove spaces)
        $('input[name="nis"]').on('input', function() {
            $(this).val($(this).val().replace(/\s+/g, ''));
        });

        // Handle reset pemilih
        $('.reset-pemilih').click(function() {
            const id = $(this).data('id');
            const name = $(this).data('name');

            if (confirm(`Anda yakin ingin mereset status pemilih ${name}?`)) {
                window.location.href = `reset_pemilih.php?id=${id}`;
            }
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>