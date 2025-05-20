<?php
// Hata görüntüleme (geliştirme aşamasında kullanın)
ini_set('display_errors', 1);
error_reporting(E_ALL);

$page_title = "Kayıt Ol";
require_once '../header.php';

// Seçili rol
$selected_role = isset($_GET['role']) ? $_GET['role'] : '';

// Basit kayıt formunu gösterelim
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title mb-0">Kayıt Ol</h3>
            </div>
            <div class="card-body">
                <form action="register-process.php" method="POST">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h4>Rol Seçimi</h4>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="role" id="role_buyer" value="buyer" required>
                                <label class="form-check-label" for="role_buyer">Alıcı</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="role" id="role_supplier" value="supplier" required>
                                <label class="form-check-label" for="role_supplier">Tedarikçi</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="role" id="role_sponsor" value="sponsor" required>
                                <label class="form-check-label" for="role_sponsor">Sponsor</label>
                            </div>
                        </div>
                    </div>
                    
                    <h4>Kişisel Bilgiler</h4>
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
                            <label for="password" class="form-label">Şifre <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <small class="text-muted">En az 6 karakter</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Şifre Tekrar <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">Kayıt Ol</button>
                    </div>
                    
                    <div class="text-center mt-3">
                        <p>Zaten hesabınız var mı? <a href="login.php">Giriş Yap</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../footer.php';
?>