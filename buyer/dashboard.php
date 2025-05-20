<?php
$page_title = "Alýcý Paneli";
require_once '../header.php';

// Yalnýzca alýcý rolüne sahip kullanýcýlar eriþebilir
if (!has_role(ROLE_BUYER)) {
    $_SESSION['flash_message'] = "Bu sayfaya eriþim izniniz yok.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL);
    exit;
}

// Kullanýcý bilgilerini getir
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Son talepleri getir
$stmt = $conn->prepare("
    SELECT r.*, 
           (SELECT COUNT(*) FROM offers WHERE request_id = r.id) AS offer_count
    FROM requests r
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$recent_requests = [];
while ($row = $result->fetch_assoc()) {
    $recent_requests[] = $row;
}

// Son gelen teklifleri getir
$stmt = $conn->prepare("
    SELECT o.*, r.title, r.reference_code, u.company_name, u.name, u.surname 
    FROM offers o
    JOIN requests r ON o.request_id = r.id
    JOIN users u ON o.supplier_id = u.id
    WHERE r.user_id = ?
    ORDER BY o.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$recent_offers = [];
while ($row = $result->fetch_assoc()) {
    $recent_offers[] = $row;
}

// Aktif sponsorluklarý getir
$stmt = $conn->prepare("
    SELECT s.*, r.title, r.reference_code, u.company_name
    FROM sponsorships s
    JOIN requests r ON s.request_id = r.id
    JOIN users u ON s.sponsor_id = u.id
    WHERE r.user_id = ? AND s.status = 'active'
    ORDER BY s.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$active_sponsorships = [];
while ($row = $result->fetch_assoc()) {
    $active_sponsorships[] = $row;
}

// Ýstatistikler
// Toplam talep sayýsý
$stmt = $conn->prepare("SELECT COUNT(*) AS total_requests FROM requests WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_requests = $row['total_requests'];

// Toplam alýnan teklif sayýsý
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total_offers
    FROM offers o
    JOIN requests r ON o.request_id = r.id
    WHERE r.user_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_offers = $row['total_offers'];

// Toplam aktif sponsorluk sayýsý
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total_sponsorships
    FROM sponsorships s
    JOIN requests r ON s.request_id = r.id
    WHERE r.user_id = ? AND s.status = 'active'
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_sponsorships = $row['total_sponsorships'];

// Durum bazlý talep sayýlarý
$stmt = $conn->prepare("
    SELECT status, COUNT(*) AS count
    FROM requests
    WHERE user_id = ?
    GROUP BY status
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$requests_by_status = [];
while ($row = $result->fetch_assoc()) {
    $requests_by_status[$row['status']] = $row['count'];
}

// Bildirimler
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
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Alýcý Paneli</h2>
        <p class="lead">Hoþ geldiniz, <?php echo $user['name'] . ' ' . $user['surname']; ?></p>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo SITE_URL; ?>/buyer/create-request.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Yeni Talep Oluþtur
        </a>
    </div>
</div>

<!-- Ýstatistikler -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Toplam Taleplerim</h5>
                <h2 class="display-6"><?php echo $total_requests; ?></h2>
                <div class="small mt-2">
                    <?php
                    $status_texts = [
                        'pending' => 'Beklemede',
                        'approved' => 'Onaylanmýþ',
                        'in_production' => 'Üretimde',
                        'completed' => 'Tamamlanmýþ',
                        'cancelled' => 'Ýptal Edilmiþ',
                        'disputed' => 'Anlaþmazlýk'
                    ];
                    
                    foreach ($status_texts as $status => $text):
                        $count = isset($requests_by_status[$status]) ? $requests_by_status[$status] : 0;
                        if ($count > 0):
                    ?>
                        <div><?php echo $text; ?>: <?php echo $count; ?></div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="<?php echo SITE_URL; ?>/buyer/my-requests.php" class="text-white">Tüm Talepleri Görüntüle <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Gelen Teklifler</h5>
                <h2 class="display-6"><?php echo $total_offers; ?></h2>
                <div class="small mt-2">
                    <div>Tekliflerinizi inceleyebilir ve en uygun olaný seçebilirsiniz.</div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="<?php echo SITE_URL; ?>/buyer/my-requests.php" class="text-white">Teklifleri Görüntüle <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Aktif Sponsorluklar</h5>
                <h2 class="display-6"><?php echo $total_sponsorships; ?></h2>
                <div class="small mt-2">
                    <div>Taleplerinize sponsor olan firmalar.</div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="<?php echo SITE_URL; ?>/buyer/view-sponsorships.php" class="text-white">Sponsorluklarý Görüntüle <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body">
                <h5 class="card-title">Profil Tamamlama</h5>
                <?php
                // Basit bir profil tamamlama yüzdesi hesaplama
                $total_fields = 6; // Toplam alan sayýsý
                $filled_fields = 0;
                
                if (!empty($user['name'])) $filled_fields++;
                if (!empty($user['surname'])) $filled_fields++;
                if (!empty($user['email'])) $filled_fields++;
                if (!empty($user['phone'])) $filled_fields++;
                if (!empty($user['address'])) $filled_fields++;
                if (!empty($user['profile_image'])) $filled_fields++;
                
                $completion_percent = ($filled_fields / $total_fields) * 100;
                ?>
                <h2 class="display-6"><?php echo round($completion_percent); ?>%</h2>
                <div class="progress mt-2" style="height: 10px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $completion_percent; ?>%" aria-valuenow="<?php echo $completion_percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="<?php echo SITE_URL; ?>/auth/profile.php" class="text-dark">Profili Düzenle <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Son Talepler -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Son Taleplerim</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_requests)): ?>
                    <div class="alert alert-info mb-0">
                        <p class="mb-0">Henüz bir talep oluþturmadýnýz. Talep oluþturmak için "Yeni Talep Oluþtur" butonuna týklayýn.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($recent_requests as $request): ?>
                            <a href="<?php echo SITE_URL; ?>/buyer/view-offers.php?id=<?php echo $request['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo $request['title']; ?></h6>
                                        <p class="mb-1 text-muted small">Referans: <?php echo $request['reference_code']; ?></p>
                                    </div>
                                    <div class="text-end">
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
                                        <br>
                                        <span class="badge bg-<?php echo $request['offer_count'] > 0 ? 'success' : 'secondary'; ?>">
                                            <?php echo $request['offer_count']; ?> Teklif
                                        </span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="<?php echo SITE_URL; ?>/buyer/my-requests.php" class="btn btn-sm btn-outline-primary">Tüm Taleplerim</a>
                <a href="<?php echo SITE_URL; ?>/buyer/create-request.php" class="btn btn-sm btn-primary">Yeni Talep</a>
            </div>
        </div>
    </div>
    
    <!-- Son Gelen Teklifler -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Son Gelen Teklifler</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_offers)): ?>
                    <div class="alert alert-info mb-0">
                        <p class="mb-0">Henüz teklifiniz bulunmamaktadýr. Teklif almak için talep oluþturun.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($recent_offers as $offer): ?>
                            <a href="<?php echo SITE_URL; ?>/buyer/view-offers.php?id=<?php echo $offer['request_id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo $offer['title']; ?></h6>
                                        <p class="mb-1 text-muted small">
                                            Teklif veren: <?php echo !empty($offer['company_name']) ? $offer['company_name'] : $offer['name'] . ' ' . $offer['surname']; ?>
                                        </p>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-primary"><?php echo format_money($offer['price']); ?></span>
                                        <br>
                                        <?php
                                        $offer_status_classes = [
                                            'pending' => 'bg-warning text-dark',
                                            'accepted' => 'bg-success',
                                            'rejected' => 'bg-danger'
                                        ];
                                        $offer_status_class = isset($offer_status_classes[$offer['status']]) ? $offer_status_classes[$offer['status']] : 'bg-secondary';
                                        
                                        $offer_status_texts = [
                                            'pending' => 'Beklemede',
                                            'accepted' => 'Kabul Edildi',
                                            'rejected' => 'Reddedildi'
                                        ];
                                        $offer_status_text = isset($offer_status_texts[$offer['status']]) ? $offer_status_texts[$offer['status']] : 'Bilinmiyor';
                                        ?>
                                        <span class="badge <?php echo $offer_status_class; ?>"><?php echo $offer_status_text; ?></span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="<?php echo SITE_URL; ?>/buyer/my-requests.php" class="btn btn-sm btn-outline-primary">Teklifleri Görüntüle</a>
            </div>
        </div>
    </div>
</div>

<!-- Aktif Sponsorluklar ve Bildirimler -->
<div class="row">
    <!-- Aktif Sponsorluklar -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Aktif Sponsorluklar</h5>
            </div>
            <div class="card-body">
                <?php if (empty($active_sponsorships)): ?>
                    <div class="alert alert-info mb-0">
                        <p class="mb-0">Henüz aktif sponsorluðunuz bulunmamaktadýr.</p>
                        <p>Talebinize sponsor bulabilmek için "Sponsor Desteðine Açýk" seçeneðini iþaretlemeyi unutmayýn.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($active_sponsorships as $sponsorship): ?>
                            <a href="<?php echo SITE_URL; ?>/buyer/view-sponsorships.php?id=<?php echo $sponsorship['request_id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo $sponsorship['title']; ?></h6>
                                        <p class="mb-1 text-muted small">
                                            Sponsor: <?php echo $sponsorship['company_name']; ?>
                                        </p>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success"><?php echo format_money($sponsorship['amount']); ?></span>
                                        <br>
                                        <span class="badge bg-<?php echo $sponsorship['is_partial'] ? 'warning text-dark' : 'success'; ?>">
                                            <?php echo $sponsorship['is_partial'] ? 'Kýsmi Sponsorluk' : 'Tam Sponsorluk'; ?>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="<?php echo SITE_URL; ?>/buyer/view-sponsorships.php" class="btn btn-sm btn-outline-primary">Tüm Sponsorluklar</a>
            </div>
        </div>
    </div>
    
    <!-- Bildirimler -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Son Bildirimler</h5>
            </div>
            <div class="card-body">
                <?php if (empty($notifications)): ?>
                    <div class="alert alert-info mb-0">
                        <p class="mb-0">Henüz bildiriminiz bulunmamaktadýr.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($notifications as $notification): ?>
                            <a href="<?php echo !empty($notification['link']) ? $notification['link'] : '#'; ?>" class="list-group-item list-group-item-action <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-1"><?php echo $notification['message']; ?></p>
                                    </div>
                                    <small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?></small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="<?php echo SITE_URL; ?>/buyer/notifications.php" class="btn btn-sm btn-outline-primary">Tüm Bildirimler</a>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../footer.php';
?>