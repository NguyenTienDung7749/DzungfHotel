<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$page_title = 'DzungfHotel | Liên hệ';
$active_page = 'contact';
$page_heading = 'Liên hệ';
$page_eyebrow = 'Thông tin hỗ trợ';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-xxl py-5">
    <div class="container">
        <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
            <h6 class="section-title text-center text-primary text-uppercase">Liên hệ</h6>
            <h1 class="mb-5">Thông tin liên hệ và hỗ trợ khách hàng</h1>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="info-card h-100 text-center">
                    <div class="icon-circle mx-auto mb-3"><i class="fa fa-phone-alt"></i></div>
                    <h5 class="mb-3">Tổng đài đặt phòng</h5>
                    <p class="mb-2">0905 123 456</p>
                    <p class="mb-0 text-muted">Hỗ trợ giờ hành chính</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card h-100 text-center">
                    <div class="icon-circle mx-auto mb-3"><i class="fa fa-envelope"></i></div>
                    <h5 class="mb-3">Email hỗ trợ</h5>
                    <p class="mb-2">support@dzungfhotel.com</p>
                    <p class="mb-0 text-muted">Phản hồi nhanh các câu hỏi cơ bản</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card h-100 text-center">
                    <div class="icon-circle mx-auto mb-3"><i class="fa fa-map-marker-alt"></i></div>
                    <h5 class="mb-3">Địa chỉ</h5>
                    <p class="mb-2">22 Trần Phú, Đà Nẵng</p>
                    <p class="mb-0 text-muted">Vị trí văn phòng giao dịch</p>
                </div>
            </div>
        </div>

        <div class="row g-5 align-items-stretch mt-1">
            <div class="col-lg-6">
                <iframe class="position-relative rounded w-100 h-100 shadow"
                    src="https://maps.google.com/maps?q=Da%20Nang%20Vietnam&t=&z=13&ie=UTF8&iwloc=&output=embed"
                    frameborder="0" style="min-height: 360px; border: 0;" allowfullscreen="" aria-hidden="false" tabindex="0"></iframe>
            </div>
            <div class="col-lg-6">
                <div class="form-card h-100">
                    <h3 class="mb-4">Chăm sóc khách hàng</h3>
                    <p class="mb-3">DzungfHotel hỗ trợ khách hàng qua điện thoại, email và văn phòng giao dịch tại Đà Nẵng. Thông tin trên trang này giúp khách lưu trú liên hệ nhanh khi cần tư vấn về phòng và lịch nhận phòng.</p>
                    <ul class="list-check mb-4">
                        <li><i class="fa fa-check-circle"></i>Tư vấn chọn hạng phòng phù hợp theo nhu cầu lưu trú.</li>
                        <li><i class="fa fa-check-circle"></i>Hỗ trợ xác nhận thông tin nhận phòng và trả phòng.</li>
                        <li><i class="fa fa-check-circle"></i>Giải đáp các câu hỏi về trạng thái booking và thanh toán.</li>
                        <li><i class="fa fa-check-circle"></i>Hỗ trợ khách hàng cá nhân và khách đoàn.</li>
                    </ul>
                    <a href="<?= e(url('rooms.php')) ?>" class="btn btn-primary py-3 px-5 me-2">Xem phòng</a>
                    <a href="<?= e(url('login.php')) ?>" class="btn btn-outline-dark py-3 px-5">Đăng nhập</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
