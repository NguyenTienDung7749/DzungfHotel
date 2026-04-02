const express = require('express');
const router = express.Router();
const { getDb } = require('../db');

function calcNights(checkIn, checkOut) {
  const d1 = new Date(checkIn);
  const d2 = new Date(checkOut);
  return Math.max(1, Math.round((d2 - d1) / (1000 * 60 * 60 * 24)));
}

// GET all bookings
router.get('/', (req, res) => {
  const db = getDb();
  const { status, customer_id, room_id, date } = req.query;
  let query = `
    SELECT b.*, c.full_name, c.phone, c.id_card, r.room_number, rt.name as type_name, rt.price_per_night
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    JOIN rooms r ON b.room_id = r.id
    JOIN room_types rt ON r.room_type_id = rt.id
    WHERE 1=1
  `;
  const params = [];
  if (status) { query += ' AND b.status = ?'; params.push(status); }
  if (customer_id) { query += ' AND b.customer_id = ?'; params.push(customer_id); }
  if (room_id) { query += ' AND b.room_id = ?'; params.push(room_id); }
  if (date) { query += ' AND b.check_in_date <= ? AND b.check_out_date > ?'; params.push(date, date); }
  query += ' ORDER BY b.created_at DESC';
  res.json(db.prepare(query).all(...params));
});

// GET single booking
router.get('/:id', (req, res) => {
  const db = getDb();
  const booking = db.prepare(`
    SELECT b.*, c.full_name, c.phone, c.id_card, c.email, c.address, c.nationality,
           r.room_number, r.floor, rt.name as type_name, rt.price_per_night, rt.capacity
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    JOIN rooms r ON b.room_id = r.id
    JOIN room_types rt ON r.room_type_id = rt.id
    WHERE b.id = ?
  `).get(req.params.id);
  if (!booking) return res.status(404).json({ error: 'Đặt phòng không tồn tại' });

  const services = db.prepare(`
    SELECT bs.*, s.name as service_name, s.category, s.unit
    FROM booking_services bs
    JOIN services s ON bs.service_id = s.id
    WHERE bs.booking_id = ?
  `).all(req.params.id);

  res.json({ ...booking, services });
});

// POST create booking
router.post('/', (req, res) => {
  const db = getDb();
  const { customer_id, room_id, check_in_date, check_out_date, adults, children, special_requests } = req.body;
  if (!customer_id || !room_id || !check_in_date || !check_out_date) {
    return res.status(400).json({ error: 'Thiếu thông tin bắt buộc' });
  }
  if (new Date(check_out_date) <= new Date(check_in_date)) {
    return res.status(400).json({ error: 'Ngày trả phòng phải sau ngày nhận phòng' });
  }

  // Check room availability
  const conflict = db.prepare(`
    SELECT COUNT(*) as c FROM bookings
    WHERE room_id=? AND status NOT IN ('cancelled','checked_out')
    AND check_in_date < ? AND check_out_date > ?
  `).get(room_id, check_out_date, check_in_date);
  if (conflict.c > 0) return res.status(409).json({ error: 'Phòng đã được đặt trong khoảng thời gian này' });

  const room = db.prepare('SELECT r.*, rt.price_per_night FROM rooms r JOIN room_types rt ON r.room_type_id=rt.id WHERE r.id=?').get(room_id);
  if (!room) return res.status(404).json({ error: 'Phòng không tồn tại' });
  if (room.status === 'maintenance') return res.status(409).json({ error: 'Phòng đang bảo trì' });

  const nights = calcNights(check_in_date, check_out_date);
  const total = nights * room.price_per_night;

  const result = db.prepare(`
    INSERT INTO bookings (customer_id, room_id, check_in_date, check_out_date, adults, children, special_requests, total_amount, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')
  `).run(customer_id, room_id, check_in_date, check_out_date, adults || 1, children || 0, special_requests || '', total);

  // Update room status to reserved
  db.prepare("UPDATE rooms SET status='reserved' WHERE id=?").run(room_id);

  res.status(201).json({ id: result.lastInsertRowid, total_amount: total, message: 'Đặt phòng thành công' });
});

// PUT update booking
router.put('/:id', (req, res) => {
  const db = getDb();
  const { check_in_date, check_out_date, adults, children, special_requests } = req.body;
  const booking = db.prepare('SELECT * FROM bookings WHERE id=?').get(req.params.id);
  if (!booking) return res.status(404).json({ error: 'Đặt phòng không tồn tại' });
  if (booking.status === 'checked_in' || booking.status === 'checked_out') {
    return res.status(409).json({ error: 'Không thể sửa đặt phòng đã check-in hoặc đã hoàn thành' });
  }

  const room = db.prepare('SELECT r.*, rt.price_per_night FROM rooms r JOIN room_types rt ON r.room_type_id=rt.id WHERE r.id=?').get(booking.room_id);
  const nights = calcNights(check_in_date, check_out_date);
  const total = nights * room.price_per_night;

  db.prepare(`
    UPDATE bookings SET check_in_date=?, check_out_date=?, adults=?, children=?, special_requests=?, total_amount=? WHERE id=?
  `).run(check_in_date, check_out_date, adults, children, special_requests, total, req.params.id);

  res.json({ message: 'Cập nhật đặt phòng thành công', total_amount: total });
});

