const express = require('express');
const cors = require('cors');
const path = require('path');
const rateLimit = require('express-rate-limit');

const app = express();
const PORT = process.env.PORT || 3000;

const apiLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 500,
  standardHeaders: true,
  legacyHeaders: false,
});

app.use(cors());
app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

// API routes
app.use('/api', apiLimiter);
app.use('/api/rooms', require('./src/routes/rooms'));
app.use('/api/customers', require('./src/routes/customers'));
app.use('/api/bookings', require('./src/routes/bookings'));
app.use('/api/services', require('./src/routes/services'));
app.use('/api/dashboard', require('./src/routes/dashboard'));

// Serve frontend
app.get('/{*path}', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

app.listen(PORT, () => {
  console.log(`DzungfHotel server running on http://localhost:${PORT}`);
});

module.exports = app;
