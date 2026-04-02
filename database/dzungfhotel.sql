-- Create/select the target database before importing this file.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS room_amenities;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS amenities;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_name VARCHAR(120) NOT NULL,
    room_type VARCHAR(80) NOT NULL,
    location VARCHAR(120) DEFAULT 'Đà Nẵng',
    price DECIMAL(10,2) NOT NULL,
    capacity INT NOT NULL,
    image VARCHAR(255),
    description TEXT,
    status ENUM('available', 'booked', 'maintenance') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    amenity_name VARCHAR(120) NOT NULL,
    icon VARCHAR(80) DEFAULT 'fa fa-check-circle'
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    guests INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    booking_status ENUM('Pending', 'Confirmed', 'OutOfStock') NOT NULL DEFAULT 'Pending',
    payment_status ENUM('Pending', 'Paid') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_bookings_room FOREIGN KEY (room_id) REFERENCES rooms(id)
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(80) NOT NULL DEFAULT 'Chờ cập nhật',
    payment_status ENUM('Pending', 'Paid') NOT NULL DEFAULT 'Pending',
    transaction_code VARCHAR(80) DEFAULT NULL,
    paid_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

CREATE TABLE room_amenities (
    room_id INT NOT NULL,
    amenity_id INT NOT NULL,
    PRIMARY KEY (room_id, amenity_id),
    CONSTRAINT fk_room_amenities_room FOREIGN KEY (room_id) REFERENCES rooms(id),
    CONSTRAINT fk_room_amenities_amenity FOREIGN KEY (amenity_id) REFERENCES amenities(id)
);

INSERT INTO users (id, full_name, email, password, role) VALUES
(1, 'Quản trị viên DzungfHotel', 'admin@dzungfhotel.com', '$2y$10$AXA5g6MHfcwFTDVIPxEDFuZHn0pGiwYkCnWvcsHP9MUSDTdMsp9/i', 'admin'),
(2, 'Nguyễn Văn Demo', 'user@dzungfhotel.com', '$2y$10$AXA5g6MHfcwFTDVIPxEDFuZHn0pGiwYkCnWvcsHP9MUSDTdMsp9/i', 'customer');

INSERT INTO rooms (id, room_name, room_type, location, price, capacity, image, description, status) VALUES
(1, 'Phòng Deluxe Biển', 'Deluxe', 'Đà Nẵng', 1250000.00, 2, 'img/room-1.jpg', 'Phòng Deluxe hướng biển, thiết kế hiện đại, phù hợp cho cặp đôi cần kỳ nghỉ yên tĩnh và tiện nghi.', 'available'),
(2, 'Suite Gia Đình', 'Suite', 'Đà Nẵng', 1850000.00, 4, 'img/room-2.jpg', 'Suite rộng rãi cho gia đình, có khu vực tiếp khách riêng, tầm nhìn thành phố đẹp và nội thất ấm cúng.', 'available'),
(3, 'Phòng Superior Cổ Điển', 'Superior', 'Hội An', 980000.00, 2, 'img/room-3.jpg', 'Phòng mang phong cách cổ điển nhẹ nhàng, phù hợp cho du khách muốn nghỉ dưỡng gần phố cổ và sông Hoài.', 'available'),
(4, 'Phòng Twin Công Tác', 'Twin', 'Đà Nẵng', 890000.00, 2, 'img/about-1.jpg', 'Hai giường đơn, bàn làm việc rộng, wifi ổn định, rất phù hợp cho chuyến đi công tác ngắn ngày.', 'available'),
(5, 'Suite Trăng Mật', 'Honeymoon Suite', 'Huế', 2100000.00, 2, 'img/about-2.jpg', 'Không gian riêng tư, nội thất sang trọng, bồn tắm thư giãn và gói trang trí nhẹ cho cặp đôi.', 'available'),
(6, 'Phòng Family Garden', 'Family', 'Đà Nẵng', 1650000.00, 5, 'img/about-3.jpg', 'Phòng gia đình gần khu vườn xanh, nhiều ánh sáng tự nhiên, phù hợp cho nhóm bạn và gia đình có trẻ em.', 'available');

INSERT INTO amenities (id, amenity_name, icon) VALUES
(1, 'Wifi tốc độ cao', 'fa fa-wifi'),
(2, 'Bữa sáng miễn phí', 'fa fa-coffee'),
(3, 'Ban công riêng', 'fa fa-archway'),
(4, 'Smart TV', 'fa fa-tv'),
(5, 'Hồ bơi', 'fa fa-swimmer'),
(6, 'Điều hòa', 'fa fa-snowflake'),
(7, 'Bồn tắm', 'fa fa-bath'),
(8, 'Bãi đỗ xe', 'fa fa-car');

INSERT INTO room_amenities (room_id, amenity_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 6),
(2, 1), (2, 2), (2, 4), (2, 8),
(3, 1), (3, 3), (3, 6), (3, 7),
(4, 1), (4, 4), (4, 6), (4, 8),
(5, 1), (5, 2), (5, 5), (5, 7),
(6, 1), (6, 2), (6, 5), (6, 8);

INSERT INTO bookings (id, user_id, room_id, check_in, check_out, guests, total_price, booking_status, payment_status, created_at) VALUES
(1, 2, 1, '2026-03-20', '2026-03-22', 2, 2500000.00, 'Confirmed', 'Paid', '2026-03-10 09:00:00'),
(2, 2, 2, '2026-04-02', '2026-09-04', 4, 99999999.99, 'Pending', 'Pending', '2026-04-02 09:02:12');

INSERT INTO payments (booking_id, amount, payment_method, payment_status, transaction_code, paid_at, created_at) VALUES
(1, 2500000.00, 'Chuyển khoản', 'Paid', 'DZH-PAID-0001', '2026-03-10 09:30:00', '2026-03-10 09:05:00'),
(2, 99999999.99, 'Chờ cập nhật', 'Pending', NULL, NULL, '2026-04-02 09:02:12');

SET FOREIGN_KEY_CHECKS = 1;
