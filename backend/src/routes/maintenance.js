const express = require('express');
const { run, get, all } = require('../database');
const { auth, authorize } = require('../middleware/auth');

const router = express.Router();

router.get('/', auth, (req, res) => {
  const { status, priority } = req.query;
  let sql = `SELECT mr.*, u.unit_number, b.name as building_name, l.name as location_name
    FROM maintenance_requests mr
    JOIN units u ON mr.unit_id = u.id
    JOIN buildings b ON u.building_id = b.id
    LEFT JOIN locations l ON b.location_id = l.id`;
  const conditions = [];
  const params = [];
  if (status) { conditions.push('mr.status = ?'); params.push(status); }
  if (priority) { conditions.push('mr.priority = ?'); params.push(priority); }
  if (conditions.length) sql += ' WHERE ' + conditions.join(' AND ');
  sql += ' ORDER BY CASE mr.priority WHEN "urgent" THEN 1 WHEN "high" THEN 2 WHEN "medium" THEN 3 ELSE 4 END, mr.created_at DESC';
  res.json({ success: true, data: all(sql, params) });
});

router.get('/:id', auth, (req, res) => {
  const r = get(`SELECT mr.*, u.unit_number, b.name as building_name, l.name as location_name
    FROM maintenance_requests mr JOIN units u ON mr.unit_id = u.id
    JOIN buildings b ON u.building_id = b.id LEFT JOIN locations l ON b.location_id = l.id
    WHERE mr.id = ?`, [req.params.id]);
  if (!r) return res.status(404).json({ success: false, message: 'طلب الصيانة غير موجود' });
  res.json({ success: true, data: r });
});

router.post('/', auth, authorize('super_admin', 'employee', 'guard'), (req, res) => {
  const { unit_id, description, priority } = req.body;
  if (!unit_id || !description) {
    return res.status(400).json({ success: false, message: 'الوحدة والوصف مطلوبان' });
  }
  const result = run('INSERT INTO maintenance_requests (unit_id, description, priority) VALUES (?, ?, ?)',
    [unit_id, description, priority || 'medium']);
  const request = get('SELECT * FROM maintenance_requests WHERE id = ?', [result.lastId]);
  res.status(201).json({ success: true, data: request, message: 'تم تسجيل طلب الصيانة بنجاح' });
});

router.put('/:id', auth, authorize('super_admin', 'employee'), (req, res) => {
  const { description, priority, status } = req.body;
  const existing = get('SELECT * FROM maintenance_requests WHERE id = ?', [req.params.id]);
  if (!existing) return res.status(404).json({ success: false, message: 'طلب الصيانة غير موجود' });

  run('UPDATE maintenance_requests SET description = ?, priority = ?, status = ?, updated_at = datetime("now") WHERE id = ?',
    [description || existing.description, priority || existing.priority, status || existing.status, req.params.id]);
  const request = get('SELECT * FROM maintenance_requests WHERE id = ?', [req.params.id]);
  res.json({ success: true, data: request, message: 'تم التحديث بنجاح' });
});

router.delete('/:id', auth, authorize('super_admin'), (req, res) => {
  const existing = get('SELECT * FROM maintenance_requests WHERE id = ?', [req.params.id]);
  if (!existing) return res.status(404).json({ success: false, message: 'طلب الصيانة غير موجود' });
  run('DELETE FROM maintenance_requests WHERE id = ?', [req.params.id]);
  res.json({ success: true, message: 'تم الحذف بنجاح' });
});

module.exports = router;
