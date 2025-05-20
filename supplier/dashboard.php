<?php
$page_title = "Tedarikçi Paneli";
require_once '../header.php';

// Yalnızca tedarikçi rolüne sahip kullanıcılar erişebilir
if (!has_role(ROLE_SUPPLIER)) {
    $_SESSION['flash_message'] = "Bu sayfaya erişim izniniz yok.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL);
    exit;
}

// Tedarikçi bilgilerini getir
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$supplier = $result->fetch_assoc();

// Teklifleri getir
$stmt = $conn->prepare("
    SELECT o.*, r.title, r.reference_code, r.status as request_status 
    FROM offers o
    JOIN requests r ON o.request_id = r.id
    WHERE o.supplier_id = ?
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

// Aktif siparişleri getir
$stmt = $conn->prepare("
    SELECT r.*, o.price, o.estimated_days
    FROM requests r
    JOIN offers o ON r.selected_offer_id = o.id
    WHERE o.supplier_id = ? AND r.status IN ('approved', 'in_production')
    ORDER BY r.selected_date DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$active_orders = [];
while ($row = $result->fetch_assoc()) {
    $active_orders[] = $row;
}

// İstatistikler
// Toplam kazanç
$stmt = $conn->prepare("
    SELECT SUM(o.price) AS total_earnings
    FROM offers o
    JOIN requests r ON r.selected_offer_id = o.id
    WHERE o.supplier_id = ? AND r.status = 'completed'
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$earnings_row = $result->fetch_assoc();
$total_earnings = $earnings_row['total_earnings'] ?? 0;

// Toplam teklif sayısı
$stmt = $conn->prepare("SELECT COUNT(*) AS total_offers FROM offers WHERE supplier_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$offers_row = $result->fetch_assoc();
$total_offers = $offers_row['total_offers'];

// Kabul edilen teklif sayısı
$stmt = $conn->prepare("SELECT COUNT(*) AS accepted_offers FROM offers WHERE supplier_id = ? AND status = 'accepted'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$accepted_row = $result->fetch_assoc();
$accepted_offers = $accepted_row['accepted_offers'];

// Tamamlanan sipariş sayısı
$stmt = $conn->prepare("
    SELECT COUNT(*) AS completed_orders
    FROM offers o
    JOIN requests r ON r.selected_offer_id = o.id
    WHERE o.supplier_id = ? AND r.status = 'completed'
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$completed_row = $result->fetch_assoc();
$completed_orders = $completed_row['completed_orders'];

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
        <h2>Tedarikçi Paneli</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo SITE_URL; ?>/supplier/request-list.php" class="btn btn-primary">
            <i class="fas fa-search"></i> Talep Listesine Git
        </a>
    </div>
</div>

<!-- İstatistikler -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Toplam Kazanç</h5>
                <h2 class="display-6"><?php echo format_money($total_earnings); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Tamamlanan Siparişler</h5>
                <h2 class="display-6"><?php echo $completed_orders; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Toplam Teklifler</h5>
                <h2 class="display-6"><?php echo $total_offers; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body">
                <h5 class="card-title">Kabul Oranı</h5>
                <h2 class="display-6">
                    <?php 
                    echo $total_offers > 0 
                        ? round(($accepted_offers / $total_offers) * 100) . '%' 
                        : '0%'; 
                    ?>
                </h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Aktif Siparişler -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Aktif Siparişler</h5>
            </div>
            <div class="card-body">
                <?php if (empty($active_orders)): ?>
                    <div class="alert alert-info mb-0">
                        <p class="mb-0">Aktif siparişiniz bulunmamaktadır.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($active_orders as $order): ?>
                            <a href="<?php echo SITE_URL; ?>/supplier/order-details.php?id=<?php echo $order['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo $order['title']; ?></h6>
                                        <p class="mb-1 text-muted small">Referans: <?php echo $order['reference_code']; ?></p>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-<?php echo $order['status'] == 'approved' ? 'success' : 'info'; ?>">
                                            <?php echo get_status_text($order['status']); ?>
                                        </span>
                                        <p class="mb-0"><?php echo format_money($order['price']); ?></p>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="<?php echo SITE_URL; ?>/supplier/my-orders.php" class="btn btn-sm btn-outline-primary">Tüm Siparişler</a>
            </div>
        </div>
    </div>
    
    <!-- Son Teklifler -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Son Tekliflerim</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_offers)): ?>
                    <div class="alert alert-info mb-0">
                        <p class="mb-0">Henüz teklif vermemişsiniz.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($recent_offers as $offer): ?>
                            <a href="<?php echo SITE_URL; ?>/supplier/offer-details.php?id=<?php echo $offer['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo $offer['title']; ?></h6>
                                        <p class="mb-1 text-muted small">Referans: <?php echo $offer['reference_code']; ?></p>
                                    </div>
                                    <div class="text-end">
                                        <?php
                                        $offer_status_classes = [
                                            'pending' => 'bg-warning text-dark',
                                            'accepted' => 'bg-success',
                                            'rejected' => 'bg-danger'
                                        ];
                                        $offer_status_class = isset($offer_status_classes[$offer['status']]) ? $offer_status_classes[$offer['status']] : 'bg-secondary';
                                        
                                        $offer_status_text = [
                                            'pending' => 'Beklemede',
                                            'accepted' => 'Kabul Edildi',
                                            'rejected' => 'Reddedildi'
                                        ];
                                        $offer_status = isset($offer_status_text[$offer['status']]) ? $offer_status_text[$offer['status']] : 'Bilinmiyor';
                                        ?>
                                        <span class="badge <?php echo $offer_status_class; ?>">
                                            <?php echo $offer_status; ?>
                                        </span>
                                        <p class="mb-0"><?php echo format_money($offer['price']); ?></p>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="<?php echo SITE_URL; ?>/supplier/my-offers.php" class="btn btn-sm btn-outline-primary">Tüm Teklifler</a>
            </div>
        </div>
    </div>
</div>

<!-- Profil ve Bildirimler -->
<div class="row">
    <!-- Profil Özeti -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Profil Bilgileri</h5>
            </div>
            <div class="card-body">
                <div class="d-flex mb-3">
                    <div class="me-3">
                        <?php if (!empty($supplier['profile_image'])): ?>
                            <img src="<?php echo SITE_URL; ?>/uploads/profiles/<?php echo $supplier['profile_image']; ?>" alt="Profil" class="rounded-circle" width="80" height="80">
                        <?php else: ?>
                            <div class="rounded-circle bg-secondary text-white d-flex justify-content-center align-items-center" style="width: 80px; height: 80px;">
                                <i class="fas fa-user fa-2x"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h4><?php echo $supplier['company_name']; ?></h4>
                        <p class="text-muted mb-0"><?php echo $supplier['name'] . ' ' . $supplier['surname']; ?></p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>E-posta:</strong> <?php echo $supplier['email']; ?></p>
                        <p><strong>Telefon:</strong> <?php echo $supplier['phone']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Vergi No:</strong> <?php echo $supplier['tax_number']; ?></p>
                        <p><strong>Üyelik Tarihi:</strong> <?php echo date('d.m.Y', strtotime($supplier['created_at'])); ?></p>
                    </div>
                </div>
                
                <div>
                    <h6>Hizmet Verilen Kategoriler:</h6>
                    <?php
                    $categories_arr = json_decode($supplier['categories'], true) ?? [];
                    if (!empty($categories_arr)):
                        foreach ($categories_arr as $category):
                            $categories_list = [
                                'elektronik' => 'Elektronik',
                                'tekstil' => 'Tekstil',
                                'mobilya' => 'Mobilya',
                                'gida' => 'Gıda',
                                'otomotiv' => 'Otomotiv',
                                'kozmetik' => 'Kozmetik',
                                'diger' => 'Diğer'
                            ];
                            $category_name = isset($categories_list[$category]) ? $categories_list[$category] : $category;
                    ?>
                        <span class="badge bg-secondary me-1"><?php echo $category_name; ?></span>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <p class="text-muted">Henüz kategori seçilmemiş</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer">
                <a href="<?php echo SITE_URL; ?>/auth/profile.php" class="btn btn-sm btn-outline-primary">Profili Düzenle</a>
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
                        <p class="mb-0">Henüz bildiriminiz bulunmamaktadır.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($notifications as $notification): ?>
                            <a href="<?php echo !empty($notification['link']) ? $notification['link'] : '#'; ?>" class="list-group-item list-group-item-action">
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
                <a href="<?php echo SITE_URL; ?>/supplier/notifications.php" class="btn btn-sm btn-outline-primary">Tüm Bildirimler</a>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../footer.php';
?>