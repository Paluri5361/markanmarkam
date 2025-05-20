<?php
// Hata görüntüleme
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Gerekli dosyalarý yükle
require_once '../config.php';
require_once '../functions.php';

// Session baþlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Form kontrol
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit;
}

// Form verilerini al
$name = isset($_POST['name']) ? clean_input($_POST['name']) : '';
$surname = isset($_POST['surname']) ? clean_input($_POST['surname']) : '';
$email = isset($_POST['email']) ? clean_input($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
$role = isset($_POST['role']) ? clean_input($_POST['role']) : '';
$phone = isset($_POST['phone']) ? clean_input($_POST['phone']) : '';

// Validasyon
$errors = [];

// Zorunlu alanlar
if (empty($name) || empty($surname) || empty($email) || empty($password) || empty($role) || empty($phone)) {
    $errors[] = "Lütfen tüm zorunlu alanlarý doldurun.";
}

// Email validasyonu
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Geçerli bir e-posta adresi girin.";
}

// Email benzersizlik kontrolü
try {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Bu e-posta adresi zaten kullanýlmaktadýr.";
    }
} catch (Exception $e) {
    $errors[] = "Veritabaný hatasý: " . $e->getMessage();
}

// Þifre kontrolü
if (strlen($password) < 6) {
    $errors[] = "Þifre en az 6 karakter olmalýdýr.";
}

if ($password !== $confirm_password) {
    $errors[] = "Þifreler eþleþmiyor.";
}

// Hata yoksa kayýt yap
if (empty($errors)) {
    try {
        // Þifre hashleme
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Kullanýcý ekleme
        $stmt = $conn->prepare("INSERT INTO users (name, surname, email, password, role, phone, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");
        $stmt->bind_param("ssssss", $name, $surname, $email, $hashed_password, $role, $phone);
        
        if ($stmt->execute()) {
            // Baþarý mesajý
            $_SESSION['flash_message'] = "Kayýt iþlemi baþarýyla tamamlandý. Lütfen giriþ yapýn.";
            $_SESSION['flash_type'] = "success";
            
            // Giriþ sayfasýna yönlendir
            header("Location: login.php");
            exit;
        } else {
            $errors[] = "Kayýt iþlemi sýrasýnda bir hata oluþtu: " . $conn->error;
        }
    } catch (Exception $e) {
        $errors[] = "Sistem hatasý: " . $e->getMessage();
    }
}

// Hata varsa register.php'ye geri dön
$_SESSION['register_errors'] = $errors;
$_SESSION['register_data'] = $_POST;
header("Location: register.php");
exit;
?>