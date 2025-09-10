<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

$page_title = 'Rekapitulasi - PEMILU ONLINE';
require_once '../includes/header.php';

// Get all kandidat with vote count
$sql = "SELECT * FROM kandidat ORDER BY jumlah_suara DESC";
$result = $conn->query($sql);
$kandidat = $result->fetch_all(MYSQLI_ASSOC);

// Get total votes
$total_suara = 0;
foreach ($kandidat as $k) {
    $total_suara += $k['jumlah_suara'];
}

// Get total pemilih
$sql = "SELECT COUNT(*) AS total FROM pemilih";
$total_pemilih = $conn->query($sql)->fetch_assoc()['total'];

// Get total yang sudah memilih
$sql = "SELECT COUNT(*) AS total FROM pemilih WHERE sudah_memilih = TRUE";
$sudah_memilih = $conn->query($sql)->fetch_assoc()['total'];

// Get pemilih by kelas - PERBAIKAN: Tambahkan kondisi WHERE sudah_memilih = TRUE
$sql = "SELECT kelas,
              COUNT(*) AS total,
              SUM(sudah_memilih) AS sudah,
              COUNT(*) - SUM(sudah_memilih) AS belum
        FROM pemilih
        WHERE sudah_memilih = TRUE
        GROUP BY kelas
        ORDER BY kelas ASC";
$pemilih_kelas = $conn->query($sql);
$kelas_data = [];
if ($pemilih_kelas) {
    $kelas_data = $pemilih_kelas->fetch_all(MYSQLI_ASSOC);
}

// PERBAIKAN: Query untuk daftar pemilih dengan filter yang benar
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where = '';
if ($filter === 'sudah') {
    $where = 'WHERE sudah_memilih = TRUE';
} elseif ($filter === 'belum') {
    $where = 'WHERE sudah_memilih = FALSE';
}

$sql = "SELECT p.*, k.nama AS nama_kandidat
        FROM pemilih p
        LEFT JOIN kandidat k ON p.pilihan = k.id
        $where
        ORDER BY p.nama ASC";
$result = $conn->query($sql);
$daftar_pemilih = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<style>
    /* Mengatur ukuran canvas untuk diagram */
    canvas {
        max-width: 400px;
        max-height: 250px;
        width: 100%;
        height: auto;
    }

    #rekapChart {
        max-width: 400px;
        max-height: 250px;
        width: 100%;
        height: auto;
        display: block;
        margin: auto;
    }
