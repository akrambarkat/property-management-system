const express = require('express');
const { run, get, all } = require('../database');
const { auth, authorize } = require('../middleware/auth');

const router = express.Router();

router.get('/', auth, (req, res) => {
  const { search } = req.query;
  let sql = 'SELECT *, (first_name || " " || last_name) as full_name FROM tenants';
  const params = [];
  if (search) {
    sql += ' WHERE first_name LIKE ? OR last_name LIKE ? OR id_number LIKE ? OR phone LIKE ?';
    const s = `%${search}%`;
    params.push(s, s, s, s);
  }
  sql += ' ORDER BY id DESC';
  res.json({ success: true, data: all(sql, params) });
});

router.get('/:id', auth, (req, res) => {
  const t = get('SELECT *, (first_name || " " || last_name) as full_name FROM tenants WHERE id = ?', [req.params.id]);
  if (!t) return res.status(404).json({ success: false, message: 'المستأجر غير موجود' });
  t.contracts = all(`SELECT c.*, u.unit_number, b.name as building_name
    FROM contracts c JOIN units u ON c.unit_id = u.id JOIN buildings b ON u.building_id = b.id
    WHERE c.tenant_id = ? ORDER BY c.id DESC`, [req.params.id]);
  t.invoices = all(`SELECT i.* FROM invoices i JOIN contracts c ON i.contract_id = c.id
    WHERE c.tenant_id = ? ORDER BY i.issue_date DESC`, [req.params.id]);
  res.json({ success: true, data: t });
});

router.post('/', auth, authorize('super_admin', 'employee'), (req, res) => {
  const { first_name, last_name, id_number, phone, phone2, email, notes } = req.body;
  if (!first_name || !last_name) return res.status(400).json({ success: false, message: 'الاسم الأول والأخير مطلوبان' });
  if (id_number) {
    const exists = get('SELECT id FROM tenants WHERE id_number = ?', [id_number]);
    if (exists) return res.status(400).json({ success: false, message: 'رقم الهوية مسجل مسبقاً' });
  }
  const result = run('INSERT INTO tenants (first_name, last_name, id_number, phone, phone2, email, notes) VALUES (?, ?, ?, ?, ?, ?, ?)',
    [first_name, last_name, id_number || null, phone || null, phone2 || null, email || null, notes || null]);
  const tenant = get('SELECT * FROM tenants WHERE id = ?', [result.lastId]);
  res.status(201).json({ success: true, data: tenant, message: 'تم إضافة المستأجر بنجاح' });
});

router.put('/:id', auth, authorize('super_admin', 'employee'), (req, res) => {
  const { first_name, last_name, id_number, phone, phone2, email, notes, is_active } = req.body;
  const existing = get('SELECT * FROM tenants WHERE id = ?', [req.params.id]);
  if (!existing) return res.status(404).json({ success: false, message: 'المستأجر غير موجود' });

  if (id_number && id_number !== existing.id_number) {
    const exists = get('SELECT id FROM tenants WHERE id_number = ? AND id != ?', [id_number, req.params.id]);
    if (exists) return res.status(400).json({ success: false, message: 'رقم الهوية مسجل مسبقاً' });
  }

  run(`UPDATE tenants SET first_name = ?, last_name = ?, id_number = ?, phone = ?, phone2 = ?, email = ?, notes = ?, is_active = ?, updated_at = datetime("now") WHERE id = ?`,
    [first_name || existing.first_name, last_name || existing.last_name, id_number !== undefined ? id_number : existing.id_number,
     phone !== undefined ? phone : existing.phone, phone2 !== undefined ? phone2 : existing.phone2,
     email !== undefined ? email : existing.email, notes !== undefined ? notes : existing.notes,
     is_active !== undefined ? is_active : existing.is_active, req.params.id]);
  const tenant = get('SELECT * FROM tenants WHERE id = ?', [req.params.id]);
  res.json({ success: true, data: tenant, message: 'تم التحديث بنجاح' });
});

router.delete('/:id', auth, authorize('super_admin'), (req, res) => {
  const existing = get('SELECT * FROM tenants WHERE id = ?', [req.params.id]);
  if (!existing) return res.status(404).json({ success: false, message: 'المستأجر غير موجود' });
  const contract = get('SELECT COUNT(*) as c FROM contracts WHERE tenant_id = ? AND status = "active"', [req.params.id]);
  if (contract.c > 0) return res.status(400).json({ success: false, message: 'لا يمكن حذف المستأجر لوجود عقد نشط' });
  run('DELETE FROM tenants WHERE id = ?', [req.params.id]);
  res.json({ success: true, message: 'تم الحذف بنجاح' });
});

module.exports = router;
