const express = require('express');
const { run, get, all } = require('../database');
const { auth, authorize } = require('../middleware/auth');

const router = express.Router();

function generateContractNumber() {
  const last = get('SELECT contract_number FROM contracts ORDER BY id DESC LIMIT 1');
  if (!last) return 'CTR-001';
  const num = parseInt(last.contract_number.split('-')[1]) + 1;
  return 'CTR-' + String(num).padStart(3, '0');
}

router.get('/', auth, (req, res) => {
  const { status, tenant_id, unit_id } = req.query;
  let sql = `SELECT c.*, t.first_name, t.last_name, (t.first_name || ' ' || t.last_name) as tenant_name,
    u.unit_number, b.name as building_name, l.name as location_name
    FROM contracts c
    JOIN tenants t ON c.tenant_id = t.id
    JOIN units u ON c.unit_id = u.id
    JOIN buildings b ON u.building_id = b.id
    LEFT JOIN locations l ON b.location_id = l.id`;
  const conditions = [];
  const params = [];
  if (status) { conditions.push('c.status = ?'); params.push(status); }
  if (tenant_id) { conditions.push('c.tenant_id = ?'); params.push(tenant_id); }
  if (unit_id) { conditions.push('c.unit_id = ?'); params.push(unit_id); }
  if (conditions.length) sql += ' WHERE ' + conditions.join(' AND ');
  sql += ' ORDER BY c.id DESC';
  res.json({ success: true, data: all(sql, params) });
});

router.get('/:id', auth, (req, res) => {
  const c = get(`SELECT c.*, t.first_name, t.last_name, (t.first_name || ' ' || t.last_name) as tenant_name, t.phone, t.email, t.id_number,
    u.unit_number, u.unit_type, u.floor, u.area, u.rent_amount as unit_rent_amount, b.name as building_name, l.name as location_name
    FROM contracts c
    JOIN tenants t ON c.tenant_id = t.id
    JOIN units u ON c.unit_id = u.id
    JOIN buildings b ON u.building_id = b.id
    LEFT JOIN locations l ON b.location_id = l.id
    WHERE c.id = ?`, [req.params.id]);
  if (!c) return res.status(404).json({ success: false, message: 'العقد غير موجود' });
  c.invoices = all('SELECT * FROM invoices WHERE contract_id = ? ORDER BY issue_date DESC', [req.params.id]);
  c.payments = all(`SELECT p.* FROM payments p JOIN invoices i ON p.invoice_id = i.id WHERE i.contract_id = ? ORDER BY p.payment_date DESC`, [req.params.id]);
  res.json({ success: true, data: c });
});

router.post('/', auth, authorize('super_admin', 'employee'), (req, res) => {
  const { tenant_id, unit_id, rent_amount, start_date, end_date, payment_frequency } = req.body;
  if (!tenant_id || !unit_id || !start_date || !end_date) {
    return res.status(400).json({ success: false, message: 'المستأجر والوحدة وتواريخ العقد مطلوبة' });
  }
  const activeContract = get('SELECT id FROM contracts WHERE unit_id = ? AND status = "active"', [unit_id]);
  if (activeContract) return res.status(400).json({ success: false, message: 'الوحدة لها عقد نشط بالفعل' });

  const unit = get('SELECT rent_amount FROM units WHERE id = ?', [unit_id]);
  const contractNumber = generateContractNumber();

  const result = run('INSERT INTO contracts (contract_number, tenant_id, unit_id, rent_amount, start_date, end_date, payment_frequency) VALUES (?, ?, ?, ?, ?, ?, ?)',
    [contractNumber, tenant_id, unit_id, rent_amount || unit?.rent_amount || 0, start_date, end_date, payment_frequency || 'monthly']);
  run('UPDATE units SET status = "occupied", updated_at = datetime("now") WHERE id = ?', [unit_id]);
  const contract = get('SELECT * FROM contracts WHERE id = ?', [result.lastId]);
  res.status(201).json({ success: true, data: contract, message: 'تم إنشاء العقد بنجاح' });
});

router.put('/:id', auth, authorize('super_admin', 'employee'), (req, res) => {
  const { tenant_id, unit_id, rent_amount, start_date, end_date, payment_frequency, status } = req.body;
  const existing = get('SELECT * FROM contracts WHERE id = ?', [req.params.id]);
  if (!existing) return res.status(404).json({ success: false, message: 'العقد غير موجود' });

  run(`UPDATE contracts SET tenant_id = ?, unit_id = ?, rent_amount = ?, start_date = ?, end_date = ?, payment_frequency = ?, status = ?, updated_at = datetime("now") WHERE id = ?`,
    [tenant_id || existing.tenant_id, unit_id || existing.unit_id, rent_amount || existing.rent_amount,
     start_date || existing.start_date, end_date || existing.end_date,
     payment_frequency || existing.payment_frequency, status || existing.status, req.params.id]);

  if (status === 'terminated' || status === 'expired') {
    run('UPDATE units SET status = "available", updated_at = datetime("now") WHERE id = ?', [existing.unit_id]);
  }

  const contract = get('SELECT * FROM contracts WHERE id = ?', [req.params.id]);
  res.json({ success: true, data: contract, message: 'تم التحديث بنجاح' });
});

router.delete('/:id', auth, authorize('super_admin'), (req, res) => {
  const existing = get('SELECT * FROM contracts WHERE id = ?', [req.params.id]);
  if (!existing) return res.status(404).json({ success: false, message: 'العقد غير موجود' });
  run('UPDATE units SET status = "available", updated_at = datetime("now") WHERE id = ?', [existing.unit_id]);
  run('DELETE FROM payments WHERE invoice_id IN (SELECT id FROM invoices WHERE contract_id = ?)', [req.params.id]);
  run('DELETE FROM invoices WHERE contract_id = ?', [req.params.id]);
  run('DELETE FROM contracts WHERE id = ?', [req.params.id]);
  res.json({ success: true, message: 'تم الحذف بنجاح' });
});

module.exports = router;
