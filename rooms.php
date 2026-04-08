<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$db = get_db();

$location = trim((string) ($_GET['location'] ?? ''));
$checkIn = trim((string) ($_GET['check_in'] ?? ''));
$checkOut = trim((string) ($_GET['check_out'] ?? ''));
$guestsInput = trim((string) ($_GET['guests'] ?? ''));
$guests = $guestsInput === '' ? 0 : max(1, (int) $guestsInput);

$locations = [];
$locationResult = $db->query("SELECT DISTINCT location FROM rooms ORDER BY location ASC");
if ($locationResult) {
    while ($row = $locationResult->fetch_assoc()) {
        $locations[] = (string) $row['location'];
    }
}

if ($location !== '' && $guests > 0) {
    $stmt = $db->prepare("SELECT id, room_name, room_type, location, price, capacity, image, description, status FROM rooms WHERE status = 'available' AND location LIKE CONCAT('%', ?, '%') AND capacity >= ? ORDER BY price ASC");
    $stmt->bind_param('si', $location, $guests);
} elseif ($location !== '') {
    $stmt = $db->prepare("SELECT id, room_name, room_type, location, price, capacity, image, description, status FROM rooms WHERE status = 'available' AND location LIKE CONCAT('%', ?, '%') ORDER BY price ASC");
    $stmt->bind_param('s', $location);
} elseif ($guests > 0) {
    $stmt = $db->prepare("SELECT id, room_name, room_type, location, price, capacity, image, description, status FROM rooms WHERE status = 'available' AND capacity >= ? ORDER BY price ASC");
    $stmt->bind_param('i', $guests);
} else {
    $stmt = $db->prepare("SELECT id, room_name, room_type, location, price, capacity, image, description, status FROM rooms WHERE status = 'available' ORDER BY price ASC");
}

$rooms = [];
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $rooms[] = $row;
}

$carryFilters = array_filter([
    'location' => $location,
    'check_in' => $checkIn,
    'check_out' => $checkOut,
    'guests' => $guestsInput,
], static fn ($value): bool => $value !== '' && $value !== null);

$filterQuery = http_build_query($carryFilters);

$page_title = 'DzungfHotel | Hạng phòng';
$active_page = 'rooms';
$page_heading = 'Hạng phòng';
$page_eyebrow = 'Lựa chọn lưu trú';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container booking pb-5 wow fadeIn" data-wow-delay="0.1s">
    <div class="bg-white shadow form-card">
        <form class="row g-3 align-items-end" method="get" action="<?= e(url('rooms.php')) ?>">
            <div class="col-lg-3 col-md-6">
                <label for="location" class="form-label fw-semibold">Địa điểm</label>
                <select class="form-select" id="location" name="location">
                    <option value="">Tất cả địa điểm</option>
                    <?php foreach ($locations as $item): ?>
                        <option value="<?= e($item) ?>" <?= selected_option($location, $item) ?>><?= e($item) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3 col-md-6">
                <label for="check_in" class="form-label fw-semibold">Ngày nhận phòng</label>
                <input type="date" class="form-control" id="check_in" name="check_in" value="<?= e($checkIn) ?>" min="<?= e(date('Y-m-d')) ?>">
            </div>
            <div class="col-lg-3 col-md-6">
                <label for="check_out" class="form-label fw-semibold">Ngày trả phòng</label>
                <input type="date" class="form-control" id="check_out" name="check_out" value="<?= e($checkOut) ?>" min="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>">
            </div>
            <div class="col-lg-2 col-md-6">
                <label for="guests" class="form-label fw-semibold">Số khách</label>
                <input type="number" class="form-control" id="guests" name="guests" min="1" max="10" value="<?= e($guestsInput === '' ? '2' : $guestsInput) ?>">
            </div>
            <div class="col-lg-1 col-md-12">
                <button class="btn btn-primary w-100 py-3" type="submit"><i class="fa fa-search"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="container-xxl py-5">
    <div class="container">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4">
            <div class="page-intro">
                <h2 class="mb-2">Tìm hạng phòng phù hợp cho chuyến đi của bạn</h2>
                <p class="mb-0">Lọc theo điểm đến, thời gian lưu trú và số lượng khách để chọn không gian nghỉ ngơi phù hợp. Thông tin bạn vừa tìm kiếm sẽ được giữ lại khi xem chi tiết để thao tác thuận tiện hơn.</p>
            </div>
            <div class="text-lg-end mt-3 mt-lg-0">
                <span class="badge bg-dark fs-6 px-3 py-2"><?= count($rooms) ?> lựa chọn</span>
            </div>
        </div>

        <?php if ($rooms): ?>
            <div class="row g-4">
                <?php foreach ($rooms as $index => $room): ?>
                    <?php
                    $detailUrl = url('room-details.php?id=' . (int) $room['id'] . ($filterQuery ? '&' . $filterQuery : ''));
                    $bookingUrl = url('booking.php?room_id=' . (int) $room['id'] . ($filterQuery ? '&' . $filterQuery : ''));
                    $shortDescription = function_exists('mb_strimwidth')
                        ? mb_strimwidth((string) $room['description'], 0, 120, '...')
                        : substr((string) $room['description'], 0, 120) . '...';
                    ?>
                    <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="<?= e(number_format(0.1 + (($index % 3) * 0.2), 1)) ?>s">
                        <div class="room-item shadow rounded overflow-hidden h-100">
                            <div class="position-relative">
                                <img class="img-fluid" src="<?= e(asset((string) $room['image'])) ?>" alt="<?= e($room['room_name']) ?>">
                                <small class="position-absolute start-0 top-100 translate-middle-y bg-primary text-white rounded py-1 px-3 ms-4"><?= e(format_currency((string) $room['price'])) ?>/đêm</small>
                            </div>
                            <div class="p-4 mt-2">
                                <div class="d-flex justify-content-between mb-3">
                                    <h5 class="mb-0"><?= e($room['room_name']) ?></h5>
                                    <span class="badge <?= e(room_badge_class((string) $room['status'])) ?> status-badge"><?= e(room_status_label((string) $room['status'])) ?></span>
                                </div>
                                <div class="d-flex flex-wrap mb-3">
                                    <small class="border-end me-3 pe-3"><i class="fa fa-bed text-primary me-2"></i><?= e($room['room_type']) ?></small>
                                    <small class="border-end me-3 pe-3"><i class="fa fa-user-friends text-primary me-2"></i><?= e((string) $room['capacity']) ?> khách</small>
                                    <small><i class="fa fa-map-marker-alt text-primary me-2"></i><?= e($room['location']) ?></small>
                                </div>
                                <p class="text-body mb-3"><?= e($shortDescription) ?></p>
                                <div class="d-flex justify-content-between">
                                    <a class="btn btn-sm btn-primary rounded py-2 px-4" href="<?= e($detailUrl) ?>">Xem chi tiết</a>
                                    <a class="btn btn-sm btn-dark rounded py-2 px-4" href="<?= e($bookingUrl) ?>">Chọn phòng này</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon-circle mx-auto mb-3"><i class="fa fa-search"></i></div>
                <h4 class="mb-3">Chưa có phòng phù hợp với tìm kiếm của bạn</h4>
                <p class="mb-0">Bạn hãy thử thay đổi điểm đến, ngày lưu trú hoặc số khách để xem thêm lựa chọn khác.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
