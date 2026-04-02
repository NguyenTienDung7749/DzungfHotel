const express = require('express');
const router = express.Router();
const { getDb } = require('../db');

// GET all services
router.get('/', (req, res) => {
  const db = getDb();
  res.json(db.prepare('SELECT * FROM services ORDER BY category, name').all());
});

// POST create service
router.post('/', (req, res) => {
  const db = getDb();
  const { name, category, unit_price, unit } = req.body;
  if (!name || !category || !unit_price) return res.status(400).json({ error: 'Thiếu thông tin bắt buộc' });
  const result = db.prepare(
    'INSERT INTO services (name, category, unit_price, unit) VALUES (?, ?, ?, ?)'
  ).run(name, category, unit_price, unit || 'lần');
  res.status(201).json({ id: result.lastInsertRowid, message: 'Thêm dịch vụ thành công' });
});

// PUT update service
router.put('/:id', (req, res) => {
  const db = getDb();
  const { name, category, unit_price, unit } = req.body;
  if (!name || !category || !unit_price) return res.status(400).json({ error: 'Thiếu thông tin bắt buộc' });
  db.prepare('UPDATE services SET name=?, category=?, unit_price=?, unit=? WHERE id=?')
    .run(name, category, unit_price, unit, req.params.id);
  res.json({ message: 'Cập nhật dịch vụ thành công' });
});

// DELETE service
router.delete('/:id', (req, res) => {
  const db = getDb();
  db.prepare('DELETE FROM services WHERE id=?').run(req.params.id);
  res.json({ message: 'Xóa dịch vụ thành công' });
});

module.exports = router;
