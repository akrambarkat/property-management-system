const express = require('express');
const { run, get, all } = require('../database');
const { auth, authorize } = require('../middleware/auth');

const router = express.Router();

router.get('/', auth, (req, res) => {
  const { building_id, category } = req.query;
  let sql = `SELECT e.*, b.name as building_name, u.unit_number
    FROM expenses e
    LEFT JOIN buildings b ON e.building_id = b.id
    LEFT JOIN units u ON e.unit_id = u.id`;
  const conditions = [];
  const params = [];
  if (building_id) { conditions.push('e.building_id = ?'); params.push(building_id); }
  if (category) { conditions.push('e.category = ?'); params.push(category); }
  if (conditions.length) sql += ' WHERE ' + conditions.join(' AND ');
  sql += ' ORDER BY e.expense_date DESC';
  res.json({ success: true, data: all(sql, params) });
});

router.get('/:id', auth, (req, res) => {
  const e = get(`SELECT e.*, b.name as building_name, u.unit_number
    FROM expenses e LEFT JOIN buildings b ON e.building_id = b.id LEFT JOIN units u ON e.unit_id = u.id
    WHERE e.id = ?`, [req.params.id]);
  if (!e) return res.status(404).json({ success: false, message: 'المصروف غير موجود' });
  res.json({ success: true, data: e });
});

router.post('/', auth, authorize('super_admin', 'employee'), (req, res) => {
  const { building_id, unit_id, category, amount, expense_date, description } = req.body;
  if (!building_id || !amount || !expense_date) {
    return res.status(400).json({ success: false, message: 'المبنى والمبلغ وتاريخ المصروف مطلوبة' });
  }
  const result = run('INSERT INTO expenses (building_id, unit_id, category, amount, expense_date, description) VALUES (?, ?, ?, ?, ?, ?)',
    [building_id, unit_id || null, category || 'general', amount, expense_date, description || null]);
  const expense = get('SELECT * FROM expenses WHERE id = ?', [result.lastId]);
  res.status(201).json({ success: true, data: expense, message: 'تم تسجيل المصروف بنجاح' });
});

router.put('/:id', auth, authorize('super_admin', 'employee'), (req, res) => {
  const { building_id, unit_id, category, amount, expense_date, description } = req.body;
  const existing = get('SELECT * FROM expenses WHERE id = ?', [req.params.id]);
  if (!existing) return res.status(404).json({ success: false, message: 'المصروف غير موجود' });

  run('UPDATE expenses SET building_id = ?, unit_id = ?, category = ?, amount = ?, expense_date = ?, description = ? WHERE id = ?',
    [building_id || existing.building_id, unit_id !== undefined ? unit_id : existing.unit_id,
     category || existing.category, amount || existing.amount,
     expense_date || existing.expense_date, description !== undefined ? description : existing.description, req.params.id]);
  const expense = get('SELECT * FROM expenses WHERE id = ?', [req.params.id]);
  res.json({ success: true, data: expense, message: 'تم التحديث بنجاح' });
});

router.delete('/:id', auth, authorize('super_admin'), (req, res) => {
  const existing = get('SELECT * FROM expenses WHERE id = ?', [req.params.id]);
  if (!existing) return res.status(404).json({ success: false, message: 'المصروف غير موجود' });
  run('DELETE FROM expenses WHERE id = ?', [req.params.id]);
  res.json({ success: true, message: 'تم الحذف بنجاح' });
});

module.exports = router;
