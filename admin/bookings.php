<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_admin();

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $actionType = trim((string) ($_POST['action_type'] ?? ''));
    $allowedActions = ['confirm_booking', 'mark_out_of_stock', 'mark_cash_paid'];

    if (!$bookingId || !in_array($actionType, $allowedActions, true)) {
        set_flash('warning', 'Yêu cầu xử lý không hợp lệ.');
        redirect('admin/bookings.php');
    }

    $db->begin_transaction();

    try {
        $selectBooking = $db->prepare(
            "SELECT b.id, b.total_price, b.booking_status, b.payment_status,
                    p.id AS payment_id, p.transaction_code
             FROM bookings b
             LEFT JOIN payments p ON p.booking_id = b.id
             WHERE b.id = ?
             LIMIT 1"
        );

        if (!$selectBooking) {
            throw new RuntimeException('Không thể tải thông tin booking.');
        }

        $selectBooking->bind_param('i', $bookingId);

        if (!$selectBooking->execute()) {
            throw new RuntimeException('Không thể tải thông tin booking.');
        }

        $bookingResult = $selectBooking->get_result();
        $bookingRow = $bookingResult ? $bookingResult->fetch_assoc() : null;
        $selectBooking->close();

        if (!$bookingRow) {
            throw new RuntimeException('Không tìm thấy booking cần xử lý.');
        }

        $currentBookingStatus = (string) $bookingRow['booking_status'];
        $currentPaymentStatus = (string) $bookingRow['payment_status'];

        if ($actionType === 'confirm_booking') {
            if ($currentBookingStatus !== 'Pending') {
                throw new RuntimeException('Chỉ booking đang chờ xác nhận mới có thể xác nhận.');
            }

            $confirmBooking = $db->prepare("UPDATE bookings SET booking_status = 'Confirmed' WHERE id = ?");

            if (!$confirmBooking) {
                throw new RuntimeException('Không thể cập nhật trạng thái booking.');
            }

            $confirmBooking->bind_param('i', $bookingId);

            if (!$confirmBooking->execute()) {
                throw new RuntimeException('Không thể cập nhật trạng thái booking.');
            }

            $confirmBooking->close();
            $message = 'Đã xác nhận booking #' . $bookingId . '.';
        } elseif ($actionType === 'mark_out_of_stock') {
            if ($currentBookingStatus !== 'Pending') {
                throw new RuntimeException('Chỉ booking đang chờ xác nhận mới có thể chuyển sang hết phòng.');
            }

            $updateBooking = $db->prepare("UPDATE bookings SET booking_status = 'OutOfStock' WHERE id = ?");

            if (!$updateBooking) {
                throw new RuntimeException('Không thể cập nhật trạng thái hết phòng.');
            }

            $updateBooking->bind_param('i', $bookingId);

            if (!$updateBooking->execute()) {
                throw new RuntimeException('Không thể cập nhật trạng thái hết phòng.');
            }

            $updateBooking->close();

            $updatePayment = $db->prepare(
                "UPDATE payments
                 SET payment_method = 'Không áp dụng',
                     payment_status = 'Pending',
                     transaction_code = NULL,
                     paid_at = NULL
                 WHERE booking_id = ?"
            );

            if ($updatePayment) {
                $updatePayment->bind_param('i', $bookingId);
                $updatePayment->execute();
                $updatePayment->close();
            }

            $message = 'Đã cập nhật booking #' . $bookingId . ' sang trạng thái hết phòng.';
        } else {
            if ($currentBookingStatus !== 'Confirmed') {
                throw new RuntimeException('Chỉ booking đã xác nhận mới được đánh dấu thanh toán tiền mặt.');
            }

            if ($currentPaymentStatus === 'Paid') {
                throw new RuntimeException('Booking này đã được thanh toán trước đó.');
            }

            $paymentMethod = 'Tiền mặt tại quầy';
            $transactionCode = trim((string) ($bookingRow['transaction_code'] ?? ''));

            if ($transactionCode === '') {
                $transactionCode = build_transaction_code($bookingId, 'cash');
            }

            $paymentId = (int) ($bookingRow['payment_id'] ?? 0);
            $bookingAmount = (float) ($bookingRow['total_price'] ?? 0);

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

            $updateBookingPayment = $db->prepare("UPDATE bookings SET payment_status = 'Paid' WHERE id = ?");

            if (!$updateBookingPayment) {
                throw new RuntimeException('Không thể đồng bộ trạng thái thanh toán.');
            }

            $updateBookingPayment->bind_param('i', $bookingId);

            if (!$updateBookingPayment->execute()) {
                throw new RuntimeException('Không thể đồng bộ trạng thái thanh toán.');
            }

            $updateBookingPayment->close();
            $message = 'Đã cập nhật thanh toán tiền mặt cho booking #' . $bookingId . '.';
        }

        $db->commit();
        set_flash('success', $message);
    } catch (Throwable $exception) {
        $db->rollback();
        set_flash('danger', $exception->getMessage());
    }

    redirect('admin/bookings.php');
}

$stats = [
    'total' => 0,
    'pending' => 0,
    'paid' => 0,
];

if ($result = $db->query("SELECT COUNT(*) AS total FROM bookings")) {
    $stats['total'] = (int) ($result->fetch_assoc()['total'] ?? 0);
}

if ($result = $db->query("SELECT COUNT(*) AS total FROM bookings WHERE booking_status = 'Pending'")) {
    $stats['pending'] = (int) ($result->fetch_assoc()['total'] ?? 0);
}

if ($result = $db->query("SELECT COUNT(*) AS total FROM payments WHERE payment_status = 'Paid'")) {
    $stats['paid'] = (int) ($result->fetch_assoc()['total'] ?? 0);
}

