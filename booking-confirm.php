<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

require_login();

$bookingId = (int) ($_GET['id'] ?? 0);

if ($bookingId <= 0) {
    set_flash('danger', 'Mã đặt phòng không hợp lệ.');
    redirect('profile.php');
}

$db = get_db();
$userId = current_user_id();

$stmt = $db->prepare("
    SELECT b.id, b.check_in, b.check_out, b.guests, b.total_price, b.booking_status, b.payment_status, b.created_at,
           r.room_name, r.room_type, r.location, r.price, u.full_name, u.email,
           p.payment_method, p.transaction_code, p.paid_at
    FROM bookings b
    INNER JOIN rooms r ON b.room_id = r.id
    INNER JOIN users u ON b.user_id = u.id
    LEFT JOIN payments p ON p.booking_id = b.id
    WHERE b.id = ? AND b.user_id = ?
    LIMIT 1
");
$stmt->bind_param('ii', $bookingId, $userId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    set_flash('danger', 'Bạn không có quyền xem thông tin đặt phòng này.');
    redirect('profile.php');
}

$checkInDate = new DateTimeImmutable((string) $booking['check_in']);
$checkOutDate = new DateTimeImmutable((string) $booking['check_out']);
$nights = (int) $checkInDate->diff($checkOutDate)->days;
$bookingStatus = (string) $booking['booking_status'];
$paymentStatus = (string) $booking['payment_status'];
$isPaid = $paymentStatus === 'Paid';
$canPay = $bookingStatus === 'Confirmed' && $paymentStatus === 'Pending';
$isPendingConfirm = $bookingStatus === 'Pending';
$isOutOfStock = $bookingStatus === 'OutOfStock';

$page_title = 'DzungfHotel | Xác nhận đặt phòng';
$active_page = 'booking-confirm';
$page_heading = 'Xác nhận đặt phòng';
$page_eyebrow = 'Thông tin lưu trú';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-xxl py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="form-card h-100">
                    <h3 class="mb-4">Yêu cầu đặt phòng của bạn đã được ghi nhận</h3>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="stat-box h-100">
                                <small class="text-muted">Mã đặt phòng</small>
                                <h4 class="mb-0">#<?= e((string) $booking['id']) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-box h-100">
                                <small class="text-muted">Khách đặt</small>
                                <h4 class="mb-0"><?= e((string) $booking['full_name']) ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <tbody>
                                <tr>
                                    <th>Phòng</th>
                                    <td><?= e((string) $booking['room_name']) ?> (<?= e((string) $booking['room_type']) ?>)</td>
                                </tr>
                                <tr>
                                    <th>Địa điểm</th>
                                    <td><?= e((string) $booking['location']) ?></td>
                                </tr>
                                <tr>
                                    <th>Ngày nhận phòng</th>
                                    <td><?= e(format_date((string) $booking['check_in'])) ?></td>
                                </tr>
                                <tr>
                                    <th>Ngày trả phòng</th>
                                    <td><?= e(format_date((string) $booking['check_out'])) ?></td>
                                </tr>
                                <tr>
                                    <th>Số đêm</th>
                                    <td><?= e((string) $nights) ?> đêm</td>
                                </tr>
                                <tr>
                                    <th>Số khách</th>
                                    <td><?= e((string) $booking['guests']) ?> khách</td>
                                </tr>
                                <tr>
                                    <th>Tổng tiền</th>
                                    <td><strong><?= e(format_currency((string) $booking['total_price'])) ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Trạng thái đặt phòng</th>
                                    <td><span class="badge <?= e(booking_badge_class($bookingStatus)) ?> status-badge"><?= e(booking_status_label($bookingStatus)) ?></span></td>
                                </tr>
                                <tr>
                                    <th>Trạng thái thanh toán</th>
                                    <td><span class="badge <?= e(booking_payment_badge_class($bookingStatus, $paymentStatus)) ?> status-badge"><?= e(booking_payment_status_label($bookingStatus, $paymentStatus)) ?></span></td>
                                </tr>
                                <tr>
                                    <th>Hình thức thanh toán</th>
                                    <td><?= e(normalize_payment_method((string) ($booking['payment_method'] ?? ''), $bookingStatus, $paymentStatus)) ?></td>
                                </tr>
                                <tr>
                                    <th>Mã giao dịch</th>
                                    <td><?= e((string) (($booking['transaction_code'] ?? '') !== '' ? $booking['transaction_code'] : 'Chưa phát sinh')) ?></td>
                                </tr>
                                <?php if (!empty($booking['paid_at'])): ?>
                                    <tr>
                                        <th>Thời gian thanh toán</th>
                                        <td><?= e(format_datetime((string) $booking['paid_at'])) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <tr>
                                    <th>Thời gian tạo</th>
                                    <td><?= e(format_datetime((string) $booking['created_at'])) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="summary-card mb-4">
                    <?php if ($isPaid): ?>
                        <h4 class="mb-3">Thanh toán đã hoàn tất</h4>
                        <p class="mb-0">Khoản thanh toán cho kỳ lưu trú này đã được ghi nhận thành công. DzungfHotel sẽ tiếp tục chuẩn bị thông tin nhận phòng dành cho bạn.</p>
                    <?php elseif ($canPay): ?>
                        <h4 class="mb-3">Yêu cầu lưu trú đã được xác nhận</h4>
                        <p class="mb-0">DzungfHotel đã xác nhận giữ phòng cho bạn. Bạn có thể hoàn tất thanh toán ngay bằng mã QR để chuẩn bị cho kỳ lưu trú.</p>
                    <?php elseif ($isOutOfStock): ?>
                        <h4 class="mb-3">Rất tiếc, phòng hiện không còn sẵn</h4>
                        <p class="mb-0">Hạng phòng này đã hết chỗ trong thời gian bạn chọn. Bạn có thể quay lại danh sách phòng để chọn một lựa chọn khác phù hợp hơn.</p>
                    <?php else: ?>
                        <h4 class="mb-3">Đang chờ xác nhận từ DzungfHotel</h4>
                        <p class="mb-0">Yêu cầu của bạn đang được tiếp nhận. Chúng tôi sẽ cập nhật trạng thái sớm nhất để bạn tiếp tục hoàn tất các bước cần thiết.</p>
                    <?php endif; ?>
                </div>

                <div class="summary-card">
                    <h5 class="mb-3">Bước tiếp theo</h5>
                    <ul class="list-check mb-4">
                        <li><i class="fa fa-check-circle"></i>Xem lại thông tin đặt phòng trong tài khoản của bạn.</li>
                        <?php if ($canPay): ?>
                            <li><i class="fa fa-check-circle"></i>Thanh toán QR để hoàn tất xác nhận lưu trú.</li>
                        <?php elseif ($isOutOfStock): ?>
                            <li><i class="fa fa-check-circle"></i>Chọn một hạng phòng khác phù hợp với lịch lưu trú của bạn.</li>
                        <?php elseif ($isPendingConfirm): ?>
                            <li><i class="fa fa-check-circle"></i>Theo dõi phản hồi xác nhận từ DzungfHotel trong tài khoản cá nhân.</li>
                        <?php else: ?>
                            <li><i class="fa fa-check-circle"></i>Theo dõi thêm cập nhật từ DzungfHotel trước ngày nhận phòng.</li>
                        <?php endif; ?>
                    </ul>

                    <?php if ($canPay): ?>
                        <a href="<?= e(url('payment.php?id=' . (int) $booking['id'])) ?>" class="btn btn-primary w-100 py-3 mb-3">Thanh toán QR</a>
                    <?php endif; ?>

                    <a href="<?= e(url('profile.php')) ?>" class="btn <?= $canPay || $isPaid ? 'btn-outline-dark' : 'btn-primary' ?> w-100 py-3 mb-3">Xem lịch sử đặt phòng</a>
                    <a href="<?= e(url('rooms.php')) ?>" class="btn btn-outline-dark w-100 py-3">Xem thêm hạng phòng</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
