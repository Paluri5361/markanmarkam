<?php
$page_title = "Ana Sayfa";
require_once 'header.php';
?>

<section class="hero bg-primary text-white py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold">Al - Üret - Sponsor Ol</h1>
                <p class="lead">Ürün taleplerini tedarikçilerle buluşturan ve sponsorluk desteği sağlayan güvenli platform</p>
                <div class="d-flex gap-3 mt-4">
                    <a href="<?php echo SITE_URL; ?>/auth/register.php" class="btn btn-light btn-lg">Hemen Başla</a>
                    <a href="<?php echo SITE_URL; ?>/pages/how-it-works.php" class="btn btn-outline-light btn-lg">Nasıl Çalışır?</a>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="<?php echo SITE_URL; ?>/assets/images/hero-image.png" alt="Platform Görsel" class="img-fluid">
            </div>
        </div>
    </div>
</section>

<section class="how-it-works mb-5">
    <div class="container">
        <h2 class="text-center mb-5">Nasıl Çalışır?</h2>
        <div class="row">
            <div class="col-md-4 text-center mb-4">
                <div class="p-4 border rounded bg-light h-100">
                    <div class="icon-circle bg-primary text-white mb-3">
                        <i class="fas fa-shopping-cart fa-2x"></i>
                    </div>
                    <h3>1. Talep Oluştur</h3>
                    <p>Üretilmesini istediğiniz ürünün detaylarını belirterek bir talep oluşturun.</p>
                </div>
            </div>
            <div class="col-md-4 text-center mb-4">
                <div class="p-4 border rounded bg-light h-100">
                    <div class="icon-circle bg-primary text-white mb-3">
                        <i class="fas fa-industry fa-2x"></i>
                    </div>
                    <h3>2. Teklif Al</h3>
                    <p>Tedarikçiler talebinizi inceleyerek size en uygun fiyat ve üretim tekliflerini sunarlar.</p>
                </div>
            </div>
            <div class="col-md-4 text-center mb-4">
                <div class="p-4 border rounded bg-light h-100">
                    <div class="icon-circle bg-primary text-white mb-3">
                        <i class="fas fa-handshake fa-2x"></i>
                    </div>
                    <h3>3. Sponsor Bul veya Öde</h3>
                    <p>İster kendiniz ödeme yapın, ister sponsorların desteğiyle ürünü ücretsiz alın.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="escrow-system bg-light py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2>Güvenli ESCROW Sistemi</h2>
                <p class="lead">Ödemeleriniz, ürün teslim edilene kadar güvenli emanet havuzunda tutulur.</p>
                <ul class="list-group list-group-flush mb-4">
                    <li class="list-group-item bg-transparent"><i class="fas fa-check-circle text-success me-2"></i> Sipariş tamamlanınca ödeme otomatik serbest bırakılır</li>
                    <li class="list-group-item bg-transparent"><i class="fas fa-check-circle text-success me-2"></i> Anlaşmazlık durumunda destek ekibimiz devreye girer</li>
                    <li class="list-group-item bg-transparent"><i class="fas fa-check-circle text-success me-2"></i> %100 güvenli ödeme garantisi</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <img src="<?php echo SITE_URL; ?>/assets/images/escrow-system.png" alt="Escrow Sistemi" class="img-fluid">
            </div>
        </div>
    </div>
</section>

<section class="user-roles mb-5">
    <div class="container">
        <h2 class="text-center mb-5">Siz Hangi Roldeydiniz?</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <img src="<?php echo SITE_URL; ?>/assets/images/buyer-icon.png" alt="Alıcı" class="mb-3" height="80">
                        <h3 class="card-title">Alıcı</h3>
                        <p class="card-text">Ürün talebinde bulunun, teklifleri değerlendirin ve size en uygun olanı seçin.</p>
                        <a href="<?php echo SITE_URL; ?>/auth/register.php?role=buyer" class="btn btn-primary">Alıcı Olarak Kayıt Ol</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <img src="<?php echo SITE_URL; ?>/assets/images/supplier-icon.png" alt="Tedarikçi" class="mb-3" height="80">
                        <h3 class="card-title">Tedarikçi</h3>
                        <p class="card-text">Alıcıların taleplerine teklif verin, üretim yapın ve güvenle ödemenizi alın.</p>
                        <a href="<?php echo SITE_URL; ?>/auth/register.php?role=supplier" class="btn btn-primary">Tedarikçi Olarak Kayıt Ol</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <img src="<?php echo SITE_URL; ?>/assets/images/sponsor-icon.png" alt="Sponsor" class="mb-3" height="80">
                        <h3 class="card-title">Sponsor</h3>
                        <p class="card-text">Alıcıların taleplerini destekleyin, markanızı tanıtın ve değer yaratın.</p>
                        <a href="<?php echo SITE_URL; ?>/auth/register.php?role=sponsor" class="btn btn-primary">Sponsor Olarak Kayıt Ol</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="testimonials bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-5">Kullanıcılarımız Ne Diyor?</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?php echo SITE_URL; ?>/assets/images/testimonial-1.jpg" alt="Kullanıcı" class="rounded-circle me-3" width="60" height="60">
                            <div>
                                <h5 class="mb-0">Ahmet Yılmaz</h5>
                                <small class="text-muted">Alıcı</small>
                            </div>
                        </div>
                        <p class="card-text">"Ürün talebimi oluşturdum ve birkaç gün içinde çok uygun fiyatlı teklifler aldım. Sponsor desteği sayesinde hiç ödeme yapmadan ürünümü aldım."</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?php echo SITE_URL; ?>/assets/images/testimonial-2.jpg" alt="Kullanıcı" class="rounded-circle me-3" width="60" height="60">
                            <div>
                                <h5 class="mb-0">Ayşe Demir</h5>
                                <small class="text-muted">Tedarikçi</small>
                            </div>
                        </div>
                        <p class="card-text">"Atölyem için yeni müşteriler bulmak çok kolaylaştı. Escrow sistemi sayesinde ödemeler konusunda hiç endişem olmuyor."</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?php echo SITE_URL; ?>/assets/images/testimonial-3.jpg" alt="Kullanıcı" class="rounded-circle me-3" width="60" height="60">
                            <div>
                                <h5 class="mb-0">Mehmet Kaya</h5>
                                <small class="text-muted">Sponsor</small>
                            </div>
                        </div>
                        <p class="card-text">"Markamızı daha geniş kitlelere ulaştırmak için harika bir yol. Sponsorluğumuz karşılığında çok iyi geri dönüşler alıyoruz."</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once 'footer.php';
?>
