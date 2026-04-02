const express = require('express');
const router = express.Router();
const { getDb } = require('../db');

// GET dashboard stats
router.get('/stats', (req, res) => {
  const db = getDb();
  const totalRooms = db.prepare('SELECT COUNT(*) as c FROM rooms').get().c;
  const availableRooms = db.prepare("SELECT COUNT(*) as c FROM rooms WHERE status='available'").get().c;
  const occupiedRooms = db.prepare("SELECT COUNT(*) as c FROM rooms WHERE status='occupied'").get().c;
  const reservedRooms = db.prepare("SELECT COUNT(*) as c FROM rooms WHERE status='reserved'").get().c;
  const maintenanceRooms = db.prepare("SELECT COUNT(*) as c FROM rooms WHERE status='maintenance'").get().c;
  const totalCustomers = db.prepare('SELECT COUNT(*) as c FROM customers').get().c;
  const activeBookings = db.prepare("SELECT COUNT(*) as c FROM bookings WHERE status IN ('confirmed','checked_in')").get().c;
  const todayCheckins = db.prepare(
    "SELECT COUNT(*) as c FROM bookings WHERE check_in_date=date('now') AND status IN ('confirmed','checked_in')"
  ).get().c;
  const todayCheckouts = db.prepare(
    "SELECT COUNT(*) as c FROM bookings WHERE check_out_date=date('now') AND status='checked_in'"
  ).get().c;

  const monthRevenue = db.prepare(`
    SELECT COALESCE(SUM(total_amount), 0) as total FROM bookings
    WHERE status='checked_out' AND strftime('%Y-%m', actual_check_out) = strftime('%Y-%m', 'now')
  `).get().total;

  const recentBookings = db.prepare(`
    SELECT b.id, b.check_in_date, b.check_out_date, b.status, b.total_amount,
           c.full_name, r.room_number
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    JOIN rooms r ON b.room_id = r.id
    ORDER BY b.created_at DESC LIMIT 5
  `).all();

  res.json({
    rooms: { total: totalRooms, available: availableRooms, occupied: occupiedRooms, reserved: reservedRooms, maintenance: maintenanceRooms },
    customers: totalCustomers,
    activeBookings,
    todayCheckins,
    todayCheckouts,
    monthRevenue,
    recentBookings
  });
});

module.exports = router;
