<?php
$page_title = "Kullanıcı Düzenle";
require_once '../header.php';

// Yalnızca admin rolüne sahip kullanıcılar erişebilir
if (!has_role(ROLE_ADMIN)) {
    $_SESSION['flash_message'] = "Bu sayfaya erişim izniniz yok.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL);
    exit;
}

// ID kontrolü
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = "Geçersiz kullanıcı ID.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL . "/admin/users.php");
    exit;
}

$user_id = (int)$_GET['id'];

// Kullanıcı bilgilerini getir
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['flash_message'] = "Kullanıcı bulunamadı.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL . "/admin/users.php");
    exit;
}

$user = $result->fetch_assoc();

// Ana admin (ID=1) sadece kendi tarafından düzenlenebilir
if ($user['id'] == 1 && $_SESSION['user_id'] != 1) {
    $_SESSION['flash_message'] = "Ana admin hesabını düzenleme yetkiniz yok.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL . "/admin/users.php");
    exit;
}

// Profil güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = clean_input($_POST['name']);
    $surname = clean_input($_POST['surname']);
    $phone = clean_input($_POST['phone']);
    $role = clean_input($_POST['role']);
    $status = clean_input($_POST['status']);
    $new_password = $_POST['new_password'];
    
    // Şirket bilgileri
    $company_name = isset($_POST['company_name']) ? clean_input($_POST['company_name']) : '';
    $company_address = isset($_POST['company_address']) ? clean_input($_POST['company_address']) : '';
    $tax_number = isset($_POST['tax_number']) ? clean_input($_POST['tax_number']) : '';
    
    // Adres bilgisi
    $address = isset($_POST['address']) ? clean_input($_POST['address']) : '';
    
    // Kategoriler
    $categories = isset($_POST['categories']) ? $_POST['categories'] : [];
    $categories_json = !empty($categories) ? json_encode($categories) : $user['categories'];
    
    // Validasyon
    $errors = [];
    
    // Temel validasyon
    if (empty($name) || empty($surname) || empty($phone)) {
        $errors[] = "Lütfen tüm zorunlu alanları doldurun.";
    }
    
    // Ana admin rolünü değiştirmeye çalışıyorsa engelle
    if ($user['id'] == 1 && $role != 'admin') {
        $errors[] = "Ana admin hesabının rolü değiştirilemez.";
    }
    
    // Hata yoksa profili güncelle
    if (empty($errors)) {
        // Temel SQL sorgusu
        $sql = "UPDATE users SET 
                name = ?, 
                surname = ?, 
                phone = ?,
                role = ?,
                status = ?,
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
            $role,
            $status,
            $company_name,
            $company_address,
            $tax_number,
            $address,
            $categories_json
        ];
        $types = "ssssssssss";
        
        // Şifre değişikliği varsa
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql .= ", password = ?";
            $params[] = $hashed_password;
            $types .= "s";
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $user_id;
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
                    $stmt->bind_param("si", $profile_image, $user_id);
                    $stmt->execute();
                    
                    // Eski profil resmini sil
                    if (!empty($user['profile_image']) && file_exists('../uploads/profiles/' . $user['profile_image'])) {
                        unlink('../uploads/profiles/' . $user['profile_image']);
                    }
                }
            }
            
            // İşlem kaydı
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $stmt = $conn->prepare("
                INSERT INTO transaction_logs (user_id, related_id, related_type, action, details, ip_address, created_at) 
                VALUES (?, ?, 'user', 'profile_update', ?, ?, NOW())
            ");
            $details = "Kullanıcı profili güncellendi: " . $name . " " . $surname;
            $stmt->bind_param("iiss", $_SESSION['user_id'], $user_id, $details, $ip_address);
            $stmt->execute();
            
            // Başarı mesajı
            $_SESSION['flash_message'] = "Kullanıcı profili başarıyla güncellendi.";
            $_SESSION['flash_type'] = "success";
            
            // Sayfayı yenile
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $user_id);
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
$user_categories = !empty($user['categories'])
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Kullanıcı Düzenle</h2>
        <p><?php echo $user['name'] . ' ' . $user['surname']; ?> (#<?php echo $user['id']; ?>)</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo SITE_URL; ?>/admin/users.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Kullanıcılara Dön
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
                <p><strong>Telefon:</strong> <?php echo $user['phone']; ?></p>
                <p><strong>Üyelik Tarihi:</strong> <?php echo date('d.m.Y', strtotime($user['created_at'])); ?></p>
                <p>
                    <strong>Durum:</strong> 
                    <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : ($user['status'] == 'inactive' ? 'secondary' : 'danger'); ?>">
                        <?php echo $user['status'] == 'active' ? 'Aktif' : ($user['status'] == 'inactive' ? 'Pasif' : 'Engelli'); ?>
                    </span>
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Kullanıcı Bilgilerini Düzenle</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $user_id; ?>" method="POST" enctype="multipart/form-data">
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
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Kullanıcı Rolü <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role" <?php echo $user['id'] == 1 ? 'disabled' : ''; ?>>
                                <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="buyer" <?php echo $user['role'] == 'buyer' ? 'selected' : ''; ?>>Alıcı</option>
                                <option value="supplier" <?php echo $user['role'] == 'supplier' ? 'selected' : ''; ?>>Tedarikçi</option>
                                <option value="sponsor" <?php echo $user['role'] == 'sponsor' ? 'selected' : ''; ?>>Sponsor</option>
                            </select>
                            <?php if ($user['id'] == 1): ?>
                                <small class="text-muted">Ana admin rolü değiştirilemez.</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Hesap Durumu <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" <?php echo $user['id'] == 1 ? 'disabled' : ''; ?>>
                                <option value="active" <?php echo $user['status'] == 'active' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="inactive" <?php echo $user['status'] == 'inactive' ? 'selected' : ''; ?>>Pasif</option>
                                <option value="blocked" <?php echo $user['status'] == 'blocked' ? 'selected' : ''; ?>>Engelli</option>
                            </select>
                            <?php if ($user['id'] == 1): ?>
                                <small class="text-muted">Ana admin hesabı her zaman aktif olmalıdır.</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="profile_image" class="form-label">Profil Resmi</label>
                        <input type="file" class="form-control" id="profile_image" name="profile_image">
                        <small class="text-muted">Maksimum dosya boyutu: 5MB (JPG, JPEG, PNG)</small>
                    </div>
                    
                    <!-- Adres Bilgileri -->
                    <h4 class="mt-4">Adres Bilgileri</h4>
                    <div class="mb-3">
                        <label for="address" class="form-label">Adres</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo $user['address']; ?></textarea>
                    </div>
                    
                    <!-- Şirket Bilgileri -->
                    <h4 class="mt-4">Şirket Bilgileri</h4>
                    <div class="mb-3">
                        <label for="company_name" class="form-label">Şirket Adı</label>
                        <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo $user['company_name']; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="tax_number" class="form-label">Vergi Numarası</label>
                        <input type="text" class="form-control" id="tax_number" name="tax_number" value="<?php echo $user['tax_number']; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="company_address" class="form-label">Şirket Adresi</label>
                        <textarea class="form-control" id="company_address" name="company_address" rows="3"><?php echo $user['company_address']; ?></textarea>
                    </div>
                    
                    <!-- Tedarikçi Kategorileri -->
                    <?php if ($user['role'] == 'supplier' || $_POST['role'] == 'supplier'): ?>
                        <h4 class="mt-4">Ürün Kategorileri</h4>
                        <p>Hizmet verdiği ürün kategorileri:</p>
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
                    <p>Şifreyi değiştirmek istemiyorsanız, bu alanı boş bırakın.</p>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Yeni Şifre</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                        <small class="text-muted">En az 6 karakter</small>
                    </div>
                    
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">Kullanıcı Bilgilerini Güncelle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Rol değişikliğinde kategorileri göster/gizle
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.getElementById('role');
        
        roleSelect.addEventListener('change', function() {
            // Eğer rol "supplier" seçildiyse kategorileri göster
            if (this.value === 'supplier') {
                document.location.reload();
            }
        });
    });
</script>

<?php
require_once '../footer.php';
?>