<?php
$page_title = "Sponsor Paneli";
require_once '../header.php';

// Yalnızca sponsor rolüne sahip kullanıcılar erişebilir
if (!has_role(ROLE_SPONSOR)) {
    $_SESSION['flash_message'] = "Bu sayfaya erişim izniniz yok.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL);
    exit;
}

// Sponsor bilgilerini getir
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$sponsor = $result->fetch_assoc();

// Sponsor olunan talepleri getir
$stmt = $conn->prepare("
    SELECT r.*, sr.amount, sr.is_partial, sr.ad_requirements
    FROM sponsorships sr
    JOIN requests r ON sr.request_id = r.id
    WHERE sr.sponsor_id = ?
    ORDER BY sr.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$recent_sponsorships = [];
while ($row = $result->fetch_assoc()) {
    $recent_sponsorships[] = $row;
}

// İstatistikler
// Toplam sponsorluk miktarı
$stmt = $conn->prepare("
    SELECT SUM(amount) AS total_amount
    FROM sponsorships
    WHERE sponsor_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$amount_row = $result->fetch_assoc();
$total_amount = $amount_row['total_amount'] ?? 0;

// Toplam sponsorluk sayısı
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total_sponsorships
    FROM sponsorships
    WHERE sponsor_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$count_row = $result->fetch_assoc();
$total_sponsorships = $count_row['total_sponsorships'];

// Sponsor olunabilecek açık talepleri getir
$stmt = $conn->prepare("
    SELECT r.*, u.name, u.surname
    FROM requests r
    JOIN users u ON r.user_id = u.id
    WHERE r.status = 'pending' AND r.allow_sponsor = 1
    AND r.id NOT IN (SELECT request_id FROM sponsorships WHERE sponsor_id = ?)
    ORDER BY r.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$available_requests = [];
while ($row = $result->fetch_assoc()) {
    $available_requests[] = $row;
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
        <h2>Sponsor Paneli</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo SITE_URL; ?>/sponsor/sponsor-requests.php" class="btn btn-primary">
            <i class="fas fa-search"></i> Sponsorluk Fırsatları
        </a>
    </div>
</div>

<!-- İstatistikler -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Toplam Sponsorluk</h5>
                <h2 class="display-6"><?php echo format_money($total_amount); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Desteklenen Talep Sayısı</h5>
                <h2 class="display-6"><?php echo $total_sponsorships; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Sponsorluklar -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Son Sponsorluklarım</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_sponsorships)): ?>
                    <div class="alert alert-info mb-0">
                        <p class="mb-0">Henüz bir sponsorluk desteği sağlamamışsınız.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($recent_sponsorships as $sponsorship): ?>
                            <a href="<?php echo SITE_URL; ?>/sponsor/sponsorship-details.php?id=<?php echo $sponsorship['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo $sponsorship['title']; ?></h6>
                                        <p class="mb-1 text-muted small">Referans: <?php echo $sponsorship['reference_code']; ?></p>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-<?php echo $sponsorship['is_partial'] ? 'warning text-dark' : 'success'; ?>">
                                            <?php echo $sponsorship['is_partial'] ? 'Kısmi' : 'Tam'; ?> Sponsorluk
                                        </span>
                                        <p class="mb-0"><?php echo format_money($sponsorship['amount']); ?></p>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="<?php echo SITE_URL; ?>/sponsor/sponsored-requests.php" class="btn btn-sm btn-outline-primary">Tüm Sponsorluklarım</a>
            </div>
        </div>
    </div>
    
    <!-- Açık Talepler -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Sponsorluk Fırsatları</h5>
            </div>
            <div class="card-body">
                <?php if (empty($available_requests)): ?>
                    <div class="alert alert-info mb-0">
                        <p class="mb-0">Şu anda sponsorluk verebileceğiniz talep bulunmamaktadır.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($available_requests as $request): ?>
                            <a href="<?php echo SITE_URL; ?>/sponsor/sponsor-form.php?id=<?php echo $request['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo $request['title']; ?></h6>
                                        <p class="mb-1 text-muted small">
                                            <?php echo $request['name'] . ' ' . $request['surname']; ?> tarafından
                                        </p>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-secondary">
                                            <?php 
                                            $categories = [
                                                'elektronik' => 'Elektronik',
                                                'tekstil' => 'Tekstil',
                                                'mobilya' => 'Mobilya',
                                                'gida' => 'Gıda',
                                                'otomotiv' => 'Otomotiv',
                                                'kozmetik' => 'Kozmetik',
                                                'diger' => 'Diğer'
                                            ];
                                            echo isset($categories[$request['category']]) ? $categories[$request['category']] : $request['category'];
                                            ?>
                                        </span>
                                        <p class="mb-0"><?php echo format_money($request['budget']); ?></p>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="<?php echo SITE_URL; ?>/sponsor/sponsor-requests.php" class="btn btn-sm btn-outline-primary">Tüm Fırsatlar</a>
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
                        <?php if (!empty($sponsor['profile_image'])): ?>
                            <img src="<?php echo SITE_URL; ?>/uploads/profiles/<?php echo $sponsor['profile_image']; ?>" alt="Profil" class="rounded-circle" width="80" height="80">
                        <?php else: ?>
                            <div class="rounded-circle bg-secondary text-white d-flex justify-content-center align-items-center" style="width: 80px; height: 80px;">
                                <i class="fas fa-user fa-2x"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h4><?php echo $sponsor['company_name']; ?></h4>
                        <p class="text-muted mb-0"><?php echo $sponsor['name'] . ' ' . $sponsor['surname']; ?></p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>E-posta:</strong> <?php echo $sponsor['email']; ?></p>
                        <p><strong>Telefon:</strong> <?php echo $sponsor['phone']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Vergi No:</strong> <?php echo $sponsor['tax_number']; ?></p>
                        <p><strong>Üyelik Tarihi:</strong> <?php echo date('d.m.Y', strtotime($sponsor['created_at'])); ?></p>
                    </div>
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
                <a href="<?php echo SITE_URL; ?>/sponsor/notifications.php" class="btn btn-sm btn-outline-primary">Tüm Bildirimler</a>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../footer.php';
?>