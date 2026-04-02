<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$roomId = (int) ($_GET['id'] ?? 0);

if ($roomId <= 0) {
    set_flash('danger', 'Phòng bạn chọn không hợp lệ.');
    redirect('rooms.php');
}

$db = get_db();

$stmt = $db->prepare("SELECT id, room_name, room_type, location, price, capacity, image, description, status FROM rooms WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $roomId);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) {
    set_flash('danger', 'Không tìm thấy phòng bạn cần xem.');
    redirect('rooms.php');
}

$amenities = [];
$amenityStmt = $db->prepare("
    SELECT a.amenity_name, a.icon
    FROM room_amenities ra
    INNER JOIN amenities a ON ra.amenity_id = a.id
    WHERE ra.room_id = ?
    ORDER BY a.id ASC
");
$amenityStmt->bind_param('i', $roomId);
$amenityStmt->execute();
$amenityResult = $amenityStmt->get_result();
while ($row = $amenityResult->fetch_assoc()) {
    $amenities[] = $row;
}

$relatedRooms = [];
$relatedStmt = $db->prepare("SELECT id, room_name, room_type, location, price, capacity, image FROM rooms WHERE status = 'available' AND id <> ? ORDER BY id ASC LIMIT 2");
$relatedStmt->bind_param('i', $roomId);
$relatedStmt->execute();
$relatedResult = $relatedStmt->get_result();
while ($row = $relatedResult->fetch_assoc()) {
    $relatedRooms[] = $row;
}

$carryFilters = array_filter([
    'check_in' => trim((string) ($_GET['check_in'] ?? '')),
    'check_out' => trim((string) ($_GET['check_out'] ?? '')),
    'guests' => trim((string) ($_GET['guests'] ?? '')),
    'location' => trim((string) ($_GET['location'] ?? '')),
], static fn ($value): bool => $value !== '' && $value !== null);

$bookingQuery = http_build_query(array_merge(['room_id' => $roomId], $carryFilters));

$page_title = 'DzungfHotel | Chi tiết phòng';
$active_page = 'room-details';
$page_heading = 'Chi tiết phòng';
$page_eyebrow = (string) $room['room_type'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-xxl py-5">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-7">
                <img class="img-fluid rounded shadow room-card-image feature-image mb-4" src="<?= e(asset((string) $room['image'])) ?>" alt="<?= e($room['room_name']) ?>">
                <div class="info-card">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                        <div>
                            <h2 class="mb-2"><?= e((string) $room['room_name']) ?></h2>
                            <p class="mb-0 text-muted"><?= e((string) $room['room_type']) ?> tại <?= e((string) $room['location']) ?></p>
                        </div>
                        <div class="mt-3 mt-md-0">
                            <span class="badge <?= e(room_badge_class((string) $room['status'])) ?> status-badge"><?= e(room_status_label((string) $room['status'])) ?></span>
                        </div>
                    </div>
                    <p class="mb-4"><?= e((string) $room['description']) ?></p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="stat-box text-center h-100">
                                <div class="icon-circle mx-auto mb-3"><i class="fa fa-user-friends"></i></div>
                                <h5 class="mb-1"><?= e((string) $room['capacity']) ?> khách</h5>
                                <p class="mb-0">Sức chứa tối đa</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-box text-center h-100">
                                <div class="icon-circle mx-auto mb-3"><i class="fa fa-map-marker-alt"></i></div>
                                <h5 class="mb-1"><?= e((string) $room['location']) ?></h5>
                                <p class="mb-0">Địa điểm phòng</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-box text-center h-100">
                                <div class="icon-circle mx-auto mb-3"><i class="fa fa-money-bill-wave"></i></div>
                                <h5 class="mb-1"><?= e(format_currency((string) $room['price'])) ?></h5>
                                <p class="mb-0">Giá mỗi đêm</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="room-sidebar-card mb-4">
                    <h4 class="mb-4">Thông tin nhanh</h4>
                    <ul class="list-check mb-4">
                        <li><i class="fa fa-check-circle"></i>Loại phòng: <?= e((string) $room['room_type']) ?></li>
                        <li><i class="fa fa-check-circle"></i>Sức chứa: <?= e((string) $room['capacity']) ?> khách</li>
                        <li><i class="fa fa-check-circle"></i>Giá tham khảo: <?= e(format_currency((string) $room['price'])) ?>/đêm</li>
                        <li><i class="fa fa-check-circle"></i>Trạng thái hiện tại: <?= e(room_status_label((string) $room['status'])) ?></li>
                    </ul>
                    <a href="<?= e(url('booking.php?' . $bookingQuery)) ?>" class="btn btn-primary w-100 py-3 mb-3">Đặt ngay</a>
                    <a href="<?= e(url('rooms.php')) ?>" class="btn btn-outline-dark w-100 py-3">Quay lại danh sách phòng</a>
                </div>
                <div class="summary-card">
                    <h5 class="mb-3">Tiện nghi nổi bật</h5>
                    <?php if ($amenities): ?>
                        <ul class="list-check mb-0">
                            <?php foreach ($amenities as $amenity): ?>
                                <li><i class="<?= e((string) ($amenity['icon'] ?: 'fa fa-check-circle')) ?>"></i><?= e((string) $amenity['amenity_name']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="mb-0">Phòng được trang bị đầy đủ các tiện nghi cơ bản để mang lại trải nghiệm lưu trú thoải mái.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($relatedRooms): ?>
    <div class="container-xxl py-5 bg-soft">
        <div class="container">
            <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                <h6 class="section-title text-center text-primary text-uppercase">Phòng liên quan</h6>
                <h1 class="mb-5">Thêm lựa chọn khác cho <span class="text-primary text-uppercase">bạn</span></h1>
            </div>
            <div class="row g-4">
                <?php foreach ($relatedRooms as $index => $item): ?>
                    <div class="col-md-6 wow fadeInUp" data-wow-delay="<?= e(number_format(0.1 + ($index * 0.2), 1)) ?>s">
                        <div class="room-item shadow rounded overflow-hidden h-100">
                            <div class="position-relative">
                                <img class="img-fluid" src="<?= e(asset((string) $item['image'])) ?>" alt="<?= e((string) $item['room_name']) ?>">
                                <small class="position-absolute start-0 top-100 translate-middle-y bg-primary text-white rounded py-1 px-3 ms-4"><?= e(format_currency((string) $item['price'])) ?>/đêm</small>
                            </div>
                            <div class="p-4 mt-2">
                                <h5 class="mb-3"><?= e((string) $item['room_name']) ?></h5>
                                <div class="d-flex flex-wrap mb-3">
                                    <small class="border-end me-3 pe-3"><i class="fa fa-bed text-primary me-2"></i><?= e((string) $item['room_type']) ?></small>
                                    <small class="border-end me-3 pe-3"><i class="fa fa-user-friends text-primary me-2"></i><?= e((string) $item['capacity']) ?> khách</small>
                                    <small><i class="fa fa-map-marker-alt text-primary me-2"></i><?= e((string) $item['location']) ?></small>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <a class="btn btn-sm btn-primary rounded py-2 px-4" href="<?= e(url('room-details.php?id=' . (int) $item['id'])) ?>">Chi tiết</a>
                                    <a class="btn btn-sm btn-dark rounded py-2 px-4" href="<?= e(url('booking.php?room_id=' . (int) $item['id'])) ?>">Đặt ngay</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
