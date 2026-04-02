<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$db = get_db();

$summary = [
    'rooms' => 0,
    'pending' => 0,
    'paid' => 0,
];

if ($result = $db->query("SELECT COUNT(*) AS total FROM rooms WHERE status = 'available'")) {
    $summary['rooms'] = (int) ($result->fetch_assoc()['total'] ?? 0);
}

if ($result = $db->query("SELECT COUNT(*) AS total FROM bookings WHERE booking_status = 'Pending'")) {
    $summary['pending'] = (int) ($result->fetch_assoc()['total'] ?? 0);
}

if ($result = $db->query("SELECT COUNT(*) AS total FROM bookings WHERE payment_status = 'Paid'")) {
    $summary['paid'] = (int) ($result->fetch_assoc()['total'] ?? 0);
}

$page_title = 'DzungfHotel | Giới thiệu';
$active_page = 'about';
$page_heading = 'Giới thiệu';
$page_eyebrow = 'Về DzungfHotel';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-xxl py-5">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <h6 class="section-title text-start text-primary text-uppercase">DzungfHotel</h6>
                <h1 class="mb-4">Không gian lưu trú tiện nghi, quy trình đặt phòng rõ ràng</h1>
                <p class="mb-4">DzungfHotel được xây dựng theo định hướng website khách sạn hiện đại, giúp khách hàng dễ dàng tìm phòng, xem chi tiết, đặt phòng và theo dõi tình trạng booking trên cùng một nền tảng.</p>
                <ul class="list-check mb-4">
                    <li><i class="fa fa-check-circle"></i>Hỗ trợ đăng ký, đăng nhập, đăng xuất và xem hồ sơ cá nhân.</li>
                    <li><i class="fa fa-check-circle"></i>Cho phép xem danh sách phòng, chi tiết phòng, đặt phòng và xem trang xác nhận.</li>
                    <li><i class="fa fa-check-circle"></i>Có trang admin để xem toàn bộ danh sách booking và trạng thái thanh toán.</li>
                </ul>
                <a href="<?= e(url('rooms.php')) ?>" class="btn btn-primary py-3 px-5">Khám phá phòng</a>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <div class="col-6 text-end">
                        <img class="img-fluid rounded w-75 wow zoomIn" data-wow-delay="0.1s" src="<?= e(asset('img/about-1.jpg')) ?>" style="margin-top: 25%;" alt="DzungfHotel">
                    </div>
                    <div class="col-6 text-start">
                        <img class="img-fluid rounded w-100 wow zoomIn" data-wow-delay="0.3s" src="<?= e(asset('img/about-2.jpg')) ?>" alt="Tiện nghi">
                    </div>
                    <div class="col-6 text-end">
                        <img class="img-fluid rounded w-50 wow zoomIn" data-wow-delay="0.5s" src="<?= e(asset('img/about-3.jpg')) ?>" alt="Nội thất">
                    </div>
                    <div class="col-6 text-start">
                        <img class="img-fluid rounded w-75 wow zoomIn" data-wow-delay="0.7s" src="<?= e(asset('img/about-4.jpg')) ?>" alt="Không gian">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-xxl py-5 bg-soft">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="stat-box text-center h-100">
                    <div class="icon-circle mx-auto mb-3"><i class="fa fa-door-open"></i></div>
                    <h3 class="mb-2"><?= $summary['rooms'] ?></h3>
                    <p class="mb-0">Phòng đang mở bán</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box text-center h-100">
                    <div class="icon-circle mx-auto mb-3"><i class="fa fa-hourglass-half"></i></div>
                    <h3 class="mb-2"><?= $summary['pending'] ?></h3>
                    <p class="mb-0">Booking đang chờ xử lý</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box text-center h-100">
                    <div class="icon-circle mx-auto mb-3"><i class="fa fa-wallet"></i></div>
                    <h3 class="mb-2"><?= $summary['paid'] ?></h3>
                    <p class="mb-0">Booking đã thanh toán</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-xxl py-5">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-7">
                <div class="info-card h-100">
                    <h3 class="mb-4">Định hướng vận hành</h3>
                    <p class="mb-3">Khách hàng có thể bắt đầu từ trang chủ, mở danh sách phòng, chọn một phòng phù hợp, thực hiện booking và quay lại hồ sơ để xem lịch sử đặt phòng của riêng mình.</p>
                    <p class="mb-0">Giao diện hiển thị hoàn toàn bằng tiếng Việt, thông tin phòng rõ ràng và tập trung vào những chức năng cần thiết cho một hệ thống đặt phòng khách sạn.</p>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="summary-card h-100">
                    <h4 class="mb-4">Chức năng chính của hệ thống</h4>
                    <ul class="list-check mb-0">
                        <li><i class="fa fa-check-circle"></i>Xác thực người dùng: đăng ký, đăng nhập, đăng xuất.</li>
                        <li><i class="fa fa-check-circle"></i>Quản lý đặt phòng: xem phòng, chi tiết phòng, đặt phòng, xác nhận.</li>
                        <li><i class="fa fa-check-circle"></i>Báo cáo quản trị: xem bảng booking tổng hợp.</li>
                        <li><i class="fa fa-check-circle"></i>Thanh toán mô phỏng: theo dõi trạng thái thanh toán ngay trong hệ thống.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
