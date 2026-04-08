<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$db = get_db();

$stats = [
    'rooms' => 0,
    'customers' => 0,
    'bookings' => 0,
];

if ($result = $db->query("SELECT COUNT(*) AS total FROM rooms WHERE status = 'available'")) {
    $stats['rooms'] = (int) ($result->fetch_assoc()['total'] ?? 0);
}

if ($result = $db->query("SELECT COUNT(*) AS total FROM users WHERE role = 'customer'")) {
    $stats['customers'] = (int) ($result->fetch_assoc()['total'] ?? 0);
}

if ($result = $db->query("SELECT COUNT(*) AS total FROM bookings")) {
    $stats['bookings'] = (int) ($result->fetch_assoc()['total'] ?? 0);
}

$featuredRooms = [];
$roomQuery = $db->query("SELECT id, room_name, room_type, location, price, capacity, image, description FROM rooms WHERE status = 'available' ORDER BY id ASC LIMIT 3");

if ($roomQuery) {
    while ($row = $roomQuery->fetch_assoc()) {
        $featuredRooms[] = $row;
    }
}

$page_title = 'DzungfHotel | Trang chủ';
$active_page = 'home';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid p-0 mb-5">
    <div id="header-carousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img class="w-100" src="<?= e(asset('img/carousel-1.jpg')) ?>" alt="DzungfHotel">
                <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                    <div class="p-3 hero-caption">
                        <h5 class="section-title text-white text-uppercase mb-3 animated slideInDown">DzungfHotel</h5>
                        <h1 class="display-3 text-white mb-4 animated slideInDown">Không gian lưu trú tiện nghi cho kỳ nghỉ trọn vẹn</h1>
                        <p class="fs-5 text-white mb-4 animated slideInDown">Khám phá các hạng phòng hiện đại, vị trí thuận tiện và trải nghiệm đặt phòng nhanh chóng ngay trên website của khách sạn.</p>
                        <a href="<?= e(url('rooms.php')) ?>" class="btn btn-primary py-md-3 px-md-5 me-3 animated slideInLeft">Xem các hạng phòng</a>
                        <a href="<?= e(url('rooms.php')) ?>" class="btn btn-light py-md-3 px-md-5 animated slideInRight">Đặt phòng ngay</a>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <img class="w-100" src="<?= e(asset('img/carousel-2.jpg')) ?>" alt="Đặt phòng">
                <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                    <div class="p-3 hero-caption">
                        <h5 class="section-title text-white text-uppercase mb-3 animated slideInDown">Kỳ nghỉ thư thái giữa miền Trung</h5>
                        <h1 class="display-3 text-white mb-4 animated slideInDown">Lựa chọn lưu trú phù hợp cho cả du lịch và công tác</h1>
                        <p class="fs-5 text-white mb-4 animated slideInDown">Tận hưởng không gian nghỉ ngơi sạch đẹp, tiện nghi và dễ dàng chọn phòng phù hợp với lịch trình của bạn.</p>
                        <a href="<?= e(url('rooms.php')) ?>" class="btn btn-primary py-md-3 px-md-5 me-3 animated slideInLeft">Khám phá phòng nghỉ</a>
                        <a href="<?= e(url('contact.php')) ?>" class="btn btn-light py-md-3 px-md-5 animated slideInRight">Liên hệ tư vấn</a>
                    </div>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#header-carousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Trước</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#header-carousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Sau</span>
        </button>
    </div>
</div>

<div class="container booking booking-quick-form pb-5 wow fadeIn" data-wow-delay="0.1s">
    <div class="bg-white shadow form-card">
        <form class="row g-3 align-items-end" method="get" action="<?= e(url('rooms.php')) ?>">
            <div class="col-lg-3 col-md-6">
                <label for="home_check_in" class="form-label fw-semibold">Ngày nhận phòng</label>
                <input type="date" class="form-control" id="home_check_in" name="check_in" min="<?= e(date('Y-m-d')) ?>">
            </div>
            <div class="col-lg-3 col-md-6">
                <label for="home_check_out" class="form-label fw-semibold">Ngày trả phòng</label>
                <input type="date" class="form-control" id="home_check_out" name="check_out" min="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>">
            </div>
            <div class="col-lg-2 col-md-6">
                <label for="home_guests" class="form-label fw-semibold">Số khách</label>
                <input type="number" class="form-control" id="home_guests" name="guests" min="1" max="5" value="2">
            </div>
            <div class="col-lg-2 col-md-6">
                <label for="home_location" class="form-label fw-semibold">Địa điểm</label>
                <select class="form-select" id="home_location" name="location">
                    <option value="">Tất cả</option>
                    <option value="Đà Nẵng">Đà Nẵng</option>
                    <option value="Hội An">Hội An</option>
                    <option value="Huế">Huế</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-12">
                <button class="btn btn-primary w-100 py-3" type="submit">Tìm phòng</button>
            </div>
        </form>
    </div>
