const express = require('express');
const { run, get, all } = require('../database');
const { auth, authorize } = require('../middleware/auth');

const router = express.Router();

function generateInvoiceNumber() {
  const last = get('SELECT invoice_number FROM invoices ORDER BY id DESC LIMIT 1');
  if (!last) return 'INV-001';
  const num = parseInt(last.invoice_number.split('-')[1]) + 1;
  return 'INV-' + String(num).padStart(3, '0');
}

router.get('/', auth, (req, res) => {
  const { status, contract_id } = req.query;
  let sql = `SELECT i.*, c.contract_number, t.first_name, t.last_name, (t.first_name || ' ' || t.last_name) as tenant_name,
    u.unit_number, b.name as building_name
    FROM invoices i
    JOIN contracts c ON i.contract_id = c.id
    JOIN tenants t ON c.tenant_id = t.id
    JOIN units u ON c.unit_id = u.id
    JOIN buildings b ON u.building_id = b.id`;
  const conditions = [];
  const params = [];
  if (status) { conditions.push('i.status = ?'); params.push(status); }
  if (contract_id) { conditions.push('i.contract_id = ?'); params.push(contract_id); }
  if (conditions.length) sql += ' WHERE ' + conditions.join(' AND ');
  sql += ' ORDER BY i.issue_date DESC';
  res.json({ success: true, data: all(sql, params) });
});

router.get('/:id', auth, (req, res) => {
  const inv = get(`SELECT i.*, c.contract_number, c.tenant_id, c.unit_id,
    t.first_name, t.last_name, (t.first_name || ' ' || t.last_name) as tenant_name, t.phone, t.id_number,
    u.unit_number, u.unit_type, b.name as building_name
    FROM invoices i
    JOIN contracts c ON i.contract_id = c.id
    JOIN tenants t ON c.tenant_id = t.id
    JOIN units u ON c.unit_id = u.id
    JOIN buildings b ON u.building_id = b.id
    WHERE i.id = ?`, [req.params.id]);
  if (!inv) return res.status(404).json({ success: false, message: 'الفاتورة غير موجودة' });
  inv.payments = all('SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC', [req.params.id]);
  res.json({ success: true, data: inv });
});

router.post('/', auth, authorize('super_admin', 'employee'), (req, res) => {
  const { contract_id, issue_date, due_date, rent_amount, utilities_amount, notes } = req.body;
  if (!contract_id || !issue_date || !due_date) {
    return res.status(400).json({ success: false, message: 'العقد وتواريخ الفاتورة مطلوبة' });
  }
  const contract = get('SELECT * FROM contracts WHERE id = ?', [contract_id]);
  if (!contract) return res.status(400).json({ success: false, message: 'العقد غير موجود' });
  const total = (rent_amount || contract.rent_amount) + (utilities_amount || 0);
  const invoiceNumber = generateInvoiceNumber();
  const result = run('INSERT INTO invoices (invoice_number, contract_id, issue_date, due_date, rent_amount, utilities_amount, total_amount, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
    [invoiceNumber, contract_id, issue_date, due_date, rent_amount || contract.rent_amount, utilities_amount || 0, total, notes || null]);
  const invoice = get('SELECT * FROM invoices WHERE id = ?', [result.lastId]);
  res.status(201).json({ success: true, data: invoice, message: 'تم إصدار الفاتورة بنجاح' });
});

router.post('/generate-monthly', auth, authorize('super_admin', 'employee'), (req, res) => {
  const { month, year } = req.body;
  if (!month || !year) return res.status(400).json({ success: false, message: 'الشهر والسنة مطلوبان' });
  const activeContracts = all('SELECT * FROM contracts WHERE status = "active"');
  const issueDate = `${year}-${String(month).padStart(2, '0')}-01`;
  const dueDate = `${year}-${String(month).padStart(2, '0')}-28`;
  let generated = 0;
  for (const contract of activeContracts) {
    const existing = get('SELECT id FROM invoices WHERE contract_id = ? AND issue_date = ?', [contract.id, issueDate]);
    if (existing) continue;
    const invoiceNumber = generateInvoiceNumber();
    run('INSERT INTO invoices (invoice_number, contract_id, issue_date, due_date, rent_amount, total_amount) VALUES (?, ?, ?, ?, ?, ?)',
      [invoiceNumber, contract.id, issueDate, dueDate, contract.rent_amount, contract.rent_amount]);
    generated++;
  }
  res.json({ success: true, data: { generated }, message: `تم إصدار ${generated} فواتير بنجاح` });
});

router.put('/:id', auth, authorize('super_admin', 'employee'), (req, res) => {
  const { issue_date, due_date, rent_amount, utilities_amount, notes, status } = req.body;
  const existing = get('SELECT * FROM invoices WHERE id = ?', [req.params.id]);
  if (!existing) return res.status(404).json({ success: false, message: 'الفاتورة غير موجودة' });

  const total = (rent_amount !== undefined ? rent_amount : existing.rent_amount) + (utilities_amount !== undefined ? utilities_amount : existing.utilities_amount);
  run(`UPDATE invoices SET issue_date = ?, due_date = ?, rent_amount = ?, utilities_amount = ?, total_amount = ?, notes = ?, status = ?, updated_at = datetime("now") WHERE id = ?`,
    [issue_date || existing.issue_date, due_date || existing.due_date,
     rent_amount !== undefined ? rent_amount : existing.rent_amount,
     utilities_amount !== undefined ? utilities_amount : existing.utilities_amount,
     total, notes !== undefined ? notes : existing.notes, status || existing.status, req.params.id]);
  const invoice = get('SELECT * FROM invoices WHERE id = ?', [req.params.id]);
  res.json({ success: true, data: invoice, message: 'تم التحديث بنجاح' });
});

router.delete('/:id', auth, authorize('super_admin'), (req, res) => {
  const existing = get('SELECT * FROM invoices WHERE id = ?', [req.params.id]);
  if (!existing) return res.status(404).json({ success: false, message: 'الفاتورة غير موجودة' });
  if (existing.paid_amount > 0) return res.status(400).json({ success: false, message: 'لا يمكن حذف فاتورة مدفوعة جزئياً أو كاملاً' });
  run('DELETE FROM invoices WHERE id = ?', [req.params.id]);
  res.json({ success: true, message: 'تم الحذف بنجاح' });
});

module.exports = router;
