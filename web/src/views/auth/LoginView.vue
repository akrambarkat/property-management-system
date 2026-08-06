<template>
  <div class="login-page">
    <div class="background-overlay"></div>
    <main class="login-container">
      <div class="form-card">
        <header class="form-header">
          <div class="logo-mark" aria-hidden="true">
            <i class="pi pi-building"></i>
          </div>
          <p class="system-name">AqarMaster</p>
          <h2>تسجيل الدخول إلى النظام</h2>
          <p class="tagline">النظام الذكي لإدارة العقارات والمرافق</p>
        </header>

        <form class="login-form" @submit.prevent="handleLogin" novalidate>
          <p v-if="error" class="error-message" role="alert">
            <i class="pi pi-exclamation-circle" aria-hidden="true"></i>
            <span>{{ error }}</span>
          </p>
          <div class="field">
            <label for="identifier">رقم الهاتف أو البريد الإلكتروني</label>
            <div class="input-shell" :class="{ 'input-error-border': errors.identifier }">
              <i class="pi pi-user input-icon" aria-hidden="true"></i>
              <input
                id="identifier"
                v-model="form.identifier"
                type="text"
                inputmode="email"
                autocomplete="username"
                placeholder="0599000000 أو name@domain.com"
                dir="ltr"
                required
                @input="errors.identifier = ''"
              />
            </div>
            <span v-if="errors.identifier" class="field-error">{{ errors.identifier }}</span>
          </div>

          <div class="field">
            <label for="password">كلمة المرور</label>
            <div class="input-shell" :class="{ 'input-error-border': errors.password }">
              <i class="pi pi-lock input-icon" aria-hidden="true"></i>
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                placeholder="••••••••"
                required
                @input="errors.password = ''"
              />
              <button
                type="button"
                class="icon-button"
                @click="showPassword = !showPassword"
                :aria-label="showPassword ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'"
              >
                <i :class="showPassword ? 'pi pi-eye' : 'pi pi-eye-slash'" aria-hidden="true"></i>
              </button>
            </div>
            <span v-if="errors.password" class="field-error">{{ errors.password }}</span>
          </div>

          <div class="form-meta">
            <div class="remember-wrap">
              <input id="remember" v-model="rememberMe" type="checkbox" />
              <label for="remember">تذكرني</label>
            </div>

            <button type="button" class="forgot-link" @click="handleForgotPassword">
              نسيت كلمة المرور؟
            </button>
          </div>

          <button type="submit" class="submit-button" :disabled="loading">
            <i v-if="loading" class="pi pi-spin pi-spinner" aria-hidden="true"></i>
            <span>{{ loading ? 'جارٍ تسجيل الدخول...' : 'تسجيل الدخول' }}</span>
          </button>
        </form>
      </div>
    </main>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  identifier: '',
  password: '',
})

const showPassword = ref(false)
const rememberMe = ref(true)
const loading = ref(false)
const error = ref('')

const errors = reactive({
  identifier: '',
  password: '',
})

function validateForm() {
  let isValid = true
  errors.identifier = ''
  errors.password = ''

  if (!form.identifier.trim()) {
    errors.identifier = 'يرجى إدخال رقم الهاتف أو البريد الإلكتروني'
    isValid = false
  } else {
    const trimmedVal = form.identifier.trim()
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    const phonePattern = /^05\d{8}$/
    
    const isEmail = emailPattern.test(trimmedVal)
    const isPhone = phonePattern.test(trimmedVal) || /^\+?\d{8,15}$/.test(trimmedVal)

    if (!isEmail && !isPhone) {
      errors.identifier = 'يرجى إدخال بريد إلكتروني صالح أو رقم هاتف صحيح'
      isValid = false
    }
  }

  if (!form.password) {
    errors.password = 'يرجى إدخال كلمة المرور'
    isValid = false
  } else if (form.password.length < 6) {
    errors.password = 'يجب أن تكون كلمة المرور 6 خانات أو أكثر'
    isValid = false
  }

  return isValid
}

async function handleLogin() {
  if (!validateForm()) return

  loading.value = true
  error.value = ''

  try {
    const payload = {
      identifier: form.identifier.trim(),
      password: form.password,
    }

    const { data } = await api.post('/login', payload)
    authStore.setAuth(data.data.token, data.data.user)

    if (!rememberMe.value) {
      sessionStorage.setItem('aqarmaster_session', 'true')
    }

    router.push({ name: 'Dashboard' })
  } catch (err) {
    error.value = err.response?.data?.message || 'حدث خطأ غير متوقع أثناء تسجيل الدخول'
  } finally {
    loading.value = false
  }
}

function handleForgotPassword() {
  error.value = 'ميزة استعادة كلمة المرور غير مفعلة بعد.'
}
</script>

