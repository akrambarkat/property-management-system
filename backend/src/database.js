const initSqlJs = require('sql.js');
const fs = require('fs');
const path = require('path');
const bcrypt = require('bcryptjs');

const DB_PATH = path.join(__dirname, '..', 'data', 'emaarplus.db');

let db = null;

async function initDatabase() {
  const SQL = await initSqlJs();

  const dir = path.dirname(DB_PATH);
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });

  if (fs.existsSync(DB_PATH)) {
    const buffer = fs.readFileSync(DB_PATH);
    db = new SQL.Database(buffer);
  } else {
    db = new SQL.Database();
  }

  db.run('PRAGMA journal_mode = WAL');
  db.run('PRAGMA foreign_keys = ON');

  createTables();
  seedData();

  return db;
}

function saveDatabase() {
  if (!db) return;
  const data = db.export();
  const buffer = Buffer.from(data);
  fs.writeFileSync(DB_PATH, buffer);
}

function getDb() {
  return db;
}

function run(sql, params = []) {
  db.run(sql, params);
  saveDatabase();
  return { changes: db.getRowsModified(), lastId: lastInsertRowid() };
}

function lastInsertRowid() {
  const result = db.exec('SELECT last_insert_rowid() as id');
  if (result.length > 0) return result[0].values[0][0];
  return null;
}

function get(sql, params = []) {
  const stmt = db.prepare(sql);
  if (params.length) stmt.bind(params);
  let row = null;
  if (stmt.step()) row = stmt.getAsObject();
  stmt.free();
  return row;
}

function all(sql, params = []) {
  const stmt = db.prepare(sql);
  if (params.length) stmt.bind(params);
  const rows = [];
  while (stmt.step()) rows.push(stmt.getAsObject());
  stmt.free();
  return rows;
}

function createTables() {
  db.run(`CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    phone TEXT,
    role TEXT NOT NULL DEFAULT 'employee' CHECK(role IN ('super_admin','employee','guard')),
    preferred_currency TEXT DEFAULT 'ILS',
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
  )`);

  db.run(`CREATE TABLE IF NOT EXISTS currencies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,
    symbol TEXT NOT NULL,
    exchange_rate REAL DEFAULT 1.0,
    is_default INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT (datetime('now'))
  )`);

  db.run(`CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT,
    updated_at TEXT DEFAULT (datetime('now'))
  )`);

  db.run(`CREATE TABLE IF NOT EXISTS locations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    address TEXT,
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
  )`);

  db.run(`CREATE TABLE IF NOT EXISTS buildings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    location_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    floors INTEGER DEFAULT 1,
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (location_id) REFERENCES locations(id)
  )`);

  db.run(`CREATE TABLE IF NOT EXISTS units (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    building_id INTEGER NOT NULL,
    unit_number TEXT NOT NULL,
    unit_type TEXT NOT NULL DEFAULT 'apartment' CHECK(unit_type IN ('apartment','shop','warehouse','office')),
    floor INTEGER DEFAULT 0,
    area REAL,
    rent_amount REAL DEFAULT 0,
    status TEXT DEFAULT 'available' CHECK(status IN ('occupied','available','maintenance')),
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (building_id) REFERENCES buildings(id)
  )`);

  db.run(`CREATE TABLE IF NOT EXISTS tenants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    id_number TEXT UNIQUE,
    phone TEXT,
    phone2 TEXT,
    email TEXT,
    notes TEXT,
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
  )`);

  db.run(`CREATE TABLE IF NOT EXISTS contracts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contract_number TEXT UNIQUE NOT NULL,
    tenant_id INTEGER NOT NULL,
    unit_id INTEGER NOT NULL,
    rent_amount REAL NOT NULL,
    start_date TEXT NOT NULL,
    end_date TEXT NOT NULL,
    payment_frequency TEXT DEFAULT 'monthly' CHECK(payment_frequency IN ('monthly','yearly','one_time','installments')),
    status TEXT DEFAULT 'active' CHECK(status IN ('active','expired','terminated')),
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (unit_id) REFERENCES units(id)
  )`);

  db.run(`CREATE TABLE IF NOT EXISTS invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_number TEXT UNIQUE NOT NULL,
    contract_id INTEGER NOT NULL,
    issue_date TEXT NOT NULL,
    due_date TEXT NOT NULL,
    rent_amount REAL DEFAULT 0,
    utilities_amount REAL DEFAULT 0,
    total_amount REAL NOT NULL,
    paid_amount REAL DEFAULT 0,
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending','paid','overdue','partially_paid')),
    notes TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (contract_id) REFERENCES contracts(id)
  )`);

  db.run(`CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    receipt_number TEXT UNIQUE NOT NULL,
    invoice_id INTEGER NOT NULL,
    amount REAL NOT NULL,
    payment_date TEXT NOT NULL,
    payment_method TEXT DEFAULT 'cash' CHECK(payment_method IN ('cash','bank_transfer','check')),
    notes TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (invoice_id) REFERENCES invoices(id)
  )`);

  db.run(`CREATE TABLE IF NOT EXISTS utility_readings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unit_id INTEGER NOT NULL,
    utility_type TEXT NOT NULL CHECK(utility_type IN ('electricity','water','internet','cleaning')),
    reading_date TEXT NOT NULL,
    previous_reading REAL DEFAULT 0,
    current_reading REAL DEFAULT 0,
    consumption REAL DEFAULT 0,
    unit_price REAL DEFAULT 0,
    total REAL DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (unit_id) REFERENCES units(id)
  )`);

  db.run(`CREATE TABLE IF NOT EXISTS expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    building_id INTEGER NOT NULL,
    unit_id INTEGER,
    category TEXT NOT NULL DEFAULT 'general' CHECK(category IN ('maintenance','plumbing','electrical','cleaning','security','general')),
    amount REAL NOT NULL,
    expense_date TEXT NOT NULL,
    description TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (building_id) REFERENCES buildings(id),
    FOREIGN KEY (unit_id) REFERENCES units(id)
  )`);

  db.run(`CREATE TABLE IF NOT EXISTS maintenance_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unit_id INTEGER NOT NULL,
    description TEXT NOT NULL,
    priority TEXT DEFAULT 'medium' CHECK(priority IN ('low','medium','high','urgent')),
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending','in_progress','completed')),
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (unit_id) REFERENCES units(id)
  )`);

  db.run(`CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT NOT NULL,
    entity_type TEXT,
    entity_id INTEGER,
    description TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (user_id) REFERENCES users(id)
  )`);
}

