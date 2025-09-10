<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$page_title = 'Vote - PEMILU ONLINE SMKN 1 LUMAJANG';

// Check voting period
$current_time = time();
$start_time = strtotime(getSetting('waktu_mulai') ?: 'today 08:00');
$end_time = strtotime(getSetting('waktu_selesai') ?: 'today 16:00');
$voting_allowed = !getSetting('waktu_pemilihan_enabled') || 
                 ($current_time >= $start_time && $current_time <= $end_time);

// Get candidates
$kandidat = $conn->query("SELECT * FROM kandidat ORDER BY nama ASC")->fetch_all(MYSQLI_ASSOC);

require_once 'includes/header.php';
?>

<style>
    body {
                padding-top: 120px; /* Lebih besar untuk mobile */
            }
</style>

<div class="container mt-4 mb-5">
    <?php if (!$voting_allowed): ?>
        <div class="alert alert-warning text-center">
            <i class="fas fa-exclamation-triangle me-2"></i> Masa pemilihan telah berakhir.
        </div>
    <?php elseif (empty($kandidat)): ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle me-2"></i> Belum ada kandidat yang terdaftar.
        </div>
    <?php else: ?>
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h1 class="fw-bold text-primary">PEMILIHAN KETUA OSIS</h1>
                <p class="lead text-muted">SMKN 1 LUMAJANG - <?= getSetting('tahun_pemilihan') ?: date('Y') ?></p>
            </div>
        </div>

        <div class="row" id="candidateContainer">
            <?php foreach ($kandidat as $k): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100 candidate-card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><?= htmlspecialchars($k['nama']) ?></h5>
                        <small class="text-muted"><?= htmlspecialchars($k['kelas']) ?></small>
                    </div>
                    <div class="card-body text-center">
                        <img src="<?= BASE_URL ?>/assets/uploads/kandidat/<?= htmlspecialchars($k['foto']) ?>" 
                             class="img-fluid rounded mb-3" 
                             style="max-height: 200px; object-fit: cover;">
                        
                        <div class="accordion mb-2" id="accordion<?= $k['id'] ?>">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading<?= $k['id'] ?>">
                                    <button class="accordion-button collapsed" type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#collapse<?= $k['id'] ?>" 
                                            aria-expanded="false" 
                                            aria-controls="collapse<?= $k['id'] ?>">
                                        <i class="fas fa-eye me-1"></i> Detail Visi & Misi
                                    </button>
                                </h2>
                                <div id="collapse<?= $k['id'] ?>" class="accordion-collapse collapse" 
                                     aria-labelledby="heading<?= $k['id'] ?>" 
                                     data-bs-parent="#accordion<?= $k['id'] ?>">
                                    <div class="accordion-body text-start">
                                        <h6>Visi:</h6>
                                        <div class="mb-3 p-2 bg-light rounded">
                                            <?= nl2br(htmlspecialchars($k['visi'])) ?>
                                        </div>
                                        
                                        <h6>Misi:</h6>
                                        <div class="p-2 bg-light rounded">
                                            <?= nl2br(htmlspecialchars($k['misi'])) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button class="btn btn-primary w-100 vote-btn" 
                                data-id="<?= $k['id'] ?>" 
                                data-name="<?= htmlspecialchars($k['nama']) ?>">
                            <i class="fas fa-check-circle me-1"></i> PILIH
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- NIS Input Modal -->
<div class="modal fade" id="nisModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Verifikasi Pemilih</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Anda akan memilih: <strong id="selectedCandidate"></strong></p>
                <div class="mb-3">
                    <label for="nisInput" class="form-label">Masukkan NIS</label>
                    <input type="text" class="form-control" id="nisInput" 
                           placeholder="Contoh: 12345/678.910" 
                           pattern="\d{5}\/\d{3}\.\d{3}"
                           title="Format: 12345/678.910 (5 digit, slash, 3 digit, dot, 3 digit)"
                           required>
                    <small class="text-muted">Format: 12345/678.910 (tanpa spasi)</small>
                </div>
                <div id="nisError" class="alert alert-danger d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </button>
                <button id="verifyNis" type="button" class="btn btn-primary">
                    <i class="fas fa-check-circle me-1"></i> Verifikasi
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Signature Modal -->
<div class="modal fade" id="signatureModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Tanda Tangan Digital</h5>
            </div>
            <div class="modal-body">
                <p>Silakan berikan tanda tangan Anda:</p>
                <div class="signature-container">
                    <canvas id="signaturePad"></canvas>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <button id="clearSignature" class="btn btn-danger btn-sm">
                        <i class="fas fa-eraser me-1"></i> Hapus
                    </button>
                    <small class="text-muted">Gunakan mouse/jari untuk menandatangani</small>
                </div>
                <div id="signatureError" class="alert alert-danger mt-2 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button id="saveSignature" type="button" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i> Berhasil!</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="successMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                    <i class="fas fa-thumbs-up me-1"></i> Mengerti
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let signaturePad;
    let currentCandidate = null;
    let currentNis = null;
    
    // Initialize Signature Pad
    function initSignaturePad() {
        const canvas = document.getElementById('signaturePad');
        const container = canvas.parentElement;
        
        // Set canvas size
        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = container.offsetWidth * ratio;
            canvas.height = container.offsetHeight * ratio;
            canvas.style.width = container.offsetWidth + 'px';
            canvas.style.height = container.offsetHeight + 'px';
            canvas.getContext('2d').scale(ratio, ratio);
            
            if (signaturePad && !signaturePad.isEmpty()) {
                const data = signaturePad.toData();
                signaturePad.clear();
                signaturePad.fromData(data);
            }
        }
        
        // Initialize signature pad
        signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgba(255, 255, 255, 0)',
            penColor: 'rgb(0, 0, 0)',
            minWidth: 1,
            maxWidth: 2.5,
            throttle: 16,
            velocityFilterWeight: 0.7
        });
        
        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();
        
        document.getElementById('clearSignature').addEventListener('click', function() {
            signaturePad.clear();
            document.getElementById('signatureError').classList.add('d-none');
        });
    }
    
    // Handle vote buttons
    function setupVoteButtons() {
        document.querySelectorAll('.vote-btn').forEach(btn => {
            btn.addEventListener('click', handleVoteClick);
        });
    }
    
    function handleVoteClick(e) {
        e.preventDefault();
        
        currentCandidate = {
            id: this.dataset.id,
            name: this.dataset.name
        };
        
        document.getElementById('selectedCandidate').textContent = currentCandidate.name;
        document.getElementById('nisInput').value = '';
        document.getElementById('nisError').classList.add('d-none');
        
        const nisModal = new bootstrap.Modal('#nisModal');
        nisModal.show();
    }
    
    // Verify NIS with strict pattern
    document.getElementById('verifyNis').addEventListener('click', async function() {
        const nisInput = document.getElementById('nisInput');
        const nis = nisInput.value.trim();
        const errorElement = document.getElementById('nisError');
        
        // Strict NIS pattern validation (12345/678.910)
        if (!/^\d{5}\/\d{3}\.\d{3}$/.test(nis)) {
            errorElement.textContent = 'Format NIS harus tepat: 12345/678.910 (5 digit, slash, 3 digit, dot, 3 digit)';
            errorElement.classList.remove('d-none');
            nisInput.focus();
            return;
        }
        
        try {
            const response = await fetch('verify_nis.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ nis: nis })
            });
            
            const result = await response.json();
            
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'NIS tidak valid atau sudah melakukan pemilihan');
            }
            
            currentNis = nis;
            bootstrap.Modal.getInstance('#nisModal').hide();
            
            const signatureModal = new bootstrap.Modal('#signatureModal');
            signatureModal.show();
            
        } catch (error) {
            errorElement.textContent = error.message;
            errorElement.classList.remove('d-none');
        }
    });
    
    // Save signature
    document.getElementById('saveSignature').addEventListener('click', async function() {
        if (!signaturePad || signaturePad.isEmpty()) {
            document.getElementById('signatureError').textContent = 'Harap berikan tanda tangan terlebih dahulu';
            document.getElementById('signatureError').classList.remove('d-none');
            return;
        }
        
        try {
            const signatureData = signaturePad.toDataURL('image/png');
            
            const response = await fetch('process_vote.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    candidate_id: currentCandidate.id,
                    nis: currentNis,
                    signature: signatureData
                })
            });
            
            const result = await response.json();
            
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Gagal menyimpan tanda tangan');
            }
            
            document.getElementById('successMessage').textContent = result.message;
            bootstrap.Modal.getInstance('#signatureModal').hide();
            
            const successModal = new bootstrap.Modal('#successModal');
            successModal.show();
            
        } catch (error) {
            document.getElementById('signatureError').textContent = error.message;
            document.getElementById('signatureError').classList.remove('d-none');
        }
    });
    
    // Initialize signature pad when modal is shown
    document.getElementById('signatureModal').addEventListener('shown.bs.modal', initSignaturePad);
    
    // Initialize
    setupVoteButtons();
});
</script>

<?php require_once 'includes/footer.php'; ?>