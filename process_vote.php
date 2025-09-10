<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

function logVoteError($message) {
    file_put_contents('vote_errors.log', date('Y-m-d H:i:s') . ' - ' . $message . "\n", FILE_APPEND);
}

function sendJsonResponse($success, $message, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'error_code' => !$success ? $code : null
    ]);
    exit;
}

try {
    // 1. Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(false, 'Metode request tidak diizinkan', 405);
    }

    // 2. Get raw input
    $input = file_get_contents('php://input');
    if (empty($input)) {
        sendJsonResponse(false, 'Tidak ada data yang diterima', 400);
    }

    // 3. Decode JSON
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(false, 'Format JSON tidak valid: ' . json_last_error_msg(), 400);
    }

    // 4. Validate required fields
    $requiredFields = ['candidate_id', 'nis', 'signature'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            sendJsonResponse(false, "Field {$field} harus ada", 400);
        }
        if (empty($data[$field])) {
            sendJsonResponse(false, "Field {$field} tidak boleh kosong", 400);
        }
    }

    // 5. Validate NIS format (12345/678.910)
    if (!preg_match('/^\d{5}\/\d{3}\.\d{3}$/', $data['nis'])) {
        sendJsonResponse(false, 'Format NIS harus tepat: 12345/678.910 (5 digit, slash, 3 digit, dot, 3 digit)', 400);
    }

    $nis = $data['nis'];
    $candidate_id = (int)$data['candidate_id'];

    // 6. Validate signature format
    if (!preg_match('/^data:image\/(png|jpe?g);base64,([a-zA-Z0-9+\/]+=*)$/i', $data['signature'], $matches)) {
        sendJsonResponse(false, 'Format tanda tangan tidak valid. Silakan coba lagi.', 400);
    }

    // 7. Create signatures directory if not exists
    if (!file_exists(UPLOAD_SIGNATURES)) {
        if (!mkdir(UPLOAD_SIGNATURES, 0755, true)) {
            sendJsonResponse(false, 'Gagal membuat direktori tanda tangan', 500);
        }
    }

    // 8. Generate filename and path
    $signatureFile = 'signature_' . time() . '_' . preg_replace('/[^a-z0-9]/i', '_', $nis) . '.png';
    $signaturePath = UPLOAD_SIGNATURES . $signatureFile;

    // 9. Save signature image
    $imageData = base64_decode($matches[2]);
    if ($imageData === false) {
        sendJsonResponse(false, 'Gagal mendekode tanda tangan', 400);
    }

    if (file_put_contents($signaturePath, $imageData) === false) {
        sendJsonResponse(false, 'Gagal menyimpan tanda tangan', 500);
    }

    // 10. Start database transaction
    $conn->begin_transaction();

    try {
        // Update voter record
        $stmt = $conn->prepare("UPDATE pemilih SET sudah_memilih = 1, waktu_memilih = NOW(), pilihan = ?, tanda_tangan = ? WHERE nis = ?");
        if (!$stmt) {
            throw new Exception('Gagal menyiapkan query update pemilih: ' . $conn->error);
        }
        
        $stmt->bind_param('iss', $candidate_id, $signatureFile, $nis);
        if (!$stmt->execute()) {
            throw new Exception('Gagal memperbarui data pemilih: ' . $stmt->error);
        }

        // Update candidate vote count
        $updateVote = $conn->query("UPDATE kandidat SET jumlah_suara = jumlah_suara + 1 WHERE id = {$candidate_id}");
        if (!$updateVote) {
            throw new Exception('Gagal memperbarui jumlah suara: ' . $conn->error);
        }

        // Commit transaction
        $conn->commit();

        // Clean output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Success response
        sendJsonResponse(true, "Terima kasih telah berpartisipasi dalam pemilihan.");

    } catch (Exception $e) {
        $conn->rollback();
        if (file_exists($signaturePath)) {
            @unlink($signaturePath);
        }
        sendJsonResponse(false, $e->getMessage(), 500);
    }

} catch (Exception $e) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    sendJsonResponse(false, $e->getMessage(), 500);
}
?>