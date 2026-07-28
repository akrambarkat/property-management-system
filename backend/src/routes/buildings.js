const express = require('express');
const { run, get, all } = require('../database');
const { auth, authorize } = require('../middleware/auth');

const router = express.Router();

router.get('/', auth, (req, res) => {
  const { location_id } = req.query;
  let sql = `SELECT b.*, l.name as location_name,
    (SELECT COUNT(*) FROM units WHERE building_id = b.id) as units_count,
    (SELECT COUNT(*) FROM units WHERE building_id = b.id AND status = 'occupied') as occupied_count,
    (SELECT COUNT(*) FROM units WHERE building_id = b.id AND status = 'available') as available_count
    FROM buildings b LEFT JOIN locations l ON b.location_id = l.id`;
  const params = [];
  if (location_id) { sql += ' WHERE b.location_id = ?'; params.push(location_id); }
  sql += ' ORDER BY b.id DESC';
  const buildings = all(sql, params);
  res.json({ success: true, data: buildings });
});

router.get('/:id', auth, (req, res) => {
  const b = get(`SELECT b.*, l.name as location_name FROM buildings b LEFT JOIN locations l ON b.location_id = l.id WHERE b.id = ?`, [req.params.id]);
  if (!b) return res.status(404).json({ success: false, message: 'المبنى غير موجود' });
  b.units = all('SELECT * FROM units WHERE building_id = ?', [req.params.id]);
  res.json({ success: true, data: b });
});

router.post('/', auth, authorize('super_admin', 'employee'), (req, res) => {
  const { location_id, name, floors } = req.body;
  if (!location_id || !name) return res.status(400).json({ success: false, message: 'الموقع والاسم مطلوبان' });
  const result = run('INSERT INTO buildings (location_id, name, floors) VALUES (?, ?, ?)', [location_id, name, floors || 1]);
  const building = get('SELECT * FROM buildings WHERE id = ?', [result.lastId]);
  res.status(201).json({ success: true, data: building, message: 'تم إضافة المبنى بنجاح' });
});

router.put('/:id', auth, authorize('super_admin', 'employee'), (req, res) => {
  const { location_id, name, floors, is_active } = req.body;
  const existing = get('SELECT * FROM buildings WHERE id = ?', [req.params.id]);
  if (!existing) return res.status(404).json({ success: false, message: 'المبنى غير موجود' });

  run('UPDATE buildings SET location_id = ?, name = ?, floors = ?, is_active = ?, updated_at = datetime("now") WHERE id = ?',
    [location_id || existing.location_id, name || existing.name, floors !== undefined ? floors : existing.floors, is_active !== undefined ? is_active : existing.is_active, req.params.id]);
  const building = get('SELECT * FROM buildings WHERE id = ?', [req.params.id]);
  res.json({ success: true, data: building, message: 'تم التحديث بنجاح' });
});

router.delete('/:id', auth, authorize('super_admin'), (req, res) => {
  const existing = get('SELECT * FROM buildings WHERE id = ?', [req.params.id]);
  if (!existing) return res.status(404).json({ success: false, message: 'المبنى غير موجود' });
  const units = get('SELECT COUNT(*) as c FROM units WHERE building_id = ?', [req.params.id]);
  if (units.c > 0) return res.status(400).json({ success: false, message: 'لا يمكن حذف المبنى لوجود وحدات مرتبطة' });
  run('DELETE FROM buildings WHERE id = ?', [req.params.id]);
  res.json({ success: true, message: 'تم الحذف بنجاح' });
});

module.exports = router;
