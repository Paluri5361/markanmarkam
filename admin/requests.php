<?php
$page_title = "Talep Yönetimi";
require_once '../header.php';

// Yalnızca admin rolüne sahip kullanıcılar erişebilir
if (!has_role(ROLE_ADMIN)) {
    $_SESSION['flash_message'] = "Bu sayfaya erişim izniniz yok.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL);
    exit;
}

// Kategorileri tanımla
$categories = [
    'elektronik' => 'Elektronik',
    'tekstil' => 'Tekstil',
    'mobilya' => 'Mobilya',
    'gida' => 'Gıda',
    'otomotiv' => 'Otomotiv',
    'kozmetik' => 'Kozmetik',
    'diger' => 'Diğer'
];

// Basitleştirilmiş talepler sorgusu - daha az join ve daha az karmaşık
try {
    $sql = "SELECT r.*, u.name, u.surname 
            FROM requests r 
            JOIN users u ON r.user_id = u.id 
            ORDER BY r.created_at DESC";
    $result = $conn->query($sql);
    
    $requests = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
    }
    
    // Toplam talep sayısı
    $total_requests = count($requests);
} catch (Exception $e) {
    $error_message = "Veritabanı sorgusu hatası: " . $e->getMessage();
    $requests = [];
    $total_requests = 0;
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Talep Yönetimi</h2>
        <p>Toplam <?php echo $total_requests; ?> talep</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Panele Dön
        </a>
    </div>
</div>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger">
        <h5><i class="fas fa-exclamation-circle"></i> Hata</h5>
        <p><?php echo $error_message; ?></p>
    </div>
<?php endif; ?>

<!-- Talep Listesi -->
<div class="card">
    <div class="card-body">
        <?php if (empty($requests)): ?>
            <div class="alert alert-info mb-0">
                <h5><i class="fas fa-info-circle"></i> Talep Bulunamadı</h5>
                <p>Sistemde henüz talep bulunmuyor veya talepleri getirirken bir hata oluştu.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ref. Kodu</th>
                            <th>Başlık</th>
                            <th>Alıcı</th>
                            <th>Kategori</th>
                            <th>Bütçe</th>
                            <th>Durum</th>
                            <th>Tarih</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?php echo $request['id']; ?></td>
                                <td><?php echo $request['reference_code']; ?></td>
                                <td><?php echo $request['title']; ?></td>
                                <td><?php echo $request['name'] . ' ' . $request['surname']; ?></td>
                                <td>
                                    <?php 
                                    $category = isset($categories[$request['category']]) ? $categories[$request['category']] : $request['category'];
                                    ?>
                                    <span class="badge bg-secondary"><?php echo $category; ?></span>
                                </td>
                                <td><?php echo $request['budget']; ?> TL</td>
                                <td>
                                    <?php
                                    $status_text = '';
                                    $status_class = '';
                                    
                                    switch ($request['status']) {
                                        case 'pending':
                                            $status_text = 'Beklemede';
                                            $status_class = 'bg-warning text-dark';
                                            break;
                                        case 'approved':
                                            $status_text = 'Onaylandı';
                                            $status_class = 'bg-success';
                                            break;
                                        case 'in_production':
                                            $status_text = 'Üretimde';
                                            $status_class = 'bg-info';
                                            break;
                                        case 'completed':
                                            $status_text = 'Tamamlandı';
                                            $status_class = 'bg-primary';
                                            break;
                                        case 'cancelled':
                                            $status_text = 'İptal Edildi';
                                            $status_class = 'bg-danger';
                                            break;
                                        case 'disputed':
                                            $status_text = 'Anlaşmazlık';
                                            $status_class = 'bg-dark';
                                            break;
                                        default:
                                            $status_text = 'Bilinmiyor';
                                            $status_class = 'bg-secondary';
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y', strtotime($request['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="#" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> Detaylar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once '../footer.php';
?>