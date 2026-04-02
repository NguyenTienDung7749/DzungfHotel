DZUNGFHOTEL - HUONG DAN CHAY LOCAL

1. Dat folder project
- Thu muc project hien tai: d:\Project\Web_Pjt\Hotelier
- URL local neu dung XAMPP + localhost: http://localhost/DzungfHotel/
- Neu chua co junction, tao bang PowerShell:
  New-Item -ItemType Junction -Path C:\xampp\htdocs\DzungfHotel -Target d:\Project\Web_Pjt\Hotelier

2. Cau hinh moi truong local
- App se tu fallback ve cau hinh local khi chay tren localhost.
- Neu ban dung host local khac localhost, hay set:
  APP_ENV=local
- Bien DB co the dung theo mau trong file `.env.example`:
  DB_HOST
  DB_PORT
  DB_NAME
  DB_USER
  DB_PASSWORD

3. Tao va import database
- Tao database `dzungfhotel` trong phpMyAdmin hoac MySQL.
- Sau do import file: database/dzungfhotel.sql
- Hoac dung lenh:
  C:\xampp\mysql\bin\mysql.exe -uroot dzungfhotel < d:\Project\Web_Pjt\Hotelier\database\dzungfhotel.sql

4. Tai khoan mac dinh
- Admin
  Email: admin@dzungfhotel.com
  Password: 123456
- Customer
  Email: user@dzungfhotel.com
  Password: 123456

5. URL can mo
- Trang chu: http://localhost/DzungfHotel/
- Danh sach phong: http://localhost/DzungfHotel/rooms.php
- Dang nhap: http://localhost/DzungfHotel/login.php
- Ho so: http://localhost/DzungfHotel/profile.php
- Quan ly booking: http://localhost/DzungfHotel/admin/bookings.php

6. Health check
- App only: http://localhost/DzungfHotel/health.php
- App + DB: http://localhost/DzungfHotel/health-db.php

7. Cau truc database
- Tong so bang: 6
- users
- rooms
- amenities
- room_amenities
- bookings
- payments
