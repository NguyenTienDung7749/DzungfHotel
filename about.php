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

$page_title = 'DzungfHotel | Về chúng tôi';
$active_page = 'about';
$page_heading = 'Về chúng tôi';
$page_eyebrow = 'DzungfHotel';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-xxl py-5">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <h6 class="section-title text-start text-primary text-uppercase">DzungfHotel</h6>
                <h1 class="mb-4">Điểm dừng chân tiện nghi cho những chuyến đi đáng nhớ</h1>
                <p class="mb-4">DzungfHotel là lựa chọn lưu trú phù hợp cho du khách tìm kiếm không gian nghỉ ngơi hiện đại, thoải mái và thuận tiện. Chúng tôi chú trọng trải nghiệm xem phòng rõ ràng, đặt phòng nhanh chóng và dịch vụ thân thiện cho cả kỳ nghỉ lẫn chuyến công tác.</p>
                <ul class="list-check mb-4">
                    <li><i class="fa fa-check-circle"></i>Dễ dàng khám phá các hạng phòng, tiện nghi nổi bật và mức giá tham khảo rõ ràng.</li>
                    <li><i class="fa fa-check-circle"></i>Gửi yêu cầu đặt phòng thuận tiện ngay trên website, phù hợp cho lưu trú ngắn ngày lẫn dài ngày.</li>
                    <li><i class="fa fa-check-circle"></i>Không gian phù hợp cho khách du lịch, gia đình và khách công tác cần sự linh hoạt.</li>
                </ul>
                <a href="<?= e(url('rooms.php')) ?>" class="btn btn-primary py-3 px-5">Khám phá hạng phòng</a>
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
                    <p class="mb-0">Hạng phòng đang mở đặt</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box text-center h-100">
                    <div class="icon-circle mx-auto mb-3"><i class="fa fa-hourglass-half"></i></div>
                    <h3 class="mb-2"><?= $summary['pending'] ?></h3>
                    <p class="mb-0">Yêu cầu đang tiếp nhận</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box text-center h-100">
                    <div class="icon-circle mx-auto mb-3"><i class="fa fa-wallet"></i></div>
                    <h3 class="mb-2"><?= $summary['paid'] ?></h3>
                    <p class="mb-0">Lượt đặt đã hoàn tất</p>
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
                    <h3 class="mb-4">Trải nghiệm nổi bật tại DzungfHotel</h3>
                    <p class="mb-3">Từ trang chủ, bạn có thể nhanh chóng khám phá các hạng phòng, so sánh thông tin lưu trú và chọn lựa không gian phù hợp với lịch trình của mình.</p>
                    <p class="mb-0">Chúng tôi ưu tiên sự rõ ràng trong thông tin, thao tác đặt phòng thuận tiện và cảm giác an tâm trước khi bạn bắt đầu hành trình lưu trú tại DzungfHotel.</p>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="summary-card h-100">
                    <h4 class="mb-4">Lý do khách hàng lựa chọn DzungfHotel</h4>
                    <ul class="list-check mb-0">
                        <li><i class="fa fa-check-circle"></i>Không gian lưu trú hiện đại, phù hợp cho nghỉ dưỡng, du lịch và công tác.</li>
                        <li><i class="fa fa-check-circle"></i>Thông tin phòng rõ ràng với tiện nghi, sức chứa và mức giá tham khảo minh bạch.</li>
                        <li><i class="fa fa-check-circle"></i>Quy trình gửi yêu cầu đặt phòng gọn gàng, dễ thao tác trên cả máy tính và điện thoại.</li>
                        <li><i class="fa fa-check-circle"></i>Dễ dàng theo dõi thông tin lưu trú và xác nhận cần thiết trong tài khoản cá nhân.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