// POST check-in
router.post('/:id/checkin', (req, res) => {
  const db = getDb();
  const booking = db.prepare('SELECT * FROM bookings WHERE id=?').get(req.params.id);
  if (!booking) return res.status(404).json({ error: 'Đặt phòng không tồn tại' });
  if (booking.status !== 'confirmed' && booking.status !== 'pending') {
    return res.status(409).json({ error: 'Không thể check-in đặt phòng này' });
  }
  const now = new Date().toISOString();
  db.prepare("UPDATE bookings SET status='checked_in', actual_check_in=? WHERE id=?").run(now, req.params.id);
  db.prepare("UPDATE rooms SET status='occupied' WHERE id=?").run(booking.room_id);
  res.json({ message: 'Check-in thành công' });
});

// POST check-out
router.post('/:id/checkout', (req, res) => {
  const db = getDb();
  const booking = db.prepare('SELECT * FROM bookings WHERE id=?').get(req.params.id);
  if (!booking) return res.status(404).json({ error: 'Đặt phòng không tồn tại' });
  if (booking.status !== 'checked_in') {
    return res.status(409).json({ error: 'Khách chưa check-in' });
  }

  // Calculate final total with services
  const services = db.prepare(`
    SELECT SUM(quantity * unit_price) as total FROM booking_services WHERE booking_id=?
  `).get(req.params.id);
  const serviceCost = services.total || 0;

  const room = db.prepare('SELECT r.*, rt.price_per_night FROM rooms r JOIN room_types rt ON r.room_type_id=rt.id WHERE r.id=?').get(booking.room_id);
  const nights = calcNights(booking.check_in_date, booking.check_out_date);
  const roomCost = nights * room.price_per_night;
  const totalAmount = roomCost + serviceCost;

  const now = new Date().toISOString();
  db.prepare(`
    UPDATE bookings SET status='checked_out', actual_check_out=?, total_amount=?, payment_status='paid', paid_amount=? WHERE id=?
  `).run(now, totalAmount, totalAmount, req.params.id);
  db.prepare("UPDATE rooms SET status='available' WHERE id=?").run(booking.room_id);
  res.json({ message: 'Check-out thành công', total_amount: totalAmount });
});

// POST cancel booking
router.post('/:id/cancel', (req, res) => {
  const db = getDb();
  const booking = db.prepare('SELECT * FROM bookings WHERE id=?').get(req.params.id);
  if (!booking) return res.status(404).json({ error: 'Đặt phòng không tồn tại' });
  if (booking.status === 'checked_in') return res.status(409).json({ error: 'Không thể hủy khi khách đang lưu trú' });
  if (booking.status === 'checked_out' || booking.status === 'cancelled') {
    return res.status(409).json({ error: 'Đặt phòng đã hoàn thành hoặc đã hủy' });
  }
  db.prepare("UPDATE bookings SET status='cancelled' WHERE id=?").run(req.params.id);
  db.prepare("UPDATE rooms SET status='available' WHERE id=(SELECT room_id FROM bookings WHERE id=?)").run(req.params.id);
  res.json({ message: 'Hủy đặt phòng thành công' });
});

// POST add service to booking
router.post('/:id/services', (req, res) => {
  const db = getDb();
  const { service_id, quantity } = req.body;
  const booking = db.prepare('SELECT * FROM bookings WHERE id=?').get(req.params.id);
  if (!booking) return res.status(404).json({ error: 'Đặt phòng không tồn tại' });
  if (booking.status !== 'checked_in') return res.status(409).json({ error: 'Chỉ có thể thêm dịch vụ khi khách đang lưu trú' });

  const service = db.prepare('SELECT * FROM services WHERE id=?').get(service_id);
  if (!service) return res.status(404).json({ error: 'Dịch vụ không tồn tại' });

  const result = db.prepare(
    'INSERT INTO booking_services (booking_id, service_id, quantity, unit_price) VALUES (?, ?, ?, ?)'
  ).run(req.params.id, service_id, quantity || 1, service.unit_price);
  res.status(201).json({ id: result.lastInsertRowid, message: 'Thêm dịch vụ thành công' });
});

module.exports = router;
