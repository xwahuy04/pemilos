<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';


// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Ambil user dari database
    $stmt = $conn->prepare("SELECT id, username, password, level, akses_menu FROM admin WHERE username=? LIMIT 1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $db_username, $db_password, $level, $akses_menu_json);
        $stmt->fetch();

        // Cek password
        if (password_verify($password, $db_password)) {
            // Simpan session
            $_SESSION['admin_id'] = $id;
            $_SESSION['admin_username'] = $db_username;
            $_SESSION['admin_level'] = $level;
            $_SESSION['admin_logged_in'] = true;

            // Simpan akses_menu ke session (khusus panitia)
            if ($level === 'panitia') {
                $_SESSION['akses_menu'] = json_decode($akses_menu_json ?: '[]', true);
            } else {
                $_SESSION['akses_menu'] = []; // super_admin akses semua menu
            }

            // Update last_login
            $now = date('Y-m-d H:i:s');
            $update = $conn->prepare("UPDATE admin SET last_login=? WHERE id=?");
            $update->bind_param('si', $now, $id);
            $update->execute();


            header('Location: index.php');
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
    $stmt->close();
}

// Redirect if already logged in
if (isAdminLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$page_title = 'Admin Login - PEMILU ONLINE';
$hide_navbar = true;
require_once '../includes/header.php';
?>

<style>
        body {
                padding-top: 120px; /* Lebih besar untuk mobile */
            }
</style>

<div class="row justify-content-center animate-on-scroll">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white text-center">
                <h4 class="mb-0"><i class="fas fa-user-shield me-2"></i>ADMIN LOGIN</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <small class="text-muted">Hanya untuk panitia pemilihan</small>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>