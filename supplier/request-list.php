<?php
$page_title = "Talep Listesi";
require_once '../header.php';

// Yalnızca tedarikçi rolüne sahip kullanıcılar erişebilir
if (!has_role(ROLE_SUPPLIER)) {
    $_SESSION['flash_message'] = "Bu sayfaya erişim izniniz yok.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL);
    exit;
}

// Tedarikçi kategorilerini getir
$stmt = $conn->prepare("SELECT categories FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$supplier_categories = json_decode($user['categories'], true) ?? [];

// Filtre parametreleri
$category = isset($_GET['category']) ? clean_input($_GET['category']) : '';
$status = isset($_GET['status']) ? clean_input($_GET['status']) : 'pending';
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// SQL sorgusu için temel koşullar
$conditions = ["r.status = ?"];
$params = [$status];
$types = "s";

// Kategori filtresi
if (!empty($category)) {
    $conditions[] = "r.category = ?";
    $params[] = $category;
    $types .= "s";
}

// Arama filtresi
if (!empty($search)) {
    $conditions[] = "(r.title LIKE ? OR r.reference_code LIKE ? OR r.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
}

// Zaten teklif verilen talepleri hariç tut
$conditions[] = "r.id NOT IN (SELECT request_id FROM offers WHERE supplier_id = ?)";
$params[] = $_SESSION['user_id'];
$types .= "i";

// Talepleri getir
$sql = "
    SELECT r.*, u.name, u.surname, u.rating,
           (SELECT COUNT(*) FROM offers WHERE request_id = r.id) AS offer_count
    FROM requests r
    JOIN users u ON r.user_id = u.id
    WHERE " . implode(" AND ", $conditions) . "
    ORDER BY r.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

// Filtre için kategorileri getir
$categories = [
    'elektronik' => 'Elektronik',
    'tekstil' => 'Tekstil',
    'mobilya' => 'Mobilya',
    'gida' => 'Gıda',
    'otomotiv' => 'Otomotiv',
    'kozmetik' => 'Kozmetik',
    'diger' => 'Diğer'
];
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Talep Listesi</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo SITE_URL; ?>/supplier/dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Panele Dön
        </a>
    </div>
</div>

<!-- Filtreler -->
<div class="card mb-4">
    <div class="card-body">
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="category" class="form-label">Kategori</label>
                <select class="form-select" id="category" name="category">
                    <option value="">Tüm Kategoriler</option>
                    <?php foreach ($categories as $key => $value): ?>
                        <?php if (empty($supplier_categories) || in_array($key, $supplier_categories)): ?>
                            <option value="<?php echo $key; ?>" <?php echo $category == $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label">Durum</label>
                <select class="form-select" id="status" name="status">
                    <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Beklemede</option>
                    <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>Tümü</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="search" class="form-label">Arama</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="search" name="search" placeholder="Başlık, referans kodu veya açıklama" value="<?php echo $search; ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Talep Listesi -->
<?php if (empty($requests)): ?>
    <div class="alert alert-info">
        <h5><i class="fas fa-info-circle"></i> Talep Bulunamadı</h5>
        <p>Seçilen kriterlere uygun talep bulunamadı. Lütfen filtre kriterlerini değiştirin.</p>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ref. Kodu</th>
                            <th>Başlık</th>
                            <th>Kategori</th>
                            <th>Adet</th>
                            <th>Bütçe</th>
                            <th>Son Teslim</th>
                            <th>Teklifler</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?php echo $request['reference_code']; ?></td>
                                <td>
                                    <strong><?php echo $request['title']; ?></strong><br>
                                    <small class="text-muted">
                                        <?php echo $request['name'] . ' ' . $request['surname']; ?>
                                        <?php if ($request['rating'] > 0): ?>
                                            <span class="text-warning">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star<?php echo ($i <= $request['rating']) ? '' : '-o'; ?>"></i>
                                                <?php endfor; ?>
                                            </span>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo isset($categories[$request['category']]) ? $categories[$request['category']] : $request['category']; ?>
                                    </span>
                                </td>
                                <td><?php echo $request['quantity']; ?></td>
                                <td><?php echo format_money($request['budget']); ?></td>
                                <td><?php echo date('d.m.Y', strtotime($request['deadline'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $request['offer_count'] > 0 ? 'success' : 'secondary'; ?>">
                                        <?php echo $request['offer_count']; ?> Teklif
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?php echo SITE_URL; ?>/supplier/request-details.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> Detaylar
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>/supplier/submit-offer.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-paper-plane"></i> Teklif Ver
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
require_once '../footer.php';
?>