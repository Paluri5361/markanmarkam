<?php
$page_title = "Profil Düzenle";
require_once '../header.php';

// Kullanıcı girişi kontrolü
if (!is_logged_in()) {
    $_SESSION['flash_message'] = "Bu sayfaya erişmek için giriş yapmalısınız.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL . "/auth/login.php");
    exit;
}

// Kullanıcı bilgilerini getir
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Profil güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = clean_input($_POST['name']);
    $surname = clean_input($_POST['surname']);
    $phone = clean_input($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Şirket bilgileri (tedarikçi ve sponsor için)
    $company_name = isset($_POST['company_name']) ? clean_input($_POST['company_name']) : '';
    $company_address = isset($_POST['company_address']) ? clean_input($_POST['company_address']) : '';
    $tax_number = isset($_POST['tax_number']) ? clean_input($_POST['tax_number']) : '';
    
    // Alıcı bilgileri
    $address = isset($_POST['address']) ? clean_input($_POST['address']) : '';
    
    // Tedarikçi kategorileri
    $categories = isset($_POST['categories']) ? $_POST['categories'] : [];
    $categories_json = !empty($categories) ? json_encode($categories) : $user['categories'];
    
    // Validasyon
    $errors = [];
    
    // Temel validasyon
    if (empty($name) || empty($surname) || empty($phone)) {
        $errors[] = "Lütfen tüm zorunlu alanları doldurun.";
    }
    
    // Rol bazlı validasyon
    if ($user['role'] == ROLE_SUPPLIER || $user['role'] == ROLE_SPONSOR) {
        if (empty($company_name) || empty($company_address) || empty($tax_number)) {
            $errors[] = "Şirket bilgilerini eksiksiz doldurun.";
        }
        
        if ($user['role'] == ROLE_SUPPLIER && empty($categories) && empty($user['categories'])) {
            $errors[] = "En az bir ürün kategorisi seçin.";
        }
    } elseif ($user['role'] == ROLE_BUYER && empty($address)) {
        $errors[] = "Adres bilgisini doldurun.";
    }
    
    // Şifre değişikliği kontrolü
    if (!empty($current_password)) {
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Mevcut şifre hatalı.";
        }
        
        if (empty($new_password) || strlen($new_password) < 6) {
            $errors[] = "Yeni şifre en az 6 karakter olmalıdır.";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "Yeni şifreler eşleşmiyor.";
        }
    }
    
    // Hata yoksa profili güncelle
    if (empty($errors)) {
        // Temel SQL sorgusu
        $sql = "UPDATE users SET 
                name = ?, 
                surname = ?, 
                phone = ?, 
                company_name = ?, 
                company_address = ?, 
                tax_number = ?, 
                address = ?, 
                categories = ?,
                updated_at = NOW()";
        
        $params = [
            $name,
            $surname,
            $phone,
            $company_name,
            $company_address,
            $tax_number,
            $address,
            $categories_json
        ];
        $types = "ssssssss";
        
        // Şifre değişikliği varsa
        if (!empty($current_password) && !empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql .= ", password = ?";
            $params[] = $hashed_password;
            $types .= "s";
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $_SESSION['user_id'];
        $types .= "i";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            // Profil resmi yükleme
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                $upload_result = upload_file($_FILES['profile_image'], 'profiles');
                
                if ($upload_result['success']) {
                    $profile_image = $upload_result['filename'];
                    
                    $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                    $stmt->bind_param("si", $profile_image, $_SESSION['user_id']);
                    $stmt->execute();
                    
                    // Eski profil resmini sil
                    if (!empty($user['profile_image']) && file_exists('../uploads/profiles/' . $user['profile_image'])) {
                        unlink('../uploads/profiles/' . $user['profile_image']);
                    }
                }
            }
            
            // Başarı mesajı
            $_SESSION['flash_message'] = "Profiliniz başarıyla güncellendi.";
            $_SESSION['flash_type'] = "success";
            
            // Sayfayı yenile
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $errors[] = "Profil güncellenirken bir hata oluştu: " . $conn->error;
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

// Kullanıcının mevcut kategorileri
$user_categories = json_decode($user['categories'], true) ?? [];
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Profil Düzenle</h2>
    </div>
    <div class="col-md-6 text-end">
        <?php if ($user['role'] == ROLE_BUYER): ?>
            <a href="<?php echo SITE_URL; ?>/buyer/dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Hesabıma Dön
            </a>
        <?php elseif ($user['role'] == ROLE_SUPPLIER): ?>
            <a href="<?php echo SITE_URL; ?>/supplier/dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Tedarikçi Paneline Dön
            </a>
        <?php elseif ($user['role'] == ROLE_SPONSOR): ?>
            <a href="<?php echo SITE_URL; ?>/sponsor/dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Sponsor Paneline Dön
            </a>
        <?php endif; ?>
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
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Profil Resmi</h5>
            </div>
            <div class="card-body text-center">
                <?php if (!empty($user['profile_image'])): ?>
                    <img src="<?php echo SITE_URL; ?>/uploads/profiles/<?php echo $user['profile_image']; ?>" alt="Profil" class="rounded-circle mb-3" width="150" height="150">
                <?php else: ?>
                    <div class="rounded-circle bg-secondary text-white d-flex justify-content-center align-items-center mx-auto mb-3" style="width: 150px; height: 150px;">
                        <i class="fas fa-user fa-4x"></i>
                    </div>
                <?php endif; ?>
                
                <p class="mb-0"><strong><?php echo $user['name'] . ' ' . $user['surname']; ?></strong></p>
                <p class="text-muted"><?php echo ucfirst($user['role']); ?></p>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Hesap Bilgileri</h5>
            </div>
            <div class="card-body">
                <p><strong>E-posta:</strong> <?php echo $user['email']; ?></p>
                <p><strong>Üyelik Tarihi:</strong> <?php echo date('d.m.Y', strtotime($user['created_at'])); ?></p>
                <p>
                    <strong>Durum:</strong> 
                    <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'secondary'; ?>">
                        <?php echo $user['status'] == 'active' ? 'Aktif' : 'Pasif'; ?>
                    </span>
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Profil Bilgilerini Düzenle</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
                    <h4>Kişisel Bilgiler</h4>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Ad <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo $user['name']; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="surname" class="form-label">Soyad <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="surname" name="surname" value="<?php echo $user['surname']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">E-posta</label>
                            <input type="email" class="form-control" id="email" value="<?php echo $user['email']; ?>" disabled>
                            <small class="text-muted">E-posta adresi değiştirilemez.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Telefon <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $user['phone']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="profile_image" class="form-label">Profil Resmi</label>
                        <input type="file" class="form-control" id="profile_image" name="profile_image">
                        <small class="text-muted">Maksimum dosya boyutu: 5MB (JPG, JPEG, PNG)</small>
                    </div>
                    
                    <!-- Alıcı Bilgileri -->
                    <?php if ($user['role'] == ROLE_BUYER): ?>
                        <h4 class="mt-4">Adres Bilgileri</h4>
                        <div class="mb-3">
                            <label for="address" class="form-label">Adres <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="address" name="address" rows="3" required><?php echo $user['address']; ?></textarea>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Tedarikçi ve Sponsor Bilgileri -->
                    <?php if ($user['role'] == ROLE_SUPPLIER || $user['role'] == ROLE_SPONSOR): ?>
                        <h4 class="mt-4">Şirket Bilgileri</h4>
                        <div class="mb-3">
                            <label for="company_name" class="form-label">Şirket Adı <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo $user['company_name']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="tax_number" class="form-label">Vergi Numarası <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tax_number" name="tax_number" value="<?php echo $user['tax_number']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="company_address" class="form-label">Şirket Adresi <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="company_address" name="company_address" rows="3" required><?php echo $user['company_address']; ?></textarea>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Tedarikçi Kategorileri -->
                    <?php if ($user['role'] == ROLE_SUPPLIER): ?>
                        <h4 class="mt-4">Ürün Kategorileri</h4>
                        <p>Hizmet verdiğiniz ürün kategorilerini seçin:</p>
                        <div class="row">
                            <?php foreach ($categories as $key => $value): ?>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categories[]" id="category_<?php echo $key; ?>" value="<?php echo $key; ?>" <?php echo in_array($key, $user_categories) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="category_<?php echo $key; ?>"><?php echo $value; ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <h4 class="mt-4">Şifre Değiştirme</h4>
                    <p>Şifrenizi değiştirmek istemiyorsanız, bu alanları boş bırakın.</p>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="current_password" class="form-label">Mevcut Şifre</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="new_password" class="form-label">Yeni Şifre</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                            <small class="text-muted">En az 6 karakter</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="confirm_password" class="form-label">Yeni Şifre Tekrar</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">Profili Güncelle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../footer.php';
?>