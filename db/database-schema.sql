-- Veritabanı oluşturma
CREATE DATABASE IF NOT EXISTS sponsorplatform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sponsorplatform;

-- Kullanıcılar tablosu
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    surname VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('buyer', 'supplier', 'sponsor', 'admin') NOT NULL,
    phone VARCHAR(20) NOT NULL,
    company_name VARCHAR(255) DEFAULT NULL,
    company_address TEXT DEFAULT NULL,
    tax_number VARCHAR(50) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    categories JSON DEFAULT NULL, -- Tedarikçilerin hizmet verdiği kategoriler
    rating DECIMAL(2,1) DEFAULT 0, -- 0.0 - 5.0 arası puanlama
    remember_token VARCHAR(100) DEFAULT NULL,
    token_expires DATETIME DEFAULT NULL,
    status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL
);

-- Talepler tablosu
CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reference_code VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    budget DECIMAL(10,2) NOT NULL,
    deadline DATE NOT NULL,
    allow_sponsor TINYINT(1) NOT NULL DEFAULT 1,
    sponsor_id INT DEFAULT NULL,
    selected_offer_id INT DEFAULT NULL,
    selected_date DATETIME DEFAULT NULL,
    status ENUM('pending', 'approved', 'in_production', 'completed', 'cancelled', 'disputed') DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sponsor_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Talep ekleri tablosu
CREATE TABLE IF NOT EXISTS request_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE
);

-- Teklifler tablosu
CREATE TABLE IF NOT EXISTS offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    supplier_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT NOT NULL,
    estimated_days INT NOT NULL,
    escrow_id INT DEFAULT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Teklif ekleri tablosu
CREATE TABLE IF NOT EXISTS offer_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    offer_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE
);

-- Sponsorluklar tablosu
CREATE TABLE IF NOT EXISTS sponsorships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    sponsor_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    is_partial TINYINT(1) NOT NULL DEFAULT 0,
    ad_requirements TEXT NOT NULL,
    transaction_id VARCHAR(50) DEFAULT NULL,
    payment_method VARCHAR(50) DEFAULT NULL,
    status ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'pending',
    paid_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (sponsor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Escrow ödemeleri tablosu
CREATE TABLE IF NOT EXISTS escrow_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    buyer_id INT NOT NULL,
    supplier_id INT DEFAULT NULL,
    amount DECIMAL(10,2) NOT NULL,
    transaction_id VARCHAR(50) DEFAULT NULL,
    payment_method VARCHAR(50) DEFAULT NULL,
    status ENUM('pending', 'paid', 'released', 'refunded', 'disputed') DEFAULT 'pending',
    paid_at DATETIME DEFAULT NULL,
    released_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Bildirimler tablosu
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Mesajlar tablosu
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Anlaşmazlıklar tablosu
CREATE TABLE IF NOT EXISTS disputes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    escrow_id INT NOT NULL,
    reporter_id INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('open', 'investigating', 'resolved', 'closed') DEFAULT 'open',
    resolution TEXT DEFAULT NULL,
    resolved_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    resolved_at DATETIME DEFAULT NULL,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (escrow_id) REFERENCES escrow_payments(id) ON DELETE CASCADE,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Değerlendirmeler tablosu
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewed_id INT NOT NULL,
    rating DECIMAL(2,1) NOT NULL, -- 0.0 - 5.0 arası puanlama
    comment TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Platform komisyonları tablosu
CREATE TABLE IF NOT EXISTS commissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    offer_id INT DEFAULT NULL,
    sponsorship_id INT DEFAULT NULL,
    supplier_commission DECIMAL(10,2) DEFAULT 0,
    sponsor_commission DECIMAL(10,2) DEFAULT 0,
    total_commission DECIMAL(10,2) NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE SET NULL,
    FOREIGN KEY (sponsorship_id) REFERENCES sponsorships(id) ON DELETE SET NULL
);

-- İşlem geçmişi tablosu
CREATE TABLE IF NOT EXISTS transaction_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    related_id INT DEFAULT NULL,
    related_type VARCHAR(50) DEFAULT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Örnek admin kullanıcısı
INSERT INTO users (
    name, surname, email, password, role, phone, 
    status, created_at
) VALUES (
    'Admin', 'User', 'admin@sponsorplatform.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password
    'admin', '+905555555555', 
    'active', NOW()
);

-- İndexler
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_requests_status ON requests(status);
CREATE INDEX idx_requests_category ON requests(category);
CREATE INDEX idx_requests_allow_sponsor ON requests(allow_sponsor);
CREATE INDEX idx_offers_status ON offers(status);
CREATE INDEX idx_sponsorships_status ON sponsorships(status);
CREATE INDEX idx_escrow_payments_status ON escrow_payments(status);
CREATE INDEX idx_notifications_user_is_read ON notifications(user_id, is_read);
CREATE INDEX idx_disputes_status ON disputes(status);
CREATE INDEX idx_reviews_reviewed_id ON reviews(reviewed_id);

-- Triggerlar

-- Yeni teklif eklendiğinde bildirim gönder
DELIMITER //
CREATE TRIGGER after_offer_insert
AFTER INSERT ON offers
FOR EACH ROW
BEGIN
    DECLARE request_user_id INT;
    DECLARE request_code VARCHAR(20);
    
    -- Talebin sahibini ve kodunu al
    SELECT user_id, reference_code INTO request_user_id, request_code 
    FROM requests 
    WHERE id = NEW.request_id;
    
    -- Bildirim ekle
    INSERT INTO notifications (
        user_id, message, type, link, is_read, created_at
    ) VALUES (
        request_user_id, 
        CONCAT('Talebinize yeni bir teklif geldi! Talep Kodu: ', request_code),
        'new_offer',
        CONCAT('/buyer/view-offers.php?id=', NEW.request_id),
        0,
        NOW()
    );
END //
DELIMITER ;

-- Teklif kabul edildiğinde bildirim gönder
DELIMITER //
CREATE TRIGGER after_offer_accept
AFTER UPDATE ON offers
FOR EACH ROW
BEGIN
    IF OLD.status = 'pending' AND NEW.status = 'accepted' THEN
        -- Tedarikçiye bildirim gönder
        INSERT INTO notifications (
            user_id, message, type, link, is_read, created_at
        ) VALUES (
            NEW.supplier_id, 
            CONCAT('Teklifiniz kabul edildi! Sipariş detaylarını kontrol edin.'),
            'offer_accepted',
            CONCAT('/supplier/my-offers.php'),
            0,
            NOW()
        );
    END IF;
END //
DELIMITER ;