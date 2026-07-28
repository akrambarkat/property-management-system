const express = require('express');
const { run, get, all } = require('../database');
const { auth, authorize } = require('../middleware/auth');

const router = express.Router();

router.get('/dashboard', auth, (req, res) => {
  const totalUnits = get('SELECT COUNT(*) as c FROM units');
  const occupiedUnits = get('SELECT COUNT(*) as c FROM units WHERE status = "occupied"');
  const availableUnits = get('SELECT COUNT(*) as c FROM units WHERE status = "available"');
  const maintenanceUnits = get('SELECT COUNT(*) as c FROM units WHERE status = "maintenance"');
  const totalTenants = get('SELECT COUNT(*) as c FROM tenants WHERE is_active = 1');
  const activeContracts = get('SELECT COUNT(*) as c FROM contracts WHERE status = "active"');
  const overdueInvoices = get('SELECT COUNT(*) as c FROM invoices WHERE status = "overdue"');
  const pendingMaintenance = get('SELECT COUNT(*) as c FROM maintenance_requests WHERE status = "pending"');

  const monthlyRevenue = get(`SELECT COALESCE(SUM(amount), 0) as total FROM payments
    WHERE payment_date >= date('now', 'start of month')`);
  const totalExpenses = get(`SELECT COALESCE(SUM(amount), 0) as total FROM expenses
    WHERE expense_date >= date('now', 'start of month')`);

  const recentPayments = all(`SELECT p.*, i.invoice_number,
    t.first_name || ' ' || t.last_name as tenant_name,
    u.unit_number, b.name as building_name
    FROM payments p
    JOIN invoices i ON p.invoice_id = i.id
    JOIN contracts c ON i.contract_id = c.id
    JOIN tenants t ON c.tenant_id = t.id
    JOIN units u ON c.unit_id = u.id
    JOIN buildings b ON u.building_id = b.id
    ORDER BY p.payment_date DESC LIMIT 5`);

  const overdueList = all(`SELECT i.*,
    t.first_name || ' ' || t.last_name as tenant_name,
    u.unit_number, b.name as building_name,
    (i.total_amount - i.paid_amount) as remaining
    FROM invoices i
    JOIN contracts c ON i.contract_id = c.id
    JOIN tenants t ON c.tenant_id = t.id
    JOIN units u ON c.unit_id = u.id
    JOIN buildings b ON u.building_id = b.id
    WHERE i.status IN ('overdue', 'partially_paid')
    ORDER BY i.due_date ASC LIMIT 5`);

  res.json({
    success: true, data: {
      stats: {
        total_units: totalUnits.c,
        occupied_units: occupiedUnits.c,
        available_units: availableUnits.c,
        maintenance_units: maintenanceUnits.c,
        total_tenants: totalTenants.c,
        active_contracts: activeContracts.c,
        overdue_invoices: overdueInvoices.c,
        pending_maintenance: pendingMaintenance.c,
        monthly_revenue: monthlyRevenue.total,
        monthly_expenses: totalExpenses.total,
        net_profit: monthlyRevenue.total - totalExpenses.total,
        occupancy_rate: totalUnits.c > 0 ? Math.round((occupiedUnits.c / totalUnits.c) * 100) : 0
      },
      recent_payments: recentPayments,
      overdue_invoices: overdueList
    }
  });
});

