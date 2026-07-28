const express = require('express');
const { run, get, all } = require('../database');
const { auth, authorize } = require('../middleware/auth');

const router = express.Router();

function generateReceiptNumber() {
  const last = get('SELECT receipt_number FROM payments ORDER BY id DESC LIMIT 1');
  if (!last) return 'REC-001';
  const num = parseInt(last.receipt_number.split('-')[1]) + 1;
  return 'REC-' + String(num).padStart(3, '0');
}

router.get('/', auth, (req, res) => {
  const { invoice_id, payment_method } = req.query;
  let sql = `SELECT p.*, i.invoice_number, i.total_amount as invoice_total,
    t.first_name, t.last_name, (t.first_name || ' ' || t.last_name) as tenant_name
    FROM payments p
    JOIN invoices i ON p.invoice_id = i.id
    JOIN contracts c ON i.contract_id = c.id
    JOIN tenants t ON c.tenant_id = t.id`;
  const conditions = [];
  const params = [];
  if (invoice_id) { conditions.push('p.invoice_id = ?'); params.push(invoice_id); }
  if (payment_method) { conditions.push('p.payment_method = ?'); params.push(payment_method); }
  if (conditions.length) sql += ' WHERE ' + conditions.join(' AND ');
  sql += ' ORDER BY p.payment_date DESC';
  res.json({ success: true, data: all(sql, params) });
});

router.get('/:id', auth, (req, res) => {
  const p = get(`SELECT p.*, i.invoice_number, i.total_amount as invoice_total, i.rent_amount, i.utilities_amount,
    t.first_name, t.last_name, (t.first_name || ' ' || t.last_name) as tenant_name, t.phone, t.id_number,
    u.unit_number, b.name as building_name, c.contract_number
    FROM payments p
    JOIN invoices i ON p.invoice_id = i.id
    JOIN contracts c ON i.contract_id = c.id
    JOIN tenants t ON c.tenant_id = t.id
    JOIN units u ON c.unit_id = u.id
    JOIN buildings b ON u.building_id = b.id
    WHERE p.id = ?`, [req.params.id]);
  if (!p) return res.status(404).json({ success: false, message: 'سند القبض غير موجود' });
  res.json({ success: true, data: p });
});

router.post('/', auth, authorize('super_admin', 'employee'), (req, res) => {
  const { invoice_id, amount, payment_date, payment_method, notes } = req.body;
  if (!invoice_id || !amount || !payment_date) {
    return res.status(400).json({ success: false, message: 'الفاتورة والمبلغ وتاريخ الدفع مطلوبة' });
  }
  const invoice = get('SELECT * FROM invoices WHERE id = ?', [invoice_id]);
  if (!invoice) return res.status(404).json({ success: false, message: 'الفاتورة غير موجودة' });
  if (invoice.status === 'paid') return res.status(400).json({ success: false, message: 'الفاتورة مدفوعة بالفعل' });
  if (amount > (invoice.total_amount - invoice.paid_amount)) {
    return res.status(400).json({ success: false, message: 'المبلغ يتجاوز المتبقي من الفاتورة' });
  }

  const receiptNumber = generateReceiptNumber();
  const result = run('INSERT INTO payments (receipt_number, invoice_id, amount, payment_date, payment_method, notes) VALUES (?, ?, ?, ?, ?, ?)',
    [receiptNumber, invoice_id, amount, payment_date, payment_method || 'cash', notes || null]);

  const newPaid = invoice.paid_amount + amount;
  let newStatus = 'partially_paid';
  if (newPaid >= invoice.total_amount) newStatus = 'paid';
  run('UPDATE invoices SET paid_amount = ?, status = ?, updated_at = datetime("now") WHERE id = ?', [newPaid, newStatus, invoice_id]);

  const payment = get('SELECT * FROM payments WHERE id = ?', [result.lastId]);
  res.status(201).json({ success: true, data: payment, message: 'تم تسجيل الدفع بنجاح' });
});

router.delete('/:id', auth, authorize('super_admin'), (req, res) => {
  const existing = get('SELECT * FROM payments WHERE id = ?', [req.params.id]);
  if (!existing) return res.status(404).json({ success: false, message: 'سند القبض غير موجود' });

  const invoice = get('SELECT * FROM invoices WHERE id = ?', [existing.invoice_id]);
  const newPaid = invoice.paid_amount - existing.amount;
  let newStatus = 'pending';
  if (newPaid > 0 && newPaid < invoice.total_amount) newStatus = 'partially_paid';
  else if (newPaid >= invoice.total_amount) newStatus = 'paid';
  run('UPDATE invoices SET paid_amount = ?, status = ?, updated_at = datetime("now") WHERE id = ?', [newPaid, newStatus, existing.invoice_id]);
  run('DELETE FROM payments WHERE id = ?', [req.params.id]);
  res.json({ success: true, message: 'تم الحذف بنجاح' });
});

module.exports = router;
