<?php
$page_title = "Gelen Teklifler";
require_once '../header.php';

// Yalnızca alıcı rolüne sahip kullanıcılar erişebilir
if (!has_role(ROLE_BUYER)) {
    $_SESSION['flash_message'] = "Bu sayfaya erişim izniniz yok.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL);
    exit;
}

// Talep ID kontrolü
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = "Geçersiz talep.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL . "/buyer/my-requests.php");
    exit;
}

$request_id = (int)$_GET['id'];

// Talebin kullanıcıya ait olup olmadığını kontrol et
$stmt = $conn->prepare("SELECT * FROM requests WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $request_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['flash_message'] = "Bu talebe erişim izniniz yok veya talep bulunamadı.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL . "/buyer/my-requests.php");
    exit;
}

$request = $result->fetch_assoc();

// Teklif seçme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accept_offer'])) {
    $offer_id = (int)$_POST['offer_id'];
    
    // Teklifi getir
    $stmt = $conn->prepare("SELECT * FROM offers WHERE id = ? AND request_id = ?");
    $stmt->bind_param("ii", $offer_id, $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $offer = $result->fetch_assoc();
        
        // İşlem başlat
        $conn->begin_transaction();
        
        try {
            // Talebin durumunu güncelle
            $stmt = $conn->prepare("UPDATE requests SET status = 'approved', selected_offer_id = ?, selected_date = NOW() WHERE id = ?");
            $stmt->bind_param("ii", $offer_id, $request_id);
            $stmt->execute();
            
            // Escrow ödeme oluştur
            $escrow_id = create_escrow_payment($offer['price'], $_SESSION['user_id'], $request_id);
            
            if (!$escrow_id) {
                throw new Exception("Escrow ödemesi oluşturulamadı.");
            }
            
            // Escrow ID'sini teklife ekle
            $stmt = $conn->prepare("UPDATE offers SET status = 'accepted', escrow_id = ? WHERE id = ?");
            $stmt->bind_param("ii", $escrow_id, $offer_id);
            $stmt->execute();
            
            // Diğer teklifleri reddet
            $stmt = $conn->prepare("UPDATE offers SET status = 'rejected' WHERE request_id = ? AND id != ?");
            $stmt->bind_param("ii", $request_id, $offer_id);
            $stmt->execute();
            
            // Tedarikçiye bildirim gönder
            create_notification(
                $offer['supplier_id'],
                "Teklifiniz kabul edildi! Talep Kodu: " . $request['reference_code'],
                'offer_accepted',
                SITE_URL . "/supplier/my-offers.php"
            );
            
            // İşlemi tamamla
            $conn->commit();
            
            // Başarı mesajı
            $_SESSION['flash_message'] = "Teklif başarıyla kabul edildi. Ödemeniz escrow hesabında güvende tutulacaktır.";
            $_SESSION['flash_type'] = "success";
            
            // Ödeme sayfasına yönlendir
            header("Location: " . SITE_URL . "/payment/process-payment.php?escrow_id=" . $escrow_id);
            exit;
        } catch (Exception $e) {
            // Hata durumunda işlemi geri al
            $conn->rollback();
            
            $errors[] = "İşlem sırasında bir hata oluştu: " . $e->getMessage();
        }
    } else {
        $errors[] = "Teklif bulunamadı veya bu talebe ait değil.";
    }
}

// Sponsor işaretleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_sponsor'])) {
    $allow_sponsor = isset($_POST['allow_sponsor']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE requests SET allow_sponsor = ? WHERE id = ?");
    $stmt->bind_param("ii", $allow_sponsor, $request_id);
    
    if ($stmt->execute()) {
        $request['allow_sponsor'] = $allow_sponsor;
        
        $_SESSION['flash_message'] = "Sponsor durumu güncellendi.";
        $_SESSION['flash_type'] = "success";
    } else {
        $errors[] = "Sponsor durumu güncellenirken bir hata oluştu.";
    }
}

