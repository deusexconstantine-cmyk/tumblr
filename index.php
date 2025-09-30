<?php
// Hata raporlamayı aktif et
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ana dizinden dashboard'a yönlendir
if ($_SERVER['REQUEST_URI'] === '/') {
    header('Location: /dashboard');
    exit;
}

require_once 'includes/config.php';
session_start();

// Router'ı yükle
require_once 'includes/router.php';
$router = Router::getInstance();
$currentRoute = $router->route();

// Yetki kontrolü - En başta yap
if (!isset($_SESSION['admin_id']) && $router->getSlug() !== 'login') {
    header('Location: /login');
    exit;
}

// AJAX isteği kontrolü
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // Tam sayfa yapısı için özel durum
    if (isset($_GET['full_layout']) && $_GET['full_layout'] === '1') {
        // Yetki kontrolü
        if (!isset($_SESSION['admin_id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Yetkisiz erişim',
                'redirect' => '/login'
            ]);
            exit;
        }
        
        ob_start();
        include 'includes/header.php';
        ?>
        <div class="min-h-screen bg-gray-50/50 dark:bg-gray-900/50" x-data="{ currentPage: '<?php echo $router->getSlug(); ?>' }">
            <?php include 'includes/sidebar.php'; ?>
            <div class="p-4 sm:ml-64">
                <?php include 'includes/topbar.php'; ?>
                <div id="content-area">
                    <?php 
                    $viewFile = __DIR__ . '/views/' . $router->getView() . '.php';
                    if (!isset($_SESSION['admin_id']) && $router->getSlug() !== 'login') {
                        header('Location: /login');
                        exit;
                    }
                    if (file_exists($viewFile)) {
                        include $viewFile;
                    } else {
                        include __DIR__ . '/views/404.php';
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
        include 'includes/footer.php';
        $content = ob_get_clean();
        
        echo json_encode([
            'title' => $router->getTitle(),
            'content' => $content
        ]);
        exit;
    }
    
    // Normal AJAX içerik yüklemesi
    $page = $_GET['page'] ?? 'dashboard';
    
    // Login sayfası dışındaki tüm sayfalar için yetki kontrolü
    if ($page !== 'login' && !isset($_SESSION['admin_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Yetkisiz erişim',
            'redirect' => '/login'
        ]);
        exit;
    }
    
    $viewFile = __DIR__ . "/views/{$page}.php";
    
    if (file_exists($viewFile)) {
        include $viewFile;
    } else {
        http_response_code(404);
        include __DIR__ . '/views/404.php';
    }
    exit;
}

// Views klasörünün varlığını kontrol et
$viewsDir = __DIR__ . '/views';
if (!is_dir($viewsDir)) {
    mkdir($viewsDir, 0755, true);
}

// Login sayfası için özel durum
if ($router->getSlug() === 'login') {
    include 'includes/header.php';
    include 'views/login.php';
    include 'includes/footer.php';
    exit;
}

include 'includes/header.php';
?>

<style>
[x-cloak] { display: none !important; }
html.loading .content-wrapper { margin-left: 18rem; }
@media (max-width: 640px) {
    html.loading .content-wrapper { margin-left: 0; }
}

/* Sweet Alert Özelleştirmeleri */
.swal2-icon {
    border: 2px solid rgba(147, 51, 234, 0.2) !important;
    background: transparent !important;
}
.swal2-icon-content {
    color: currentColor !important;
}
.swal2-success-circular-line-left,
.swal2-success-circular-line-right,
.swal2-success-fix,
.swal2-success-ring {
    display: none !important;
}

/* Dark mode için özel stil */
.dark .swal2-icon {
    border-color: rgba(147, 51, 234, 0.3) !important;
}
</style>

<div class="min-h-screen bg-gray-50/50 dark:bg-gray-900/50" x-data="{ currentPage: '<?php echo $router->getSlug(); ?>' }">
    <?php include 'includes/sidebar.php'; ?>
    <div class="p-4 transition-all duration-300 content-wrapper" 
        :class="{ 
            'sm:ml-72': $store.app.sidebarOpen,
            'sm:ml-0': !$store.app.sidebarOpen 
        }">
        <?php include 'includes/topbar.php'; ?>

        <!-- Dynamic Content Area -->
        <div id="content-area" class="mt-4 sm:mt-0">
            <?php
            $viewFile = __DIR__ . '/views/' . $router->getView() . '.php';
            if (!isset($_SESSION['admin_id']) && $router->getSlug() !== 'login') {
                header('Location: /login');
                exit;
            }
            if (file_exists($viewFile)) {
                include $viewFile;
            } else {
                include __DIR__ . '/views/404.php';
            }
            ?>
        </div>
    </div>
