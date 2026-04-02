<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

require_login();

$db = get_db();
$currentUser = current_user();

$availableRooms = [];
$roomLookup = [];
$roomsResult = $db->query("SELECT id, room_name, room_type, location, price, capacity, image, description FROM rooms WHERE status = 'available' ORDER BY room_name ASC");

if ($roomsResult) {
    while ($row = $roomsResult->fetch_assoc()) {
        $availableRooms[] = $row;
        $roomLookup[(int) $row['id']] = $row;
    }
}

$selectedRoomId = (int) ($_POST['room_id'] ?? $_GET['room_id'] ?? 0);
$selectedRoom = $selectedRoomId > 0 ? ($roomLookup[$selectedRoomId] ?? null) : null;

if ($selectedRoomId > 0 && !$selectedRoom) {
    set_flash('danger', 'Phòng đã chọn không còn khả dụng.');
    redirect('rooms.php');
}

$errors = [];
$checkInValue = trim((string) ($_POST['check_in'] ?? $_GET['check_in'] ?? ''));
$checkOutValue = trim((string) ($_POST['check_out'] ?? $_GET['check_out'] ?? ''));
$guestsValue = trim((string) ($_POST['guests'] ?? $_GET['guests'] ?? '1'));

$parseDate = static function (string $value): ?DateTimeImmutable {
    $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    $dateErrors = DateTimeImmutable::getLastErrors();

    if ($date === false) {
        return null;
    }

    if (is_array($dateErrors) && (($dateErrors['warning_count'] ?? 0) > 0 || ($dateErrors['error_count'] ?? 0) > 0)) {
        return null;
    }

    return $date->format('Y-m-d') === $value ? $date : null;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($selectedRoomId <= 0 || !$selectedRoom) {
        $errors[] = 'Vui lòng chọn phòng muốn đặt.';
    }

    $checkInDate = $parseDate($checkInValue);
    $checkOutDate = $parseDate($checkOutValue);
    $today = new DateTimeImmutable('today');

    if (!$checkInDate) {
        $errors[] = 'Ngày nhận phòng không hợp lệ.';
    }

    if (!$checkOutDate) {
        $errors[] = 'Ngày trả phòng không hợp lệ.';
    }

    if ($checkInDate && $checkInDate < $today) {
        $errors[] = 'Ngày nhận phòng phải từ hôm nay trở đi.';
    }

    $guests = filter_var($guestsValue, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    if ($guests === false) {
        $errors[] = 'Số khách phải lớn hơn 0.';
    }

    if ($checkInDate && $checkOutDate && $checkOutDate <= $checkInDate) {
        $errors[] = 'Ngày trả phòng phải sau ngày nhận phòng.';
    }

    if ($guests !== false && $selectedRoom && $guests > (int) $selectedRoom['capacity']) {
        $errors[] = 'Số khách vượt quá sức chứa của phòng đã chọn.';
    }

    if (!$errors && $selectedRoom && $checkInDate && $checkOutDate && $guests !== false) {
        $nights = (int) $checkInDate->diff($checkOutDate)->days;
        $totalPrice = $nights * (float) $selectedRoom['price'];
        $userId = current_user_id();
        $checkInSql = $checkInDate->format('Y-m-d');
        $checkOutSql = $checkOutDate->format('Y-m-d');

        $db->begin_transaction();

        try {
            $insertStmt = $db->prepare("
                INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, total_price, booking_status, payment_status)
                VALUES (?, ?, ?, ?, ?, ?, 'Pending', 'Pending')
            ");
            $insertStmt->bind_param('iissid', $userId, $selectedRoomId, $checkInSql, $checkOutSql, $guests, $totalPrice);

            if (!$insertStmt->execute()) {
                throw new RuntimeException('Không thể tạo booking.');
            }

            $bookingId = (int) $db->insert_id;
            $paymentMethod = 'Chờ cập nhật';
            $paymentStmt = $db->prepare("
                INSERT INTO payments (booking_id, amount, payment_method, payment_status)
                VALUES (?, ?, ?, 'Pending')
            ");
            $paymentStmt->bind_param('ids', $bookingId, $totalPrice, $paymentMethod);

            if (!$paymentStmt->execute()) {
                throw new RuntimeException('Không thể tạo thanh toán.');
            }

            $db->commit();
            set_flash('success', 'Đặt phòng thành công. Đây là trang xác nhận booking của bạn.');
            redirect('booking-confirm.php?id=' . $bookingId);
        } catch (Throwable $exception) {
            $db->rollback();
            $errors[] = 'Không thể lưu booking. Vui lòng thử lại.';
        }
    }
}

$page_title = 'DzungfHotel | Đặt phòng';
$active_page = 'booking';
$page_heading = 'Đặt phòng';
$page_eyebrow = 'Thông tin đặt phòng';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-xxl py-5">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-7">
                <div class="form-card">
                    <h3 class="mb-4">Thông tin booking</h3>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?= e(url('booking.php')) ?>">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="room_id" class="form-label fw-semibold">Chọn phòng</label>
                                <select class="form-select" id="room_id" name="room_id" required>
                                    <option value="">-- Chọn phòng muốn đặt --</option>
                                    <?php foreach ($availableRooms as $room): ?>
                                        <option value="<?= e((string) $room['id']) ?>" <?= (int) $room['id'] === $selectedRoomId ? 'selected' : '' ?>>
                                            <?= e((string) $room['room_name']) ?> - <?= e(format_currency((string) $room['price'])) ?>/đêm
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="check_in" class="form-label fw-semibold">Ngày nhận phòng</label>
                                <input type="date" class="form-control" id="check_in" name="check_in" value="<?= e($checkInValue) ?>" min="<?= e(date('Y-m-d')) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="check_out" class="form-label fw-semibold">Ngày trả phòng</label>
                                <input type="date" class="form-control" id="check_out" name="check_out" value="<?= e($checkOutValue) ?>" min="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="guests" class="form-label fw-semibold">Số khách</label>
                                <input type="number" class="form-control" id="guests" name="guests" min="1" max="10" value="<?= e($guestsValue === '' ? '1' : $guestsValue) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Khách đặt</label>
                                <input type="text" class="form-control" value="<?= e((string) $currentUser['full_name']) ?> - <?= e((string) $currentUser['email']) ?>" readonly>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary w-100 py-3" type="submit">Xác nhận đặt phòng</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-5">
                <?php if ($selectedRoom): ?>
                    <div class="room-sidebar-card mb-4">
                        <img class="img-fluid rounded room-card-image mb-4" src="<?= e(asset((string) $selectedRoom['image'])) ?>" alt="<?= e((string) $selectedRoom['room_name']) ?>">
                        <h4 class="mb-3"><?= e((string) $selectedRoom['room_name']) ?></h4>
                        <ul class="list-check mb-4">
                            <li><i class="fa fa-check-circle"></i>Loại phòng: <?= e((string) $selectedRoom['room_type']) ?></li>
                            <li><i class="fa fa-check-circle"></i>Địa điểm: <?= e((string) $selectedRoom['location']) ?></li>
                            <li><i class="fa fa-check-circle"></i>Sức chứa tối đa: <?= e((string) $selectedRoom['capacity']) ?> khách</li>
                            <li><i class="fa fa-check-circle"></i>Giá mỗi đêm: <?= e(format_currency((string) $selectedRoom['price'])) ?></li>
                            <li><i class="fa fa-check-circle"></i>Thanh toán mặc định: Chờ thanh toán</li>
                        </ul>
                        <p class="mb-0"><?= e((string) $selectedRoom['description']) ?></p>
                    </div>
                <?php else: ?>
                    <div class="summary-card mb-4">
                        <h4 class="mb-3">Chọn phòng trước khi đặt</h4>
                        <p class="mb-0">Bạn có thể chọn trực tiếp từ danh sách ở form bên trái hoặc quay lại trang Phòng để xem thông tin chi tiết trước khi booking.</p>
                    </div>
                <?php endif; ?>

                <div class="summary-card">
                    <h5 class="mb-3">Thông tin cần biết</h5>
                    <ul class="list-check mb-0">
                        <li><i class="fa fa-check-circle"></i>Booking mới sẽ mặc định ở trạng thái chờ xác nhận.</li>
                        <li><i class="fa fa-check-circle"></i>Tổng tiền được tính theo số đêm nhân với giá phòng.</li>
                        <li><i class="fa fa-check-circle"></i>Sau khi đặt thành công, hệ thống chuyển đến trang xác nhận booking.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