// Teklifleri getir
$stmt = $conn->prepare("
    SELECT o.*, u.company_name, u.rating 
    FROM offers o
    JOIN users u ON o.supplier_id = u.id
    WHERE o.request_id = ?
    ORDER BY o.price ASC
");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

$offers = [];
while ($row = $result->fetch_assoc()) {
    $offers[] = $row;
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Gelen Teklifler</h2>
        <p class="lead">Talep: <?php echo $request['title']; ?> (<?php echo $request['reference_code']; ?>)</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo SITE_URL; ?>/buyer/my-requests.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Taleplerime Dön
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <?php if (empty($offers)): ?>
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle"></i> Henüz Teklif Yok</h5>
                <p>Bu talebinize henüz teklif gelmemiştir. Teklifler geldiğinde burada listelenecektir.</p>
            </div>
        <?php else: ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><?php echo count($offers); ?> Teklif Bulundu</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($offers as $offer): ?>
                            <div class="list-group-item p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="mb-0">
                                        <?php echo $offer['company_name']; ?>
                                        <?php if ($offer['rating'] > 0): ?>
                                            <span class="text-warning">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star<?php echo ($i <= $offer['rating']) ? '' : '-o'; ?>"></i>
                                                <?php endfor; ?>
                                                (<?php echo $offer['rating']; ?>)
                                            </span>
                                        <?php endif; ?>
                                    </h5>
                                    <span class="badge bg-primary fs-5"><?php echo format_money($offer['price']); ?></span>
                                </div>
                                
                                <p><?php echo nl2br($offer['description']); ?></p>
                                
                                <?php if (!empty($offer['estimated_days'])): ?>
                                    <p><strong>Tahmini Üretim Süresi:</strong> <?php echo $offer['estimated_days']; ?> gün</p>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div>
                                        <small class="text-muted">Teklif Tarihi: <?php echo date('d.m.Y H:i', strtotime($offer['created_at'])); ?></small>
                                    </div>
                                    
                                    <?php if ($request['status'] == 'pending'): ?>
                                        <form action="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $request_id; ?>" method="POST" onsubmit="return confirm('Bu teklifi kabul etmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                            <button type="submit" name="accept_offer" class="btn btn-success">
                                                <i class="fas fa-check"></i> Teklifi Kabul Et
                                            </button>
                                        </form>
                                    <?php elseif ($request['selected_offer_id'] == $offer['id']): ?>
                                        <span class="badge bg-success p-2"><i class="fas fa-check"></i> Seçilen Teklif</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <!-- Talep Bilgileri -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Talep Bilgileri</h5>
            </div>
            <div class="card-body">
                <p><strong>Başlık:</strong> <?php echo $request['title']; ?></p>
                <p><strong>Kategori:</strong> 
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
                </p>
                <p><strong>Adet:</strong> <?php echo $request['quantity']; ?></p>
                <p><strong>Bütçe:</strong> <?php echo format_money($request['budget']); ?></p>
                <p><strong>Son Teslim:</strong> <?php echo date('d.m.Y', strtotime($request['deadline'])); ?></p>
                <p><strong>Durum:</strong> <span class="badge bg-<?php echo $request['status'] == 'pending' ? 'warning text-dark' : 'success'; ?>"><?php echo get_status_text($request['status']); ?></span></p>
                <p><strong>Oluşturma Tarihi:</strong> <?php echo date('d.m.Y', strtotime($request['created_at'])); ?></p>
            </div>
        </div>
        
        <!-- Sponsor Ayarları -->
        <?php if ($request['status'] == 'pending'): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Sponsor Ayarları</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $request_id; ?>" method="POST">
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="allow_sponsor" name="allow_sponsor" <?php echo $request['allow_sponsor'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="allow_sponsor">Sponsor desteğine açık olsun</label>
                            <div class="form-text">Sponsor desteği kabul ederseniz, ürününüz tamamen veya kısmen sponsor tarafından finanse edilebilir.</div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="mark_sponsor" class="btn btn-primary">Güncelle</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once '../footer.php';
?>