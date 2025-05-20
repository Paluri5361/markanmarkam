<?php
$page_title = "Teklif Gönder";
require_once '../header.php';

// Yalnızca tedarikçi rolüne sahip kullanıcılar erişebilir
if (!has_role(ROLE_SUPPLIER)) {
    $_SESSION['flash_message'] = "Bu sayfaya erişim izniniz yok.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL);
    exit;
}

// Talep ID kontrolü
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = "Geçersiz talep.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL . "/supplier/request-list.php");
    exit;
}

$request_id = (int)$_GET['id'];

// Talebi getir
$stmt = $conn->prepare("SELECT r.*, u.name, u.surname FROM requests r JOIN users u ON r.user_id = u.id WHERE r.id = ? AND r.status = 'pending'");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['flash_message'] = "Talep bulunamadı veya artık aktif değil.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL . "/supplier/request-list.php");
    exit;
}

$request = $result->fetch_assoc();

// Zaten teklif verilmiş mi kontrol et
$stmt = $conn->prepare("SELECT * FROM offers WHERE request_id = ? AND supplier_id = ?");
$stmt->bind_param("ii", $request_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['flash_message'] = "Bu talebe zaten teklif verdiniz.";
    $_SESSION['flash_type'] = "warning";
    header("Location: " . SITE_URL . "/supplier/request-list.php");
    exit;
}