</style>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold mb-2 d-flex align-items-center dashboard-title">
            </h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb custom-breadcrumb">
                <li class="breadcrumb-item active text-black" aria-current="page">
                    <i class="fas fa-chart-bar me-2"></i> Rekapitulasi Hasil
                </li>
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            </ol>
        </nav>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Statistik Pemilihan</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body text-center">
                                <h6 class="card-title text-uppercase">Total Pemilih</h6>
                                <h3 class="mb-0"><?php echo $total_pemilih; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body text-center">
                                <h6 class="card-title text-uppercase">Sudah Memilih</h6>
                                <h3 class="mb-0"><?php echo $sudah_memilih; ?></h3>
                                <small><?php echo $total_pemilih > 0 ? round(($sudah_memilih / $total_pemilih) * 100, 2) : 0; ?>%</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-warning text-white h-100">
                            <div class="card-body text-center">
                                <h6 class="card-title text-uppercase">Belum Memilih</h6>
                                <h3 class="mb-0"><?php echo $total_pemilih - $sudah_memilih; ?></h3>
                                <small><?php echo $total_pemilih > 0 ? round((($total_pemilih - $sudah_memilih) / $total_pemilih) * 100, 2) : 0; ?>%</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Hasil Pemilihan</h5>
            </div>
            <div class="card-body">
                <?php if (count($kandidat) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">No</th>
                                    <th>Kandidat</th>
                                    <th>Kelas</th>
                                    <th width="120">Suara</th>
                                    <th width="120">Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kandidat as $index => $k): ?>
                                    <?php
                                    $percentage = $total_suara > 0 ? ($k['jumlah_suara'] / $total_suara) * 100 : 0;
                                    $is_leading = ($index === 0 && $k['jumlah_suara'] > 0);
                                    ?>
                                    <tr class="<?php echo $is_leading ? 'table-success' : ''; ?>">
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo BASE_URL; ?>/assets/uploads/kandidat/<?php echo $k['foto']; ?>"
                                                     alt="<?php echo $k['nama']; ?>"
                                                     class="rounded-circle me-3"
                                                     width="40" height="40">
                                                <div>
                                                    <h6 class="mb-0"><?php echo $k['nama']; ?></h6>
                                                    <small class="text-muted">
                                                        <button class="btn btn-sm btn-link p-0"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#kandidatModal<?php echo $k['id']; ?>">
                                                            Lihat Visi & Misi
                                                        </button>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo $k['kelas']; ?></td>
                                        <td><?php echo $k['jumlah_suara']; ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-primary"
                                                     role="progressbar"
                                                     style="width: <?php echo $percentage; ?>%"
                                                     aria-valuenow="<?php echo $percentage; ?>"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100">
                                                     <?php echo round($percentage, 1); ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="kandidatModal<?php echo $k['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><?php echo $k['nama']; ?> - <?php echo $k['kelas']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="text-center mb-3">
                                                        <img src="<?php echo BASE_URL; ?>/assets/uploads/kandidat/<?php echo $k['foto']; ?>"
                                                             alt="<?php echo $k['nama']; ?>"
                                                             class="img-fluid rounded"
                                                             style="max-height: 300px;">
                                                    </div>

                                                    <div class="mb-3">
                                                        <h6>Visi:</h6>
                                                        <p><?php echo nl2br($k['visi']); ?></p>
                                                    </div>
                                                    <div>
                                                        <h6>Misi:</h6>
                                                        <p><?php echo nl2br($k['misi']); ?></p>
                                                    </div>

                                                    <hr>

                                                    <div class="text-center">
                                                        <h5>Hasil Sementara</h5>
                                                        <h2 class="text-primary"><?php echo $k['jumlah_suara']; ?> Suara</h2>
                                                        <div class="progress" style="height: 30px;">
                                                            <div class="progress-bar bg-primary"
                                                                 role="progressbar"
                                                                 style="width: <?php echo $percentage; ?>%"
                                                                 aria-valuenow="<?php echo $percentage; ?>"
                                                                 aria-valuemin="0"
                                                                 aria-valuemax="100">
                                                                 <?php echo round($percentage, 1); ?>%
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Daftar Pemilih</h5>
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
                            Belum Memilih <span class="badge bg-warning"><?= $total_pemilih - $sudah_memilih ?></span>
                        </a>
                    </div>
                </div>

                <?php if (count($daftar_pemilih) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama</th>
                                    <th>Kelas</th>
                                    <th>Status</th>
                                    <th>Pilihan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($daftar_pemilih as $p): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($p['nama']) ?></td>
                                        <td><?= htmlspecialchars($p['kelas']) ?></td>
                                        <td>
                                            <?php if ($p['sudah_memilih']): ?>
                                                <span class="badge bg-success">Sudah</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Belum</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($p['nama_kandidat']): ?>
                                                <?= htmlspecialchars($p['nama_kandidat']) ?>
                                            <?php else: ?>
                                                -
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

<div class="row mt-4">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Diagram Pie</h5>
            </div>
            <div class="card-body">
                <canvas id="pieChart" height="200" style="max-height: 400px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Diagram Batang</h5>
            </div>
            <div class="card-body">
                <canvas id="barChart" height="200" style="max-height: 400px;"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (count($kandidat) > 0): ?>
            // Pie Chart
            const pieCtx = document.getElementById('pieChart').getContext('2d');
            const pieChart = new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: [
                        <?php foreach ($kandidat as $k): ?> '<?php echo addslashes($k['nama']); ?>',
                        <?php endforeach; ?>
                    ],
                    datasets: [{
                        data: [
                            <?php foreach ($kandidat as $k): ?>
                                <?php echo $k['jumlah_suara']; ?>,
                            <?php endforeach; ?>
                        ],
                        backgroundColor: [
                            '#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6',
                            '#1abc9c', '#d35400', '#34495e', '#16a085', '#c0392b'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Menambahkan ini agar bisa diatur ukurannya
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} suara (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Bar Chart
            const barCtx = document.getElementById('barChart').getContext('2d');
            const barChart = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: [
                        <?php foreach ($kandidat as $k): ?> '<?php echo addslashes($k['nama']); ?>',
                        <?php endforeach; ?>
                    ],
                    datasets: [{
                        label: 'Jumlah Suara',
                        data: [
                            <?php foreach ($kandidat as $k): ?>
                                <?php echo $k['jumlah_suara']; ?>,
                            <?php endforeach; ?>
                        ],
                        backgroundColor: '#3498db',
                        borderColor: '#2980b9',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Menambahkan ini agar bisa diatur ukurannya
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.raw || 0;
                                    const total = <?php echo $total_suara; ?>;
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return `${label}: ${value} suara (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
    });
</script>

<?php require_once '../includes/footer.php'; ?>