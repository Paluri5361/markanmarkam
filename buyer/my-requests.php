<?php
$page_title = "Taleplerim";
require_once '../header.php';

// Yalnızca alıcı rolüne sahip kullanıcılar erişebilir
if (!has_role(ROLE_BUYER)) {
    $_SESSION['flash_message'] = "Bu sayfaya erişim izniniz yok.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL);
    exit;
}

// Talepleri getir
$stmt = $conn->prepare("
    SELECT r.*, 
           COUNT(o.id) AS offer_count,
           (SELECT COUNT(*) FROM escrow_payments WHERE request_id = r.id AND status = 'pending') AS has_escrow
    FROM requests r
    LEFT JOIN offers o ON r.id = o.request_id
    WHERE r.user_id = ?
    GROUP BY r.id
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Taleplerim</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo SITE_URL; ?>/buyer/create-request.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Yeni Talep Oluştur
        </a>
    </div>
</div>

<?php if (empty($requests)): ?>
    <div class="alert alert-info">
        Henüz oluşturduğunuz bir talep bulunmamaktadır. Yeni bir talep oluşturmak için "Yeni Talep Oluştur" butonuna tıklayın.
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Referans Kodu</th>
                            <th>Başlık</th>
                            <th>Kategori</th>
                            <th>Bütçe</th>
                            <th>Son Teslim</th>
                            <th>Teklifler</th>
                            <th>Sponsor</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?php echo $request['reference_code']; ?></td>
                                <td><?php echo $request['title']; ?></td>
                                <td>
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
                                </td>
                                <td><?php echo format_money($request['budget']); ?></td>
                                <td><?php echo date('d.m.Y', strtotime($request['deadline'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $request['offer_count'] > 0 ? 'success' : 'secondary'; ?>">
                                        <?php echo $request['offer_count']; ?> Teklif
                                    </span>
                                </td>
                                <td>
                                    <?php if ($request['sponsor_id']): ?>
                                        <span class="badge bg-warning text-dark">Sponsorlu</span>
                                    <?php elseif ($request['allow_sponsor']): ?>
                                        <span class="badge bg-light text-dark">Açık</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Kapalı</span>
                                    <?php endif; ?>
                                </td>
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
                                    <div class="btn-group">
                                        <a href="<?php echo SITE_URL; ?>/buyer/view-offers.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> Teklifleri Gör
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="visually-hidden">Menü</span>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/buyer/request-details.php?id=<?php echo $request['id']; ?>">
                                                    <i class="fas fa-info-circle"></i> Detaylar
                                                </a>
                                            </li>
                                            <?php if ($request['status'] == 'pending' && $request['offer_count'] == 0): ?>
                                                <li>
                                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/buyer/edit-request.php?id=<?php echo $request['id']; ?>">
                                                        <i class="fas fa-edit"></i> Düzenle
                                                    </a>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/buyer/cancel-request.php?id=<?php echo $request['id']; ?>" onclick="return confirm('Bu talebi iptal etmek istediğinize emin misiniz?')">
                                                        <i class="fas fa-trash"></i> İptal Et
                                                    </a>
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