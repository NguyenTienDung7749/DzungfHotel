<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function app_base_url(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    if ($scriptName === '') {
        return '';
    }

    $adminMarker = '/admin/';
    $adminPos = strpos($scriptName, $adminMarker);

    if ($adminPos !== false) {
        return rtrim(substr($scriptName, 0, $adminPos), '/');
    }

    $dir = str_replace('\\', '/', dirname($scriptName));

    if ($dir === '/' || $dir === '.' || $dir === '\\') {
        return '';
    }

    return rtrim($dir, '/');
}

function url(string $path = ''): string
{
    $base = app_base_url();
    $cleanPath = ltrim($path, '/');

    if ($cleanPath === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . $cleanPath;
}

function asset(string $path): string
{
    return url($path);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function old(string $key, string $default = ''): string
{
    if (isset($_POST[$key])) {
        return trim((string) $_POST[$key]);
    }

    if (isset($_GET[$key])) {
        return trim((string) $_GET[$key]);
    }

    return $default;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function redirect_raw(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function flash_class(string $type): string
{
    return match ($type) {
        'success' => 'success',
        'warning' => 'warning',
        'danger' => 'danger',
        default => 'info',
    };
}

function display_flash(): void
{
    $flash = get_flash();

    if (!$flash) {
        return;
    }

    echo '<div class="container pt-4">';
    echo '<div class="alert alert-' . e(flash_class((string) $flash['type'])) . ' alert-dismissible fade show shadow-sm" role="alert">';
    echo e((string) $flash['message']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    echo '</div>';
}

function remember_requested_url(): void
{
    if (!empty($_SERVER['REQUEST_URI'])) {
        $_SESSION['intended_url'] = (string) $_SERVER['REQUEST_URI'];
    }
}

function consume_intended_url(): ?string
{
    if (!isset($_SESSION['intended_url'])) {
        return null;
    }

    $url = (string) $_SESSION['intended_url'];
    unset($_SESSION['intended_url']);

    return $url;
}

function login_user(array $user): void
{
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'full_name' => (string) $user['full_name'],
        'email' => (string) $user['email'],
        'role' => (string) $user['role'],
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function current_user_id(): int
{
    return (int) (current_user()['id'] ?? 0);
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function require_login(): void
{
    if (!is_logged_in()) {
        remember_requested_url();
        set_flash('warning', 'Vui lòng đăng nhập để tiếp tục.');
        redirect('login.php');
    }
}

function require_admin(): void
{
    if (!is_logged_in()) {
        remember_requested_url();
        set_flash('warning', 'Vui lòng đăng nhập với tài khoản admin.');
        redirect('login.php');
    }

    if (!is_admin()) {
        set_flash('danger', 'Bạn không có quyền truy cập trang quản trị.');
        redirect('index.php');
    }
}

function redirect_after_login(): never
{
    if (is_admin()) {
        redirect('admin/bookings.php');
    }

    $intended = consume_intended_url();

    if ($intended) {
        redirect_raw($intended);
    }

    redirect('profile.php');
}

function is_active(string $activePage, array $candidates): string
{
    return in_array($activePage, $candidates, true) ? 'active' : '';
}

function format_currency(float|string $amount): string
{
    return number_format((float) $amount, 0, ',', '.') . ' VNĐ';
}

function format_datetime(string $value): string
{
    $date = date_create($value);

    return $date ? $date->format('d/m/Y H:i') : $value;
}

function format_date(string $value): string
{
    $date = date_create($value);

    return $date ? $date->format('d/m/Y') : $value;
}

function booking_badge_class(string $status): string
{
    return match ($status) {
        'Confirmed' => 'bg-success',
        'OutOfStock' => 'bg-danger',
        'Cancelled' => 'bg-danger',
        'Pending' => 'bg-warning text-dark',
        default => 'bg-secondary',
    };
}

function booking_status_label(string $status): string
{
    return match ($status) {
        'Pending' => 'Chờ xác nhận',
        'Confirmed' => 'Đã xác nhận',
        'OutOfStock' => 'Hết phòng',
        'Cancelled' => 'Đã hủy',
        default => $status,
    };
}

function room_badge_class(string $status): string
{
    return match ($status) {
        'available' => 'bg-success',
        'booked' => 'bg-warning text-dark',
        'maintenance' => 'bg-danger',
        default => 'bg-secondary',
    };
}

function payment_badge_class(string $status): string
{
    return match ($status) {
        'Paid' => 'bg-success',
        'Pending' => 'bg-warning text-dark',
        default => 'bg-secondary',
    };
}

function payment_status_label(string $status): string
{
    return match ($status) {
        'Pending' => 'Chờ thanh toán',
        'Paid' => 'Đã thanh toán',
        default => $status,
    };
}

function booking_payment_badge_class(string $bookingStatus, string $paymentStatus): string
{
    if ($bookingStatus === 'OutOfStock') {
        return 'bg-secondary';
    }

    if ($bookingStatus === 'Pending' && $paymentStatus === 'Pending') {
        return 'bg-info text-dark';
    }

    return payment_badge_class($paymentStatus);
}

function booking_payment_status_label(string $bookingStatus, string $paymentStatus): string
{
    if ($bookingStatus === 'OutOfStock') {
        return 'Không áp dụng';
    }

    if ($bookingStatus === 'Pending' && $paymentStatus === 'Pending') {
        return 'Đợi xác nhận';
    }

    return payment_status_label($paymentStatus);
}

function normalize_payment_method(?string $paymentMethod, string $bookingStatus = '', string $paymentStatus = ''): string
{
    if ($bookingStatus === 'OutOfStock') {
        return 'Không áp dụng';
    }

    if ($paymentStatus === 'Pending') {
        return 'Chờ cập nhật';
    }

    $value = trim((string) $paymentMethod);

    if ($value === '' || $value === 'Ch? c?p nh?t' || $value === 'Chá» cáº­p nháº­t' || $value === 'Cho cap nhat') {
        return 'Chờ cập nhật';
    }

    if ($value === 'KhÃ´ng Ã¡p dá»¥ng' || $value === 'Khong ap dung') {
        return 'Không áp dụng';
    }

    if ($value === 'Tiá»n máº·t táº¡i quáº§y') {
        return 'Tiền mặt tại quầy';
    }

    if ($value === 'Chuyá»ƒn khoáº£n QR') {
        return 'Chuyển khoản QR';
    }

    if ($value === 'Chuyá»ƒn khoáº£n') {
        return 'Chuyển khoản';
    }

    return $value;
}

function build_transaction_code(int $bookingId, string $channel): string
{
    return 'DZH-' . strtoupper($channel) . '-' . str_pad((string) $bookingId, 4, '0', STR_PAD_LEFT);
}

function room_status_label(string $status): string
{
    return match ($status) {
        'available' => 'Sẵn sàng',
        'booked' => 'Đã đặt',
        'maintenance' => 'Bảo trì',
        default => $status,
    };
}

function render_page_header(string $title, string $eyebrow = 'DzungfHotel'): void
{
    ?>
    <div class="container-fluid page-header mb-5 p-0" style="background-image: url(<?= e(asset('img/carousel-1.jpg')) ?>);">
        <div class="container-fluid page-header-inner py-5">
            <div class="container text-center pb-5">
                <h6 class="section-title text-white text-uppercase mb-3 animated slideInDown"><?= e($eyebrow) ?></h6>
                <h1 class="display-3 text-white mb-3 animated slideInDown"><?= e($title) ?></h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb justify-content-center text-uppercase">
                        <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Trang chủ</a></li>
                        <li class="breadcrumb-item text-white active" aria-current="page"><?= e($title) ?></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <?php
}

function selected_option(string $currentValue, string $expectedValue): string
{
    return $currentValue === $expectedValue ? 'selected' : '';
}
