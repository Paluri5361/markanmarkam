<?php
// Önce konfigürasyon dosyasını yükle
require_once '../config.php';
require_once '../functions.php';

// Kullanıcı girişi yoksa ana sayfaya yönlendir
if (!is_logged_in()) {
    header("Location: " . SITE_URL);
    exit;
}

// Remember token'ı sil
if (isset($_COOKIE['remember_token'])) {
    // Veritabanındaki token'ı temizle
    $stmt = $conn->prepare("UPDATE users SET remember_token = NULL, token_expires = NULL WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    
    // Cookie'yi sil
    setcookie('remember_token', '', time() - 3600, '/');
}

// Oturumu sonlandır
session_unset();
session_destroy();

// Yeni oturum başlat ve mesaj göster
session_start();
$_SESSION['flash_message'] = "Başarıyla çıkış yapıldı.";
$_SESSION['flash_type'] = "success";

// Ana sayfaya yönlendir
header("Location: " . SITE_URL);
exit;
?>