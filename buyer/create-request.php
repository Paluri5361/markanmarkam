<?php
$page_title = "Ürün Talep Formu";
require_once '../header.php';

// Yalnızca alıcı rolüne sahip kullanıcılar erişebilir
if (!has_role(ROLE_BUYER)) {
    $_SESSION['flash_message'] = "Bu sayfaya erişim izniniz yok.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL);
    exit;
}

// Talep oluşturma işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = clean_input($_POST['title']);
    $category = clean_input($_POST['category']);
    $description = clean_input($_POST['description']);
    $quantity = (int)$_POST['quantity'];
    $budget = (float)$_POST['budget'];
    $deadline = clean_input($_POST['deadline']);
    $allow_sponsor = isset($_POST['allow_sponsor']) ? 1 : 0;
    
    // Debug için deadline'ı kontrol edelim
    error_log("Deadline value: " . $deadline);
    
    // Validasyon
    $errors = [];
    
    if (empty($title) || empty($category) || empty($description) || empty($quantity) || empty($budget) || empty($deadline)) {
        $errors[] = "Lütfen tüm zorunlu alanları doldurun.";
    }
    
    if ($quantity <= 0) {
        $errors[] = "Adet sayısı sıfırdan büyük olmalıdır.";
    }
    
    if ($budget <= 0) {
        $errors[] = "Bütçe sıfırdan büyük olmalıdır.";
    }
    
    // Tarih validasyonu - doğru formatta olduğundan emin ol
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline)) {
        $errors[] = "Geçersiz tarih formatı.";
    } else {
        $deadline_timestamp = strtotime($deadline);
        $today_timestamp = strtotime(date('Y-m-d'));
        
        if ($deadline_timestamp < $today_timestamp) {
            $errors[] = "Son teslim tarihi bugünden itibaren olmalıdır.";
        }
    }
    
    // Hata yoksa talep oluştur
    if (empty($errors)) {
        try {
            // Önce referans kodu oluştur
            $reference_code = 'RQ' . date('ymd') . rand(1000, 9999);
            
            // Tarih formatını açıkça kontrol et
            $formatted_deadline = date('Y-m-d', strtotime($deadline));
            
            // Talep ekle - reference_code'u da dahil et
            $stmt = $conn->prepare("INSERT INTO requests (user_id, reference_code, title, category, description, quantity, budget, deadline, allow_sponsor, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
            $stmt->bind_param("isssidssi", $_SESSION['user_id'], $reference_code, $title, $category, $description, $quantity, $budget, $formatted_deadline, $allow_sponsor);
            
            if ($stmt->execute()) {
                $request_id = $conn->insert_id;
                
                // Dosya yükleme
                if (isset($_FILES['attachments']) && $_FILES['attachments']['error'][0] != 4) {
                    $attachments = $_FILES['attachments'];
                    $attachment_count = count($attachments['name']);
                    
                    for ($i = 0; $i < $attachment_count; $i++) {
                        if ($attachments['error'][$i] == 0) {
                            $file = [
                                'name' => $attachments['name'][$i],
                                'type' => $attachments['type'][$i],
                                'tmp_name' => $attachments['tmp_name'][$i],
                                'error' => $attachments['error'][$i],
                                'size' => $attachments['size'][$i]
                            ];
                            
                            $upload_result = upload_file($file, 'requests');
                            
                            if ($upload_result['success']) {
                                // Dosya bilgilerini kaydet
                                $stmt = $conn->prepare("INSERT INTO request_attachments (request_id, filename, created_at) VALUES (?, ?, NOW())");
                                $stmt->bind_param("is", $request_id, $upload_result['filename']);
                                $stmt->execute();
                            }
                        }
                    }
                }
                
                // Başarı mesajı
                $_SESSION['flash_message'] = "Ürün talebi başarıyla oluşturuldu. Referans Kodu: " . $reference_code;
                $_SESSION['flash_type'] = "success";
                
                // Taleplerim sayfasına yönlendir
                header("Location: " . SITE_URL . "/buyer/my-requests.php");
                exit;
            } else {
                $errors[] = "Talep oluşturulurken bir hata oluştu: " . $conn->error;
            }
        } catch (Exception $e) {
            $errors[] = "Hata: " . $e->getMessage();
        }
    }
}
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title mb-0">Ürün Talep Formu</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="title" class="form-label">Ürün Başlığı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="Örn: 3D Yazıcı için Parça Üretimi" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category" class="form-label">Kategori <span class="text-danger">*</span></label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="">Kategori Seçin</option>
                            <option value="elektronik" <?php echo (isset($_POST['category']) && $_POST['category'] == 'elektronik') ? 'selected' : ''; ?>>Elektronik</option>
                            <option value="tekstil" <?php echo (isset($_POST['category']) && $_POST['category'] == 'tekstil') ? 'selected' : ''; ?>>Tekstil</option>
                            <option value="mobilya" <?php echo (isset($_POST['category']) && $_POST['category'] == 'mobilya') ? 'selected' : ''; ?>>Mobilya</option>
                            <option value="gida" <?php echo (isset($_POST['category']) && $_POST['category'] == 'gida') ? 'selected' : ''; ?>>Gıda</option>
                            <option value="otomotiv" <?php echo (isset($_POST['category']) && $_POST['category'] == 'otomotiv') ? 'selected' : ''; ?>>Otomotiv</option>
                            <option value="kozmetik" <?php echo (isset($_POST['category']) && $_POST['category'] == 'kozmetik') ? 'selected' : ''; ?>>Kozmetik</option>
                            <option value="diger" <?php echo (isset($_POST['category']) && $_POST['category'] == 'diger') ? 'selected' : ''; ?>>Diğer</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Ürün Detayları <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="5" placeholder="Ürün özellikleri, beklentiler, özel şartlar vb." required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="quantity" class="form-label">Adet <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="<?php echo isset($_POST['quantity']) ? $_POST['quantity'] : '1'; ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="budget" class="form-label">Bütçe (TL) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="budget" name="budget" min="0" step="0.01" value="<?php echo isset($_POST['budget']) ? $_POST['budget'] : ''; ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="deadline" class="form-label">Son Teslim Tarihi <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="deadline" name="deadline" value="<?php echo isset($_POST['deadline']) ? $_POST['deadline'] : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="attachments" class="form-label">Ek Dosyalar</label>
                        <input type="file" class="form-control" id="attachments" name="attachments[]" multiple>
                        <small class="text-muted">Teknik çizim, şartname, referans görseller vb. Maksimum 5 dosya, her biri en fazla 5MB (JPG, JPEG, PNG, PDF, DOC, DOCX)</small>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="allow_sponsor" name="allow_sponsor" <?php echo (isset($_POST['allow_sponsor']) || !isset($_POST['title'])) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="allow_sponsor">Sponsor desteğine açık olsun</label>
                        <div class="form-text">Sponsorlar, ürünü tamamen veya kısmen finanse edebilirler. Bu seçenek işaretli değilse, sponsorlar talebi göremez.</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Talep Oluştur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Bugünün tarihini minimum değer olarak ayarla
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('deadline').setAttribute('min', today);
    });
</script>

<?php
require_once '../footer.php';
?>