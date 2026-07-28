const express = require('express');
const { run, get, all } = require('../database');
const { auth, authorize } = require('../middleware/auth');

const router = express.Router();

router.get('/currencies', auth, (req, res) => {
  res.json({ success: true, data: all('SELECT * FROM currencies WHERE is_active = 1 ORDER BY is_default DESC') });
});

router.get('/settings', auth, (req, res) => {
  const settings = all('SELECT * FROM settings');
  const obj = {};
  settings.forEach(s => { obj[s.key] = s.value; });
  res.json({ success: true, data: obj });
});

router.put('/settings', auth, authorize('super_admin'), (req, res) => {
  const updates = req.body;
  for (const [key, value] of Object.entries(updates)) {
    const existing = get('SELECT key FROM settings WHERE key = ?', [key]);
    if (existing) {
      run('UPDATE settings SET value = ?, updated_at = datetime("now") WHERE key = ?', [String(value), key]);
    } else {
      run('INSERT INTO settings (key, value) VALUES (?, ?)', [key, String(value)]);
    }
  }
  const settings = all('SELECT * FROM settings');
  const obj = {};
  settings.forEach(s => { obj[s.key] = s.value; });
  res.json({ success: true, data: obj, message: 'تم تحديث الإعدادات بنجاح' });
});

router.put('/currencies/:id', auth, authorize('super_admin'), (req, res) => {
  const { exchange_rate, is_default } = req.body;
  const existing = get('SELECT * FROM currencies WHERE id = ?', [req.params.id]);
  if (!existing) return res.status(404).json({ success: false, message: 'العملة غير موجودة' });

  if (is_default) {
    run('UPDATE currencies SET is_default = 0');
  }
  run('UPDATE currencies SET exchange_rate = ?, is_default = ? WHERE id = ?',
    [exchange_rate !== undefined ? exchange_rate : existing.exchange_rate,
     is_default !== undefined ? (is_default ? 1 : 0) : existing.is_default, req.params.id]);
  const currency = get('SELECT * FROM currencies WHERE id = ?', [req.params.id]);
  res.json({ success: true, data: currency, message: 'تم التحديث بنجاح' });
});

module.exports = router;