</div>

<script>
// Sayfa yüklendiğinde loading class'ını ekle
document.documentElement.classList.add('loading');
// Alpine.js yüklendiğinde loading class'ını kaldır
document.addEventListener('alpine:init', () => {
    document.documentElement.classList.remove('loading');
    Alpine.store('app', {
        currentPage: '<?php echo $router->getSlug(); ?>',
        sidebarOpen: window.innerWidth >= 640,
        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            if (window.innerWidth < 640) {
                document.body.style.overflow = this.sidebarOpen ? 'hidden' : '';
            }
        },
        async setPage(page) {
            this.currentPage = page;
            
            // Mobilde sidebar'ı kapat
            if (window.innerWidth < 640) {
                this.sidebarOpen = false;
                document.body.style.overflow = '';
            }
            
            // URL'i güncelle
            window.history.pushState({ page }, '', `/${page}`);
            
            try {
                // Sayfayı AJAX ile yükle
                const response = await fetch(`/?page=${page}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Sayfa yüklenemedi');
                }
                
                const content = await response.text();
                document.getElementById('content-area').innerHTML = content;
                
                // Sayfa başlığını güncelle
                const titles = {
                    'dashboard': 'Dashboard - urlredgrimJS',
                    'link-add': 'Link Ekle - urlredgrimJS',
                    'login': 'Giriş Yap - urlredgrimJS',
                    'settings': 'Ayarlar - urlredgrimJS',
                    'links': 'Linkler - urlredgrimJS'
                };
                document.title = titles[page] || 'urlredgrimJS';
            } catch (error) {
                Swal.fire({
                    title: 'Hata!',
                    text: error.message,
                    icon: 'error',
                    confirmButtonText: 'Tamam',
                    confirmButtonColor: '#9333ea'
                });
            }
        }
    });

    // Tarayıcının geri/ileri butonları için event listener
    window.addEventListener('popstate', (event) => {
        if (event.state && event.state.page) {
            Alpine.store('app').setPage(event.state.page);
        }
    });

    // Ekran boyutu değiştiğinde sidebar durumunu güncelle
    const mediaQuery = window.matchMedia('(min-width: 640px)');
    mediaQuery.addEventListener('change', (e) => {
        Alpine.store('app').sidebarOpen = e.matches;
        document.body.style.overflow = '';
    });
});

// Link ekleme işlemi
function handleLinkAdd(e) {
    const form = e.target;
    const formData = new FormData(form);
    formData.append('action', 'add_link');
    
    // Checkbox değerlerini boolean olarak ekle
    formData.set('mobile_off', form.mobile_off.checked ? 1 : 0);
    formData.set('desktop_off', form.desktop_off.checked ? 1 : 0);
    formData.set('active', form.active.checked ? 1 : 0);

    fetch('/includes/api.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Başarılı!',
                text: data.message,
                icon: 'success',
                confirmButtonText: 'Tamam',
                confirmButtonColor: '#9333ea',
                background: 'rgba(255, 255, 255, 0.95)',
                backdrop: `rgba(147, 51, 234, 0.1)`,
                customClass: {
                    popup: 'rounded-2xl shadow-2xl dark:!bg-gray-800/95 dark:text-white',
                    confirmButton: 'rounded-xl',
                    icon: 'border-0 !bg-transparent'
                },
                showClass: {
                    popup: 'animate__animated animate__fadeIn animate__faster'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOut animate__faster'
                }
            }).then(() => {
                Alpine.store('app').setPage('dashboard');
            });
        } else {
            Swal.fire({
                title: 'Hata!',
                text: data.message,
                icon: 'error',
                confirmButtonText: 'Tamam',
                confirmButtonColor: '#9333ea',
                background: 'rgba(255, 255, 255, 0.95)',
                backdrop: `rgba(147, 51, 234, 0.1)`,
                customClass: {
                    popup: 'rounded-2xl shadow-2xl dark:!bg-gray-800/95 dark:text-white',
                    confirmButton: 'rounded-xl',
                    icon: 'border-0 !bg-transparent'
                },
                showClass: {
                    popup: 'animate__animated animate__fadeIn animate__faster'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOut animate__faster'
                }
            });
        }
    })
    .catch(error => {
        Swal.fire({
            title: 'Hata!',
            text: 'Bir bağlantı hatası oluştu. Lütfen tekrar deneyin.',
            icon: 'error',
            confirmButtonText: 'Tamam',
            confirmButtonColor: '#9333ea',
            background: 'rgba(255, 255, 255, 0.95)',
            backdrop: `rgba(147, 51, 234, 0.1)`,
            customClass: {
                popup: 'rounded-2xl shadow-2xl dark:!bg-gray-800/95 dark:text-white',
                confirmButton: 'rounded-xl'
            }
        });
    });
}

// Çıkış işlemi
function handleLogout() {
    const formData = new FormData();
    formData.append('action', 'logout');

    fetch('/includes/api.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect;
        }
    });
}

// Kullanıcı adı değiştirme işlemi
function handleUsernameChange(e) {
    const form = e.target;
    const formData = new FormData();
    formData.append('action', 'change_username');
    formData.append('new_username', form.querySelector('[x-model="newUsername"]').value);
    formData.append('current_password', form.querySelector('[x-model="currentPasswordForUsername"]').value);

    fetch('/includes/api.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Başarılı!',
                text: data.message,
                icon: 'success',
                confirmButtonText: 'Tamam',
                confirmButtonColor: '#9333ea',
                background: 'rgba(255, 255, 255, 0.95)',
                backdrop: `rgba(147, 51, 234, 0.1)`,
                customClass: {
                    popup: 'rounded-2xl shadow-2xl dark:!bg-gray-800/95 dark:text-white',
                    confirmButton: 'rounded-xl',
                    icon: 'border-0 !bg-transparent'
                },
                showClass: {
                    popup: 'animate__animated animate__fadeIn animate__faster'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOut animate__faster'
                }
            }).then(() => {
                // Formu temizle
                form.reset();
                // Sayfayı yenile
                window.location.reload();
            });
        } else {
            Swal.fire({
                title: 'Hata!',
                text: data.message,
                icon: 'error',
                confirmButtonText: 'Tamam',
                confirmButtonColor: '#9333ea',
                background: 'rgba(255, 255, 255, 0.95)',
                backdrop: `rgba(147, 51, 234, 0.1)`,
                customClass: {
                    popup: 'rounded-2xl shadow-2xl dark:!bg-gray-800/95 dark:text-white',
                    confirmButton: 'rounded-xl',
                    icon: 'border-0 !bg-transparent'
                },
                showClass: {
                    popup: 'animate__animated animate__fadeIn animate__faster'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOut animate__faster'
                }
            });
        }
    });
}

// Şifre değiştirme işlemi
function handlePasswordChange(e) {
    e.preventDefault();
    const formData = new FormData();
    formData.append('action', 'change_password');
    formData.append('current_password', currentPassword);
    formData.append('new_password', newPassword);
    formData.append('confirm_password', confirmPassword);

    fetch('/includes/api.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Başarılı!',
                text: data.message,
                icon: 'success',
                showConfirmButton: false,
                timer: 2000,
                background: 'rgba(255, 255, 255, 0.95)',
                backdrop: `rgba(147, 51, 234, 0.1)`,
                customClass: {
                    popup: 'rounded-2xl shadow-2xl dark:!bg-gray-800/95 dark:text-white'
                }
            }).then(() => {
                window.location.href = data.redirect;
            });
        } else {
            Swal.fire({
                title: 'Hata!',
                text: data.message,
                icon: 'error',
                confirmButtonText: 'Tamam',
                confirmButtonColor: '#9333ea',
                background: 'rgba(255, 255, 255, 0.95)',
                backdrop: `rgba(147, 51, 234, 0.1)`,
                customClass: {
                    popup: 'rounded-2xl shadow-2xl dark:!bg-gray-800/95 dark:text-white',
                    confirmButton: 'rounded-xl'
                }
            });
        }
    })
    .catch(error => {
        Swal.fire({
            title: 'Hata!',
            text: 'Bir bağlantı hatası oluştu. Lütfen tekrar deneyin.',
            icon: 'error',
            confirmButtonText: 'Tamam',
            confirmButtonColor: '#9333ea',
            background: 'rgba(255, 255, 255, 0.95)',
            backdrop: `rgba(147, 51, 234, 0.1)`,
            customClass: {
                popup: 'rounded-2xl shadow-2xl dark:!bg-gray-800/95 dark:text-white',
                confirmButton: 'rounded-xl'
            }
        });
    });
}
</script>

<?php include 'includes/footer.php'; ?> 