<style scoped>
.login-page {
  position: relative;
  min-height: 100dvh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  background-image: url("@/assets/images/login_bg.png");
  background-size: cover;
  background-position: center;
  font-family: var(--font-family);
  padding: 20px;
}

.background-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(27, 42, 74, 0.7) 0%, rgba(15, 23, 42, 0.85) 100%);
  backdrop-filter: blur(4px);
  z-index: 1;
}

.login-container {
  position: relative;
  z-index: 2;
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}

.form-card {
  width: min(100%, 420px);
  padding: clamp(18px, 3vw, 26px);
  border-radius: 20px;
  background: var(--bg-surface-elevated);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-lg);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.form-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-xl);
}

.form-header {
  text-align: center;
  margin-bottom: 18px;
}

.logo-mark {
  width: 58px;
  height: 58px;
  display: grid;
  place-items: center;
  margin: 0 auto 12px;
  border-radius: 16px;
  background: linear-gradient(135deg, #1B2A4A 0%, #243b63 100%);
  color: #C9A84C;
  box-shadow: 0 12px 24px rgba(27, 42, 74, 0.15);
  border: 1px solid rgba(201, 168, 76, 0.3);
}

.logo-mark i {
  font-size: 1.5rem;
}

.system-name {
  margin-bottom: 6px;
  color: var(--text-primary);
  font-size: 1.1rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.form-header h2 {
  margin: 0 0 4px;
  color: var(--text-primary);
  font-size: clamp(1.25rem, 2vw, 1.5rem);
  font-weight: 700;
  line-height: 1.25;
}

.tagline {
  color: var(--text-secondary);
  font-size: 0.85rem;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 14px;
  direction: rtl;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field label {
  color: var(--text-secondary);
  font-size: 0.9rem;
  font-weight: 600;
  padding-inline-start: 4px;
}

.input-shell {
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 46px;
  padding: 0 16px;
  border: 1px solid var(--input-border);
  border-radius: 12px;
  background: var(--input-bg);
  transition: all 0.25s ease;
}

.input-shell:focus-within {
  border-color: var(--border-active);
  box-shadow: var(--shadow-focus);
  background: var(--input-bg);
}

.input-icon {
  color: var(--text-muted);
  font-size: 0.95rem;
}

.input-shell input {
  flex: 1;
  min-width: 0;
  border: none;
  outline: none;
  background: transparent;
  font: inherit;
  color: var(--text-primary);
  font-size: 0.95rem;
}

.input-shell input::placeholder {
  color: var(--input-placeholder);
}

.icon-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  margin-inline-start: -4px;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: var(--text-muted);
  cursor: pointer;
  transition: all 0.2s ease;
}

.icon-button:hover {
  background: var(--bg-hover);
  color: var(--text-primary);
}

.form-meta {
  display: flex;
  flex-direction: row-reverse;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  font-size: 0.88rem;
  direction: ltr;
  margin-top: 2px;
}

.remember-wrap {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  direction: rtl;
  color: var(--text-secondary);
  font-weight: 500;
}

.remember-wrap input {
  width: 15px;
  height: 15px;
  accent-color: var(--accent);
  cursor: pointer;
}

.forgot-link {
  border: none;
  background: transparent;
  color: var(--accent);
  cursor: pointer;
  font: inherit;
  font-weight: 600;
  padding: 0;
  text-decoration: none;
  transition: color 0.2s ease;
}

.forgot-link:hover {
  color: var(--accent-hover);
  text-decoration: underline;
}

.submit-button {
  margin-top: 2px;
  min-height: 46px;
  width: 100%;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, #1B2A4A 0%, #243b63 100%);
  color: #ffffff;
  font: inherit;
  font-size: 0.95rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 10px 20px rgba(27, 42, 74, 0.18);
  transition: all 0.25s ease;
}

.submit-button:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 14px 28px rgba(27, 42, 74, 0.25);
  background: linear-gradient(135deg, #243b63 0%, #1B2A4A 100%);
}

.submit-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.error-message {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
  color: var(--danger-contrast);
  font-size: 0.85rem;
  background: var(--danger-bg);
  padding: 9px 14px;
  border-radius: 10px;
  border: 1px solid var(--danger-border);
}

.field-error {
  color: var(--danger-contrast);
  font-size: 0.82rem;
  margin-top: 4px;
  padding-inline-start: 4px;
  display: block;
}

.input-shell.input-error-border {
  border-color: var(--danger);
  background: var(--danger-bg);
}

.input-shell.input-error-border:focus-within {
  border-color: var(--danger);
  box-shadow: var(--shadow-focus);
}

@media (max-width: 480px) {
  .login-page {
    padding: 16px;
  }

  .form-card {
    padding: 24px 20px;
    border-radius: 20px;
  }
}
</style>
