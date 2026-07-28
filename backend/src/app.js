const express = require('express');
const cors = require('cors');
const morgan = require('morgan');

const authRoutes = require('./routes/auth');
const locationRoutes = require('./routes/locations');
const buildingRoutes = require('./routes/buildings');
const unitRoutes = require('./routes/units');
const tenantRoutes = require('./routes/tenants');
const contractRoutes = require('./routes/contracts');
const invoiceRoutes = require('./routes/invoices');
const paymentRoutes = require('./routes/payments');
const utilityRoutes = require('./routes/utilities');
const expenseRoutes = require('./routes/expenses');
const maintenanceRoutes = require('./routes/maintenance');
const userRoutes = require('./routes/users');
const reportRoutes = require('./routes/reports');
const settingsRoutes = require('./routes/settings');

const app = express();

app.use(cors({ origin: true, credentials: true }));
app.use(express.json());
app.use(morgan('dev'));

app.use('/api/v1/auth', authRoutes);
app.use('/api/v1/locations', locationRoutes);
app.use('/api/v1/buildings', buildingRoutes);
app.use('/api/v1/units', unitRoutes);
app.use('/api/v1/tenants', tenantRoutes);
app.use('/api/v1/contracts', contractRoutes);
app.use('/api/v1/invoices', invoiceRoutes);
app.use('/api/v1/payments', paymentRoutes);
app.use('/api/v1/utility-readings', utilityRoutes);
app.use('/api/v1/expenses', expenseRoutes);
app.use('/api/v1/maintenance', maintenanceRoutes);
app.use('/api/v1/users', userRoutes);
app.use('/api/v1/reports', reportRoutes);
app.use('/api/v1/currencies', settingsRoutes);
app.use('/api/v1/settings', settingsRoutes);

app.get('/api/v1/health', (req, res) => {
  res.json({ success: true, message: 'EMAARPlus API is running', timestamp: new Date().toISOString() });
});

app.use((err, req, res, next) => {
  console.error(err.stack);
  res.status(500).json({ success: false, message: 'خطأ داخلي في الخادم' });
});

module.exports = app;
