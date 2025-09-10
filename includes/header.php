<?php
    $akses_menu = [];
    if (isAdminLoggedIn()) {
        if ($_SESSION['admin_level'] === 'super_admin') {
            // Super admin akses semua menu
            $akses_menu = ['dashboard', 'kandidat', 'rekapitulasi', 'pengaturan'];
        } else {
            // Panitia, ambil akses_menu dari tabel admin
            $user_id = $_SESSION['admin_id'];
            $stmt = $conn->prepare("SELECT akses_menu FROM admin WHERE id=?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $stmt->bind_result($akses_menu_json);
            $stmt->fetch();
            $stmt->close();
            $akses_menu = json_decode($akses_menu_json ?: '[]', true);
        }
    }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'PEMILU ONLINE SMKN 1 LUMAJANG'; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" href="../assets/uploads/logo/IMG_20230612_125630(1).png" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        
    <style>
        .dashboard-title {
        font-size: 1.8rem;
        color: var(--primary-color);
        text-transform: uppercase;
        letter-spacing: 1px;
        padding-bottom: 8px;
        border-bottom: 3px solid var(--primary-color);
        display: inline-block;
    }

    .dashboard-title i {
        color: var(--primary-color);
        font-size: 1.6rem;
    }

    .custom-breadcrumb {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 8px 15px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        margin-top: 10px;
    }

    .custom-breadcrumb .breadcrumb-item.active {
        font-weight: 600;
        color: var(--primary-color);
    }

    </style>

    
    <style>

       
        :root {
            --primary-color: <?php echo getSetting('warna_utama') ?: '#87CEEB'; ?>;
            --primary-hover: <?php echo adjustBrightness(getSetting('warna_utama') ?: '#87CEEB', -20); ?>;
            --header-height: <?php echo (isAdminLoggedIn() ? '60px' : '70px'); ?>;
        }


        /* Flying Header Style */
        .flying-header {
            position: fixed;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            border: 2px solid var(--primary-color);
            border-radius: 30px;
            width: 95%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            height: 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            z-index: 1030; /* Lebih tinggi dari konten tapi tidak terlalu tinggi */
        }

        .flying-header .logo-container {
            display: flex;
            align-items: center;
        }

        .flying-header .logo-container img {
            height: 40px;
            margin-right: 10px;
        }

        .flying-header .logo-container span {
            font-weight: bold;
            color: var(--primary-color);
        }

        .flying-header .nav-menu {
            display: flex;
            height: 100%;
            margin: 0;
            padding: 0;
        }

        .flying-header .nav-item {
            list-style: none;
            height: 100%;
            display: flex;
            align-items: center;
            position: relative;
        }

        .flying-header .nav-link {
            text-decoration: none;
            color: #333;
            height: 100%;
            display: flex;
            align-items: center;
            padding: 0 18px;
            transition: all 0.3s ease;
            border-radius: 25px;
            margin: 0 2px;
        }

        .flying-header .nav-link i {
            margin-right: 8px;
            color: var(--primary-color);
            transition: all 0.3s ease;
        }

        /* Hover Effect */
        .flying-header .nav-link:hover {
            background: var(--primary-color);
            color: white;
        }

        .flying-header .nav-link:hover i {
            color: white;
        }

        /* Mobile Toggle */
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--primary-color);
            cursor: pointer;
        }

        @media (max-width: 992px) {
            
            .flying-header {
                width: 90%;
                flex-direction: column;
                height: auto;
                padding: 10px 15px;
                top: 10px;
            }
            
            .flying-header .logo-container {
                width: 100%;
                justify-content: space-between;
            }
            
            .mobile-toggle {
                display: block;
            }
            
            .nav-menu {
                flex-direction: column;
                width: 100%;
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease;
            }
            
            .nav-menu.show {
                max-height: 500px;
                margin-top: 10px;
            }
            
            .nav-item {
                width: 100%;
                height: auto;
                margin: 5px 0;
            }
            
            .nav-link {
                padding: 10px 15px !important;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php if (!isset($hide_navbar) || !$hide_navbar): ?>
        <?php if (isAdminLoggedIn()): ?>
            <!-- Admin Header (Tetap sama seperti aslinya) -->
            <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm admin-navbar">
                <div class="container">
                    <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_URL; ?>/admin">
                        <img src="../assets/uploads/logo/IMG_20230612_125630(1).png" alt="Logo SMKN 1 LUMAJANG" height="40" class="me-2">
                        <span class="fw-bold"> PANITIA</span>
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav ms-auto">
                            <?php if ($_SESSION['admin_level'] === 'super_admin'): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>admin"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>admin/kandidat.php"><i class="fas fa-users me-1"></i> Kandidat</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>admin/users.php"><i class="fas fa-user-friends me-1"></i> Pemilih</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>admin/rekapitulasi.php"><i class="fas fa-chart-bar me-1"></i> Rekapitulasi</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>admin/settings.php"><i class="fas fa-cog me-1"></i> Pengaturan</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>admin/management_users.php"><i class="fas fa-user-cog"></i> Management Users</a>
                                </li>
                            <?php else: ?>
                                <?php if (in_array('dashboard', $akses_menu)): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>admin"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                                </li>
                                <?php endif; ?>
                                <?php if (in_array('kandidat', $akses_menu)): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>admin/kandidat.php"><i class="fas fa-users me-1"></i> Kandidat</a>
                                </li>
                                <?php endif; ?>
                                <?php if (in_array('pemilih', $akses_menu)): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>admin/users.php"><i class="fas fa-user-friends me-1"></i> Pemilih</a>
                                </li>
                                <?php endif; ?>
                                <?php if (in_array('rekapitulasi', $akses_menu)): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>admin/rekapitulasi.php"><i class="fas fa-chart-bar me-1"></i> Rekapitulasi</a>
                                </li>
                                <?php endif; ?>
                                <?php if (in_array('pengaturan', $akses_menu)): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>admin/settings.php"><i class="fas fa-cog me-1"></i> Pengaturan</a>
                                </li>
                                <?php endif; ?>
                            <?php endif; ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>admin/index.php?logout=1"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        <?php else: ?>
            <!-- User Flying Header -->
            <header class="flying-header">
                <div class="logo-container">
                    <a href="<?php echo BASE_URL; ?>" style="display: flex; align-items: center;">
                       <img src="assets/uploads/logo/NAVY.png" alt="Logo SMKN 1 LUMAJANG" height="40" class="me-2">
                        <span></span>
                    </a>
                    <button class="mobile-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>"><i class="fas fa-home"></i> Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>hasil.php"><i class="fas fa-chart-bar"></i> Hasil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>cek_nis.php"><i class="fas fa-search"></i> Cek NIS</a>
                    </li>
                    <?php if (isAdminLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>admin"><i class="fas fa-user-shield"></i> Admin</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </header>
        <?php endif; ?>
    <?php endif; ?>

    <script>
        // Toggle mobile menu
        document.querySelector('.mobile-toggle')?.addEventListener('click', function() {
            document.querySelector('.nav-menu').classList.toggle('show');
        });
    </script>
