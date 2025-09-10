<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = 'Cek NIS - PEMILU ONLINE SMKN 1 LUMAJANG';
require_once 'includes/header.php';

$nis = isset($_GET['nis']) ? formatNIS($_GET['nis']) : '';
$result = null;

if (!empty($nis)) {
    // First get basic voter info
    $sql = "SELECT id, nis, nama, kelas, sudah_memilih, waktu_memilih, pilihan 
            FROM pemilih 
            WHERE nis = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die('Error preparing statement: ' . $conn->error);
    }
    
    $stmt->bind_param('s', $nis);
    
    if (!$stmt->execute()) {
        die('Error executing query: ' . $stmt->error);
    }
    
    // Store the result first
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        // Bind result variables
        $stmt->bind_result($id, $nis_db, $nama, $kelas, $sudah_memilih, $waktu_memilih, $pilihan);
        
        if ($stmt->fetch()) {
            $result = [
                'id' => $id,
                'nis' => $nis_db,
                'nama' => $nama,
                'kelas' => $kelas,
                'sudah_memilih' => $sudah_memilih,
                'waktu_memilih' => $waktu_memilih,
                'pilihan' => $pilihan,
                'nama_kandidat' => null
            ];
        }
    }
    
    // Close the statement before making new queries
    $stmt->close();
    
    // Get candidate name if already voted
    if ($result && $result['sudah_memilih'] && $result['pilihan']) {
        $sql_kandidat = "SELECT nama FROM kandidat WHERE id = ?";
        $stmt_k = $conn->prepare($sql_kandidat);
        
        if ($stmt_k === false) {
            die('Error preparing candidate statement: ' . $conn->error);
        }
        
        $stmt_k->bind_param('i', $result['pilihan']);
        
        if (!$stmt_k->execute()) {
            die('Error executing candidate query: ' . $stmt_k->error);
        }
        
        $stmt_k->bind_result($nama_kandidat);
        
        if ($stmt_k->fetch()) {
            $result['nama_kandidat'] = $nama_kandidat;
        }
        
        $stmt_k->close();
    }
}
?>

<style>
    body {
                padding-top: 120px; /* Lebih besar untuk mobile */
            }
</style>

<!-- Rest of your HTML and JavaScript remains the same -->
<div class="row justify-content-center animate-on-scroll">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-search me-2"></i>CEK STATUS NIS</h4>
            </div>
            <div class="card-body">
                <form method="GET" class="mb-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="nis" 
                               placeholder="Masukkan NIS (Contoh: 19680/041.063)" 
                               value="<?php echo htmlspecialchars($nis); ?>" required>
                        <button class="btn btn-primary" type="submit">Cek</button>
                    </div>
                    <div class="form-text">Format NIS: 19680/041.063 (tanpa spasi)</div>
                </form>
                
                <?php if (!empty($nis)): ?>
                    <?php if ($result): ?>
                        <div class="alert <?php echo $result['sudah_memilih'] ? 'alert-success' : 'alert-info'; ?>">
                            <h5 class="alert-heading">NIS: <?php echo htmlspecialchars($nis); ?></h5>
                            <p>
                                <strong>Nama:</strong> <?php echo htmlspecialchars($result['nama']); ?><br>
                                <strong>Kelas:</strong> <?php echo htmlspecialchars($result['kelas']); ?>
                            </p>
                            <hr>
                            <p class="mb-0">
                                <?php if ($result['sudah_memilih']): ?>
                                    <i class="fas fa-check-circle me-2"></i> 
                                    <strong>Status:</strong> Sudah memilih<br>
                                    <strong>Waktu:</strong> <?php echo date('d/m/Y H:i', strtotime($result['waktu_memilih'])); ?><br>
                                    <?php if (!empty($result['nama_kandidat'])): ?>
                                        <strong>Pilihan:</strong> <?php echo htmlspecialchars($result['nama_kandidat']); ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <i class="fas fa-info-circle me-2"></i> 
                                    <strong>Status:</strong> Belum memilih
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i> 
                            NIS <strong><?php echo htmlspecialchars($nis); ?></strong> tidak terdaftar sebagai pemilih.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Format NIS input (remove spaces)
    $('input[name="nis"]').on('input', function() {
        $(this).val($(this).val().replace(/\s+/g, ''));
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>