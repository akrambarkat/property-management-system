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

          <p v-if="error" class="error-message" role="alert">
            <i class="pi pi-exclamation-circle" aria-hidden="true"></i>
            <span>{{ error }}</span>
          </p>
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
  width: min(100%, 460px);
  padding: clamp(24px, 4vw, 40px);
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.4);
  box-shadow: 
    0 4px 30px rgba(0, 0, 0, 0.05),
    0 20px 50px rgba(27, 42, 74, 0.3);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.form-card:hover {
  transform: translateY(-2px);
  box-shadow: 
    0 8px 40px rgba(0, 0, 0, 0.08),
    0 24px 60px rgba(27, 42, 74, 0.35);
}

.form-header {
  text-align: center;
  margin-bottom: 30px;
}

.logo-mark {
  width: 72px;
  height: 72px;
  display: grid;
  place-items: center;
  margin: 0 auto 16px;
  border-radius: 20px;
  background: linear-gradient(135deg, #1B2A4A 0%, #243b63 100%);
  color: #C9A84C;
  box-shadow: 0 12px 24px rgba(27, 42, 74, 0.15);
  border: 1px solid rgba(201, 168, 76, 0.3);
}

.logo-mark i {
  font-size: 1.8rem;
}

.system-name {
  margin-bottom: 6px;
  color: #1B2A4A;
  font-size: 1.1rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.form-header h2 {
  margin: 0 0 8px;
  color: #1A1A2E;
  font-size: clamp(1.4rem, 2vw, 1.75rem);
  font-weight: 700;
  line-height: 1.25;
}

.tagline {
  color: #475569;
  font-size: 0.9rem;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
  direction: rtl;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.field label {
  color: #1B2A4A;
  font-size: 0.9rem;
  font-weight: 600;
  padding-inline-start: 4px;
}

.input-shell {
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 52px;
  padding: 0 16px;
  border: 1px solid rgba(27, 42, 74, 0.15);
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.9);
  transition: all 0.25s ease;
}

.input-shell:focus-within {
  border-color: #1B2A4A;
  box-shadow: 0 0 0 4px rgba(27, 42, 74, 0.08);
  background: #ffffff;
}

.input-icon {
  color: #64748b;
  font-size: 0.95rem;
}

.input-shell input {
  flex: 1;
  min-width: 0;
  border: none;
  outline: none;
  background: transparent;
  font: inherit;
  color: #1A1A2E;
  font-size: 0.95rem;
}

.input-shell input::placeholder {
  color: #94a3b8;
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
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s ease;
}

.icon-button:hover {
  background: #f1f5f9;
  color: #1A1A2E;
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
  color: #475569;
  font-weight: 500;
}

.remember-wrap input {
  width: 15px;
  height: 15px;
  accent-color: #1B2A4A;
  cursor: pointer;
}

.forgot-link {
  border: none;
  background: transparent;
  color: #C9A84C;
  cursor: pointer;
  font: inherit;
  font-weight: 600;
  padding: 0;
  text-decoration: none;
  transition: color 0.2s ease;
}

.forgot-link:hover {
  color: #b0913b;
  text-decoration: underline;
}

.submit-button {
  margin-top: 6px;
  min-height: 52px;
  width: 100%;
  border: none;
  border-radius: 14px;
  background: linear-gradient(135deg, #1B2A4A 0%, #243b63 100%);
  color: #ffffff;
  font: inherit;
  font-size: 0.98rem;
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
  margin-top: 4px;
  color: #ef4444;
  font-size: 0.88rem;
  background: rgba(239, 68, 68, 0.08);
  padding: 10px 14px;
  border-radius: 10px;
  border: 1px solid rgba(239, 68, 68, 0.15);
}

.field-error {
  color: #ef4444;
  font-size: 0.82rem;
  margin-top: 4px;
  padding-inline-start: 4px;
  display: block;
}

.input-shell.input-error-border {
  border-color: rgba(239, 68, 68, 0.5);
  background: rgba(239, 68, 68, 0.02);
}

.input-shell.input-error-border:focus-within {
  border-color: #ef4444;
  box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
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
