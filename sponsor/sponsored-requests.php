<?php
// Hata ayıklama (geliştirme ortamında)
ini_set('display_errors', 1);
error_reporting(E_ALL);

$page_title = "Sponsorluk Fırsatları";
require_once '../header.php';

// Yalnızca sponsor rolüne sahip kullanıcılar erişebilir
if (!has_role(ROLE_SPONSOR)) {
    $_SESSION['flash_message'] = "Bu sayfaya erişim izniniz yok.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL);
    exit;
}

// Basit sorgu ile başlayalım
try {
    // Sponsor olunabilecek talepleri getir
    $sql = "SELECT r.*, u.name, u.surname 
            FROM requests r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.status = 'pending' 
            AND r.allow_sponsor = 1 
            ORDER BY r.created_at DESC";
    
    $result = $conn->query($sql);
    
    $requests = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
    }
} catch (Exception $e) {
    $error_message = "Veri yüklenirken hata oluştu: " . $e->getMessage();
}

// Kategoriler
$categories = [
    'elektronik' => 'Elektronik',
    'tekstil' => 'Tekstil',
    'mobilya' => 'Mobilya',
    'gida' => 'Gıda',
    'otomotiv' => 'Otomotiv',
    'kozmetik' => 'Kozmetik',
    'diger' => 'Diğer'
];
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Sponsorluk Fırsatları</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo SITE_URL; ?>/sponsor/dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Panele Dön
        </a>
    </div>
</div>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger">
        <p><?php echo $error_message; ?></p>
    </div>
<?php endif; ?>

<!-- Talep Listesi -->
<div class="card">
    <div class="card-body">
        <?php if (empty($requests)): ?>
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle"></i> Sponsorluk Fırsatı Bulunamadı</h5>
                <p>Şu anda sponsor olabileceğiniz talep bulunmamaktadır.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Ref. Kodu</th>
                            <th>Başlık</th>
                            <th>Alıcı</th>
                            <th>Kategori</th>
                            <th>Bütçe</th>
                            <th>Son Teslim</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?php echo $request['reference_code']; ?></td>
                                <td><strong><?php echo $request['title']; ?></strong></td>
                                <td><?php echo $request['name'] . ' ' . $request['surname']; ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo isset($categories[$request['category']]) ? $categories[$request['category']] : $request['category']; ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($request['budget'], 2, ',', '.'); ?> TL</td>
                                <td><?php echo date('d.m.Y', strtotime($request['deadline'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?php echo SITE_URL; ?>/sponsor/sponsor-form.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-handshake"></i> Sponsor Ol
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