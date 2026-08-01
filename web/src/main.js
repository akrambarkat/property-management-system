import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import Aura from '@primevue/themes/aura'
import router from './router'
import App from './App.vue'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import SelectButton from 'primevue/selectbutton'
import DatePicker from 'primevue/datepicker'
import Tag from 'primevue/tag'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import RadioButton from 'primevue/radiobutton'
import InputSwitch from 'primevue/inputswitch'
import Drawer from 'primevue/drawer'

import './assets/styles/main.css'
import 'primeicons/primeicons.css'

// Initialize dark mode BEFORE app mounts so PrimeVue picks it up
const savedTheme = localStorage.getItem('theme')
if (savedTheme === 'dark') {
  document.documentElement.classList.add('p-dark')
  document.documentElement.setAttribute('data-theme', 'dark')
}

const app = createApp(App)

app.component('DataTable', DataTable)
app.component('Column', Column)
app.component('Card', Card)
app.component('Button', Button)
app.component('Dialog', Dialog)
app.component('InputText', InputText)
app.component('InputNumber', InputNumber)
app.component('Textarea', Textarea)
app.component('Select', Select)
app.component('SelectButton', SelectButton)
app.component('DatePicker', DatePicker)
app.component('Tag', Tag)
app.component('IconField', IconField)
app.component('InputIcon', InputIcon)
app.component('RadioButton', RadioButton)
app.component('InputSwitch', InputSwitch)
app.component('Drawer', Drawer)

app.use(createPinia())
app.use(router)
app.use(PrimeVue, {
  theme: {
    preset: Aura,
    options: {
      prefix: 'p',
      darkModeSelector: '.p-dark',
      cssLayer: false
    }
  }
})

app.mount('#app')