function seedData() {
  const existingUsers = all('SELECT COUNT(*) as count FROM users');
  if (existingUsers[0] && existingUsers[0].count > 0) return;

  const hash = bcrypt.hashSync('password123', 10);

  run(`INSERT INTO users (name, email, password, phone, role, preferred_currency) VALUES (?, ?, ?, ?, ?, ?)`,
    ['أكرم بركات', 'admin@emaarplus.com', hash, '0599000000', 'super_admin', 'ILS']);
  run(`INSERT INTO users (name, email, password, phone, role, preferred_currency) VALUES (?, ?, ?, ?, ?, ?)`,
    ['سارة أحمد', 'employee@emaarplus.com', hash, '0599111111', 'employee', 'ILS']);
  run(`INSERT INTO users (name, email, password, phone, role, preferred_currency) VALUES (?, ?, ?, ?, ?, ?)`,
    ['محمود حسن', 'guard@emaarplus.com', hash, '0599222222', 'guard', 'ILS']);

  run(`INSERT INTO currencies (code, name, symbol, exchange_rate, is_default) VALUES ('ILS', 'شيكل', '₪', 1.0, 1)`);
  run(`INSERT INTO currencies (code, name, symbol, exchange_rate, is_default) VALUES ('JOD', 'دينار أردني', 'د.أ', 0.2, 0)`);
  run(`INSERT INTO currencies (code, name, symbol, exchange_rate, is_default) VALUES ('USD', 'دولار أمريكي', '$', 0.28, 0)`);

  run(`INSERT INTO settings (key, value) VALUES ('app_name', 'EMAARPlus')`);
  run(`INSERT INTO settings (key, value) VALUES ('electricity_unit_price', '0.50')`);
  run(`INSERT INTO settings (key, value) VALUES ('water_unit_price', '3.00')`);
  run(`INSERT INTO settings (key, value) VALUES ('internet_unit_price', '25.00')`);
  run(`INSERT INTO settings (key, value) VALUES ('cleaning_unit_price', '15.00')`);

  run(`INSERT INTO locations (name, address) VALUES ('الموقع الرئيسي', 'رام الله - شارع الإرسال')`);
  run(`INSERT INTO locations (name, address) VALUES ('موقع البيرة', 'البيرة - دوار المنارة')`);
  run(`INSERT INTO locations (name, address) VALUES ('موقع نابلس', 'نابلس - شارع القدس')`);

  run(`INSERT INTO buildings (location_id, name, floors) VALUES (1, 'برج EMAAR 1', 8)`);
  run(`INSERT INTO buildings (location_id, name, floors) VALUES (1, 'برج EMAAR 2', 6)`);
  run(`INSERT INTO buildings (location_id, name, floors) VALUES (2, 'مبنى السوق', 3)`);
  run(`INSERT INTO buildings (location_id, name, floors) VALUES (3, 'مبنى الشهداء', 5)`);

  run(`INSERT INTO units (building_id, unit_number, unit_type, floor, area, rent_amount, status) VALUES (1, '101', 'apartment', 1, 120, 1500, 'occupied')`);
  run(`INSERT INTO units (building_id, unit_number, unit_type, floor, area, rent_amount, status) VALUES (1, '102', 'apartment', 1, 100, 1300, 'occupied')`);
  run(`INSERT INTO units (building_id, unit_number, unit_type, floor, area, rent_amount, status) VALUES (1, '201', 'apartment', 2, 150, 1800, 'available')`);
  run(`INSERT INTO units (building_id, unit_number, unit_type, floor, area, rent_amount, status) VALUES (1, '202', 'apartment', 2, 110, 1400, 'available')`);
  run(`INSERT INTO units (building_id, unit_number, unit_type, floor, area, rent_amount, status) VALUES (2, '101', 'apartment', 1, 130, 1600, 'occupied')`);
  run(`INSERT INTO units (building_id, unit_number, unit_type, floor, area, rent_amount, status) VALUES (2, '201', 'apartment', 2, 140, 1700, 'available')`);
  run(`INSERT INTO units (building_id, unit_number, unit_type, floor, area, rent_amount, status) VALUES (3, '001', 'shop', 0, 45, 2500, 'occupied')`);
  run(`INSERT INTO units (building_id, unit_number, unit_type, floor, area, rent_amount, status) VALUES (3, '002', 'shop', 0, 60, 3000, 'available')`);
  run(`INSERT INTO units (building_id, unit_number, unit_type, floor, area, rent_amount, status) VALUES (3, 'S-01', 'warehouse', -1, 200, 3000, 'maintenance')`);
  run(`INSERT INTO units (building_id, unit_number, unit_type, floor, area, rent_amount, status) VALUES (4, '101', 'apartment', 1, 100, 1200, 'occupied')`);
  run(`INSERT INTO units (building_id, unit_number, unit_type, floor, area, rent_amount, status) VALUES (4, '102', 'apartment', 1, 90, 1100, 'occupied')`);

  run(`INSERT INTO tenants (first_name, last_name, id_number, phone, phone2, email) VALUES ('أحمد', 'محمود', '987654321', '0595111111', '0595111112', 'ahmed@email.com')`);
  run(`INSERT INTO tenants (first_name, last_name, id_number, phone, phone2, email) VALUES ('محمود', 'علي', '987654322', '0595222222', NULL, 'mahmoud@email.com')`);
  run(`INSERT INTO tenants (first_name, last_name, id_number, phone, phone2, email) VALUES ('سامر', 'حسن', '987654323', '0595333333', NULL, 'samer@email.com')`);
  run(`INSERT INTO tenants (first_name, last_name, id_number, phone, phone2, email) VALUES ('خالد', 'عمر', '987654324', '0595444444', NULL, 'khaled@email.com')`);
  run(`INSERT INTO tenants (first_name, last_name, id_number, phone, phone2, email) VALUES ('نادر', 'سليم', '987654325', '0595555555', NULL, 'nader@email.com')`);

  run(`INSERT INTO contracts (contract_number, tenant_id, unit_id, rent_amount, start_date, end_date, payment_frequency, status) VALUES ('CTR-001', 1, 1, 1500, '2026-01-01', '2027-01-01', 'monthly', 'active')`);
  run(`INSERT INTO contracts (contract_number, tenant_id, unit_id, rent_amount, start_date, end_date, payment_frequency, status) VALUES ('CTR-002', 2, 2, 1300, '2026-03-01', '2027-03-01', 'monthly', 'active')`);
  run(`INSERT INTO contracts (contract_number, tenant_id, unit_id, rent_amount, start_date, end_date, payment_frequency, status) VALUES ('CTR-003', 3, 7, 2500, '2025-06-01', '2026-06-01', 'yearly', 'expired')`);
  run(`INSERT INTO contracts (contract_number, tenant_id, unit_id, rent_amount, start_date, end_date, payment_frequency, status) VALUES ('CTR-004', 4, 10, 1200, '2026-01-01', '2027-01-01', 'monthly', 'active')`);
  run(`INSERT INTO contracts (contract_number, tenant_id, unit_id, rent_amount, start_date, end_date, payment_frequency, status) VALUES ('CTR-005', 5, 11, 1100, '2026-06-01', '2027-06-01', 'monthly', 'active')`);

  run(`INSERT INTO invoices (invoice_number, contract_id, issue_date, due_date, rent_amount, utilities_amount, total_amount, paid_amount, status) VALUES ('INV-001', 1, '2026-07-01', '2026-08-01', 1500, 250, 1750, 1750, 'paid')`);
  run(`INSERT INTO invoices (invoice_number, contract_id, issue_date, due_date, rent_amount, utilities_amount, total_amount, paid_amount, status) VALUES ('INV-002', 2, '2026-07-01', '2026-08-01', 1300, 250, 1550, 1550, 'paid')`);
  run(`INSERT INTO invoices (invoice_number, contract_id, issue_date, due_date, rent_amount, utilities_amount, total_amount, paid_amount, status) VALUES ('INV-003', 4, '2026-06-01', '2026-07-01', 1200, 0, 1200, 0, 'overdue')`);
  run(`INSERT INTO invoices (invoice_number, contract_id, issue_date, due_date, rent_amount, utilities_amount, total_amount, paid_amount, status) VALUES ('INV-004', 5, '2026-06-01', '2026-07-15', 1100, 0, 1100, 500, 'partially_paid')`);
  run(`INSERT INTO invoices (invoice_number, contract_id, issue_date, due_date, rent_amount, utilities_amount, total_amount, paid_amount, status) VALUES ('INV-005', 1, '2026-08-01', '2026-09-01', 1500, 300, 1800, 0, 'pending')`);

  run(`INSERT INTO payments (receipt_number, invoice_id, amount, payment_date, payment_method) VALUES ('REC-001', 1, 1750, '2026-07-15', 'cash')`);
  run(`INSERT INTO payments (receipt_number, invoice_id, amount, payment_date, payment_method) VALUES ('REC-002', 2, 1550, '2026-07-20', 'bank_transfer')`);
  run(`INSERT INTO payments (receipt_number, invoice_id, amount, payment_date, payment_method) VALUES ('REC-003', 4, 500, '2026-06-10', 'cash')`);

  run(`INSERT INTO utility_readings (unit_id, utility_type, reading_date, previous_reading, current_reading, consumption, unit_price, total) VALUES (1, 'electricity', '2026-07-01', 1200, 1450, 250, 0.50, 125)`);
  run(`INSERT INTO utility_readings (unit_id, utility_type, reading_date, previous_reading, current_reading, consumption, unit_price, total) VALUES (1, 'water', '2026-07-01', 300, 380, 80, 3.00, 240)`);
  run(`INSERT INTO utility_readings (unit_id, utility_type, reading_date, previous_reading, current_reading, consumption, unit_price, total) VALUES (2, 'electricity', '2026-07-01', 900, 1100, 200, 0.50, 100)`);
  run(`INSERT INTO utility_readings (unit_id, utility_type, reading_date, previous_reading, current_reading, consumption, unit_price, total) VALUES (5, 'electricity', '2026-07-01', 800, 1050, 250, 0.50, 125)`);

  run(`INSERT INTO expenses (building_id, category, amount, expense_date, description) VALUES (1, 'maintenance', 1500, '2026-07-10', 'إصلاح مصعد')`);
  run(`INSERT INTO expenses (building_id, category, amount, expense_date, description) VALUES (2, 'cleaning', 800, '2026-07-12', 'خدمات نظافة شهرية')`);
  run(`INSERT INTO expenses (building_id, category, amount, expense_date, description) VALUES (3, 'electrical', 2200, '2026-07-05', 'تبديل أسلاك كهرباء')`);
  run(`INSERT INTO expenses (building_id, category, amount, expense_date, description) VALUES (1, 'security', 1000, '2026-07-01', 'خدمات أمن شهرية')`);

  run(`INSERT INTO maintenance_requests (unit_id, description, priority, status) VALUES (9, 'تسرب مياه من السقف', 'urgent', 'in_progress')`);
  run(`INSERT INTO maintenance_requests (unit_id, description, priority, status) VALUES (3, 'مكيف لا يعمل', 'medium', 'pending')`);
  run(`INSERT INTO maintenance_requests (unit_id, description, priority, status) VALUES (1, 'إصلاح باب الشقة', 'low', 'completed')`);
}

module.exports = { initDatabase, getDb, saveDatabase, run, get, all };