router.get('/profit-loss', auth, authorize('super_admin', 'employee'), (req, res) => {
  const { building_id, from, to } = req.query;
  const fromDate = from || '2026-01-01';
  const toDate = to || '2026-12-31';

  let incomeSql = `SELECT COALESCE(SUM(p.amount), 0) as total FROM payments p
    JOIN invoices i ON p.invoice_id = i.id
    JOIN contracts c ON i.contract_id = c.id
    JOIN units u ON c.unit_id = u.id`;
  let expenseSql = `SELECT COALESCE(SUM(amount), 0) as total FROM expenses`;
  const incomeParams = [];
  const expenseParams = [];
  const conditions = [];
  const expConditions = [];

  conditions.push('p.payment_date BETWEEN ? AND ?');
  incomeParams.push(fromDate, toDate);
  expConditions.push('expense_date BETWEEN ? AND ?');
  expenseParams.push(fromDate, toDate);

  if (building_id) {
    conditions.push('u.building_id = ?');
    incomeParams.push(building_id);
    expConditions.push('building_id = ?');
    expenseParams.push(building_id);
  }

  incomeSql += ' WHERE ' + conditions.join(' AND ');
  expenseSql += ' WHERE ' + expConditions.join(' AND ');

  const totalIncome = get(incomeSql, incomeParams);
  const totalExpenses = get(expenseSql, expenseParams);

  const byBuilding = all(`SELECT b.name as building_name, b.id as building_id,
    COALESCE((SELECT SUM(p.amount) FROM payments p JOIN invoices i ON p.invoice_id = i.id
      JOIN contracts c ON i.contract_id = c.id JOIN units u ON c.unit_id = u.id
      WHERE u.building_id = b.id AND p.payment_date BETWEEN ? AND ?), 0) as income,
    COALESCE((SELECT SUM(amount) FROM expenses WHERE building_id = b.id
      AND expense_date BETWEEN ? AND ?), 0) as expenses
    FROM buildings b ORDER BY b.name`, [fromDate, toDate, fromDate, toDate]);

  const byCategory = all(`SELECT category, SUM(amount) as total
    FROM expenses WHERE expense_date BETWEEN ? AND ?
    GROUP BY category ORDER BY total DESC`, [fromDate, toDate]);

  res.json({
    success: true, data: {
      total_income: totalIncome.total,
      total_expenses: totalExpenses.total,
      net_profit: totalIncome.total - totalExpenses.total,
      by_building: byBuilding,
      by_category: byCategory
    }
  });
});

router.get('/tenant-account/:tenantId', auth, authorize('super_admin', 'employee'), (req, res) => {
  const tenant = get('SELECT *, (first_name || " " || last_name) as full_name FROM tenants WHERE id = ?', [req.params.tenantId]);
  if (!tenant) return res.status(404).json({ success: false, message: 'المستأجر غير موجود' });

  const invoices = all(`SELECT i.* FROM invoices i JOIN contracts c ON i.contract_id = c.id
    WHERE c.tenant_id = ? ORDER BY i.issue_date DESC`, [req.params.tenantId]);
  const payments = all(`SELECT p.*, i.invoice_number FROM payments p
    JOIN invoices i ON p.invoice_id = i.id JOIN contracts c ON i.contract_id = c.id
    WHERE c.tenant_id = ? ORDER BY p.payment_date DESC`, [req.params.tenantId]);

  const totalInvoiced = invoices.reduce((sum, i) => sum + i.total_amount, 0);
  const totalPaid = payments.reduce((sum, p) => sum + p.amount, 0);

  res.json({
    success: true, data: {
      tenant,
      invoices,
      payments,
      total_invoiced: totalInvoiced,
      total_paid: totalPaid,
      balance: totalInvoiced - totalPaid
    }
  });
});

router.get('/overdue', auth, (req, res) => {
  const overdue = all(`SELECT i.*,
    t.first_name || ' ' || t.last_name as tenant_name, t.phone,
    u.unit_number, b.name as building_name,
    (i.total_amount - i.paid_amount) as remaining
    FROM invoices i
    JOIN contracts c ON i.contract_id = c.id
    JOIN tenants t ON c.tenant_id = t.id
    JOIN units u ON c.unit_id = u.id
    JOIN buildings b ON u.building_id = b.id
    WHERE i.status IN ('overdue', 'partially_paid')
    ORDER BY i.due_date ASC`);
  res.json({ success: true, data: overdue });
});

module.exports = router;
