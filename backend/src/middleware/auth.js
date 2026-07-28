const jwt = require('jsonwebtoken');
const { get } = require('../database');

const JWT_SECRET = process.env.JWT_SECRET || 'emaarplus_secret_key_2026';

function auth(req, res, next) {
  const header = req.headers.authorization;
  if (!header || !header.startsWith('Bearer ')) {
    return res.status(401).json({ success: false, message: 'غير مصرح - يرجى تسجيل الدخول' });
  }

  try {
    const token = header.split(' ')[1];
    const decoded = jwt.verify(token, JWT_SECRET);
    const user = get('SELECT id, name, email, role, is_active FROM users WHERE id = ?', [decoded.id]);
    if (!user || !user.is_active) {
      return res.status(401).json({ success: false, message: 'المستخدم غير موجود أو معطل' });
    }
    req.user = user;
    next();
  } catch {
    return res.status(401).json({ success: false, message: 'الرمز غير صالح' });
  }
}

function authorize(...roles) {
  return (req, res, next) => {
    if (!roles.includes(req.user.role)) {
      return res.status(403).json({ success: false, message: 'ليس لديك صلاحية لهذا الإجراء' });
    }
    next();
  };
}

module.exports = { auth, authorize, JWT_SECRET };
