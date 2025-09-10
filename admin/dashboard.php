<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

$page_title = 'Dashboard Admin - PEMILU ONLINE';
require_once '../includes/header.php';

// Get statistics
$sql = "SELECT COUNT(*) AS total FROM kandidat";
$total_kandidat = $conn->query($sql)->fetch_assoc()['total'];

$sql = "SELECT COUNT(*) AS total FROM pemilih";
$total_pemilih = $conn->query($sql)->fetch_assoc()['total'];

$sql = "SELECT COUNT(*) AS total FROM pemilih WHERE sudah_memilih = TRUE";
$sudah_memilih = $conn->query($sql)->fetch_assoc()['total'];

$sql = "SELECT COUNT(*) AS total FROM pemilih WHERE sudah_memilih = FALSE";
$belum_memilih = $conn->query($sql)->fetch_assoc()['total'];

// Get latest voters
$sql = "SELECT p.nis, p.nama, p.kelas, p.waktu_memilih, k.nama AS nama_kandidat 
        FROM pemilih p 
        LEFT JOIN kandidat k ON p.pilihan = k.id 
        WHERE p.sudah_memilih = TRUE 
        ORDER BY p.waktu_memilih DESC 
        LIMIT 5";
$latest_voters = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>


<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold mb-2 d-flex align-items-center dashboard-title">
            <!-- <i class="fas fa-tachometer-alt me-2"></i> Dashboard -->
        </h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb custom-breadcrumb">
                <li class="breadcrumb-item active text-black" aria-current="page">
                    <i class="fas fa-home me-1"></i> Dashboard
                </li>
            </ol>
        </nav>
    </div>
</div>


<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase">Kandidat</h6>
                        <h3 class="mb-0"><?php echo $total_kandidat; ?></h3>
                    </div>
                    <div class="icon-circle bg-white text-primary">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-primary-dark">
                <a href="kandidat.php" class="text-white stretched-link small">Lihat Detail <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase">Total Pemilih</h6>
                        <h3 class="mb-0"><?php echo $total_pemilih; ?></h3>
                    </div>
                    <div class="icon-circle bg-white text-success">
                        <i class="fas fa-user-friends"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-success-dark">
                <a href="users.php" class="text-white stretched-link small">Lihat Detail <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase">Sudah Memilih</h6>
                        <h3 class="mb-0"><?php echo $sudah_memilih; ?></h3>
                        <small><?php echo $total_pemilih > 0 ? round(($sudah_memilih/$total_pemilih)*100, 2) : 0; ?>%</small>
                    </div>
                    <div class="icon-circle bg-white text-info">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-info-dark">
                <a href="rekapitulasi.php" class="text-white stretched-link small">Lihat Detail <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-white shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase">Belum Memilih</h6>
                        <h3 class="mb-0"><?php echo $belum_memilih; ?></h3>
                        <small><?php echo $total_pemilih > 0 ? round(($belum_memilih/$total_pemilih)*100, 2) : 0; ?>%</small>
                    </div>
                    <div class="icon-circle bg-white text-warning">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-warning-dark">
                <a href="users.php?filter=belum" class="text-white stretched-link small">Lihat Detail <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Statistik Pemilihan</h5>
            </div>
            <div class="card-body">
                <canvas id="statsChart" height="200" style="max-height: 400px;"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Pemilih Terakhir</h5>
            </div>
            <div class="card-body">
                <?php if (count($latest_voters) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>NIS</th>
                                    <th>Nama</th>
                                    <th>Kelas</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latest_voters as $voter): ?>
                                    <tr>
                                        <td><?php echo $voter['nis']; ?></td>
                                        <td><?php echo $voter['nama']; ?></td>
                                        <td><?php echo $voter['kelas']; ?></td>
                                        <td><?php echo date('H:i', strtotime($voter['waktu_memilih'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i> Belum ada yang memilih.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Stats Chart
    const ctx = document.getElementById('statsChart').getContext('2d');
    const statsChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Sudah Memilih', 'Belum Memilih'],
            datasets: [{
                data: [<?php echo $sudah_memilih; ?>, <?php echo $belum_memilih; ?>],
                backgroundColor: ['#17a2b8', '#ffc107'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>