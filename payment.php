<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

require_login();

$db = get_db();
$userId = current_user_id();
$bookingId = (int) ($_POST['booking_id'] ?? $_GET['id'] ?? 0);

if ($bookingId <= 0) {
    set_flash('danger', 'Booking không hợp lệ.');
    redirect('profile.php');
}

$loadBooking = static function (mysqli $db, int $bookingId, int $userId): ?array {
    $stmt = $db->prepare("
        SELECT b.id, b.user_id, b.check_in, b.check_out, b.guests, b.total_price, b.booking_status, b.payment_status, b.created_at,
               r.room_name, r.room_type, r.location,
               p.id AS payment_id, p.payment_method, p.transaction_code, p.paid_at
        FROM bookings b
        INNER JOIN rooms r ON b.room_id = r.id
        LEFT JOIN payments p ON p.booking_id = b.id
        WHERE b.id = ? AND b.user_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('ii', $bookingId, $userId);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $booking ?: null;
};

$booking = $loadBooking($db, $bookingId, $userId);

if (!$booking) {
    set_flash('danger', 'Bạn không có quyền thanh toán booking này.');
    redirect('profile.php');
}

$bookingStatus = (string) $booking['booking_status'];
$paymentStatus = (string) $booking['payment_status'];

if ($bookingStatus !== 'Confirmed' && $paymentStatus !== 'Paid') {
    if ($bookingStatus === 'OutOfStock') {
        set_flash('danger', 'Booking này không thể thanh toán vì khách sạn đã báo hết phòng.');
    } else {
        set_flash('warning', 'Booking này đang chờ khách sạn xác nhận, chưa thể thanh toán.');
    }

    redirect('booking-confirm.php?id=' . $bookingId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action_type'] ?? '') === 'complete_qr_payment') {
    if ((string) $booking['booking_status'] !== 'Confirmed') {
        set_flash('warning', 'Chỉ booking đã xác nhận mới được thanh toán.');
        redirect('booking-confirm.php?id=' . $bookingId);
    }

    if ((string) $booking['payment_status'] !== 'Paid') {
        $db->begin_transaction();

        try {
            $paymentMethod = 'Chuyển khoản QR';
            $transactionCode = build_transaction_code($bookingId, 'qr');
            $paymentId = (int) ($booking['payment_id'] ?? 0);
            $bookingAmount = (float) $booking['total_price'];

            if ($paymentId > 0) {
                $updatePayment = $db->prepare(
                    "UPDATE payments
                     SET payment_method = ?, payment_status = 'Paid', transaction_code = ?, paid_at = COALESCE(paid_at, NOW())
                     WHERE booking_id = ?"
                );

                if (!$updatePayment) {
                    throw new RuntimeException('Không thể cập nhật thanh toán.');
                }

                $updatePayment->bind_param('ssi', $paymentMethod, $transactionCode, $bookingId);

                if (!$updatePayment->execute()) {
                    throw new RuntimeException('Không thể cập nhật thanh toán.');
                }

                $updatePayment->close();
            } else {
                $insertPayment = $db->prepare(
                    "INSERT INTO payments (booking_id, amount, payment_method, payment_status, transaction_code, paid_at)
                     VALUES (?, ?, ?, 'Paid', ?, NOW())"
                );

                if (!$insertPayment) {
                    throw new RuntimeException('Không thể tạo bản ghi thanh toán.');
                }

                $insertPayment->bind_param('idss', $bookingId, $bookingAmount, $paymentMethod, $transactionCode);

                if (!$insertPayment->execute()) {
                    throw new RuntimeException('Không thể tạo bản ghi thanh toán.');
                }

                $insertPayment->close();
            }

            $updateBooking = $db->prepare("UPDATE bookings SET payment_status = 'Paid' WHERE id = ? AND user_id = ?");

            if (!$updateBooking) {
                throw new RuntimeException('Không thể cập nhật trạng thái booking.');
            }

            $updateBooking->bind_param('ii', $bookingId, $userId);

            if (!$updateBooking->execute()) {
                throw new RuntimeException('Không thể cập nhật trạng thái booking.');
            }

            $updateBooking->close();
            $db->commit();

            set_flash('success', 'Thanh toán thành công cho booking #' . $bookingId . '.');
        } catch (Throwable $exception) {
            $db->rollback();
            set_flash('danger', $exception->getMessage());
        }
    } else {
        set_flash('info', 'Booking này đã được thanh toán trước đó.');
    }

    redirect('booking-confirm.php?id=' . $bookingId);
}

$booking = $loadBooking($db, $bookingId, $userId);

if (!$booking) {
    set_flash('danger', 'Không thể tải lại thông tin booking.');
    redirect('profile.php');
}

$checkInDate = new DateTimeImmutable((string) $booking['check_in']);
$checkOutDate = new DateTimeImmutable((string) $booking['check_out']);
$nights = (int) $checkInDate->diff($checkOutDate)->days;
$isPaid = (string) $booking['payment_status'] === 'Paid';

$page_title = 'DzungfHotel | Thanh toán QR';
$active_page = 'booking-confirm';
$page_heading = 'Thanh toán QR';
$page_eyebrow = 'Hoàn tất thanh toán';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-xxl py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="form-card h-100">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                        <div>
                            <h3 class="mb-2">Thanh toán booking #<?= e((string) $booking['id']) ?></h3>
                            <p class="mb-0 text-muted">Quét mã QR để hoàn tất thanh toán cho phòng <?= e((string) $booking['room_name']) ?>.</p>
                        </div>
                        <span class="badge <?= e(payment_badge_class((string) $booking['payment_status'])) ?> status-badge mt-3 mt-md-0">
                            <?= e(payment_status_label((string) $booking['payment_status'])) ?>
                        </span>
                    </div>

                    <?php if ($isPaid): ?>
                        <div class="alert alert-success">
                            Booking này đã được cập nhật thanh toán thành công. Bạn có thể quay lại trang xác nhận để xem chi tiết.
                        </div>
                    <?php else: ?>
                        <div class="payment-qr-card">
                            <img class="payment-qr-image" src="<?= e(asset('img/payment-qr-vcb.svg')) ?>" alt="Mã QR thanh toán Vietcombank">
                            <div class="payment-status-box">
                                <span class="countdown-badge">Tự động xác nhận sau <strong id="paymentSeconds">10</strong> giây</span>
                                <div class="progress payment-progress mt-3">
                                    <div
                                        id="paymentProgressBar"
                                        class="progress-bar bg-success"
                                        role="progressbar"
                                        style="width: 0%;"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                        aria-valuenow="0"
                                    ></div>
                                </div>
                                <p class="text-muted mt-3 mb-0">Hệ thống sẽ tự cập nhật trạng thái thanh toán sau vài giây. Nếu khách thanh toán tiền mặt tại quầy, admin cũng có thể xác nhận thủ công.</p>
                            </div>
                        </div>

                        <form id="qrPaymentForm" method="post" action="<?= e(url('payment.php?id=' . $bookingId)) ?>" class="mt-4">
                            <input type="hidden" name="booking_id" value="<?= e((string) $bookingId) ?>">
                            <input type="hidden" name="action_type" value="complete_qr_payment">
                            <noscript>
                                <button type="submit" class="btn btn-primary w-100 py-3">Xác nhận đã chuyển khoản</button>
                            </noscript>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="summary-card mb-4">
                    <h4 class="mb-3">Tóm tắt booking</h4>
                    <ul class="list-check mb-0">
                        <li><i class="fa fa-check-circle"></i>Phòng: <?= e((string) $booking['room_name']) ?> (<?= e((string) $booking['room_type']) ?>)</li>
                        <li><i class="fa fa-check-circle"></i>Địa điểm: <?= e((string) $booking['location']) ?></li>
                        <li><i class="fa fa-check-circle"></i>Ngày ở: <?= e(format_date((string) $booking['check_in'])) ?> đến <?= e(format_date((string) $booking['check_out'])) ?></li>
                        <li><i class="fa fa-check-circle"></i>Số đêm: <?= e((string) $nights) ?> đêm</li>
                        <li><i class="fa fa-check-circle"></i>Số khách: <?= e((string) $booking['guests']) ?> khách</li>
                        <li><i class="fa fa-check-circle"></i>Tổng tiền: <?= e(format_currency((string) $booking['total_price'])) ?></li>
                    </ul>
                </div>

                <div class="summary-card">
                    <h5 class="mb-3">Lựa chọn khác</h5>
                    <ul class="list-check mb-4">
                        <li><i class="fa fa-check-circle"></i>Bạn có thể quay lại hồ sơ để theo dõi lịch sử booking.</li>
                        <li><i class="fa fa-check-circle"></i>Nếu khách đến trực tiếp hoặc đặt qua điện thoại, khách sạn có thể xác nhận tiền mặt tại quầy.</li>
                    </ul>
                    <a href="<?= e(url('booking-confirm.php?id=' . $bookingId)) ?>" class="btn btn-primary w-100 py-3 mb-3">Quay lại xác nhận booking</a>
                    <a href="<?= e(url('profile.php')) ?>" class="btn btn-outline-dark w-100 py-3">Xem hồ sơ cá nhân</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!$isPaid): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const secondsElement = document.getElementById('paymentSeconds');
            const progressBar = document.getElementById('paymentProgressBar');
            const form = document.getElementById('qrPaymentForm');

            if (!secondsElement || !progressBar || !form) {
                return;
            }

            let remaining = 10;
            const total = 10;

            const updateCountdown = function () {
                secondsElement.textContent = String(remaining);
                const percent = Math.min(100, Math.round(((total - remaining) / total) * 100));
                progressBar.style.width = percent + '%';
                progressBar.setAttribute('aria-valuenow', String(percent));
            };

            updateCountdown();

            const timer = window.setInterval(function () {
                remaining -= 1;
                updateCountdown();

                if (remaining <= 0) {
                    window.clearInterval(timer);
                    progressBar.style.width = '100%';
                    progressBar.setAttribute('aria-valuenow', '100');
                    form.submit();
                }
            }, 1000);
        });
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
