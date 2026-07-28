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

import './assets/styles/main.css'
import 'primeicons/primeicons.css'

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

app.use(createPinia())
app.use(router)
app.use(PrimeVue, {
  theme: {
    preset: Aura,
    options: {
      prefix: 'p',
      darkModeSelector: false,
      cssLayer: false
    }
  }
})

app.mount('#app')
