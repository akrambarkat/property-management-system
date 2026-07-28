const express = require('express');
const bcrypt = require('bcryptjs');
const { run, get, all } = require('../database');
const { auth, authorize } = require('../middleware/auth');

const router = express.Router();

router.get('/', auth, authorize('super_admin'), (req, res) => {
  const users = all('SELECT id, name, email, phone, role, preferred_currency, is_active, created_at FROM users ORDER BY id');
  res.json({ success: true, data: users });
});

router.get('/:id', auth, authorize('super_admin'), (req, res) => {
  const user = get('SELECT id, name, email, phone, role, preferred_currency, is_active, created_at FROM users WHERE id = ?', [req.params.id]);
  if (!user) return res.status(404).json({ success: false, message: 'المستخدم غير موجود' });
  res.json({ success: true, data: user });
});

router.post('/', auth, authorize('super_admin'), (req, res) => {
  const { name, email, password, phone, role, preferred_currency } = req.body;
  if (!name || !email || !password) {
    return res.status(400).json({ success: false, message: 'الاسم والبريد الإلكتروني وكلمة المرور مطلوبة' });
  }
  const exists = get('SELECT id FROM users WHERE email = ?', [email]);
  if (exists) return res.status(400).json({ success: false, message: 'البريد الإلكتروني مسجل مسبقاً' });

  const hash = bcrypt.hashSync(password, 10);
  const result = run('INSERT INTO users (name, email, password, phone, role, preferred_currency) VALUES (?, ?, ?, ?, ?, ?)',
    [name, email, hash, phone || null, role || 'employee', preferred_currency || 'ILS']);
  const user = get('SELECT id, name, email, phone, role, preferred_currency, is_active FROM users WHERE id = ?', [result.lastId]);
  res.status(201).json({ success: true, data: user, message: 'تم إضافة المستخدم بنجاح' });
});

router.put('/:id', auth, authorize('super_admin'), (req, res) => {
  const { name, email, phone, role, preferred_currency, is_active, password } = req.body;
  const existing = get('SELECT * FROM users WHERE id = ?', [req.params.id]);
  if (!existing) return res.status(404).json({ success: false, message: 'المستخدم غير موجود' });

  if (email && email !== existing.email) {
    const exists = get('SELECT id FROM users WHERE email = ? AND id != ?', [email, req.params.id]);
    if (exists) return res.status(400).json({ success: false, message: 'البريد الإلكتروني مسجل مسبقاً' });
  }

  if (password) {
    const hash = bcrypt.hashSync(password, 10);
    run('UPDATE users SET password = ?, updated_at = datetime("now") WHERE id = ?', [hash, req.params.id]);
  }

  run('UPDATE users SET name = ?, email = ?, phone = ?, role = ?, preferred_currency = ?, is_active = ?, updated_at = datetime("now") WHERE id = ?',
    [name || existing.name, email || existing.email, phone !== undefined ? phone : existing.phone,
     role || existing.role, preferred_currency || existing.preferred_currency,
     is_active !== undefined ? is_active : existing.is_active, req.params.id]);
  const user = get('SELECT id, name, email, phone, role, preferred_currency, is_active FROM users WHERE id = ?', [req.params.id]);
  res.json({ success: true, data: user, message: 'تم التحديث بنجاح' });
});

router.delete('/:id', auth, authorize('super_admin'), (req, res) => {
  const existing = get('SELECT * FROM users WHERE id = ?', [req.params.id]);
  if (!existing) return res.status(404).json({ success: false, message: 'المستخدم غير موجود' });
  if (existing.role === 'super_admin') {
    const count = get('SELECT COUNT(*) as c FROM users WHERE role = "super_admin" AND is_active = 1');
    if (count.c <= 1) return res.status(400).json({ success: false, message: 'لا يمكن حذف آخر مدير عام' });
  }
  run('DELETE FROM users WHERE id = ?', [req.params.id]);
  res.json({ success: true, message: 'تم الحذف بنجاح' });
});

module.exports = router;
