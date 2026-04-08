<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$page_title = 'DzungfHotel | Liên hệ';
$active_page = 'contact';
$page_heading = 'Liên hệ';
$page_eyebrow = 'Chăm sóc khách hàng';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-xxl py-5">
    <div class="container">
        <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
            <h6 class="section-title text-center text-primary text-uppercase">Liên hệ</h6>
            <h1 class="mb-5">Kết nối cùng DzungfHotel cho mọi nhu cầu lưu trú</h1>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="info-card h-100 text-center">
                    <div class="icon-circle mx-auto mb-3"><i class="fa fa-phone-alt"></i></div>
                    <h5 class="mb-3">Đường dây hỗ trợ</h5>
                    <p class="mb-2">0905 123 456</p>
                    <p class="mb-0 text-muted">Tư vấn nhanh về hạng phòng và lịch lưu trú</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card h-100 text-center">
                    <div class="icon-circle mx-auto mb-3"><i class="fa fa-envelope"></i></div>
                    <h5 class="mb-3">Email chăm sóc khách</h5>
                    <p class="mb-2">support@dzungfhotel.com</p>
                    <p class="mb-0 text-muted">Phản hồi các yêu cầu tư vấn và hỗ trợ lưu trú</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card h-100 text-center">
                    <div class="icon-circle mx-auto mb-3"><i class="fa fa-map-marker-alt"></i></div>
                    <h5 class="mb-3">Địa chỉ</h5>
                    <p class="mb-2">22 Trần Phú, Đà Nẵng</p>
                    <p class="mb-0 text-muted">Thuận tiện ghé thăm và trao đổi trực tiếp</p>
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
                    <h3 class="mb-4">Hỗ trợ nhanh cho chuyến đi của bạn</h3>
                    <p class="mb-3">DzungfHotel luôn sẵn sàng đồng hành cùng bạn trong quá trình chọn phòng, sắp xếp lịch lưu trú và chuẩn bị cho kỳ nghỉ hoặc chuyến công tác sắp tới.</p>
                    <ul class="list-check mb-4">
                        <li><i class="fa fa-check-circle"></i>Tư vấn chọn hạng phòng phù hợp theo nhu cầu lưu trú.</li>
                        <li><i class="fa fa-check-circle"></i>Hỗ trợ xác nhận thông tin nhận phòng, trả phòng và số lượng khách.</li>
                        <li><i class="fa fa-check-circle"></i>Giải đáp các thắc mắc liên quan đến đặt phòng và thông tin lưu trú.</li>
                        <li><i class="fa fa-check-circle"></i>Hỗ trợ khách hàng cá nhân và khách đoàn.</li>
                    </ul>
                    <a href="<?= e(url('rooms.php')) ?>" class="btn btn-primary py-3 px-5 me-2">Xem hạng phòng</a>
                    <a href="tel:0905123456" class="btn btn-outline-dark py-3 px-5">Gọi ngay</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