</div>

<div class="container-xxl py-5">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <h6 class="section-title text-start text-primary text-uppercase">Về chúng tôi</h6>
                <h1 class="mb-4">Chào mừng đến với <span class="text-primary text-uppercase">DzungfHotel</span></h1>
                <p class="mb-4">DzungfHotel mang đến trải nghiệm lưu trú tiện nghi, hiện đại và thuận tiện cho du khách. Website hỗ trợ bạn dễ dàng tìm hạng phòng phù hợp, xem thông tin chi tiết và gửi yêu cầu đặt phòng nhanh chóng cho mỗi chuyến đi.</p>
                <div class="row g-3 pb-4">
                    <div class="col-sm-4 wow fadeIn" data-wow-delay="0.1s">
                        <div class="border rounded p-1">
                            <div class="border rounded text-center p-4">
                                <i class="fa fa-hotel fa-2x text-primary mb-2"></i>
                                <h2 class="mb-1" data-toggle="counter-up"><?= $stats['rooms'] ?></h2>
                                <p class="mb-0">Phòng đang mở đặt</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4 wow fadeIn" data-wow-delay="0.3s">
                        <div class="border rounded p-1">
                            <div class="border rounded text-center p-4">
                                <i class="fa fa-user-check fa-2x text-primary mb-2"></i>
                                <h2 class="mb-1" data-toggle="counter-up"><?= $stats['customers'] ?></h2>
                                <p class="mb-0">Lượt khách</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4 wow fadeIn" data-wow-delay="0.5s">
                        <div class="border rounded p-1">
                            <div class="border rounded text-center p-4">
                                <i class="fa fa-calendar-check fa-2x text-primary mb-2"></i>
                                <h2 class="mb-1" data-toggle="counter-up"><?= $stats['bookings'] ?></h2>
                                <p class="mb-0">Lượt đặt phòng</p>
                            </div>
                        </div>
                    </div>
                </div>
                <a class="btn btn-primary py-3 px-5 mt-2" href="<?= e(url('about.php')) ?>">Tìm hiểu thêm</a>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <div class="col-6 text-end">
                        <img class="img-fluid rounded w-75 wow zoomIn" data-wow-delay="0.1s" src="<?= e(asset('img/about-1.jpg')) ?>" style="margin-top: 25%;" alt="Không gian khách sạn">
                    </div>
                    <div class="col-6 text-start">
                        <img class="img-fluid rounded w-100 wow zoomIn" data-wow-delay="0.3s" src="<?= e(asset('img/about-2.jpg')) ?>" alt="Phòng nghỉ sang trọng">
                    </div>
                    <div class="col-6 text-end">
                        <img class="img-fluid rounded w-50 wow zoomIn" data-wow-delay="0.5s" src="<?= e(asset('img/about-3.jpg')) ?>" alt="Nội thất hiện đại">
                    </div>
                    <div class="col-6 text-start">
                        <img class="img-fluid rounded w-75 wow zoomIn" data-wow-delay="0.7s" src="<?= e(asset('img/about-4.jpg')) ?>" alt="Tiện ích khách sạn">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-xxl py-5 bg-soft">
    <div class="container">
        <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
            <h6 class="section-title text-center text-primary text-uppercase">Phòng nổi bật</h6>
            <h1 class="mb-5">Lựa chọn được đặt <span class="text-primary text-uppercase">nhiều nhất</span></h1>
        </div>
        <div class="row g-4">
            <?php foreach ($featuredRooms as $index => $room): ?>
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="<?= e(number_format(0.1 + ($index * 0.2), 1)) ?>s">
                    <div class="room-item shadow rounded overflow-hidden h-100">
                        <div class="position-relative">
                            <img class="img-fluid" src="<?= e(asset((string) $room['image'])) ?>" alt="<?= e($room['room_name']) ?>">
                            <small class="position-absolute start-0 top-100 translate-middle-y bg-primary text-white rounded py-1 px-3 ms-4"><?= e(format_currency((string) $room['price'])) ?>/đêm</small>
                        </div>
                        <div class="p-4 mt-2">
                            <div class="d-flex justify-content-between mb-3">
                                <h5 class="mb-0"><?= e($room['room_name']) ?></h5>
                                <div class="ps-2">
                                    <small class="fa fa-star text-primary"></small>
                                    <small class="fa fa-star text-primary"></small>
                                    <small class="fa fa-star text-primary"></small>
                                    <small class="fa fa-star text-primary"></small>
                                    <small class="fa fa-star text-primary"></small>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap mb-3 room-amenities">
                                <small class="border-end me-3 pe-3"><i class="fa fa-bed text-primary me-2"></i><?= e($room['room_type']) ?></small>
                                <small class="border-end me-3 pe-3"><i class="fa fa-user-friends text-primary me-2"></i><?= e((string) $room['capacity']) ?> khách</small>
                                <small><i class="fa fa-map-marker-alt text-primary me-2"></i><?= e($room['location']) ?></small>
                            </div>
                            <p class="text-body mb-3"><?= e(function_exists('mb_strimwidth') ? mb_strimwidth((string) $room['description'], 0, 110, '...') : substr((string) $room['description'], 0, 110) . '...') ?></p>
                            <div class="d-flex justify-content-between">
                                <a class="btn btn-sm btn-primary rounded py-2 px-4" href="<?= e(url('room-details.php?id=' . (int) $room['id'])) ?>">Xem chi tiết</a>
                                <a class="btn btn-sm btn-dark rounded py-2 px-4" href="<?= e(url('booking.php?room_id=' . (int) $room['id'])) ?>">Chọn phòng này</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="container-xxl py-5">
    <div class="container">
        <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
            <h6 class="section-title text-center text-primary text-uppercase">Điểm nổi bật</h6>
            <h1 class="mb-5">3 điểm mạnh của <span class="text-primary text-uppercase">DzungfHotel</span></h1>
        </div>
        <div class="row g-4">
            <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
                <div class="service-item rounded">
                    <div class="service-icon bg-transparent border rounded p-1">
                        <div class="w-100 h-100 border rounded d-flex align-items-center justify-content-center">
                            <i class="fa fa-map-marker-alt fa-2x text-primary"></i>
                        </div>
                    </div>
                    <h5 class="mb-3">Vị trí thuận tiện</h5>
                    <p class="text-body mb-0">Dễ dàng di chuyển đến trung tâm, khu mua sắm và các địa điểm tham quan nổi bật trong khu vực.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.3s">
                <div class="service-item rounded">
                    <div class="service-icon bg-transparent border rounded p-1">
                        <div class="w-100 h-100 border rounded d-flex align-items-center justify-content-center">
                            <i class="fa fa-bed fa-2x text-primary"></i>
                        </div>
                    </div>
                    <h5 class="mb-3">Phòng nghỉ tiện nghi</h5>
                    <p class="text-body mb-0">Không gian lưu trú sạch sẽ, hiện đại và thoải mái, phù hợp cho cả khách du lịch lẫn khách công tác.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.5s">
                <div class="service-item rounded">
                    <div class="service-icon bg-transparent border rounded p-1">
                        <div class="w-100 h-100 border rounded d-flex align-items-center justify-content-center">
                            <i class="fa fa-calendar-check fa-2x text-primary"></i>
                        </div>
                    </div>
                    <h5 class="mb-3">Đặt phòng nhanh chóng</h5>
                    <p class="text-body mb-0">Chọn phòng, chọn ngày lưu trú và gửi yêu cầu đặt phòng dễ dàng ngay trên website của khách sạn.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid bg-dark py-5">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <h2 class="text-white mb-3">Đặt phòng trực tuyến nhanh chóng và thuận tiện</h2>
                <p class="text-white mb-0">Chọn phòng phù hợp, gửi yêu cầu đặt phòng và nhận thông tin xác nhận ngay trên website của khách sạn.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="<?= e(url('rooms.php')) ?>" class="btn btn-primary py-3 px-5 me-2">Đặt phòng ngay</a>
                <a href="<?= e(url('rooms.php')) ?>" class="btn btn-light py-3 px-5">Xem các hạng phòng</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