$bookings = [];
$query = "
    SELECT b.id, b.check_in, b.check_out, b.guests, b.total_price, b.booking_status, b.payment_status, b.created_at,
           u.full_name, u.email, r.room_name, r.room_type,
           p.payment_method, p.transaction_code
    FROM bookings b
    INNER JOIN users u ON b.user_id = u.id
    INNER JOIN rooms r ON b.room_id = r.id
    LEFT JOIN payments p ON p.booking_id = b.id
    ORDER BY b.created_at DESC, b.id DESC
";

if ($result = $db->query($query)) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

$page_title = 'DzungfHotel | Quản lý đặt phòng';
$active_page = 'admin-bookings';
$page_heading = 'Quản lý đặt phòng';
$page_eyebrow = 'Khu vực quản trị';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-xxl py-5">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-box text-center h-100">
                    <div class="icon-circle mx-auto mb-3"><i class="fa fa-list"></i></div>
                    <h3 class="mb-2"><?= $stats['total'] ?></h3>
                    <p class="mb-0">Tổng booking</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box text-center h-100">
                    <div class="icon-circle mx-auto mb-3"><i class="fa fa-hourglass-half"></i></div>
                    <h3 class="mb-2"><?= $stats['pending'] ?></h3>
                    <p class="mb-0">Chờ xác nhận</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box text-center h-100">
                    <div class="icon-circle mx-auto mb-3"><i class="fa fa-wallet"></i></div>
                    <h3 class="mb-2"><?= $stats['paid'] ?></h3>
                    <p class="mb-0">Đã thanh toán</p>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                <div>
                    <h3 class="mb-2">Danh sách booking</h3>
                    <p class="mb-0 text-muted">Admin có thể xác nhận, báo hết phòng hoặc ghi nhận tiền mặt ngay trên cùng một bảng.</p>
                </div>
                <span class="badge bg-dark fs-6 px-3 py-2 mt-3 mt-md-0"><?= count($bookings) ?> dòng dữ liệu</span>
            </div>

            <?php if ($bookings === []): ?>
                <div class="empty-state">
                    <h5 class="mb-2">Chưa có booking nào</h5>
                    <p class="mb-0 text-muted">Khi khách đặt phòng, danh sách sẽ hiển thị tại đây để admin xử lý.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Khách hàng</th>
                                <th>Phòng</th>
                                <th>Nhận phòng</th>
                                <th>Trả phòng</th>
                                <th>Số khách</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Thanh toán</th>
                                <th>Phương thức</th>
                                <th>Ngày tạo</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <?php
                                $bookingStatus = (string) $booking['booking_status'];
                                $paymentStatus = (string) $booking['payment_status'];
                                $needsConfirmation = $bookingStatus === 'Pending';
                                $canMarkCashPaid = $bookingStatus === 'Confirmed' && $paymentStatus === 'Pending';
                                ?>
                                <tr>
                                    <td>#<?= e((string) $booking['id']) ?></td>
                                    <td>
                                        <strong><?= e((string) $booking['full_name']) ?></strong><br>
                                        <small class="text-muted"><?= e((string) $booking['email']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= e((string) $booking['room_name']) ?></strong><br>
                                        <small class="text-muted"><?= e((string) $booking['room_type']) ?></small>
                                    </td>
                                    <td><?= e(format_date((string) $booking['check_in'])) ?></td>
                                    <td><?= e(format_date((string) $booking['check_out'])) ?></td>
                                    <td><?= e((string) $booking['guests']) ?></td>
                                    <td><?= e(format_currency((string) $booking['total_price'])) ?></td>
                                    <td><span class="badge <?= e(booking_badge_class($bookingStatus)) ?> status-badge"><?= e(booking_status_label($bookingStatus)) ?></span></td>
                                    <td><span class="badge <?= e(booking_payment_badge_class($bookingStatus, $paymentStatus)) ?> status-badge"><?= e(booking_payment_status_label($bookingStatus, $paymentStatus)) ?></span></td>
                                    <td>
                                        <?= e(normalize_payment_method((string) ($booking['payment_method'] ?? ''), $bookingStatus, $paymentStatus)) ?><br>
                                        <small class="text-muted"><?= e((string) (($booking['transaction_code'] ?? '') !== '' ? $booking['transaction_code'] : 'Chưa có mã')) ?></small>
                                    </td>
                                    <td><?= e(format_datetime((string) $booking['created_at'])) ?></td>
                                    <td>
                                        <div class="d-grid gap-2 admin-action-stack">
                                            <?php if ($needsConfirmation): ?>
                                                <form method="post" class="m-0">
                                                    <input type="hidden" name="booking_id" value="<?= e((string) $booking['id']) ?>">
                                                    <input type="hidden" name="action_type" value="confirm_booking">
                                                    <button type="submit" class="btn btn-primary btn-sm w-100">Xác nhận booking</button>
                                                </form>
                                                <form method="post" class="m-0">
                                                    <input type="hidden" name="booking_id" value="<?= e((string) $booking['id']) ?>">
                                                    <input type="hidden" name="action_type" value="mark_out_of_stock">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">Báo hết phòng</button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($canMarkCashPaid): ?>
                                                <form method="post" class="m-0">
                                                    <input type="hidden" name="booking_id" value="<?= e((string) $booking['id']) ?>">
                                                    <input type="hidden" name="action_type" value="mark_cash_paid">
                                                    <button type="submit" class="btn btn-outline-success btn-sm w-100">Xác nhận tiền mặt</button>
                                                </form>
                                                <small class="text-muted">Dành cho khách đặt qua điện thoại hoặc trả tại quầy.</small>
                                            <?php endif; ?>

                                            <?php if (!$needsConfirmation && !$canMarkCashPaid): ?>
                                                <span class="text-muted small">Đã xử lý</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
