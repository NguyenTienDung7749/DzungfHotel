<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (is_logged_in()) {
    redirect_after_login();
}

$db = get_db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($fullName === '' || mb_strlen($fullName) < 3) {
        $errors[] = 'Họ tên phải có ít nhất 3 ký tự.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Xác nhận mật khẩu chưa khớp.';
    }

    if (!$errors) {
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $checkStmt->bind_param('s', $email);
        $checkStmt->execute();
        $exists = $checkStmt->get_result()->fetch_assoc();

        if ($exists) {
            $errors[] = 'Email này đã được đăng ký.';
        }
    }

    if (!$errors) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $insertStmt = $db->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'customer')");
        $insertStmt->bind_param('sss', $fullName, $email, $hashedPassword);

        if ($insertStmt->execute()) {
            set_flash('success', 'Đăng ký thành công. Bạn hãy đăng nhập để tiếp tục.');
            redirect('login.php');
        }

        $errors[] = 'Không thể tạo tài khoản mới. Vui lòng thử lại.';
    }
}

$page_title = 'DzungfHotel | Đăng ký';
$active_page = 'register';
$page_heading = 'Đăng ký';
$page_eyebrow = 'Tạo tài khoản';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-xxl py-5">
    <div class="container">
        <div class="row g-5 align-items-center justify-content-center">
            <div class="col-lg-5">
                <div class="info-card">
                    <h3 class="mb-4">Tạo tài khoản khách hàng</h3>
                    <p class="mb-4">Sau khi đăng ký, bạn có thể đăng nhập để đặt phòng, xem xác nhận booking và kiểm tra lịch sử đặt phòng của riêng mình.</p>
                    <ul class="list-check mb-0">
                        <li><i class="fa fa-check-circle"></i>Đăng ký nhanh với họ tên, email và mật khẩu.</li>
                        <li><i class="fa fa-check-circle"></i>Mật khẩu được lưu ở dạng băm an toàn.</li>
                        <li><i class="fa fa-check-circle"></i>Sau khi đăng nhập, hệ thống sẽ chuyển bạn đến trang hồ sơ.</li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="auth-card">
                    <h3 class="mb-4">Đăng ký tài khoản mới</h3>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?= e(url('register.php')) ?>">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="full_name" class="form-label fw-semibold">Họ và tên</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e(old('full_name')) ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="email" class="form-label fw-semibold">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= e(old('email')) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label fw-semibold">Mật khẩu</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label fw-semibold">Xác nhận mật khẩu</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary w-100 py-3" type="submit">Đăng ký</button>
                            </div>
                        </div>
                    </form>

                    <p class="mb-0 mt-4 text-center">Đã có tài khoản? <a href="<?= e(url('login.php')) ?>">Đăng nhập tại đây</a>.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
