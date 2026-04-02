const express = require('express');
const router = express.Router();
const { getDb } = require('../db');

// GET all rooms with type info
router.get('/', (req, res) => {
  const db = getDb();
  const { status, floor, type } = req.query;
  let query = `
    SELECT r.*, rt.name as type_name, rt.price_per_night, rt.capacity
    FROM rooms r
    JOIN room_types rt ON r.room_type_id = rt.id
    WHERE 1=1
  `;
  const params = [];
  if (status) { query += ' AND r.status = ?'; params.push(status); }
  if (floor) { query += ' AND r.floor = ?'; params.push(floor); }
  if (type) { query += ' AND r.room_type_id = ?'; params.push(type); }
  query += ' ORDER BY r.room_number';
  res.json(db.prepare(query).all(...params));
});

// GET room types
router.get('/types', (req, res) => {
  const db = getDb();
  res.json(db.prepare('SELECT * FROM room_types ORDER BY price_per_night').all());
});

// GET single room
router.get('/:id', (req, res) => {
  const db = getDb();
  const room = db.prepare(`
    SELECT r.*, rt.name as type_name, rt.price_per_night, rt.capacity
    FROM rooms r
    JOIN room_types rt ON r.room_type_id = rt.id
    WHERE r.id = ?
  `).get(req.params.id);
  if (!room) return res.status(404).json({ error: 'Phòng không tồn tại' });
  res.json(room);
});

// POST create room
router.post('/', (req, res) => {
  const db = getDb();
  const { room_number, room_type_id, floor, status, description } = req.body;
  if (!room_number || !room_type_id || !floor) {
    return res.status(400).json({ error: 'Thiếu thông tin bắt buộc' });
  }
  try {
    const result = db.prepare(
      'INSERT INTO rooms (room_number, room_type_id, floor, status, description) VALUES (?, ?, ?, ?, ?)'
    ).run(room_number, room_type_id, floor, status || 'available', description || '');
    res.status(201).json({ id: result.lastInsertRowid, message: 'Tạo phòng thành công' });
  } catch (e) {
    if (e.message.includes('UNIQUE')) return res.status(409).json({ error: 'Số phòng đã tồn tại' });
    res.status(500).json({ error: e.message });
  }
});

// PUT update room
router.put('/:id', (req, res) => {
  const db = getDb();
  const { room_number, room_type_id, floor, status, description } = req.body;
  try {
    db.prepare(
      'UPDATE rooms SET room_number=?, room_type_id=?, floor=?, status=?, description=? WHERE id=?'
    ).run(room_number, room_type_id, floor, status, description, req.params.id);
    res.json({ message: 'Cập nhật phòng thành công' });
  } catch (e) {
    if (e.message.includes('UNIQUE')) return res.status(409).json({ error: 'Số phòng đã tồn tại' });
    res.status(500).json({ error: e.message });
  }
});

// DELETE room
router.delete('/:id', (req, res) => {
  const db = getDb();
  const active = db.prepare(
    "SELECT COUNT(*) as c FROM bookings WHERE room_id=? AND status NOT IN ('cancelled','checked_out')"
  ).get(req.params.id);
  if (active.c > 0) return res.status(409).json({ error: 'Không thể xóa phòng đang có đặt phòng' });
  db.prepare('DELETE FROM rooms WHERE id=?').run(req.params.id);
  res.json({ message: 'Xóa phòng thành công' });
});

// POST create room type
router.post('/types', (req, res) => {
  const db = getDb();
  const { name, description, price_per_night, capacity } = req.body;
  if (!name || !price_per_night) return res.status(400).json({ error: 'Thiếu thông tin bắt buộc' });
  try {
    const result = db.prepare(
      'INSERT INTO room_types (name, description, price_per_night, capacity) VALUES (?, ?, ?, ?)'
    ).run(name, description || '', price_per_night, capacity || 2);
    res.status(201).json({ id: result.lastInsertRowid, message: 'Tạo loại phòng thành công' });
  } catch (e) {
    if (e.message.includes('UNIQUE')) return res.status(409).json({ error: 'Tên loại phòng đã tồn tại' });
    res.status(500).json({ error: e.message });
  }
});

// PUT update room type
router.put('/types/:id', (req, res) => {
  const db = getDb();
  const { name, description, price_per_night, capacity } = req.body;
  if (!name || !price_per_night) return res.status(400).json({ error: 'Thiếu thông tin bắt buộc' });
  try {
    db.prepare(
      'UPDATE room_types SET name=?, description=?, price_per_night=?, capacity=? WHERE id=?'
    ).run(name, description, price_per_night, capacity, req.params.id);
    res.json({ message: 'Cập nhật loại phòng thành công' });
  } catch (e) {
    res.status(500).json({ error: e.message });
  }
});

module.exports = router;
