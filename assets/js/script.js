/**
 * Sponsor Platform JS
 * Temel JavaScript işlevleri
 */

document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap bileşenlerini etkinleştir
    initializeBootstrapComponents();
    
    // Bildirim kapatma düğmelerini etkinleştir
    initializeAlertDismiss();
    
    // Form doğrulama
    initializeFormValidation();
    
    // Dosya yükleme önizleme
    initializeFileUploadPreview();
});

/**
 * Bootstrap bileşenlerini etkinleştir (Tooltips, Popovers vb.)
 */
function initializeBootstrapComponents() {
    // Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

/**
 * Alert kapatma düğmelerini etkinleştir
 */
function initializeAlertDismiss() {
    var alertList = document.querySelectorAll('.alert');
    alertList.forEach(function(alert) {
        var closeButton = alert.querySelector('.btn-close');
        if (closeButton) {
            closeButton.addEventListener('click', function() {
                alert.classList.add('fade');
                setTimeout(function() {
                    alert.remove();
                }, 150);
            });
        }
    });
}

/**
 * Form doğrulama
 */
function initializeFormValidation() {
    var forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
}

/**
 * Dosya yükleme önizleme
 */
function initializeFileUploadPreview() {
    var fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(function(input) {
        input.addEventListener('change', function(e) {
            var previewContainer = document.querySelector('#' + input.id + '_preview');
            
            if (previewContainer) {
                previewContainer.innerHTML = '';
                
                if (input.files && input.files.length > 0) {
                    for (var i = 0; i < input.files.length; i++) {
                        var file = input.files[i];
                        var reader = new FileReader();
                        
                        reader.onload = (function(file) {
                            return function(e) {
                                var fileType = file.type.split('/')[0];
                                var previewItem = document.createElement('div');
                                previewItem.className = 'preview-item';
                                
                                if (fileType === 'image') {
                                    var img = document.createElement('img');
                                    img.src = e.target.result;
                                    img.className = 'img-thumbnail';
                                    img.style.maxHeight = '100px';
                                    img.style.marginRight = '10px';
                                    previewItem.appendChild(img);
                                } else {
                                    var icon = document.createElement('i');
                                    icon.className = 'fas fa-file';
                                    icon.style.fontSize = '2rem';
                                    icon.style.marginRight = '10px';
                                    previewItem.appendChild(icon);
                                }
                                
                                var fileInfo = document.createElement('div');
                                fileInfo.textContent = file.name;
                                previewItem.appendChild(fileInfo);
                                
                                previewContainer.appendChild(previewItem);
                            };
                        })(file);
                        
                        reader.readAsDataURL(file);
                    }
                }
            }
        });
    });
}

/**
 * Para formatla (TL)
 */
function formatMoney(amount) {
    return new Intl.NumberFormat('tr-TR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
}

/**
 * Tarihi formatla
 */
function formatDate(dateString) {
    var options = { day: '2-digit', month: '2-digit', year: 'numeric' };
    return new Date(dateString).toLocaleDateString('tr-TR', options);
}

/**
 * AJAX ile veri gönder
 */
function sendAjaxRequest(url, method, data, successCallback, errorCallback) {
    var xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                var response = JSON.parse(xhr.responseText);
                successCallback(response);
            } catch (e) {
                errorCallback('JSON parse hatası: ' + e.message);
            }
        } else {
            errorCallback('Sunucu hatası: ' + xhr.status);
        }
    };
    
    xhr.onerror = function() {
        errorCallback('İstek hatası');
    };
    
    xhr.send(JSON.stringify(data));
}

/**
 * Bildirimleri güncelle
 */
function updateNotifications() {
    sendAjaxRequest('/api/notifications/unread-count.php', 'GET', {}, function(response) {
        var notificationBadge = document.getElementById('notification-badge');
        if (notificationBadge) {
            if (response.count > 0) {
                notificationBadge.textContent = response.count;
                notificationBadge.classList.remove('d-none');
            } else {
                notificationBadge.classList.add('d-none');
            }
        }
    }, function(error) {
        console.error('Bildirim güncellenirken hata oluştu:', error);
    });
}

/**
 * Bildirimleri okundu olarak işaretle
 */
function markNotificationAsRead(notificationId, element) {
    sendAjaxRequest('/api/notifications/mark-read.php', 'POST', {
        notification_id: notificationId
    }, function(response) {
        if (response.success && element) {
            element.classList.remove('bg-light');
            var unreadIndicator = element.querySelector('.unread-indicator');
            if (unreadIndicator) {
                unreadIndicator.remove();
            }
        }
    }, function(error) {
        console.error('Bildirim okundu işaretlenirken hata oluştu:', error);
    });
}

/**
 * Bildirimleri periyodik olarak kontrol et
 */
function startNotificationChecker() {
    updateNotifications();
    setInterval(updateNotifications, 60000); // Her 1 dakikada bir kontrol et
}

/**
 * Talep durumunu güncelle
 */
function updateRequestStatus(requestId, newStatus, successCallback) {
    sendAjaxRequest('/api/requests/update-status.php', 'POST', {
        request_id: requestId,
        status: newStatus
    }, function(response) {
        if (response.success) {
            if (typeof successCallback === 'function') {
                successCallback(response);
            }
        } else {
            alert('Durum güncellenemedi: ' + response.message);
        }
    }, function(error) {
        alert('Durum güncellenirken hata oluştu: ' + error);
    });
}

/**
 * Escrow ödeme durumunu güncelle
 */
function updateEscrowStatus(escrowId, newStatus, successCallback) {
    sendAjaxRequest('/api/payments/update-escrow.php', 'POST', {
        escrow_id: escrowId,
        status: newStatus
    }, function(response) {
        if (response.success) {
            if (typeof successCallback === 'function') {
                successCallback(response);
            }
        } else {
            alert('Ödeme durumu güncellenemedi: ' + response.message);
        }
    }, function(error) {
        alert('Ödeme durumu güncellenirken hata oluştu: ' + error);
    });
}

/**
 * Sayfayı yeniden yükle onayı
 */
function confirmReload(message) {
    if (confirm(message || 'Sayfayı yenilemek istediğinize emin misiniz?')) {
        window.location.reload();
    }
}

/**
 * Sayfayı yönlendir onayı
 */
function confirmRedirect(url, message) {
    if (confirm(message || 'Yönlendirilmek istediğinize emin misiniz?')) {
        window.location.href = url;
    }
}

// Otomatik olarak bildirimleri kontrol etmeyi başlat
if (document.querySelector('.notification-badge')) {
    startNotificationChecker();
}