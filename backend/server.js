const app = require('./src/app');
const { initDatabase } = require('./src/database');

const PORT = process.env.PORT || 8000;

async function start() {
  try {
    console.log('جاري تهيئة قاعدة البيانات...');
    await initDatabase();
    console.log('قاعدة البيانات جاهزة');

    app.listen(PORT, () => {
      console.log(`EMAARPlus API يعمل على http://localhost:${PORT}/api/v1`);
    });
  } catch (err) {
    console.error('فشل في تشغيل الخادم:', err);
    process.exit(1);
  }
}

start();
