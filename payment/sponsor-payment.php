<?php
$page_title = "Sponsorluk Ödemesi";
require_once '../header.php';

// Kullanıcı girişi kontrolü
if (!is_logged_in()) {
    $_SESSION['flash_message'] = "Bu sayfaya erişmek için giriş yapmalısınız.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL . "/auth/login.php");
    exit;
}

// Sponsorluk ID kontrolü
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = "Geçersiz sponsorluk işlemi.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL . "/sponsor/sponsored-requests.php");
    exit;
}

$sponsorship_id = (int)$_GET['id'];

// Sponsorluk bilgilerini getir
$stmt = $conn->prepare("
    SELECT s.*, r.title, r.reference_code, r.user_id as buyer_id, u.name, u.surname
    FROM sponsorships s
    JOIN requests r ON s.request_id = r.id
    JOIN users u ON r.user_id = u.id
    WHERE s.id = ? AND s.sponsor_id = ? AND s.status = 'pending'
");
$stmt->bind_param("ii", $sponsorship_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['flash_message'] = "Sponsorluk bilgisi bulunamadı veya bu sponsorluğa erişim izniniz yok.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL . "/sponsor/sponsored-requests.php");
    exit;
}

$sponsorship = $result->fetch_assoc();

// Ödeme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = clean_input($_POST['payment_method']);
    $card_number = isset($_POST['card_number']) ? clean_input($_POST['card_number']) : '';
    $card_name = isset($_POST['card_name']) ? clean_input($_POST['card_name']) : '';
    $card_expiry = isset($_POST['card_expiry']) ? clean_input($_POST['card_expiry']) : '';
    $card_cvv = isset($_POST['card_cvv']) ? clean_input($_POST['card_cvv']) : '';
    
    // Validasyon
    $errors = [];
    
    if (empty($payment_method)) {
        $errors[] = "Lütfen bir ödeme yöntemi seçin.";
    }
    
    if ($payment_method == 'credit_card') {
        if (empty($card_number) || empty($card_name) || empty($card_expiry) || empty($card_cvv)) {
            $errors[] = "Lütfen tüm kart bilgilerini doldurun.";
        }
        
        // Kart numarası kontrolü (basit)
        if (!preg_match('/^[0-9]{16}$/', str_replace(' ', '', $card_number))) {
            $errors[] = "Geçerli bir kart numarası girin.";
        }
        
        // CVV kontrolü
        if (!preg_match('/^[0-9]{3,4}$/', $card_cvv)) {
            $errors[] = "Geçerli bir CVV kodu girin.";
        }
        
        // Tarih kontrolü
        if (!preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $card_expiry)) {
            $errors[] = "Geçerli bir son kullanma tarihi girin (AA/YY).";
        } else {
            list($month, $year) = explode('/', $card_expiry);
            $year = '20' . $year;
            $expiry_date = \DateTime::createFromFormat('Y-m-d', $year . '-' . $month . '-01');
            $now = new \DateTime();
            
            if ($expiry_date < $now) {
                $errors[] = "Kartınızın son kullanma tarihi geçmiş.";
            }
        }
    }
    
    // Hata yoksa ödemeyi işle
    if (empty($errors)) {
        // Ödeme işlemi simülasyonu (gerçek entegrasyon burada yapılır)
        $payment_success = true;
        $transaction_id = uniqid('TR');
        
        if ($payment_success) {
            // Sponsorluk bilgilerini güncelle
            $stmt = $conn->prepare("
                UPDATE sponsorships 
                SET status = 'active', 
                    transaction_id = ?,
                    payment_method = ?,
                    paid_at = NOW() 
                WHERE id = ?
            ");
            $stmt->bind_param("ssi", $transaction_id, $payment_method, $sponsorship_id);
            
            if ($stmt->execute()) {
                // İşlem başlat
                $conn->begin_transaction();
                
                try {
                    // Eğer tam sponsorluk ve kalan tutar tamamen karşılanıyorsa
                    if (!$sponsorship['is_partial']) {
                        // Talebin sponsor_id'sini güncelle
                        $stmt = $conn->prepare("UPDATE requests SET sponsor_id = ? WHERE id = ?");
                        $stmt->bind_param("ii", $_SESSION['user_id'], $sponsorship['request_id']);
                        $stmt->execute();
                    }
                    
                    // Alıcıya bildirim gönder
                    create_notification(
                        $sponsorship['buyer_id'],
                        "Talebinize sponsorluk ödemesi yapıldı! Talep Kodu: " . $sponsorship['reference_code'],
                        'sponsorship_paid',
                        SITE_URL . "/buyer/view-sponsorships.php?id=" . $sponsorship['request_id']
                    );
                    
                    // İşlemi tamamla
                    $conn->commit();
                    
                    // Başarı mesajı
                    $_SESSION['flash_message'] = "Sponsorluk ödemesi başarıyla tamamlandı.";
                    $_SESSION['flash_type'] = "success";
                    
                    // Sponsorluklarım sayfasına yönlendir
                    header("Location: " . SITE_URL . "/sponsor/sponsored-requests.php");
                    exit;
                } catch (Exception $e) {
                    // Hata durumunda işlemi geri al
                    $conn->rollback();
                    
                    $errors[] = "İşlem sırasında bir hata oluştu: " . $e->getMessage();
                }
            } else {
                $errors[] = "Ödeme kaydedilirken bir hata oluştu: " . $conn->error;
            }
        } else {
            $errors[] = "Ödeme işlemi başarısız oldu. Lütfen tekrar deneyin.";
        }
    }
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Sponsorluk Ödemesi</h2>
        <p class="lead">Talep: <?php echo $sponsorship['title']; ?> (<?php echo $sponsorship['reference_code']; ?>)</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo SITE_URL; ?>/sponsor/sponsored-requests.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Sponsorluklarıma Dön
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
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Ödeme Bilgileri</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $sponsorship_id; ?>" method="POST" id="payment-form">
                    <div class="alert alert-info">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="fas fa-info-circle fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="alert-heading">Sponsorluk Süreci</h5>
                                <p>Ödemenizi tamamladıktan sonra, sponsorluk talebiniz aktifleşecek ve talep sahibi ile paylaşılacaktır.</p>
                                <p class="mb-0">Reklam yerleştirme koşullarınız, ürün tamamlandığında talep sahibi tarafından uygulanacaktır.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Ödeme Yöntemi</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check payment-method-card">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment_credit_card" value="credit_card" checked>
                                    <label class="form-check-label d-flex align-items-center" for="payment_credit_card">
                                        <div class="me-auto">Kredi/Banka Kartı</div>
                                        <div>
                                            <i class="fab fa-cc-visa fa-2x me-1"></i>
                                            <i class="fab fa-cc-mastercard fa-2x me-1"></i>
                                            <i class="fab fa-cc-amex fa-2x"></i>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check payment-method-card">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment_bank_transfer" value="bank_transfer">
                                    <label class="form-check-label d-flex align-items-center" for="payment_bank_transfer">
                                        <div class="me-auto">Banka Havalesi</div>
                                        <div>
                                            <i class="fas fa-university fa-2x"></i>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="credit_card_form" class="mb-4">
                        <h5>Kart Bilgileri</h5>
                        <div class="mb-3">
                            <label for="card_number" class="form-label">Kart Numarası</label>
                            <input type="text" class="form-control" id="card_number" name="card_number" placeholder="1234 5678 9012 3456">
                        </div>
                        <div class="mb-3">
                            <label for="card_name" class="form-label">Kart Üzerindeki İsim</label>
                            <input type="text" class="form-control" id="card_name" name="card_name" placeholder="AD SOYAD">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="card_expiry" class="form-label">Son Kullanma Tarihi</label>
                                <input type="text" class="form-control" id="card_expiry" name="card_expiry" placeholder="AA/YY">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="card_cvv" class="form-label">CVV</label>
                                <input type="text" class="form-control" id="card_cvv" name="card_cvv" placeholder="123">
                            </div>
                        </div>
                    </div>
                    
                    <div id="bank_transfer_form" class="mb-4 d-none">
                        <h5>Banka Havalesi Bilgileri</h5>
                        <div class="alert alert-warning">
                            <p class="mb-0">Aşağıdaki hesap bilgilerimize havale yaparak, ödeme dekontunu <a href="mailto:info@sponsorplatform.com">info@sponsorplatform.com</a> adresine gönderebilirsiniz.</p>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th scope="row">Banka</th>
                                        <td>Örnek Bank</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Hesap Sahibi</th>
                                        <td>Sponsor Platform A.Ş.</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">IBAN</th>
                                        <td>TR00 0000 0000 0000 0000 0000 00</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Açıklama</th>
                                        <td>SPONSOR-<?php echo $sponsorship_id; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Ödemeyi Tamamla</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Ödeme Özeti -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Ödeme Özeti</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <strong>Sponsorluk Tutarı:</strong>
                    <span class="fs-4 fw-bold text-primary"><?php echo format_money($sponsorship['amount']); ?></span>
                </div>
                <hr>
                <p><strong>Sponsorluk Türü:</strong> <?php echo $sponsorship['is_partial'] ? 'Kısmi Sponsorluk' : 'Tam Sponsorluk'; ?></p>
                <p><strong>Talep Sahibi:</strong> <?php echo $sponsorship['name'] . ' ' . $sponsorship['surname']; ?></p>
                <p><strong>Talep Kodu:</strong> <?php echo $sponsorship['reference_code']; ?></p>
                <p><strong>Talep:</strong> <?php echo $sponsorship['title']; ?></p>
                
                <div class="alert alert-warning mt-3 mb-0">
                    <p class="mb-0"><i class="fas fa-shield-alt"></i> Ödemenizi tamamladıktan sonra, sponsorluk talebiniz aktifleşecektir.</p>
                </div>
            </div>
        </div>
        
        <!-- Reklam Koşulları -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Reklam Koşulları</h5>
            </div>
            <div class="card-body">
                <p><?php echo nl2br($sponsorship['ad_requirements']); ?></p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const creditCardForm = document.getElementById('credit_card_form');
        const bankTransferForm = document.getElementById('bank_transfer_form');
        const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
        
        // Ödeme yöntemi değiştiğinde formları göster/gizle
        paymentMethods.forEach(function(method) {
            method.addEventListener('change', function() {
                if (this.value === 'credit_card') {
                    creditCardForm.classList.remove('d-none');
                    bankTransferForm.classList.add('d-none');
                } else if (this.value === 'bank_transfer') {
                    creditCardForm.classList.add('d-none');
                    bankTransferForm.classList.remove('d-none');
                }
            });
        });
        
        // Kart numarası formatı (her 4 rakamda bir boşluk)
        const cardNumberInput = document.getElementById('card_number');
        cardNumberInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length > 16) {
                value = value.slice(0, 16);
            }
            
            // Her 4 rakamda bir boşluk ekle
            const chunks = [];
            for (let i = 0; i < value.length; i += 4) {
                chunks.push(value.slice(i, i + 4));
            }
            
            e.target.value = chunks.join(' ');
        });
        
        // Son kullanma tarihi formatı (AA/YY)
        const expiryInput = document.getElementById('card_expiry');
        expiryInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length > 4) {
                value = value.slice(0, 4);
            }
            
            if (value.length > 2) {
                value = value.slice(0, 2) + '/' + value.slice(2);
            }
            
            e.target.value = value;
        });
        
        // CVV formatı (sadece sayı)
        const cvvInput = document.getElementById('card_cvv');
        cvvInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length > 4) {
                value = value.slice(0, 4);
            }
            
            e.target.value = value;
        });
    });
</script>

<?php
require_once '../footer.php';
?>