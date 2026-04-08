<?php
declare(strict_types=1);
?>
        <div class="container-fluid bg-dark text-light footer wow fadeIn" data-wow-delay="0.1s">
            <div class="container pb-5">
                <div class="row g-5">
                    <div class="col-md-6 col-lg-4">
                        <div class="bg-primary rounded p-4 h-100">
                            <a href="<?= e(url('index.php')) ?>"><h1 class="text-white text-uppercase mb-3">DzungfHotel</h1></a>
                            <p class="text-white mb-0">
                                DzungfHotel là điểm dừng chân mang phong cách hiện đại, thuận tiện để bạn khám phá các hạng phòng và lên kế hoạch lưu trú thật dễ dàng.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <h6 class="section-title text-start text-primary text-uppercase mb-4">Liên hệ nhanh</h6>
                        <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>22 Trần Phú, Đà Nẵng</p>
                        <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>0905 123 456</p>
                        <p class="mb-2"><i class="fa fa-envelope me-3"></i>support@dzungfhotel.com</p>
                        <div class="d-flex pt-2">
                            <a class="btn btn-outline-light btn-social" href="tel:0905123456"><i class="fa fa-phone-alt"></i></a>
                            <a class="btn btn-outline-light btn-social" href="mailto:support@dzungfhotel.com"><i class="fa fa-envelope"></i></a>
                            <a class="btn btn-outline-light btn-social" href="<?= e(url('contact.php')) ?>"><i class="fa fa-map-marked-alt"></i></a>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-12">
                        <h6 class="section-title text-start text-primary text-uppercase mb-4">Điều hướng nhanh</h6>
                        <a class="btn btn-link" href="<?= e(url('index.php')) ?>">Trang chủ</a>
                        <a class="btn btn-link" href="<?= e(url('rooms.php')) ?>">Hạng phòng</a>
                        <a class="btn btn-link" href="<?= e(url('rooms.php')) ?>">Đặt phòng</a>
                        <a class="btn btn-link" href="<?= e(url('contact.php')) ?>">Liên hệ</a>
                        <?php if (is_logged_in()): ?>
                            <a class="btn btn-link" href="<?= e(url('profile.php')) ?>">Tài khoản của tôi</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="container">
                <div class="copyright">
                    <div class="row">
                        <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                            &copy; <a class="border-bottom" href="<?= e(url('index.php')) ?>">DzungfHotel</a>, lưu trú tiện nghi cho mọi chuyến đi.
                        </div>
                        <div class="col-md-6 text-center text-md-end">
                            <div class="footer-menu">
                                <a href="<?= e(url('about.php')) ?>">Giới thiệu</a>
                                <a href="<?= e(url('rooms.php')) ?>">Phòng</a>
                                <a href="<?= e(url('rooms.php')) ?>">Đặt phòng</a>
                                <a href="<?= e(url('contact.php')) ?>">Liên hệ</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= e(asset('lib/wow/wow.min.js')) ?>"></script>
    <script src="<?= e(asset('lib/easing/easing.min.js')) ?>"></script>
    <script src="<?= e(asset('lib/waypoints/waypoints.min.js')) ?>"></script>
    <script src="<?= e(asset('lib/counterup/counterup.min.js')) ?>"></script>
    <script src="<?= e(asset('lib/owlcarousel/owl.carousel.min.js')) ?>"></script>
    <script src="<?= e(asset('lib/tempusdominus/js/moment.min.js')) ?>"></script>
    <script src="<?= e(asset('lib/tempusdominus/js/moment-timezone.min.js')) ?>"></script>
    <script src="<?= e(asset('lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js')) ?>"></script>
    <script src="<?= e(asset('js/main.js')) ?>"></script>
</body>
</html>
