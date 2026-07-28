const express = require('express');
const { run, get, all } = require('../database');
const { auth, authorize } = require('../middleware/auth');

const router = express.Router();

router.get('/', auth, (req, res) => {
  const { building_id, status, unit_type } = req.query;
  let sql = `SELECT u.*, b.name as building_name, l.name as location_name,
    (SELECT first_name || ' ' || last_name FROM tenants t
     JOIN contracts c ON c.tenant_id = t.id WHERE c.unit_id = u.id AND c.status = 'active' LIMIT 1) as tenant_name
    FROM units u
    LEFT JOIN buildings b ON u.building_id = b.id
    LEFT JOIN locations l ON b.location_id = l.id`;
  const conditions = [];
  const params = [];
  if (building_id) { conditions.push('u.building_id = ?'); params.push(building_id); }
  if (status) { conditions.push('u.status = ?'); params.push(status); }
  if (unit_type) { conditions.push('u.unit_type = ?'); params.push(unit_type); }
  if (conditions.length) sql += ' WHERE ' + conditions.join(' AND ');
  sql += ' ORDER BY b.name, u.unit_number';
  const units = all(sql, params);
  res.json({ success: true, data: units });
});

router.get('/:id', auth, (req, res) => {
  const u = get(`SELECT u.*, b.name as building_name, l.name as location_name
    FROM units u LEFT JOIN buildings b ON u.building_id = b.id LEFT JOIN locations l ON b.location_id = l.id
    WHERE u.id = ?`, [req.params.id]);
  if (!u) return res.status(404).json({ success: false, message: 'الوحدة غير موجودة' });
  u.active_contract = get(`SELECT c.*, t.first_name, t.last_name, t.phone, t.email
    FROM contracts c JOIN tenants t ON c.tenant_id = t.id
    WHERE c.unit_id = ? AND c.status = 'active'`, [req.params.id]);
  u.recent_readings = all('SELECT * FROM utility_readings WHERE unit_id = ? ORDER BY reading_date DESC LIMIT 5', [req.params.id]);
  res.json({ success: true, data: u });
});

router.post('/', auth, authorize('super_admin', 'employee'), (req, res) => {
  const { building_id, unit_number, unit_type, floor, area, rent_amount, status } = req.body;
  if (!building_id || !unit_number) return res.status(400).json({ success: false, message: 'المبنى ورقم الوحدة مطلوبان' });
  const result = run('INSERT INTO units (building_id, unit_number, unit_type, floor, area, rent_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?)',
    [building_id, unit_number, unit_type || 'apartment', floor || 0, area || 0, rent_amount || 0, status || 'available']);
  const unit = get('SELECT * FROM units WHERE id = ?', [result.lastId]);
  res.status(201).json({ success: true, data: unit, message: 'تم إضافة الوحدة بنجاح' });
});

router.put('/:id', auth, authorize('super_admin', 'employee'), (req, res) => {
  const { building_id, unit_number, unit_type, floor, area, rent_amount, status } = req.body;
  const existing = get('SELECT * FROM units WHERE id = ?', [req.params.id]);
  if (!existing) return res.status(404).json({ success: false, message: 'الوحدة غير موجودة' });

  run(`UPDATE units SET building_id = ?, unit_number = ?, unit_type = ?, floor = ?, area = ?, rent_amount = ?, status = ?, updated_at = datetime("now") WHERE id = ?`,
    [building_id || existing.building_id, unit_number || existing.unit_number, unit_type || existing.unit_type,
     floor !== undefined ? floor : existing.floor, area !== undefined ? area : existing.area,
     rent_amount !== undefined ? rent_amount : existing.rent_amount, status || existing.status, req.params.id]);
  const unit = get('SELECT * FROM units WHERE id = ?', [req.params.id]);
  res.json({ success: true, data: unit, message: 'تم التحديث بنجاح' });
});

router.delete('/:id', auth, authorize('super_admin'), (req, res) => {
  const existing = get('SELECT * FROM units WHERE id = ?', [req.params.id]);
  if (!existing) return res.status(404).json({ success: false, message: 'الوحدة غير موجودة' });
  const contract = get('SELECT COUNT(*) as c FROM contracts WHERE unit_id = ? AND status = "active"', [req.params.id]);
  if (contract.c > 0) return res.status(400).json({ success: false, message: 'لا يمكن حذف الوحدة لوجود عقد نشط' });
  run('DELETE FROM units WHERE id = ?', [req.params.id]);
  res.json({ success: true, message: 'تم الحذف بنجاح' });
});

module.exports = router;
