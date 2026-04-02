# DzungfHotel - Hệ thống Quản lý Khách sạn

Đồ án SDLC - Hệ thống quản lý khách sạn trực tuyến được xây dựng bằng Node.js/Express và SQLite.

## Tính năng

- **Tổng quan** (Dashboard): Thống kê phòng, doanh thu, đặt phòng gần đây
- **Quản lý phòng**: Thêm/sửa/xóa phòng và loại phòng, lọc theo trạng thái/loại, xem dạng lưới hoặc bảng
- **Quản lý khách hàng**: Thêm/sửa/xóa khách hàng, tìm kiếm, xem lịch sử đặt phòng
- **Đặt phòng**: Tạo/sửa/hủy đặt phòng, kiểm tra trùng lịch tự động, dự toán chi phí
- **Check-in / Check-out**: Quản lý khách đến và đi hôm nay, danh sách phòng đang có khách
- **Dịch vụ**: Quản lý các dịch vụ bổ sung (ăn uống, vận chuyển, giải trí...)

## Cài đặt & Chạy

```bash
npm install
npm start
```

Truy cập: http://localhost:3000

## Công nghệ

- **Backend**: Node.js, Express.js
- **Database**: SQLite (better-sqlite3)
- **Frontend**: HTML5, Bootstrap 5, Bootstrap Icons, Vanilla JS
