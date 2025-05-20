<?php
$page_title = "Admin Paneli";
require_once '../header.php';

// Yalnızca admin rolüne sahip kullanıcılar erişebilir
if (!has_role(ROLE_ADMIN)) {
    $_SESSION['flash_message'] = "Bu sayfaya erişim izniniz yok.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL);
    exit;
}

// İstatistikler
// Toplam kullanıcı sayısı
$stmt = $conn->prepare("SELECT COUNT(*) AS total_users FROM users");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_users = $row['total_users'];

// Rol bazlı kullanıcı sayısı
$stmt = $conn->prepare("SELECT role, COUNT(*) AS count FROM users GROUP BY role");
$stmt->execute();
$result = $stmt->get_result();
$users_by_role = [];
while ($row = $result->fetch_assoc()) {
    $users_by_role[$row['role']] = $row['count'];
}

// Toplam talep sayısı
$stmt = $conn->prepare("SELECT COUNT(*) AS total_requests FROM requests");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_requests = $row['total_requests'];

// Durum bazlı talep sayısı
$stmt = $conn->prepare("SELECT status, COUNT(*) AS count FROM requests GROUP BY status");
$stmt->execute();
$result = $stmt->get_result();
$requests_by_status = [];
while ($row = $result->fetch_assoc()) {
    $requests_by_status[$row['status']] = $row['count'];
}

// Toplam teklif sayısı
$stmt = $conn->prepare("SELECT COUNT(*) AS total_offers FROM offers");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_offers = $row['total_offers'];

// Toplam sponsorluk sayısı
$stmt = $conn->prepare("SELECT COUNT(*) AS total_sponsorships FROM sponsorships");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_sponsorships = $row['total_sponsorships'];

// Toplam komisyon
$stmt = $conn->prepare("SELECT SUM(total_commission) AS total_commission FROM commissions");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_commission = $row['total_commission'] ?? 0;

// Son işlemler
$stmt = $conn->prepare("
    SELECT tl.*, u.name, u.surname, u.email 
    FROM transaction_logs tl
    JOIN users u ON tl.user_id = u.id
    ORDER BY tl.created_at DESC
    LIMIT 10
");
$stmt->execute();
$result = $stmt->get_result();
$recent_transactions = [];
while ($row = $result->fetch_assoc()) {
    $recent_transactions[] = $row;
}

// Son kullanıcılar
$stmt = $conn->prepare("
    SELECT * FROM users
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->execute();
$result = $stmt->get_result();
$recent_users = [];
while ($row = $result->fetch_assoc()) {
    $recent_users[] = $row;
}

// Son talepler
$stmt = $conn->prepare("
    SELECT r.*, u.name, u.surname
    FROM requests r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
    LIMIT 5
");
$stmt->execute();
$result = $stmt->get_result();
$recent_requests = [];
while ($row = $result->fetch_assoc()) {
    $recent_requests[] = $row;
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Admin Paneli</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo SITE_URL; ?>/admin/users.php" class="btn btn-primary me-2">
            <i class="fas fa-users"></i> Kullanıcılar
        </a>
        <a href="<?php echo SITE_URL; ?>/admin/requests.php" class="btn btn-primary">
            <i class="fas fa-list"></i> Talepler
        </a>
    </div>
</div>

<!-- İstatistikler -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Toplam Kullanıcı</h5>
                <h2 class="display-6"><?php echo $total_users; ?></h2>
                <div class="small mt-2">
                    <?php foreach ($users_by_role as $role => $count): ?>
                        <div><?php echo ucfirst($role); ?>: <?php echo $count; ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="<?php echo SITE_URL; ?>/admin/users.php" class="text-white">Detaylı Görüntüle <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Toplam Talep</h5>
                <h2 class="display-6"><?php echo $total_requests; ?></h2>
                <div class="small mt-2">
                    <?php foreach ($requests_by_status as $status => $count): ?>
                        <div><?php echo get_status_text($status); ?>: <?php echo $count; ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="<?php echo SITE_URL; ?>/admin/requests.php" class="text-white">Detaylı Görüntüle <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <h5 class="card-title">İşlem Özeti</h5>
                <h2 class="display-6"><?php echo $total_offers; ?></h2>
                <div class="small mt-2">
                    <div>Teklif Sayısı: <?php echo $total_offers; ?></div>
                    <div>Sponsorluk Sayısı: <?php echo $total_sponsorships; ?></div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="<?php echo SITE_URL; ?>/admin/transactions.php" class="text-white">Detaylı Görüntüle <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body">
                <h5 class="card-title">Toplam Kazanç</h5>
                <h2 class="display-6"><?php echo format_money($total_commission); ?></h2>
                <div class="small mt-2">
                    <div>Platform Komisyonu: <?php echo format_money($total_commission); ?></div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="<?php echo SITE_URL; ?>/admin/commissions.php" class="text-dark">Detaylı Görüntüle <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Son Kullanıcılar -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Son Kullanıcılar</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ad Soyad</th>
                                <th>E-posta</th>
                                <th>Rol</th>
                                <th>Kayıt Tarihi</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td><?php echo $user['name'] . ' ' . $user['surname']; ?></td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $user['role'] == 'admin' ? 'dark' : 
                                                ($user['role'] == 'buyer' ? 'primary' : 
                                                    ($user['role'] == 'supplier' ? 'success' : 'warning')); 
                                        ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>/admin/user-edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <a href="<?php echo SITE_URL; ?>/admin/users.php" class="btn btn-sm btn-outline-primary">Tüm Kullanıcılar</a>
            </div>
        </div>
    </div>
    
    <!-- Son Talepler -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Son Talepler</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ref. Kodu</th>
                                <th>Başlık</th>
                                <th>Alıcı</th>
                                <th>Durum</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_requests as $request): ?>
                                <tr>
                                    <td><?php echo $request['reference_code']; ?></td>
                                    <td><?php echo $request['title']; ?></td>
                                    <td><?php echo $request['name'] . ' ' . $request['surname']; ?></td>
                                    <td>
                                        <?php
                                        $status_classes = [
                                            'pending' => 'bg-warning text-dark',
                                            'approved' => 'bg-success',
                                            'in_production' => 'bg-info',
                                            'completed' => 'bg-primary',
                                            'cancelled' => 'bg-danger',
                                            'disputed' => 'bg-dark'
                                        ];
                                        $status_class = isset($status_classes[$request['status']]) ? $status_classes[$request['status']] : 'bg-secondary';
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo get_status_text($request['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>/admin/request-details.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <a href="<?php echo SITE_URL; ?>/admin/requests.php" class="btn btn-sm btn-outline-primary">Tüm Talepler</a>
            </div>
        </div>
    </div>
</div>

<!-- Son İşlemler -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0">Son İşlemler</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kullanıcı</th>
                        <th>İşlem</th>
                        <th>Detay</th>
                        <th>IP Adresi</th>
                        <th>Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_transactions as $transaction): ?>
                        <tr>
                            <td><?php echo $transaction['name'] . ' ' . $transaction['surname']; ?></td>
                            <td><?php echo $transaction['action']; ?></td>
                            <td><?php echo $transaction['details']; ?></td>
                            <td><?php echo $transaction['ip_address']; ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($transaction['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <a href="<?php echo SITE_URL; ?>/admin/transactions.php" class="btn btn-sm btn-outline-primary">Tüm İşlemler</a>
    </div>
</div>

<?php
require_once '../footer.php';
?>