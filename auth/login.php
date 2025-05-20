<?php
$page_title = "Giriş Yap";
require_once '../header.php';

// Kullanıcı zaten giriş yapmışsa ana sayfaya yönlendir
if (is_logged_in()) {
    header("Location: " . SITE_URL);
    exit;
}

// Giriş işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validasyon
    $errors = [];
    
    if (empty($email) || empty($password)) {
        $errors[] = "Lütfen e-posta ve şifre alanlarını doldurun.";
    }
    
    // Kullanıcı kontrolü
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Şifre kontrolü
            if (password_verify($password, $user['password'])) {
                // Oturum başlat
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                
                // Beni hatırla
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expires = time() + 60 * 60 * 24 * 30; // 30 gün
                    
                    setcookie('remember_token', $token, $expires, '/');
                    
                    // Token'ı veritabanına kaydet
                    $stmt = $conn->prepare("UPDATE users SET remember_token = ?, token_expires = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $token, date('Y-m-d H:i:s', $expires), $user['id']);
                    $stmt->execute();
                }
                
                // Giriş başarılı mesajı
                $_SESSION['flash_message'] = "Giriş başarılı! Hoş geldiniz, " . $user['name'] . " " . $user['surname'] . ".";
                $_SESSION['flash_type'] = "success";
                
                // Rol bazlı yönlendirme
                switch ($user['role']) {
                    case ROLE_BUYER:
                        header("Location: " . SITE_URL . "/buyer/dashboard.php");
                        break;
                    case ROLE_SUPPLIER:
                        header("Location: " . SITE_URL . "/supplier/dashboard.php");
                        break;
                    case ROLE_SPONSOR:
                        header("Location: " . SITE_URL . "/sponsor/dashboard.php");
                        break;
                    default:
                        header("Location: " . SITE_URL);
                }
                exit;
            } else {
                $errors[] = "Hatalı şifre.";
            }
        } else {
            $errors[] = "Bu e-posta adresi ile kayıtlı kullanıcı bulunamadı.";
        }
    }
}
?>

<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title mb-0">Giriş Yap</h3>
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
                
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">E-posta</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Beni Hatırla</label>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Giriş Yap</button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <p><a href="reset-password.php">Şifremi Unuttum</a></p>
                    <p>Hesabınız yok mu? <a href="register.php">Kayıt Ol</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../footer.php';
?>