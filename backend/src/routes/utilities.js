const express = require('express');
const { run, get, all } = require('../database');
const { auth, authorize } = require('../middleware/auth');

const router = express.Router();

router.get('/', auth, (req, res) => {
  const { unit_id, utility_type } = req.query;
  let sql = `SELECT ur.*, u.unit_number, b.name as building_name, l.name as location_name
    FROM utility_readings ur
    JOIN units u ON ur.unit_id = u.id
    JOIN buildings b ON u.building_id = b.id
    LEFT JOIN locations l ON b.location_id = l.id`;
  const conditions = [];
  const params = [];
  if (unit_id) { conditions.push('ur.unit_id = ?'); params.push(unit_id); }
  if (utility_type) { conditions.push('ur.utility_type = ?'); params.push(utility_type); }
  if (conditions.length) sql += ' WHERE ' + conditions.join(' AND ');
  sql += ' ORDER BY ur.reading_date DESC';
  res.json({ success: true, data: all(sql, params) });
});

router.get('/settings', auth, (req, res) => {
  const settings = all('SELECT * FROM settings WHERE key LIKE "%_unit_price"');
  res.json({ success: true, data: settings });
});

router.get('/:id', auth, (req, res) => {
  const r = get(`SELECT ur.*, u.unit_number, b.name as building_name
    FROM utility_readings ur JOIN units u ON ur.unit_id = u.id JOIN buildings b ON u.building_id = b.id
    WHERE ur.id = ?`, [req.params.id]);
  if (!r) return res.status(404).json({ success: false, message: 'القراءة غير موجودة' });
  res.json({ success: true, data: r });
});

router.post('/', auth, authorize('super_admin', 'employee', 'guard'), (req, res) => {
  const { unit_id, utility_type, reading_date, previous_reading, current_reading, unit_price } = req.body;
  if (!unit_id || !utility_type || !reading_date) {
    return res.status(400).json({ success: false, message: 'الوحدة ونوع الخدمة وتاريخ القراءة مطلوبة' });
  }
  if (current_reading === undefined || previous_reading === undefined) {
    return res.status(400).json({ success: false, message: 'القراءة الحالية والسابقة مطلوبة' });
  }
  const consumption = current_reading - previous_reading;
  const price = unit_price !== undefined ? unit_price : getUnitPrice(utility_type);
  const total = consumption * price;

  const result = run('INSERT INTO utility_readings (unit_id, utility_type, reading_date, previous_reading, current_reading, consumption, unit_price, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
    [unit_id, utility_type, reading_date, previous_reading, current_reading, consumption, price, total]);
  const reading = get('SELECT * FROM utility_readings WHERE id = ?', [result.lastId]);
  res.status(201).json({ success: true, data: reading, message: 'تم تسجيل القراءة بنجاح' });
});

router.put('/:id', auth, authorize('super_admin', 'employee'), (req, res) => {
  const { utility_type, reading_date, previous_reading, current_reading, unit_price } = req.body;
  const existing = get('SELECT * FROM utility_readings WHERE id = ?', [req.params.id]);
  if (!existing) return res.status(404).json({ success: false, message: 'القراءة غير موجودة' });

  const prev = previous_reading !== undefined ? previous_reading : existing.previous_reading;
  const curr = current_reading !== undefined ? current_reading : existing.current_reading;
  const consumption = curr - prev;
  const price = unit_price !== undefined ? unit_price : existing.unit_price;
  const total = consumption * price;

  run(`UPDATE utility_readings SET utility_type = ?, reading_date = ?, previous_reading = ?, current_reading = ?, consumption = ?, unit_price = ?, total = ? WHERE id = ?`,
    [utility_type || existing.utility_type, reading_date || existing.reading_date, prev, curr, consumption, price, total, req.params.id]);
  const reading = get('SELECT * FROM utility_readings WHERE id = ?', [req.params.id]);
  res.json({ success: true, data: reading, message: 'تم التحديث بنجاح' });
});

router.delete('/:id', auth, authorize('super_admin'), (req, res) => {
  const existing = get('SELECT * FROM utility_readings WHERE id = ?', [req.params.id]);
  if (!existing) return res.status(404).json({ success: false, message: 'القراءة غير موجودة' });
  run('DELETE FROM utility_readings WHERE id = ?', [req.params.id]);
  res.json({ success: true, message: 'تم الحذف بنجاح' });
});

function getUnitPrice(type) {
  const key = type + '_unit_price';
  const setting = get('SELECT value FROM settings WHERE key = ?', [key]);
  return setting ? parseFloat(setting.value) : 0;
}

module.exports = router;
