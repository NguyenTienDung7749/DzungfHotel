<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

require_login();

$db = get_db();
$userId = current_user_id();

$userStmt = $db->prepare("SELECT id, full_name, email, role, created_at FROM users WHERE id = ? LIMIT 1");
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

$bookingStmt = $db->prepare("
    SELECT b.id, b.check_in, b.check_out, b.guests, b.total_price, b.booking_status, b.payment_status, b.created_at,
           r.room_name, r.room_type, r.location,
           p.payment_method, p.transaction_code
    FROM bookings b
    INNER JOIN rooms r ON b.room_id = r.id
    LEFT JOIN payments p ON p.booking_id = b.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC, b.id DESC
");
$bookingStmt->bind_param('i', $userId);
$bookingStmt->execute();

$bookings = [];
$bookingResult = $bookingStmt->get_result();
while ($row = $bookingResult->fetch_assoc()) {
    $bookings[] = $row;
}
$bookingStmt->close();

$page_title = 'DzungfHotel | Tài khoản của tôi';
$active_page = 'profile';
$page_heading = 'Tài khoản của tôi';
$page_eyebrow = 'Thông tin lưu trú';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-xxl py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="profile-card h-100">
                    <div class="text-center mb-4">
                        <div class="icon-circle mx-auto mb-3"><i class="fa fa-user"></i></div>
                        <h3 class="mb-1"><?= e((string) $user['full_name']) ?></h3>
                        <p class="text-muted mb-0"><?= e((string) $user['email']) ?></p>
                    </div>
                    <ul class="list-check mb-4">
                        <li><i class="fa fa-check-circle"></i>Loại tài khoản: <?= e((string) $user['role'] === 'admin' ? 'Quản trị viên' : 'Khách lưu trú') ?></li>
                        <li><i class="fa fa-check-circle"></i>Thành viên từ: <?= e(format_datetime((string) $user['created_at'])) ?></li>
                        <li><i class="fa fa-check-circle"></i>Lượt đặt phòng: <?= count($bookings) ?></li>
                    </ul>
                    <a href="<?= e(url('rooms.php')) ?>" class="btn btn-primary w-100 py-3 mb-3">Khám phá hạng phòng</a>
                    <a href="<?= e(url('rooms.php')) ?>" class="btn btn-outline-dark w-100 py-3">Xem danh sách phòng</a>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="table-card h-100">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                        <div>
                            <h3 class="mb-2">Lịch sử đặt phòng</h3>
                            <p class="mb-0 text-muted">Theo dõi các yêu cầu lưu trú, trạng thái xác nhận và thông tin thanh toán của từng lần đặt phòng.</p>
                        </div>
                        <span class="badge bg-dark fs-6 px-3 py-2 mt-3 mt-md-0"><?= count($bookings) ?> lượt đặt</span>
                    </div>

                    <?php if ($bookings): ?>
                        <div class="booking-history-list">
                            <?php foreach ($bookings as $booking): ?>
                                <?php
                                $bookingStatus = (string) $booking['booking_status'];
                                $paymentStatus = (string) $booking['payment_status'];
                                $canPay = $bookingStatus === 'Confirmed' && $paymentStatus === 'Pending';
                                $paymentMethod = normalize_payment_method((string) ($booking['payment_method'] ?? ''), $bookingStatus, $paymentStatus);
                                ?>
                                <div class="booking-history-item">
                                    <div class="booking-history-header">
                                        <div>
                                            <div class="booking-history-label">Mã đặt phòng #<?= e((string) $booking['id']) ?></div>
                                            <h4 class="booking-history-title"><?= e((string) $booking['room_name']) ?></h4>
                                            <p class="booking-history-subtitle mb-0"><?= e((string) $booking['room_type']) ?> • <?= e((string) $booking['location']) ?></p>
                                        </div>

                                        <div class="booking-history-badges">
                                            <span class="badge <?= e(booking_badge_class($bookingStatus)) ?> status-badge"><?= e(booking_status_label($bookingStatus)) ?></span>
                                            <span class="badge <?= e(booking_payment_badge_class($bookingStatus, $paymentStatus)) ?> status-badge"><?= e(booking_payment_status_label($bookingStatus, $paymentStatus)) ?></span>
                                        </div>
                                    </div>

                                    <div class="row g-3 booking-history-grid">
                                        <div class="col-md-6 col-xl-3">
                                            <div class="booking-info-chip">
                                                <span class="booking-info-chip-label">Ngày ở</span>
                                                <strong><?= e(format_date((string) $booking['check_in'])) ?></strong>
                                                <small>đến <?= e(format_date((string) $booking['check_out'])) ?></small>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xl-3">
                                            <div class="booking-info-chip">
                                                <span class="booking-info-chip-label">Số khách</span>
                                                <strong><?= e((string) $booking['guests']) ?> khách</strong>
                                                <small>Tạo lúc <?= e(format_datetime((string) $booking['created_at'])) ?></small>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xl-3">
                                            <div class="booking-info-chip">
                                                <span class="booking-info-chip-label">Tổng tiền</span>
                                                <strong><?= e(format_currency((string) $booking['total_price'])) ?></strong>
                                                <small>Thanh toán cho toàn bộ kỳ ở</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xl-3">
                                            <div class="booking-info-chip">
                                                <span class="booking-info-chip-label">Phương thức</span>
                                                <strong><?= e($paymentMethod) ?></strong>
                                                <small><?= e((string) (($booking['transaction_code'] ?? '') !== '' ? $booking['transaction_code'] : 'Chưa có mã giao dịch')) ?></small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="booking-history-actions">
                                        <a href="<?= e(url('booking-confirm.php?id=' . (int) $booking['id'])) ?>" class="btn btn-outline-dark">Xem chi tiết</a>
                                        <?php if ($canPay): ?>
                                            <a href="<?= e(url('payment.php?id=' . (int) $booking['id'])) ?>" class="btn btn-primary">Thanh toán QR</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="icon-circle mx-auto mb-3"><i class="fa fa-calendar-times"></i></div>
                            <h4 class="mb-3">Bạn chưa có lượt đặt phòng nào</h4>
                            <p class="mb-4">Hãy chọn một hạng phòng phù hợp để bắt đầu kế hoạch lưu trú đầu tiên cùng DzungfHotel.</p>
                            <a href="<?= e(url('rooms.php')) ?>" class="btn btn-primary py-3 px-5">Khám phá hạng phòng</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
