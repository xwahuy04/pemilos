<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = 'Hasil Pemilihan - PEMILU ONLINE SMKN 1 LUMAJANG';
require_once 'includes/header.php';

// Get all candidates with vote count
$sql = "SELECT * FROM kandidat ORDER BY jumlah_suara DESC";
$result = $conn->query($sql);
$kandidat = $result->fetch_all(MYSQLI_ASSOC);

// Get total votes
$total_suara = 0;
foreach ($kandidat as $k) {
    $total_suara += $k['jumlah_suara'];
}

// Get total pemilih
$sql = "SELECT COUNT(*) AS total_pemilih FROM pemilih";
$total_pemilih = $conn->query($sql)->fetch_assoc()['total_pemilih'];

// Get total yang sudah memilih
$sql = "SELECT COUNT(*) AS sudah_memilih FROM pemilih WHERE sudah_memilih = TRUE";
$sudah_memilih = $conn->query($sql)->fetch_assoc()['sudah_memilih'];
?>

<style>
    body {
                padding-top: 120px; /* Lebih besar untuk mobile */
            }
</style>

<div class="row justify-content-center animate-on-scroll">
    <div class="col-lg-10">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-chart-bar me-2"></i>HASIL PEMILIHAN SEMENTARA</h4>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-info mb-3">
                            <div class="card-body text-center">
                                <h5 class="card-title">Total Pemilih</h5>
                                <p class="card-text display-6"><?php echo $total_pemilih; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-body text-center">
                                <h5 class="card-title">Sudah Memilih</h5>
                                <p class="card-text display-6"><?php echo $sudah_memilih; ?></p>
                                <small><?php echo $total_pemilih > 0 ? round(($sudah_memilih/$total_pemilih)*100, 2) : 0; ?>%</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-warning mb-3">
                            <div class="card-body text-center">
                                <h5 class="card-title">Belum Memilih</h5>
                                <p class="card-text display-6"><?php echo $total_pemilih - $sudah_memilih; ?></p>
                                <small><?php echo $total_pemilih > 0 ? round((($total_pemilih-$sudah_memilih)/$total_pemilih)*100, 2) : 0; ?>%</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (count($kandidat) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th width="50">No</th>
                                    <th>Kandidat</th>
                                    <th>Kelas</th>
                                    <th width="120">Jumlah Suara</th>
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
                                    
                                    <!-- Kandidat Modal -->
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
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Diagram Pie</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="pieChart" height="250"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Diagram Batang</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="barChart" height="250"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i> Belum ada kandidat yang terdaftar.
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

<?php require_once 'includes/footer.php'; ?>