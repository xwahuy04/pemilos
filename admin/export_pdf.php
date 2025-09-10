<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();

// Include TCPDF library
require_once '../assets/tcpdf/tcpdf.php';

// Get voter data
$id = intval($_GET['id']);
$sql = "SELECT p.*, k.nama AS nama_kandidat 
        FROM pemilih p 
        LEFT JOIN kandidat k ON p.pilihan = k.id 
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Data pemilih tidak ditemukan.');
}

$voter = $result->fetch_assoc();

// Create new PDF document
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('PEMILU ONLINE SMKN 1 LUMAJANG');
$pdf->SetAuthor('Panitia Pemilihan');
$pdf->SetTitle('Bukti Pemilihan - ' . $voter['nama']);
$pdf->SetSubject('Bukti Pemilihan');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Add a page
$pdf->AddPage();

// Content
$html = '
<style>
    .header { text-align: center; margin-bottom: 20px; }
    .title { font-size: 18px; font-weight: bold; }
    .subtitle { font-size: 14px; }
    .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .info-table td { padding: 8px; border: 1px solid #ddd; }
    .label { font-weight: bold; width: 30%; }
    .signature { text-align: center; margin-top: 30px; }
    .footer { font-size: 10px; text-align: center; margin-top: 30px; }
</style>

<div class="header">
    <div class="title">BUKTI PEMILIHAN KETUA OSIS</div>
    <div class="subtitle">SMKN 1 LUMAJANG - ' . (getSetting('tahun_pemilihan') ?: date('Y')) . '</div>
</div>

<table class="info-table">
    <tr>
        <td class="label">NIS</td>
        <td>' . htmlspecialchars($voter['nis']) . '</td>
    </tr>
    <tr>
        <td class="label">Nama</td>
        <td>' . htmlspecialchars($voter['nama']) . '</td>
    </tr>
    <tr>
        <td class="label">Kelas</td>
        <td>' . htmlspecialchars($voter['kelas']) . '</td>
    </tr>
    <tr>
        <td class="label">Waktu Memilih</td>
        <td>' . date('d/m/Y H:i', strtotime($voter['waktu_memilih'])) . '</td>
    </tr>
    <tr>
        <td class="label">Kandidat Pilihan</td>
        <td>' . htmlspecialchars($voter['nama_kandidat'] ?? '-') . '</td>
    </tr>
</table>';

// Add signature if exists
if (!empty($voter['tanda_tangan'])) {
    $signature_path = '../assets/uploads/signatures/' . $voter['tanda_tangan'];
    if (file_exists($signature_path)) {
        $html .= '
        <div class="signature">
            <div style="font-weight: bold; margin-bottom: 10px;">Tanda Tangan Pemilih</div>
            <img src="' . $signature_path . '" style="height: 50px;" />
        </div>';
    }
}

// Add footer
$html .= '
<div class="footer">
    Dokumen ini dicetak secara otomatis oleh sistem PEMILU ONLINE SMKN 1 Lumajang<br>
    Tanggal cetak: ' . date('d/m/Y H:i') . '
</div>';

// Print content
$pdf->writeHTML($html, true, false, true, false, '');

// Close and output PDF document
$pdf->Output('bukti_pemilihan_' . $voter['nis'] . '.pdf', 'I');