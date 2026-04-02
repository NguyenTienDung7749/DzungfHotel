const express = require('express');
const router = express.Router();
const { getDb } = require('../db');

// GET all customers
router.get('/', (req, res) => {
  const db = getDb();
  const { search } = req.query;
  let query = 'SELECT * FROM customers WHERE 1=1';
  const params = [];
  if (search) {
    query += ' AND (full_name LIKE ? OR id_card LIKE ? OR phone LIKE ? OR email LIKE ?)';
    const s = `%${search}%`;
    params.push(s, s, s, s);
  }
  query += ' ORDER BY full_name';
  res.json(db.prepare(query).all(...params));
});

// GET single customer
router.get('/:id', (req, res) => {
  const db = getDb();
  const customer = db.prepare('SELECT * FROM customers WHERE id = ?').get(req.params.id);
  if (!customer) return res.status(404).json({ error: 'Khách hàng không tồn tại' });
  res.json(customer);
});

// GET customer booking history
router.get('/:id/bookings', (req, res) => {
  const db = getDb();
  const bookings = db.prepare(`
    SELECT b.*, r.room_number, rt.name as type_name, rt.price_per_night
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN room_types rt ON r.room_type_id = rt.id
    WHERE b.customer_id = ?
    ORDER BY b.created_at DESC
  `).all(req.params.id);
  res.json(bookings);
});

// POST create customer
router.post('/', (req, res) => {
  const db = getDb();
  const { full_name, id_card, phone, email, address, nationality } = req.body;
  if (!full_name || !id_card || !phone) {
    return res.status(400).json({ error: 'Thiếu thông tin bắt buộc' });
  }
  try {
    const result = db.prepare(
      'INSERT INTO customers (full_name, id_card, phone, email, address, nationality) VALUES (?, ?, ?, ?, ?, ?)'
    ).run(full_name, id_card, phone, email || '', address || '', nationality || 'Việt Nam');
    res.status(201).json({ id: result.lastInsertRowid, message: 'Thêm khách hàng thành công' });
  } catch (e) {
    if (e.message.includes('UNIQUE')) return res.status(409).json({ error: 'Số CMND/Căn cước đã tồn tại' });
    res.status(500).json({ error: e.message });
  }
});

// PUT update customer
router.put('/:id', (req, res) => {
  const db = getDb();
  const { full_name, id_card, phone, email, address, nationality } = req.body;
  try {
    db.prepare(
      'UPDATE customers SET full_name=?, id_card=?, phone=?, email=?, address=?, nationality=? WHERE id=?'
    ).run(full_name, id_card, phone, email, address, nationality, req.params.id);
    res.json({ message: 'Cập nhật khách hàng thành công' });
  } catch (e) {
    if (e.message.includes('UNIQUE')) return res.status(409).json({ error: 'Số CMND/Căn cước đã tồn tại' });
    res.status(500).json({ error: e.message });
  }
});

// DELETE customer
router.delete('/:id', (req, res) => {
  const db = getDb();
  const active = db.prepare(
    "SELECT COUNT(*) as c FROM bookings WHERE customer_id=? AND status NOT IN ('cancelled','checked_out')"
  ).get(req.params.id);
  if (active.c > 0) return res.status(409).json({ error: 'Không thể xóa khách hàng đang có đặt phòng' });
  db.prepare('DELETE FROM customers WHERE id=?').run(req.params.id);
  res.json({ message: 'Xóa khách hàng thành công' });
});

module.exports = router;
