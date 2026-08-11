import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import i18n from './i18n'
import vuetify from './plugins/vuetify'
import './styles/main.css'

const stored = localStorage.getItem('bloodcare.theme')
if (stored === 'light' || stored === 'dark') {
  vuetify.theme.change(stored)
}

createApp(App).use(router).use(i18n).use(vuetify).mount('#app')
