<?php
require_once 'config.php';
require_once 'functions.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Özel CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap Bundle JS (Popper.js dahil) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container">
                <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                    <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" alt="<?php echo SITE_NAME; ?>" height="40">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/about.php">Hakkımızda</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/how-it-works.php">Nasıl Çalışır</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/faq.php">SSS</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/contact.php">İletişim</a>
                        </li>
                    </ul>
                    
                    <ul class="navbar-nav ms-auto">
                        <?php if (is_logged_in()): ?>
                            <!-- Kullanıcı Dropdown -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php 
                                    $user = get_user_by_id($_SESSION['user_id']);
                                    echo $user['name'] . ' ' . $user['surname']; 
                                    ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <?php if ($_SESSION['user_role'] == ROLE_ADMIN): ?>
                                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/dashboard.php">Admin Paneli</a></li>
                                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/users.php">Kullanıcı Yönetimi</a></li>
                                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/requests.php">Talep Yönetimi</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                    <?php elseif ($_SESSION['user_role'] == ROLE_BUYER): ?>
                                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/buyer/dashboard.php">Hesabım</a></li>
                                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/buyer/my-requests.php">Taleplerim</a></li>
                                    <?php elseif ($_SESSION['user_role'] == ROLE_SUPPLIER): ?>
                                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/supplier/dashboard.php">Tedarikçi Paneli</a></li>
                                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/supplier/request-list.php">Talep Listesi</a></li>
                                    <?php elseif ($_SESSION['user_role'] == ROLE_SPONSOR): ?>
                                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/sponsor/dashboard.php">Sponsor Paneli</a></li>
                                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/sponsor/sponsored-requests.php">Desteklediğim Talepler</a></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/auth/profile.php">Profil</a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/auth/logout.php">Çıkış Yap</a></li>
                                </ul>
                            </li>
                            
                            <!-- Bildirimler Dropdown -->
                            <li class="nav-item dropdown">
                                <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-bell"></i>
                                    <?php
                                    // Okunmamış bildirimleri say
                                    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM notifications WHERE user_id = ? AND is_read = 0");
                                    $stmt->bind_param("i", $_SESSION['user_id']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $row = $result->fetch_assoc();
                                    $unread_count = $row['count'];
                                    
                                    if ($unread_count > 0):
                                    ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo $unread_count; ?>
                                        <span class="visually-hidden">okunmamış bildirimler</span>
                                    </span>
                                    <?php endif; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                                    <?php
                                    // Son 5 bildirimi getir
                                    $stmt = $conn->prepare("
                                        SELECT * FROM notifications 
                                        WHERE user_id = ? 
                                        ORDER BY created_at DESC 
                                        LIMIT 5
                                    ");
                                    $stmt->bind_param("i", $_SESSION['user_id']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $notifications = [];
                                    
                                    while ($row = $result->fetch_assoc()) {
                                        $notifications[] = $row;
                                    }
                                    
                                    if (empty($notifications)):
                                    ?>
                                    <li><a class="dropdown-item text-center" href="#">Bildirim Bulunmuyor</a></li>
                                    <?php else: 
                                        foreach ($notifications as $notification):
                                    ?>
                                    <li>
                                        <a class="dropdown-item <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>" 
                                           href="<?php echo !empty($notification['link']) ? $notification['link'] : '#'; ?>">
                                            <div class="d-flex">
                                                <div class="me-2">
                                                    <?php if (!$notification['is_read']): ?>
                                                    <span class="badge bg-primary"><i class="fas fa-circle fa-xs"></i></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="small"><?php echo $notification['message']; ?></div>
                                                    <div class="text-muted small"><?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?></div>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <?php 
                                        endforeach;
                                    ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-center" href="<?php echo SITE_URL; ?>/notifications.php">Tüm Bildirimleri Görüntüle</a></li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>/auth/login.php">Giriş Yap</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link btn btn-primary text-white" href="<?php echo SITE_URL; ?>/auth/register.php">Kayıt Ol</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="container py-4">
        <?php
        // Flash mesajları göster
        if (isset($_SESSION['flash_message'])) {
            echo '<div class="alert alert-' . $_SESSION['flash_type'] . ' alert-dismissible fade show" role="alert">';
            echo $_SESSION['flash_message'];
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
            
            // Mesajı temizle
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
        }
        ?>