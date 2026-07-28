const express = require('express');
const { run, get, all } = require('../database');
const { auth, authorize } = require('../middleware/auth');

const router = express.Router();

router.get('/', auth, (req, res) => {
  const locations = all(`SELECT l.*, (SELECT COUNT(*) FROM buildings WHERE location_id = l.id) as buildings_count
    FROM locations l ORDER BY l.id DESC`);
  res.json({ success: true, data: locations });
});

router.get('/:id', auth, (req, res) => {
  const location = get('SELECT * FROM locations WHERE id = ?', [req.params.id]);
  if (!location) return res.status(404).json({ success: false, message: 'الموقع غير موجود' });
  location.buildings_count = get('SELECT COUNT(*) as c FROM buildings WHERE location_id = ?', [req.params.id]).c;
  res.json({ success: true, data: location });
});

router.post('/', auth, authorize('super_admin', 'employee'), (req, res) => {
  const { name, address } = req.body;
  if (!name) return res.status(400).json({ success: false, message: 'اسم الموقع مطلوب' });
  const result = run('INSERT INTO locations (name, address) VALUES (?, ?)', [name, address || '']);
  const location = get('SELECT * FROM locations WHERE id = ?', [result.lastId]);
  res.status(201).json({ success: true, data: location, message: 'تم إضافة الموقع بنجاح' });
});

router.put('/:id', auth, authorize('super_admin', 'employee'), (req, res) => {
  const { name, address, is_active } = req.body;
  const existing = get('SELECT * FROM locations WHERE id = ?', [req.params.id]);
  if (!existing) return res.status(404).json({ success: false, message: 'الموقع غير موجود' });

  run('UPDATE locations SET name = ?, address = ?, is_active = ?, updated_at = datetime("now") WHERE id = ?',
    [name || existing.name, address !== undefined ? address : existing.address, is_active !== undefined ? is_active : existing.is_active, req.params.id]);
  const location = get('SELECT * FROM locations WHERE id = ?', [req.params.id]);
  res.json({ success: true, data: location, message: 'تم التحديث بنجاح' });
});

router.delete('/:id', auth, authorize('super_admin'), (req, res) => {
  const existing = get('SELECT * FROM locations WHERE id = ?', [req.params.id]);
  if (!existing) return res.status(404).json({ success: false, message: 'الموقع غير موجود' });
  const buildings = get('SELECT COUNT(*) as c FROM buildings WHERE location_id = ?', [req.params.id]);
  if (buildings.c > 0) return res.status(400).json({ success: false, message: 'لا يمكن حذف الموقع لوجود مباني مرتبطة' });
  run('DELETE FROM locations WHERE id = ?', [req.params.id]);
  res.json({ success: true, message: 'تم الحذف بنجاح' });
});

module.exports = router;
