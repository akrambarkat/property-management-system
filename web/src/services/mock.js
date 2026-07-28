export const demoUser = {
  token: 'demo-token-emaarplus',
  user: {
    id: 1,
    name: 'أكرم بركات',
    email: 'admin@emaarplus.com',
    role: 'super_admin',
    phone: '0599000000',
    preferred_currency: 'ILS',
    is_active: true
  }
}

export const mockData = {
  locations: [
    { id: 1, name: 'الموقع الرئيسي', address: 'رام الله - شارع الإرسال', buildings_count: 3, is_active: true },
    { id: 2, name: 'موقع البيرة', address: 'البيرة - دوار المنارة', buildings_count: 2, is_active: true },
    { id: 3, name: 'موقع نابلس', address: 'نابلس - شارع القدس', buildings_count: 1, is_active: false }
  ],
  buildings: [
    { id: 1, name: 'برج EMAAR 1', location: { id: 1, name: 'الموقع الرئيسي' }, floors: 8, units_count: 32, is_active: true },
    { id: 2, name: 'برج EMAAR 2', location: { id: 1, name: 'الموقع الرئيسي' }, floors: 6, units_count: 24, is_active: true },
    { id: 3, name: 'مبنى السوق', location: { id: 2, name: 'موقع البيرة' }, floors: 3, units_count: 12, is_active: true },
    { id: 4, name: 'مبنى الشهداء', location: { id: 3, name: 'موقع نابلس' }, floors: 5, units_count: 20, is_active: false }
  ],
  units: [
    { id: 1, unit_number: '101', building: { id: 1, name: 'برج EMAAR 1' }, unit_type: 'apartment', floor: 1, area: 120, rent_amount: 1500, status: 'occupied' },
    { id: 2, unit_number: '102', building: { id: 1, name: 'برج EMAAR 1' }, unit_type: 'apartment', floor: 1, area: 100, rent_amount: 1300, status: 'occupied' },
    { id: 3, unit_number: '201', building: { id: 1, name: 'برج EMAAR 1' }, unit_type: 'apartment', floor: 2, area: 150, rent_amount: 1800, status: 'available' },
    { id: 4, unit_number: '001', building: { id: 3, name: 'مبنى السوق' }, unit_type: 'shop', floor: 0, area: 45, rent_amount: 2500, status: 'occupied' },
    { id: 5, unit_number: 'S-01', building: { id: 3, name: 'مبنى السوق' }, unit_type: 'warehouse', floor: -1, area: 200, rent_amount: 3000, status: 'maintenance' }
  ],
  tenants: [
    { id: 1, first_name: 'أحمد', last_name: 'محمود', id_number: '987654321', phone: '0595111111', email: 'ahmed@email.com', is_active: true },
    { id: 2, first_name: 'محمود', last_name: 'علي', id_number: '987654322', phone: '0595222222', email: 'mahmoud@email.com', is_active: true },
    { id: 3, first_name: 'سامر', last_name: 'حسن', id_number: '987654323', phone: '0595333333', email: 'samer@email.com', is_active: true },
    { id: 4, first_name: 'خالد', last_name: 'عمر', id_number: '987654324', phone: '0595444444', email: 'khaled@email.com', is_active: false },
    { id: 5, first_name: 'نادر', last_name: 'سليم', id_number: '987654325', phone: '0595555555', email: 'nader@email.com', is_active: true }
  ],
  contracts: [
    { id: 1, contract_number: 'CTR-001', tenant: { first_name: 'أحمد', last_name: 'محمود' }, unit: { unit_number: '101', building: { name: 'برج EMAAR 1' } }, rent_amount: 1500, start_date: '2026-01-01', end_date: '2027-01-01', contract_type: 'yearly', status: 'active' },
    { id: 2, contract_number: 'CTR-002', tenant: { first_name: 'محمود', last_name: 'علي' }, unit: { unit_number: '102', building: { name: 'برج EMAAR 1' } }, rent_amount: 1300, start_date: '2026-03-01', end_date: '2027-03-01', contract_type: 'yearly', status: 'active' },
    { id: 3, contract_number: 'CTR-003', tenant: { first_name: 'سامر', last_name: 'حسن' }, unit: { unit_number: '001', building: { name: 'مبنى السوق' } }, rent_amount: 2500, start_date: '2025-06-01', end_date: '2026-06-01', contract_type: 'yearly', status: 'expired' }
  ],
  invoices: [
    { id: 1, invoice_number: 'INV-001', contract: { tenant: { first_name: 'أحمد', last_name: 'محمود' } }, issue_date: '2026-07-01', due_date: '2026-08-01', total_amount: 1750, paid_amount: 1750, status: 'paid' },
    { id: 2, invoice_number: 'INV-002', contract: { tenant: { first_name: 'محمود', last_name: 'علي' } }, issue_date: '2026-07-01', due_date: '2026-08-01', total_amount: 1550, paid_amount: 1550, status: 'paid' },
    { id: 3, invoice_number: 'INV-003', contract: { tenant: { first_name: 'خالد', last_name: 'عمر' } }, issue_date: '2026-06-01', due_date: '2026-07-01', total_amount: 2500, paid_amount: 0, status: 'overdue' },
    { id: 4, invoice_number: 'INV-004', contract: { tenant: { first_name: 'نادر', last_name: 'سليم' } }, issue_date: '2026-05-01', due_date: '2026-06-15', total_amount: 3000, paid_amount: 500, status: 'overdue' }
  ],
  payments: [
    { id: 1, receipt_number: 'REC-001', invoice: { invoice_number: 'INV-001', contract: { tenant: { first_name: 'أحمد', last_name: 'محمود' } } }, amount: 1750, payment_date: '2026-07-15', payment_method: 'cash' },
    { id: 2, receipt_number: 'REC-002', invoice: { invoice_number: 'INV-002', contract: { tenant: { first_name: 'محمود', last_name: 'علي' } } }, amount: 1550, payment_date: '2026-07-20', payment_method: 'bank_transfer' }
  ],
  utility_readings: [
    { id: 1, unit: { unit_number: '101', building: { name: 'برج EMAAR 1' } }, utility_type: 'electricity', reading_date: '2026-07-01', previous_reading: 1200, current_reading: 1450, consumption: 250, unit_price: 0.50, total: 125 },
    { id: 2, unit: { unit_number: '102', building: { name: 'برج EMAAR 1' } }, utility_type: 'water', reading_date: '2026-07-01', previous_reading: 300, current_reading: 380, consumption: 80, unit_price: 3.00, total: 240 }
  ],
  expenses: [
    { id: 1, building: { name: 'برج EMAAR 1' }, category: 'maintenance', amount: 1500, expense_date: '2026-07-10', description: 'إصلاح مصعد' },
    { id: 2, building: { name: 'برج EMAAR 2' }, category: 'cleaning', amount: 800, expense_date: '2026-07-12', description: 'خدمات نظافة شهرية' },
    { id: 3, building: { name: 'مبنى السوق' }, category: 'electrical', amount: 2200, expense_date: '2026-07-05', description: 'تبديل أسلاك كهرباء' }
  ],
  maintenance_requests: [
    { id: 1, unit: { unit_number: 'S-01' }, description: 'تسرب مياه من السقف', priority: 'urgent', status: 'in_progress', created_at: '2026-07-20' },
    { id: 2, unit: { unit_number: '201' }, description: 'مكيف لا يعمل', priority: 'medium', status: 'pending', created_at: '2026-07-25' }
  ],
  users: [
    { id: 1, name: 'أكرم بركات', email: 'admin@emaarplus.com', phone: '0599000000', role: 'super_admin', is_active: true },
    { id: 2, name: 'سارة أحمد', email: 'employee@emaarplus.com', phone: '0599111111', role: 'employee', is_active: true },
    { id: 3, name: 'محمود حسن', email: 'guard@emaarplus.com', phone: '0599222222', role: 'guard', is_active: true }
  ],
  report: {
    total_rent: 34000, total_utilities: 4250, total_income: 38250,
    expenses_by_category: { maintenance: 1500, plumbing: 0, electrical: 2200, cleaning: 800, security: 0, general: 0 },
    total_expenses: 4500, net_profit: 33750,
    details: [
      { building: 'برج EMAAR 1', unit: '101', tenant: 'أحمد محمود', rent: 1500, utilities: 250, total: 1750 },
      { building: 'برج EMAAR 1', unit: '102', tenant: 'محمود علي', rent: 1300, utilities: 250, total: 1550 },
      { building: 'مبنى السوق', unit: '001', tenant: 'سامر حسن', rent: 2500, utilities: 400, total: 2900 }
    ]
  }
}

export const mockCurrencies = [
  { id: 1, code: 'ILS', name: 'شيكل', symbol: '₪', exchange_rate: 1.0000, is_default: true },
  { id: 2, code: 'JOD', name: 'دينار أردني', symbol: 'د.أ', exchange_rate: 0.2000, is_default: false },
  { id: 3, code: 'USD', name: 'دولار أمريكي', symbol: '$', exchange_rate: 0.2800, is_default: false }
]

export const mockSettings = {
  app_name: 'EMAARPlus',
  electricity_unit_price: 0.50,
  water_unit_price: 3.00
}
