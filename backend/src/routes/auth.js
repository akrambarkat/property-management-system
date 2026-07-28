const express = require('express');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const { run, get, all } = require('../database');
const { auth, JWT_SECRET } = require('../middleware/auth');

const router = express.Router();

router.post('/login', (req, res) => {
  const { email, password } = req.body;
  if (!email || !password) {
    return res.status(400).json({ success: false, message: 'البريد الإلكتروني وكلمة المرور مطلوبان' });
  }

  const user = get('SELECT * FROM users WHERE email = ?', [email]);
  if (!user) {
    return res.status(401).json({ success: false, message: 'البريد الإلكتروني أو كلمة المرور غير صحيحة' });
  }
  if (!user.is_active) {
    return res.status(403).json({ success: false, message: 'الحساب معطل' });
  }

  if (!bcrypt.compareSync(password, user.password)) {
    return res.status(401).json({ success: false, message: 'البريد الإلكتروني أو كلمة المرور غير صحيحة' });
  }

  const token = jwt.sign({ id: user.id, role: user.role }, JWT_SECRET, { expiresIn: '7d' });
  const { password: _, ...userData } = user;

  res.json({ success: true, data: { token, user: userData }, message: 'تم تسجيل الدخول بنجاح' });
});

router.get('/profile', auth, (req, res) => {
  const user = get('SELECT id, name, email, phone, role, preferred_currency, is_active, created_at FROM users WHERE id = ?', [req.user.id]);
  res.json({ success: true, data: user });
});

router.put('/profile', auth, (req, res) => {
  const { name, phone, preferred_currency, current_password, new_password } = req.body;

  if (new_password) {
    const user = get('SELECT password FROM users WHERE id = ?', [req.user.id]);
    if (!current_password || !bcrypt.compareSync(current_password, user.password)) {
      return res.status(400).json({ success: false, message: 'كلمة المرور الحالية غير صحيحة' });
    }
    const hash = bcrypt.hashSync(new_password, 10);
    run('UPDATE users SET password = ?, updated_at = datetime("now") WHERE id = ?', [hash, req.user.id]);
  }

  if (name) run('UPDATE users SET name = ?, updated_at = datetime("now") WHERE id = ?', [name, req.user.id]);
  if (phone) run('UPDATE users SET phone = ?, updated_at = datetime("now") WHERE id = ?', [phone, req.user.id]);
  if (preferred_currency) run('UPDATE users SET preferred_currency = ?, updated_at = datetime("now") WHERE id = ?', [preferred_currency, req.user.id]);

  const user = get('SELECT id, name, email, phone, role, preferred_currency, is_active FROM users WHERE id = ?', [req.user.id]);
  res.json({ success: true, data: user, message: 'تم التحديث بنجاح' });
});

module.exports = router;
