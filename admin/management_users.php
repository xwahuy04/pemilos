<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();
checkPermission('super_admin');

$page_title = 'Kelola Users - PEMILU ONLINE';
require_once '../includes/header.php';

// Proses tambah user
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = password_hash(($_POST['password']), PASSWORD_DEFAULT);
    $level = $_POST['level'];
    $last_login = date('Y-m-d H:i:s');
    $akses_menu = isset($_POST['akses_menu']) ? json_encode($_POST['akses_menu']) : '[]';

    // Cek username unik
    $cek = $conn->prepare("SELECT id FROM admin WHERE username=?");
    $cek->bind_param('s', $username);
    $cek->execute();
    $cek->store_result();
    if ($cek->num_rows > 0) {
        $error = "Username sudah digunakan!";
    } else {
        // Perbaikan: Tambahkan placeholder kelima di query, dan tambahkan 's' di tipe data.
        $stmt = $conn->prepare("INSERT INTO admin (username, password, level, last_login, akses_menu) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssss', $username, $password, $level, $last_login, $akses_menu);
        $stmt->execute();

        echo "<script>location.reload();</script>";
        exit;
    }
}

// Proses simpan akses menu (edit)
if (isset($_POST['save_akses'])) {
    $user_id = intval($_POST['user_id']);
    $akses_menu = isset($_POST['akses_menu']) ? json_encode($_POST['akses_menu']) : '[]';
    $stmt = $conn->prepare("UPDATE admin SET akses_menu=? WHERE id=?");
    $stmt->bind_param('si', $akses_menu, $user_id);
    $stmt->execute();
    echo "<script>location.reload();</script>";
}

// Ambil data users
$sql = "SELECT * FROM admin";
$result = $conn->query($sql);
$users = $result->fetch_all(MYSQLI_ASSOC);

// Daftar menu yang bisa dipilih
$menu_list = [
    'dashboard' => 'Dashboard',
    'kandidat' => 'Kandidat',
    'rekapitulasi' => 'Rekapitulasi',
    'pengaturan' => 'Pengaturan'
];
?>

<div class="container mt-4">
    <h3>Kelola Users</h3>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="fas fa-user-plus"></i> Tambah User
    </button>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Username</th>
                <th>Level</th>
                <th>Last Login</th>
                <th>Akses Menu</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): 
            $akses = json_decode($u['akses_menu'] ?? '[]', true);
        ?>
            <tr>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['level']) ?></td>
                <td><?= htmlspecialchars($u['last_login']) ?></td>
                <td>
                    <?php foreach ($menu_list as $key => $label): ?>
                        <?php
                        // Jika super_admin, semua badge hijau
                        $badge = ($u['level'] === 'super_admin' || in_array($key, $akses)) ? 'success' : 'secondary';
                        ?>
                        <span class="badge bg-<?= $badge ?>">
                            <?= $label ?>
                        </span>
                    <?php endforeach; ?>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                        data-bs-target="#editUserModal<?= $u['id'] ?>">Edit</button>
                </td>
            </tr>

            <!-- Modal Edit User -->
            <div class="modal fade" id="editUserModal<?= $u['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="post" action="">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5>Edit Akses Menu: <?= htmlspecialchars($u['username']) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <?php foreach ($menu_list as $key => $label): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                            name="akses_menu[]" value="<?= $key ?>"
                                            id="akses_<?= $key . '_' . $u['id'] ?>"
                                            <?= in_array($key, $akses) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="akses_<?= $key . '_' . $u['id'] ?>">
                                            <?= $label ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="save_akses" class="btn btn-success">Simpan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Tambah User -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Tambah User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" required maxlength="50">
                    </div>
                    <div class="mb-2">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required maxlength="50">
                    </div>
                    <div class="mb-2">
                        <label>Level</label>
                        <select name="level" class="form-select" required>
                            <option value="super_admin">Super Admin</option>
                            <option value="panitia">Panitia</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Akses Menu</label>
                        <?php foreach ($menu_list as $key => $label): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                    name="akses_menu[]" value="<?= $key ?>" id="add_akses_<?= $key ?>">
                                <label class="form-check-label" for="add_akses_<?= $key ?>">
                                    <?= $label ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add_user" class="btn btn-success">Tambah</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>