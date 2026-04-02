const Database = require('better-sqlite3');
const path = require('path');

const DB_PATH = path.join(__dirname, '..', 'hotel.db');

let db;

function getDb() {
  if (!db) {
    db = new Database(DB_PATH);
    db.pragma('journal_mode = WAL');
    db.pragma('foreign_keys = ON');
    initSchema();
  }
  return db;
}

function initSchema() {
  db.exec(`
    CREATE TABLE IF NOT EXISTS room_types (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL UNIQUE,
      description TEXT,
      price_per_night REAL NOT NULL,
      capacity INTEGER NOT NULL DEFAULT 2
    );

    CREATE TABLE IF NOT EXISTS rooms (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      room_number TEXT NOT NULL UNIQUE,
      room_type_id INTEGER NOT NULL,
      floor INTEGER NOT NULL DEFAULT 1,
      status TEXT NOT NULL DEFAULT 'available' CHECK(status IN ('available','occupied','maintenance','reserved')),
      description TEXT,
      FOREIGN KEY (room_type_id) REFERENCES room_types(id)
    );

    CREATE TABLE IF NOT EXISTS customers (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      full_name TEXT NOT NULL,
      id_card TEXT NOT NULL UNIQUE,
      phone TEXT NOT NULL,
      email TEXT,
      address TEXT,
      nationality TEXT DEFAULT 'Việt Nam',
      created_at TEXT DEFAULT (datetime('now','localtime'))
    );

    CREATE TABLE IF NOT EXISTS bookings (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      customer_id INTEGER NOT NULL,
      room_id INTEGER NOT NULL,
      check_in_date TEXT NOT NULL,
      check_out_date TEXT NOT NULL,
      actual_check_in TEXT,
      actual_check_out TEXT,
      status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending','confirmed','checked_in','checked_out','cancelled')),
      adults INTEGER NOT NULL DEFAULT 1,
      children INTEGER NOT NULL DEFAULT 0,
      special_requests TEXT,
      total_amount REAL,
      paid_amount REAL DEFAULT 0,
      payment_status TEXT DEFAULT 'unpaid' CHECK(payment_status IN ('unpaid','partial','paid')),
      created_at TEXT DEFAULT (datetime('now','localtime')),
      FOREIGN KEY (customer_id) REFERENCES customers(id),
      FOREIGN KEY (room_id) REFERENCES rooms(id)
    );

    CREATE TABLE IF NOT EXISTS services (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      category TEXT NOT NULL,
      unit_price REAL NOT NULL,
      unit TEXT DEFAULT 'lần'
    );

    CREATE TABLE IF NOT EXISTS booking_services (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      booking_id INTEGER NOT NULL,
      service_id INTEGER NOT NULL,
      quantity INTEGER NOT NULL DEFAULT 1,
      unit_price REAL NOT NULL,
      used_at TEXT DEFAULT (datetime('now','localtime')),
      FOREIGN KEY (booking_id) REFERENCES bookings(id),
      FOREIGN KEY (service_id) REFERENCES services(id)
    );
  `);

  seedData();
}

function seedData() {
  const count = db.prepare('SELECT COUNT(*) as c FROM room_types').get();
  if (count.c > 0) return;

  // Room types
  const insertType = db.prepare('INSERT INTO room_types (name, description, price_per_night, capacity) VALUES (?, ?, ?, ?)');
  insertType.run('Standard', 'Phòng tiêu chuẩn, đầy đủ tiện nghi cơ bản', 500000, 2);
  insertType.run('Deluxe', 'Phòng cao cấp với view đẹp và tiện nghi đầy đủ', 850000, 2);
  insertType.run('Suite', 'Phòng Suite sang trọng với phòng khách riêng', 1500000, 3);
  insertType.run('Family', 'Phòng gia đình rộng rãi phù hợp cho gia đình 4-6 người', 1200000, 6);
  insertType.run('VIP', 'Phòng VIP hạng sang với đầy đủ dịch vụ cao cấp', 2500000, 2);

  // Rooms
  const insertRoom = db.prepare('INSERT INTO rooms (room_number, room_type_id, floor, status, description) VALUES (?, ?, ?, ?, ?)');
  // Floor 1 - Standard
  insertRoom.run('101', 1, 1, 'available', 'Phòng hướng vườn');
  insertRoom.run('102', 1, 1, 'available', 'Phòng hướng vườn');
  insertRoom.run('103', 1, 1, 'maintenance', 'Đang bảo trì');
  // Floor 2 - Deluxe
  insertRoom.run('201', 2, 2, 'available', 'Phòng hướng biển');
  insertRoom.run('202', 2, 2, 'available', 'Phòng hướng phố');
  insertRoom.run('203', 2, 2, 'available', 'Phòng hướng biển');
  // Floor 3 - Suite & Family
  insertRoom.run('301', 3, 3, 'available', 'Suite hướng biển');
  insertRoom.run('302', 4, 3, 'available', 'Phòng gia đình');
  insertRoom.run('303', 3, 3, 'available', 'Suite hướng núi');
  // Floor 4 - VIP
  insertRoom.run('401', 5, 4, 'available', 'VIP Penthouse hướng biển');
  insertRoom.run('402', 5, 4, 'available', 'VIP với bồn tắm jacuzzi');

  // Services
  const insertService = db.prepare('INSERT INTO services (name, category, unit_price, unit) VALUES (?, ?, ?, ?)');
  insertService.run('Bữa sáng', 'Ăn uống', 120000, 'người');
  insertService.run('Bữa tối', 'Ăn uống', 250000, 'người');
  insertService.run('Giặt ủi', 'Dịch vụ phòng', 80000, 'kg');
  insertService.run('Đưa đón sân bay', 'Vận chuyển', 350000, 'lượt');
  insertService.run('Thuê xe máy', 'Vận chuyển', 150000, 'ngày');
  insertService.run('Thuê xe ô tô', 'Vận chuyển', 800000, 'ngày');
  insertService.run('Spa & Massage', 'Giải trí', 450000, 'lần');
  insertService.run('Phòng tập gym', 'Giải trí', 100000, 'ngày');
  insertService.run('Bể bơi', 'Giải trí', 50000, 'ngày');
  insertService.run('Minibar', 'Ăn uống', 200000, 'lần');

  // Sample customers
  const insertCustomer = db.prepare('INSERT INTO customers (full_name, id_card, phone, email, address, nationality) VALUES (?, ?, ?, ?, ?, ?)');
  insertCustomer.run('Nguyễn Văn An', '001234567890', '0901234567', 'nguyenvanan@email.com', '123 Lê Lợi, Hà Nội', 'Việt Nam');
  insertCustomer.run('Trần Thị Bình', '001234567891', '0912345678', 'tranthibibh@email.com', '456 Nguyễn Huệ, TP.HCM', 'Việt Nam');
  insertCustomer.run('Lê Minh Cường', '001234567892', '0923456789', 'leminhcuong@email.com', '789 Trần Phú, Đà Nẵng', 'Việt Nam');
  insertCustomer.run('John Smith', 'US123456789', '+1234567890', 'john.smith@email.com', 'New York, USA', 'Mỹ');
  insertCustomer.run('Yamamoto Kenji', 'JP987654321', '+81901234567', 'yamamoto@email.com', 'Tokyo, Japan', 'Nhật Bản');
}

module.exports = { getDb };