// Teklif dosyalarını getir
$stmt = $conn->prepare("SELECT * FROM request_attachments WHERE request_id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

$attachments = [];
while ($row = $result->fetch_assoc()) {
    $attachments[] = $row;
}

// Teklif gönderme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $price = (float)$_POST['price'];
    $description = clean_input($_POST['description']);
    $estimated_days = (int)$_POST['estimated_days'];
    
    // Validasyon
    $errors = [];
    
    if (empty($price) || $price <= 0) {
        $errors[] = "Geçerli bir fiyat girin.";
    }
    
    if (empty($description)) {
        $errors[] = "Teklif açıklaması gereklidir.";
    }
    
    if (empty($estimated_days) || $estimated_days <= 0) {
        $errors[] = "Geçerli bir üretim süresi girin.";
    }
    
    // Son teslim tarihinden önce mi kontrol et
    $estimated_completion = date('Y-m-d', strtotime("+$estimated_days days"));
    $deadline = date('Y-m-d', strtotime($request['deadline']));
    
    if ($estimated_completion > $deadline) {
        $errors[] = "Tahmini üretim süresi, son teslim tarihini aşıyor. Lütfen daha kısa bir süre belirtin.";
    }
    
    // Hata yoksa teklifi kaydet
    if (empty($errors)) {
        // Teklifi ekle
        $stmt = $conn->prepare("INSERT INTO offers (request_id, supplier_id, price, description, estimated_days, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("iidsi", $request_id, $_SESSION['user_id'], $price, $description, $estimated_days);
        
        if ($stmt->execute()) {
            $offer_id = $conn->insert_id;
            
            // Dosya yükleme
            if (isset($_FILES['design_files']) && $_FILES['design_files']['error'][0] != 4) {
                $files = $_FILES['design_files'];
                $file_count = count($files['name']);
                
                for ($i = 0; $i < $file_count; $i++) {
                    if ($files['error'][$i] == 0) {
                        $file = [
                            'name' => $files['name'][$i],
                            'type' => $files['type'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'error' => $files['error'][$i],
                            'size' => $files['size'][$i]
                        ];
                        
                        $upload_result = upload_file($file, 'designs');
                        
                        if ($upload_result['success']) {
                            // Dosya bilgilerini kaydet
                            $stmt = $conn->prepare("INSERT INTO offer_attachments (offer_id, filename, created_at) VALUES (?, ?, NOW())");
                            $stmt->bind_param("is", $offer_id, $upload_result['filename']);
                            $stmt->execute();
                        }
                    }
                }
            }
            
            // Alıcıya bildirim gönder
            create_notification(
                $request['user_id'],
                "Talebinize yeni bir teklif geldi! Talep Kodu: " . $request['reference_code'],
                'new_offer',
                SITE_URL . "/buyer/view-offers.php?id=" . $request_id
            );
            
            // Başarı mesajı
            $_SESSION['flash_message'] = "Teklifiniz başarıyla gönderildi.";
            $_SESSION['flash_type'] = "success";
            
            // Teklif listesine yönlendir
            header("Location: " . SITE_URL . "/supplier/my-offers.php");
            exit;
        } else {
            $errors[] = "Teklif gönderilirken bir hata oluştu: " . $conn->error;
        }
    }
}

// Kategorileri getir
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
        <h2>Teklif Gönder</h2>
        <p class="lead">Talep: <?php echo $request['title']; ?> (<?php echo $request['reference_code']; ?>)</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo SITE_URL; ?>/supplier/request-list.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Talep Listesine Dön
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
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Teklif Bilgileri</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $request_id; ?>" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="price" class="form-label">Teklif Fiyatı (TL) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="price" name="price" min="0.01" step="0.01" required>
                        <small class="text-muted">Alıcıdan talep edeceğiniz toplam fiyatı girin.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Teklif Açıklaması <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="5" required placeholder="Ürün özellikleri, kullanılacak malzeme, üretim süreci vb. detayları belirtin."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="estimated_days" class="form-label">Tahmini Üretim Süresi (Gün) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="estimated_days" name="estimated_days" min="1" required>
                        <small class="text-muted">Teklif kabul edildikten sonra ürünü teslim edebileceğiniz gün sayısı.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="design_files" class="form-label">Tasarım/Teknik Dosyalar</label>
                        <input type="file" class="form-control" id="design_files" name="design_files[]" multiple>
                        <small class="text-muted">Teknik çizimler, 3D modeller, ön tasarımlar vb. (Maksimum 5 dosya, her biri en fazla 5MB)</small>
                    </div>
                    
                    <div class="alert alert-warning">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="alert-heading">Dikkat!</h5>
                                <p>Teklifinizi gönderdikten sonra düzenleyemezsiniz. Lütfen tüm bilgileri doğru girdiğinizden emin olun.</p>
                                <p class="mb-0"><strong>Son Teslim Tarihi:</strong> <?php echo date('d.m.Y', strtotime($request['deadline'])); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Teklifi Gönder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Talep Bilgileri -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Talep Bilgileri</h5>
            </div>
            <div class="card-body">
                <p><strong>Başlık:</strong> <?php echo $request['title']; ?></p>
                <p><strong>Alıcı:</strong> <?php echo $request['name'] . ' ' . $request['surname']; ?></p>
                <p><strong>Kategori:</strong> <?php echo isset($categories[$request['category']]) ? $categories[$request['category']] : $request['category']; ?></p>
                <p><strong>Adet:</strong> <?php echo $request['quantity']; ?></p>
                <p><strong>Bütçe:</strong> <?php echo format_money($request['budget']); ?></p>
                <p><strong>Son Teslim:</strong> <?php echo date('d.m.Y', strtotime($request['deadline'])); ?></p>
                <p><strong>Oluşturma Tarihi:</strong> <?php echo date('d.m.Y', strtotime($request['created_at'])); ?></p>
                
                <div class="mt-3">
                    <h6>Detaylı Açıklama:</h6>
                    <div class="border p-3 bg-light">
                        <?php echo nl2br($request['description']); ?>
                    </div>
                </div>
                
                <?php if (!empty($attachments)): ?>
                    <div class="mt-3">
                        <h6>Ek Dosyalar:</h6>
                        <ul class="list-group">
                            <?php foreach ($attachments as $attachment): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?php echo $attachment['filename']; ?></span>
                                    <a href="<?php echo SITE_URL; ?>/uploads/requests/<?php echo $attachment['filename']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../footer.php';
?>