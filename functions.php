<?php
// Güvenlik fonksiyonları
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = $conn->real_escape_string($data);
    return $data;
}

// Kullanıcı oturum kontrolü
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Rol kontrolü
function has_role($role) {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] == $role;
}

// Kullanıcı bilgilerini getir
function get_user_by_id($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return false;
}

// Talep bilgilerini getir
function get_request_by_id($request_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return false;
}

// Teklif bilgilerini getir
function get_offers_by_request($request_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT o.*, u.company_name FROM offers o 
                           JOIN users u ON o.supplier_id = u.id 
                           WHERE o.request_id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $offers = [];
    while ($row = $result->fetch_assoc()) {
        $offers[] = $row;
    }
    
    return $offers;
}

// Para formatı
function format_money($amount) {
    return number_format($amount, 2, ',', '.') . ' TL';
}

// Tarih formatı
function format_date($date) {
    return date('d.m.Y H:i', strtotime($date));
}

// Durum metni
function get_status_text($status) {
    $statuses = [
        'pending' => 'Beklemede',
        'approved' => 'Onaylandı',
        'in_production' => 'Üretimde',
        'completed' => 'Tamamlandı',
        'cancelled' => 'İptal Edildi',
        'disputed' => 'Anlaşmazlık'
    ];
    
    return isset($statuses[$status]) ? $statuses[$status] : 'Bilinmiyor';
}

// Dosya yükleme
function upload_file($file, $directory) {
    $target_dir = "uploads/" . $directory . "/";
    
    // Klasör yoksa oluştur
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Dosya boyutu kontrolü (5MB)
    if ($file["size"] > 5000000) {
        return [
            'success' => false,
            'message' => 'Dosya boyutu çok büyük (max: 5MB)'
        ];
    }
    
    // İzin verilen dosya türleri
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
    if (!in_array($file_extension, $allowed_extensions)) {
        return [
            'success' => false,
            'message' => 'Yalnızca JPG, JPEG, PNG, PDF, DOC, DOCX dosya türlerine izin verilmektedir'
        ];
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return [
            'success' => true,
            'filename' => $new_filename,
            'path' => $target_file
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Dosya yüklenirken bir hata oluştu'
        ];
    }
}

// Bildirim oluştur
function create_notification($user_id, $message, $type, $link = '') {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, link, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("isss", $user_id, $message, $type, $link);
    return $stmt->execute();
}

// Escrow hesabı oluştur
function create_escrow_payment($amount, $buyer_id, $request_id) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO escrow_payments (amount, buyer_id, request_id, status, created_at) 
                           VALUES (?, ?, ?, 'pending', NOW())");
    $stmt->bind_param("dii", $amount, $buyer_id, $request_id);
    return $stmt->execute() ? $conn->insert_id : false;
}

// Escrow ödemesini serbest bırak
function release_escrow_payment($escrow_id, $supplier_id) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE escrow_payments SET status = 'released', released_at = NOW(), supplier_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $supplier_id, $escrow_id);
    return $stmt->execute();
}

// Komisyon hesapla
function calculate_commission($amount, $rate) {
    return $amount * $rate;
}
?>