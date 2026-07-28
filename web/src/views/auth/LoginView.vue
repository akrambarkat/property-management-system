<template>
  <div class="login-page">
    <div class="login-card">
      <div class="login-header">
        <div class="login-logo">
          <i class="pi pi-building" style="font-size: 2.5rem; color: var(--primary);"></i>
        </div>
        <h1>EMAARPlus</h1>
        <p>نظام إدارة العقارات</p>
      </div>

      <form @submit.prevent="handleLogin" class="login-form">
        <div class="field">
          <label for="phone">رقم الهاتف</label>
          <div class="input-wrapper">
            <i class="pi pi-phone"></i>
            <input
              id="phone"
              v-model="form.phone"
              type="tel"
              placeholder="0599000000"
              required
              dir="ltr"
            />
          </div>
        </div>

        <div class="field">
          <label for="password">كلمة المرور</label>
          <div class="input-wrapper">
            <input
              id="password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="••••••••"
              required
            />
            <button type="button" class="toggle-password" @click="showPassword = !showPassword">
              <i :class="showPassword ? 'pi pi-eye' : 'pi pi-eye-slash'"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="login-btn" :disabled="loading">
          <i v-if="loading" class="pi pi-spin pi-spinner"></i>
          <span v-else>تسجيل الدخول</span>
        </button>

        <p v-if="error" class="error-msg">
          <i class="pi pi-exclamation-circle"></i>
          {{ error }}
        </p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({ phone: '', password: '' })
const showPassword = ref(false)
const loading = ref(false)
const error = ref('')

async function handleLogin() {
  loading.value = true
  error.value = ''

  try {
    const { data } = await api.post('/login', form)
    authStore.setAuth(data.data.token, data.data.user)
    router.push({ name: 'Dashboard' })
  } catch (err) {
    error.value = err.response?.data?.message || 'حدث خطأ في تسجيل الدخول'
  } finally {
    loading.value = false
  }
}

</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--primary) 0%, #0f1a2e 100%);
  padding: 20px;
}

.login-card {
  background: white;
  border-radius: 20px;
  padding: 48px 40px;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.login-header {
  text-align: center;
  margin-bottom: 36px;
}

.login-logo {
  width: 72px;
  height: 72px;
  background: var(--bg-secondary);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
}

.login-header h1 {
  font-size: 28px;
  font-weight: 700;
  color: var(--primary);
  margin-bottom: 4px;
}

.login-header p {
  color: var(--text-secondary);
  font-size: 15px;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field label {
  font-size: 14px;
  font-weight: 500;
  color: var(--text-primary);
}

.input-wrapper {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: var(--bg-secondary);
  transition: border-color 0.2s;
}

.input-wrapper:focus-within {
  border-color: var(--primary);
}

.input-wrapper i {
  color: var(--text-secondary);
  font-size: 1rem;
}

.input-wrapper input {
  flex: 1;
  border: none;
  background: none;
  font-family: var(--font-family);
  font-size: 15px;
  color: var(--text-primary);
  outline: none;
}

.input-wrapper input::placeholder {
  color: #9CA3AF;
}

.toggle-password {
  background: none;
  border: none;
  color: var(--text-secondary);
  cursor: pointer;
  padding: 0;
  font-size: 1rem;
}

.login-btn {
  width: 100%;
  padding: 14px;
  background: var(--primary);
  color: white;
  border: none;
  border-radius: 10px;
  font-family: var(--font-family);
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.login-btn:hover:not(:disabled) {
  background: var(--primary-hover);
}

.login-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.error-msg {
  text-align: center;
  color: var(--danger);
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.divider {
  text-align: center;
  position: relative;
  margin: 8px 0;
}

.divider::before,
.divider::after {
  content: '';
  position: absolute;
  top: 50%;
  width: 42%;
  height: 1px;
  background: var(--border);
}

.divider::before { left: 0; }
.divider::after { right: 0; }

.divider span {
  background: white;
  padding: 0 12px;
  color: var(--text-secondary);
  font-size: 13px;
}

.demo-btn {
  width: 100%;
  padding: 14px;
  background: transparent;
  color: var(--primary);
  border: 2px dashed var(--border);
  border-radius: 10px;
  font-family: var(--font-family);
  font-size: 15px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.demo-btn:hover {
  border-color: var(--primary);
  background: rgba(27, 42, 74, 0.05);
}

.demo-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
