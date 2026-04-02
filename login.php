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
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }

    if ($password === '') {
        $errors[] = 'Vui lòng nhập mật khẩu.';
    }

    if (!$errors) {
        $stmt = $db->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, (string) $user['password'])) {
            $errors[] = 'Email hoặc mật khẩu chưa đúng.';
        } else {
            login_user($user);
            set_flash('success', 'Đăng nhập thành công.');
            redirect_after_login();
        }
    }
}

$page_title = 'DzungfHotel | Đăng nhập';
$active_page = 'login';
$page_heading = 'Đăng nhập';
$page_eyebrow = 'Tài khoản khách hàng';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-xxl py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-xl-5">
                <div class="auth-card">
                    <div class="text-center mb-4">
                        <h3 class="mb-2">Đăng nhập hệ thống</h3>
                        <p class="text-muted mb-0">Nhập email và mật khẩu để tiếp tục quản lý booking của bạn.</p>
                    </div>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?= e(url('login.php')) ?>">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="email" class="form-label fw-semibold">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= e(old('email')) ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="password" class="form-label fw-semibold">Mật khẩu</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary w-100 py-3" type="submit">Đăng nhập</button>
                            </div>
                        </div>
                    </form>

                    <p class="mb-0 mt-4 text-center">Chưa có tài khoản? <a href="<?= e(url('register.php')) ?>">Đăng ký ngay</a>.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
