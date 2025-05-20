<?php
$page_title = "Kullanıcı Yönetimi";
require_once '../header.php';

// Yalnızca admin rolüne sahip kullanıcılar erişebilir
if (!has_role(ROLE_ADMIN)) {
    $_SESSION['flash_message'] = "Bu sayfaya erişim izniniz yok.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL);
    exit;
}

// Filtre parametreleri
$role = isset($_GET['role']) ? clean_input($_GET['role']) : '';
$status = isset($_GET['status']) ? clean_input($_GET['status']) : '';
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// SQL sorgusu için temel koşullar
$conditions = ["1=1"]; // Her zaman doğru koşul
$params = [];
$types = "";

// Rol filtresi
if (!empty($role)) {
    $conditions[] = "role = ?";
    $params[] = $role;
    $types .= "s";
}

// Durum filtresi
if (!empty($status)) {
    $conditions[] = "status = ?";
    $params[] = $status;
    $types .= "s";
}

// Arama filtresi
if (!empty($search)) {
    $conditions[] = "(name LIKE ? OR surname LIKE ? OR email LIKE ? OR company_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ssss";
}

// Kullanıcıları getir
$sql = "
    SELECT * FROM users
    WHERE " . implode(" AND ", $conditions) . "
    ORDER BY created_at DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// Toplam kullanıcı sayısı
$total_users = count($users);

// Kullanıcı durumu değiştirme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_status'])) {
    $user_id = (int)$_POST['user_id'];
    $new_status = clean_input($_POST['new_status']);
    
    $stmt = $conn->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $new_status, $user_id);
    
    if ($stmt->execute()) {
        // İşlem kaydı
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $stmt = $conn->prepare("
            INSERT INTO transaction_logs (user_id, related_id, related_type, action, details, ip_address, created_at) 
            VALUES (?, ?, 'user', 'status_change', ?, ?, NOW())
        ");
        $details = "Kullanıcı durumu değiştirildi: " . $new_status;
        $stmt->bind_param("iiss", $_SESSION['user_id'], $user_id, $details, $ip_address);
        $stmt->execute();
        
        $_SESSION['flash_message'] = "Kullanıcı durumu başarıyla güncellendi.";
        $_SESSION['flash_type'] = "success";
        
        // Sayfayı yenile
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);
        exit;
    } else {
        $_SESSION['flash_message'] = "Kullanıcı durumu güncellenirken bir hata oluştu: " . $conn->error;
        $_SESSION['flash_type'] = "danger";
    }
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Kullanıcı Yönetimi</h2>
        <p>Toplam <?php echo $total_users; ?> kullanıcı</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Panele Dön
        </a>
    </div>
</div>

<!-- Filtreler -->
<div class="card mb-4">
    <div class="card-body">
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="role" class="form-label">Rol</label>
                <select class="form-select" id="role" name="role">
                    <option value="">Tüm Roller</option>
                    <option value="admin" <?php echo $role == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="buyer" <?php echo $role == 'buyer' ? 'selected' : ''; ?>>Alıcı</option>
                    <option value="supplier" <?php echo $role == 'supplier' ? 'selected' : ''; ?>>Tedarikçi</option>
                    <option value="sponsor" <?php echo $role == 'sponsor' ? 'selected' : ''; ?>>Sponsor</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Durum</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Tüm Durumlar</option>
                    <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Aktif</option>
                    <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>Pasif</option>
                    <option value="blocked" <?php echo $status == 'blocked' ? 'selected' : ''; ?>>Engelli</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="search" class="form-label">Arama</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="search" name="search" placeholder="Ad, soyad, e-posta veya şirket adı" value="<?php echo $search; ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-sync"></i> Sıfırla
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Kullanıcı Listesi -->
<?php if (empty($users)): ?>
    <div class="alert alert-info">
        <h5><i class="fas fa-info-circle"></i> Kullanıcı Bulunamadı</h5>
        <p>Seçilen kriterlere uygun kullanıcı bulunamadı. Lütfen filtre kriterlerini değiştirin.</p>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Ad Soyad</th>
                            <th>E-posta</th>
                            <th>Telefon</th>
                            <th>Rol</th>
                            <th>Şirket</th>
                            <th>Kayıt Tarihi</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <?php if (!empty($user['profile_image'])): ?>
                                        <img src="<?php echo SITE_URL; ?>/uploads/profiles/<?php echo $user['profile_image']; ?>" alt="Profil" class="rounded-circle me-1" width="25" height="25">
                                    <?php else: ?>
                                        <i class="fas fa-user-circle me-1"></i>
                                    <?php endif; ?>
                                    <?php echo $user['name'] . ' ' . $user['surname']; ?>
                                </td>
                                <td><?php echo $user['email']; ?></td>
                                <td><?php echo $user['phone']; ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $user['role'] == 'admin' ? 'dark' : 
                                            ($user['role'] == 'buyer' ? 'primary' : 
                                                ($user['role'] == 'supplier' ? 'success' : 'warning')); 
                                    ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo $user['company_name'] ?? '-'; ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $user['status'] == 'active' ? 'success' : 
                                            ($user['status'] == 'inactive' ? 'secondary' : 'danger'); 
                                    ?>">
                                        <?php 
                                            echo $user['status'] == 'active' ? 'Aktif' : 
                                                ($user['status'] == 'inactive' ? 'Pasif' : 'Engelli'); 
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?php echo SITE_URL; ?>/admin/user-edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="visually-hidden">Menü</span>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php if ($user['role'] != 'admin' || $_SESSION['user_id'] == 1): // Ana admin (ID=1) diğer adminleri yönetebilir ?>
                                                <li>
                                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/user-details.php?id=<?php echo $user['id']; ?>">
                                                        <i class="fas fa-eye"></i> Detaylar
                                                    </a>
                                                </li>
                                                <li>
                                                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" onsubmit="return confirm('Kullanıcı durumunu değiştirmek istediğinize emin misiniz?');">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <?php if ($user['status'] == 'active'): ?>
                                                            <input type="hidden" name="new_status" value="blocked">
                                                            <button type="submit" name="change_status" class="dropdown-item text-danger">
                                                                <i class="fas fa-ban"></i> Engelle
                                                            </button>
                                                        <?php elseif ($user['status'] == 'blocked'): ?>
                                                            <input type="hidden" name="new_status" value="active">
                                                            <button type="submit" name="change_status" class="dropdown-item text-success">
                                                                <i class="fas fa-check"></i> Aktifleştir
                                                            </button>
                                                        <?php else: ?>
                                                            <input type="hidden" name="new_status" value="active">
                                                            <button type="submit" name="change_status" class="dropdown-item text-success">
                                                                <i class="fas fa-check"></i> Aktifleştir
                                                            </button>
                                                        <?php endif; ?>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
require_once '../footer.php';
?>