<?php
// Start output buffering and clean any previous output
if (ob_get_level()) ob_end_clean();
ob_start();

// Error reporting configuration
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/verify_nis_errors.log');

// Set default timezone
date_default_timezone_set('Asia/Jakarta');

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    // ======================
    // 1. REQUEST VALIDATION
    // ======================
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException("Hanya metode POST yang diperbolehkan", 405);
    }

    // ======================
    // 2. INPUT VALIDATION
    // ======================
    $json_input = file_get_contents('php://input');
    if (empty($json_input)) {
        throw new RuntimeException("Data input tidak ditemukan", 400);
    }

    $data = json_decode($json_input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException("Format JSON tidak valid: " . json_last_error_msg(), 400);
    }

    if (!isset($data['nis']) || empty(trim($data['nis']))) {
        throw new RuntimeException("NIS harus diisi", 400);
    }

    // ======================
    // 3. NIS FORMAT VALIDATION
    // ======================
    $nis = trim($data['nis']);
    
    // Strict NIS pattern validation (12345/678.910)
    if (!preg_match('/^\d{5}\/\d{3}\.\d{3}$/', $nis)) {
        throw new RuntimeException("Format NIS harus tepat: 12345/678.910 (5 digit, slash, 3 digit, dot, 3 digit)", 400);
    }

    // ======================
    // 4. DATABASE CONNECTION
    // ======================
    require_once 'includes/config.php';
    require_once 'includes/functions.php';

    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new RuntimeException("Koneksi database gagal", 500);
    }

    if ($conn->connect_error) {
        throw new RuntimeException("Koneksi database error: " . $conn->connect_error, 500);
    }

    if (!$conn->set_charset("utf8mb4")) {
        throw new RuntimeException("Gagal mengatur charset database", 500);
    }

    // ======================
    // 5. DATABASE QUERY
    // ======================
    $query = "SELECT id, nis, nama, kelas, sudah_memilih, waktu_memilih 
              FROM pemilih 
              WHERE nis = ? 
              LIMIT 1";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new RuntimeException("Persiapan query database gagal: " . $conn->error, 500);
    }

    $stmt->bind_param("s", $nis);
    if (!$stmt->execute()) {
        throw new RuntimeException("Eksekusi query database gagal: " . $stmt->error, 500);
    }

    $stmt->bind_result($id, $nis_db, $nama, $kelas, $sudah_memilih, $waktu_memilih);

    if (!$stmt->fetch()) {
        throw new RuntimeException("NIS Anda belum terdaftar", 404);
    }

    $stmt->close();

    // ======================
    // 6. VOTING STATUS CHECK
    // ======================
    if ($sudah_memilih) {
        throw new RuntimeException("Anda sudah melakukan pemilihan sebelumnya pada: " . 
            ($waktu_memilih ? date('d/m/Y H:i', strtotime($waktu_memilih)) : 'waktu tidak diketahui'), 403);
    }

    // ======================
    // 7. PREPARE SUCCESS RESPONSE
    // ======================
    $response = [
        'success' => true,
        'message' => 'Verifikasi NIS berhasil',
        'data' => [
            'id' => $id,
            'nama' => $nama,
            'kelas' => $kelas,
            'nis' => $nis_db
        ]
    ];

} catch (RuntimeException $e) {
    // Known application errors
    http_response_code($e->getCode() >= 400 ? $e->getCode() : 500);
    $response['message'] = $e->getMessage();
    $response['code'] = $e->getCode();
    
    error_log(sprintf(
        "[%s] NIS Verification Error: %s\n%s\n",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getTraceAsString()
    ), 3, __DIR__ . '/verify_nis_errors.log');

} catch (Throwable $e) {
    // Unexpected errors
    http_response_code(500);
    $response['message'] = "Terjadi kesalahan sistem";
    
    error_log(sprintf(
        "[%s] CRITICAL NIS Verification Error: %s\n%s\n",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getTraceAsString()
    ), 3, __DIR__ . '/verify_nis_errors.log');

} finally {
    // Clean output buffer and send JSON response
    ob_end_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($response);
    exit;
}
?>