<?php
$page_title = "Kullanýcý Ekle";
require_once '../header.php';

// Yalnýzca admin rolüne sahip kullanýcýlar eriþebilir
if (!has_role(ROLE_ADMIN)) {
    $_SESSION['flash_message'] = "Bu sayfaya eriþim izniniz yok.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL);
    exit;
}

// Kullanýcý ekleme iþlemi
$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Form verilerini al
    $name = isset($_POST['name']) ? clean_input($_POST['name']) : '';
    $surname = isset($_POST['surname']) ? clean_input($_POST['surname']) : '';
    $email = isset($_POST['email']) ? clean_input($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $role = isset($_POST['role']) ? clean_input($_POST['role']) : '';
    $phone = isset($_POST['phone']) ? clean_input($_POST['phone']) : '';
    
    // Þirket bilgileri
    $company_name = isset($_POST['company_name']) ? clean_input($_POST['company_name']) : '';
    $company_address = isset($_POST['company_address']) ? clean_input($_POST['company_address']) : '';
    $tax_number = isset($_POST['tax_number']) ? clean_input($_POST['tax_number']) : '';
    
    // Adres bilgisi
    $address = isset($_POST['address']) ? clean_input($_POST['address']) : '';
    
    // Temel validasyon
    if (empty($name) || empty($surname) || empty($email) || empty($password) || empty($role) || empty($phone)) {
        $errors[] = "Lütfen tüm zorunlu alanlarý doldurun.";
    }
    
    // Email formatý kontrolü
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçerli bir e-posta adresi girin.";
    }
    
    // Email benzersizlik kontrolü
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Bu e-posta adresi zaten kullanýlmaktadýr.";
    }
    
    // Þifre kontrolü
    if (strlen($password) < 6) {
        $errors[] = "Þifre en az 6 karakter olmalýdýr.";
    }
    
    // Hata yoksa kullanýcýyý ekle
    if (empty($errors)) {
        // Þifre hashleme
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            // Kullanýcý ekleme
            $stmt = $conn->prepare("INSERT INTO users (name, surname, email, password, role, phone, company_name, company_address, tax_number, address, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
            $stmt->bind_param("ssssssssss", $name, $surname, $email, $hashed_password, $role, $phone, $company_name, $company_address, $tax_number, $address);
            
            if ($stmt->execute()) {
                $success = true;
                $_SESSION['flash_message'] = "Kullanýcý baþarýyla eklendi.";
                $_SESSION['flash_type'] = "success";
                
                // Kullanýcý listesine yönlendir
                header("Location: " . SITE_URL . "/admin/users.php");
                exit;
            } else {
                $errors[] = "Kullanýcý eklenirken bir hata oluþtu: " . $conn->error;
            }
        } catch (Exception $e) {
            $errors[] = "Ýþlem sýrasýnda bir hata oluþtu: " . $e->getMessage();
        }
    }
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Kullanýcý Ekle</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo SITE_URL; ?>/admin/users.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Kullanýcýlara Dön
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

<?php if ($success): ?>
    <div class="alert alert-success">
        <p>Kullanýcý baþarýyla eklendi.</p>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0">Kullanýcý Bilgileri</h5>
    </div>
    <div class="card-body">
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
            <h4>Kiþisel Bilgiler</h4>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Ad <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="surname" class="form-label">Soyad <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="surname" name="surname" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">E-posta <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Telefon <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control" id="phone" name="phone" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Þifre <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <small class="text-muted">En az 6 karakter</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="role" class="form-label">Kullanýcý Rolü <span class="text-danger">*</span></label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="">Rol Seçin</option>
                        <option value="admin">Admin</option>
                        <option value="buyer">Alýcý</option>
                        <option value="supplier">Tedarikçi</option>
                        <option value="sponsor">Sponsor</option>
                    </select>
                </div>
            </div>
            
            <!-- Adres Bilgileri -->
            <h4 class="mt-4">Adres Bilgileri</h4>
            <div class="mb-3">
                <label for="address" class="form-label">Adres</label>
                <textarea class="form-control" id="address" name="address" rows="3"></textarea>
            </div>
            
            <!-- Þirket Bilgileri -->
            <h4 class="mt-4">Þirket Bilgileri</h4>
            <div class="mb-3">
                <label for="company_name" class="form-label">Þirket Adý</label>
                <input type="text" class="form-control" id="company_name" name="company_name">
            </div>
            <div class="mb-3">
                <label for="tax_number" class="form-label">Vergi Numarasý</label>
                <input type="text" class="form-control" id="tax_number" name="tax_number">
            </div>
            <div class="mb-3">
                <label for="company_address" class="form-label">Þirket Adresi</label>
                <textarea class="form-control" id="company_address" name="company_address" rows="3"></textarea>
            </div>
            
            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg">Kullanýcý Ekle</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once '../footer.php';
?>