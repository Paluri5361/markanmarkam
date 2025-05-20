<?php
$page_title = "Sponsor Ol";
require_once '../header.php';

if (!has_role(ROLE_SPONSOR)) {
    $_SESSION['flash_message'] = "Bu sayfaya erişim izniniz yok.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL);
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = "Geçersiz talep.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL . "/sponsor/sponsor-requests.php");
    exit;
}

$request_id = (int)$_GET['id'];

$stmt = $conn->prepare("
    SELECT r.*, u.name, u.surname, 
           (SELECT COUNT(*) FROM offers WHERE request_id = r.id) AS offer_count,
           (SELECT SUM(amount) FROM sponsorships WHERE request_id = r.id) AS sponsored_amount
    FROM requests r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.id = ? AND r.status = 'pending' AND r.allow_sponsor = 1
");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['flash_message'] = "Talep bulunamadı veya sponsorluğa uygun değil.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL . "/sponsor/sponsor-requests.php");
    exit;
}

$request = $result->fetch_assoc();
$sponsored_amount = $request['sponsored_amount'] ?? 0;
$remaining_amount = $request['budget'] - $sponsored_amount;

if ($sponsored_amount >= $request['budget']) {
    $_SESSION['flash_message'] = "Bu talep için zaten tam sponsorluk sağlanmış.";
    $_SESSION['flash_type'] = "warning";
    header("Location: " . SITE_URL . "/sponsor/sponsor-requests.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM sponsorships WHERE request_id = ? AND sponsor_id = ?");
$stmt->bind_param("ii", $request_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['flash_message'] = "Bu talebe zaten sponsor oldunuz.";
    $_SESSION['flash_type'] = "warning";
    header("Location: " . SITE_URL . "/sponsor/sponsored-requests.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM request_attachments WHERE request_id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

$attachments = [];
while ($row = $result->fetch_assoc()) {
    $attachments[] = $row;
}

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = (float)$_POST['amount'];
    $is_partial = isset($_POST['is_partial']) ? 1 : 0;
    $ad_requirements = clean_input($_POST['ad_requirements']);
    
    $errors = [];

    if (empty($amount) || $amount <= 0) {
        $errors[] = "Geçerli bir sponsorluk tutarı girin.";
    }

    if (!$is_partial && $amount < $remaining_amount) {
        $errors[] = "Tam sponsorluk için kalan tutarın tamamı (" . format_money($remaining_amount) . ") karşılanmalıdır.";
    }

    if ($is_partial && $amount >= $remaining_amount) {
        $errors[] = "Kısmi sponsorluk için tutar, kalan tutardan daha az olmalıdır (" . format_money($remaining_amount) . ").";
    }

    if (empty($ad_requirements)) {
        $errors[] = "Reklam yerleştirme koşullarını belirtin.";
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO sponsorships (request_id, sponsor_id, amount, is_partial, ad_requirements, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
            $stmt->bind_param("iidis", $request_id, $_SESSION['user_id'], $amount, $is_partial, $ad_requirements);
            $stmt->execute();
            $sponsorship_id = $conn->insert_id;

            if (!$is_partial && $amount >= $remaining_amount) {
                $stmt = $conn->prepare("UPDATE requests SET sponsor_id = ? WHERE id = ?");
                $stmt->bind_param("ii", $_SESSION['user_id'], $request_id);
                $stmt->execute();
            }

            create_notification(
                $request['user_id'],
                "Talebinize yeni bir sponsorluk desteği geldi! Talep Kodu: " . $request['reference_code'],
                'new_sponsorship',
                SITE_URL . "/buyer/view-sponsorships.php?id=" . $request_id
            );

            $conn->commit();

            $_SESSION['flash_message'] = "Sponsorluk desteğiniz başarıyla kaydedildi.";
            $_SESSION['flash_type'] = "success";

            header("Location: " . SITE_URL . "/payment/sponsor-payment.php?id=" . $sponsorship_id);
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "İşlem sırasında bir hata oluştu: " . $e->getMessage();
        }
    }
}
?>

<!-- JS kısmı -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isPartialCheckbox = document.getElementById('is_partial');
    const amountInput = document.getElementById('amount');
    const amountHelp = document.getElementById('amount_help');
    const remainingAmount = <?php echo $remaining_amount; ?>;

    function updateAmountInput() {
        if (isPartialCheckbox.checked) {
            amountInput.min = 0.01;
            amountInput.max = (remainingAmount - 0.01).toFixed(2);
            if (parseFloat(amountInput.value) >= remainingAmount) {
                amountInput.value = (remainingAmount - 0.01).toFixed(2);
            }
            amountHelp.textContent = "Kısmi sponsorluk için " + formatMoney(remainingAmount) + " TL'den daha az bir tutar girin.";
        } else {
            amountInput.min = remainingAmount;
            amountInput.max = remainingAmount;
            amountInput.value = remainingAmount.toFixed(2);
            amountHelp.textContent = "Tam sponsorluk için " + formatMoney(remainingAmount) + " TL girilmelidir.";
        }
    }

    function formatMoney(amount) {
        return new Intl.NumberFormat('tr-TR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    }

    isPartialCheckbox.addEventListener('change', updateAmountInput);
    updateAmountInput();
});
</script>
