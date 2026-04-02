<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$pageTitle = $page_title ?? 'DzungfHotel';
$activePage = $active_page ?? '';
$pageHeading = $page_heading ?? '';
$pageEyebrow = $page_eyebrow ?? 'DzungfHotel';
$currentUser = current_user();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title><?= e($pageTitle) ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="DzungfHotel, đặt phòng khách sạn, khách sạn trực tuyến" name="keywords">
    <meta content="DzungfHotel là nền tảng đặt phòng khách sạn trực tuyến bằng PHP và MySQL." name="description">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= e(asset('lib/animate/animate.min.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('lib/owlcarousel/assets/owl.carousel.min.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/bootstrap.min.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/style.css')) ?>" rel="stylesheet">
</head>
<body>
    <div class="container-xxl bg-white p-0">
        <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="sr-only">Đang tải...</span>
            </div>
        </div>

        <div class="container-fluid bg-dark px-0">
            <div class="row gx-0">
                <div class="col-lg-3 bg-dark d-none d-lg-block">
                    <a href="<?= e(url('index.php')) ?>" class="navbar-brand w-100 h-100 m-0 p-0 d-flex align-items-center justify-content-center">
                        <h1 class="m-0 text-primary text-uppercase">DzungfHotel</h1>
                    </a>
                </div>
                <div class="col-lg-9">
                    <div class="row gx-0 bg-white d-none d-lg-flex">
                        <div class="col-lg-7 px-5 text-start">
                            <div class="h-100 d-inline-flex align-items-center py-2 me-4">
                                <i class="fa fa-envelope text-primary me-2"></i>
                                <p class="mb-0">support@dzungfhotel.com</p>
                            </div>
                            <div class="h-100 d-inline-flex align-items-center py-2 me-4">
                                <i class="fa fa-phone-alt text-primary me-2"></i>
                                <p class="mb-0">0905 123 456</p>
                            </div>
                            <div class="h-100 d-inline-flex align-items-center py-2">
                                <i class="fa fa-map-marker-alt text-primary me-2"></i>
                                <p class="mb-0">Đà Nẵng, Việt Nam</p>
                            </div>
                        </div>
                        <div class="col-lg-5 px-5 text-end">
                            <div class="h-100 d-inline-flex align-items-center py-2 text-muted small">
                                <?php if ($currentUser): ?>
                                    Xin chào, <span class="text-dark fw-bold ms-1"><?= e($currentUser['full_name']) ?></span>
                                <?php else: ?>
                                    Nền tảng đặt phòng khách sạn trực tuyến
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <nav class="navbar navbar-expand-lg bg-dark navbar-dark p-3 p-lg-0">
                        <a href="<?= e(url('index.php')) ?>" class="navbar-brand d-block d-lg-none">
                            <h1 class="m-0 text-primary text-uppercase">DzungfHotel</h1>
                        </a>
                        <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                            <div class="navbar-nav mr-auto py-0">
                                <a href="<?= e(url('index.php')) ?>" class="nav-item nav-link <?= e(is_active($activePage, ['home'])) ?>">Trang chủ</a>
                                <a href="<?= e(url('about.php')) ?>" class="nav-item nav-link <?= e(is_active($activePage, ['about'])) ?>">Giới thiệu</a>
                                <a href="<?= e(url('rooms.php')) ?>" class="nav-item nav-link <?= e(is_active($activePage, ['rooms', 'room-details'])) ?>">Phòng</a>
                                <a href="<?= e(url('booking.php')) ?>" class="nav-item nav-link <?= e(is_active($activePage, ['booking', 'booking-confirm'])) ?>">Đặt phòng</a>
                                <a href="<?= e(url('contact.php')) ?>" class="nav-item nav-link <?= e(is_active($activePage, ['contact'])) ?>">Liên hệ</a>
                                <?php if (is_admin()): ?>
                                    <a href="<?= e(url('admin/bookings.php')) ?>" class="nav-item nav-link <?= e(is_active($activePage, ['admin-bookings'])) ?>">Quản lý booking</a>
                                <?php elseif (is_logged_in()): ?>
                                    <a href="<?= e(url('profile.php')) ?>" class="nav-item nav-link <?= e(is_active($activePage, ['profile'])) ?>">Hồ sơ</a>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex flex-column flex-lg-row gap-2 py-3 py-lg-0">
                                <?php if (is_logged_in()): ?>
                                    <a href="<?= e(url('logout.php')) ?>" class="btn btn-outline-light rounded py-2 px-4">Đăng xuất</a>
                                <?php else: ?>
                                    <a href="<?= e(url('login.php')) ?>" class="btn btn-outline-light rounded py-2 px-4">Đăng nhập</a>
                                    <a href="<?= e(url('register.php')) ?>" class="btn btn-primary rounded py-2 px-4">Đăng ký</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>
        </div>

        <?php if ($pageHeading !== ''): ?>
            <?php render_page_header($pageHeading, $pageEyebrow); ?>
        <?php endif; ?>

        <?php display_flash(); ?>
