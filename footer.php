</main>
    
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><?php echo SITE_NAME; ?></h5>
                    <p>Üreticiler ve sponsorlar arasında güvenli bir şekilde bağlantı kurmanızı sağlayan platform.</p>
                </div>
                <div class="col-md-2">
                    <h5>Platform</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>/pages/about.php">Hakkımızda</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/how-it-works.php">Nasıl Çalışır</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/faq.php">SSS</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/contact.php">İletişim</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h5>Yasal</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>/pages/terms.php">Kullanım Koşulları</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/privacy.php">Gizlilik Politikası</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>İletişim</h5>
                    <address>
                        <strong><?php echo SITE_NAME; ?></strong><br>
                        Örnek Mahallesi, Örnek Sokak No:123<br>
                        İstanbul, Türkiye<br>
                        <abbr title="Telefon">T:</abbr> (0212) 123 45 67<br>
                        <abbr title="E-posta">E:</abbr> <a href="mailto:info@sponsorplatform.com">info@sponsorplatform.com</a>
                    </address>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </footer>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap dropdown'ları başlat
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    dropdownElementList.forEach(function(dropdownToggleEl) {
        new bootstrap.Dropdown(dropdownToggleEl);
    });
    
    // Dropdown tıklama eventi
    document.querySelectorAll('.dropdown').forEach(function(dropdown) {
        dropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
});
</script>

    <script src="<?php echo SITE_URL; ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
    <script>
function toggleDropdown(menuId) {
    const menu = document.getElementById(menuId);
    
    // Diğer tüm açık menüleri kapat
    document.querySelectorAll('.custom-dropdown-menu.show').forEach(function(openMenu) {
        if (openMenu.id !== menuId) {
            openMenu.classList.remove('show');
        }
    });
    
    // Bu menüyü aç/kapat
    menu.classList.toggle('show');
}

// Sayfa herhangi bir yerine tıklandığında menüleri kapat
document.addEventListener('click', function(event) {
    if (!event.target.closest('.custom-dropdown')) {
        document.querySelectorAll('.custom-dropdown-menu.show').forEach(function(menu) {
            menu.classList.remove('show');
        });
    }
});

// Bildirimi okundu olarak işaretle
function markNotificationAsRead(notificationId, element) {
    // AJAX isteği ile bildirimi okundu olarak işaretle
    // Bu bir örnek implementasyondur
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '<?php echo SITE_URL; ?>/api/mark-notification-read.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status === 200) {
            // Bildirimi görsel olarak okundu olarak işaretle
            element.classList.remove('bg-light');
            const badge = element.querySelector('.badge');
            if (badge) {
                badge.style.display = 'none';
            }
        }
    };
    xhr.send('notification_id=' + notificationId);
}
</script>
</body>
</html>