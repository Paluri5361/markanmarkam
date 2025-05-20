<?php
// Hata g�r�nt�leme
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Gerekli dosyalar� y�kle
require_once '../config.php';
require_once '../functions.php';

// Session ba�lat
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
    $errors[] = "L�tfen t�m zorunlu alanlar� doldurun.";
}

// Email validasyonu
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Ge�erli bir e-posta adresi girin.";
}

// Email benzersizlik kontrol�
try {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Bu e-posta adresi zaten kullan�lmaktad�r.";
    }
} catch (Exception $e) {
    $errors[] = "Veritaban� hatas�: " . $e->getMessage();
}

// �ifre kontrol�
if (strlen($password) < 6) {
    $errors[] = "�ifre en az 6 karakter olmal�d�r.";
}

if ($password !== $confirm_password) {
    $errors[] = "�ifreler e�le�miyor.";
}

// Hata yoksa kay�t yap
if (empty($errors)) {
    try {
        // �ifre hashleme
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Kullan�c� ekleme
        $stmt = $conn->prepare("INSERT INTO users (name, surname, email, password, role, phone, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");
        $stmt->bind_param("ssssss", $name, $surname, $email, $hashed_password, $role, $phone);
        
        if ($stmt->execute()) {
            // Ba�ar� mesaj�
            $_SESSION['flash_message'] = "Kay�t i�lemi ba�ar�yla tamamland�. L�tfen giri� yap�n.";
            $_SESSION['flash_type'] = "success";
            
            // Giri� sayfas�na y�nlendir
            header("Location: login.php");
            exit;
        } else {
            $errors[] = "Kay�t i�lemi s�ras�nda bir hata olu�tu: " . $conn->error;
        }
    } catch (Exception $e) {
        $errors[] = "Sistem hatas�: " . $e->getMessage();
    }
}

// Hata varsa register.php'ye geri d�n
$_SESSION['register_errors'] = $errors;
$_SESSION['register_data'] = $_POST;
header("Location: register.php");
exit;
?>