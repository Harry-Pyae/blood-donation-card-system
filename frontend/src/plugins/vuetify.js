import 'vuetify/styles'
import '@mdi/font/css/materialdesignicons.css'

import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

// Components and directives are registered explicitly rather than through
// vite-plugin-vuetify. This keeps the build toolchain to @vitejs/plugin-vue
// only, so a Vite major upgrade cannot be blocked by a second plugin.
// Revisit tree-shaking when bundle size becomes a real constraint.
//
// Buttons no longer force uppercase in Vuetify 4; the sentence-case labels in
// src/i18n are therefore rendered as written, which is what Myanmar needs.

const shared = {
  'bloodcare-red': '#C41E3A',
  'bloodcare-pink': '#E8546B',
  error: '#B3261E',
  success: '#1E7A46',
  warning: '#B26A00',
  info: '#1F5FA9',
}

const light = {
  dark: false,
  colors: {
    ...shared,
    background: '#F6F7FB',
    surface: '#FFFFFF',
    'surface-variant': '#EEF0F6',
    primary: '#C41E3A',
    secondary: '#1B2A4A',
    'on-background': '#141A26',
    'on-surface': '#141A26',
  },
}

const dark = {
  dark: true,
  colors: {
    ...shared,
    background: '#0D1424',
    surface: '#16203A',
    'surface-variant': '#1E2A48',
    primary: '#E8546B',
    secondary: '#9FB3D9',
    'on-background': '#EAEEF7',
    'on-surface': '#EAEEF7',
  },
}

export default createVuetify({
  components,
  directives,
  theme: {
    // Vuetify 4 defaults to 'system'. BloodCare pins 'light' so the first
    // paint is predictable, then main.js applies any stored preference.
    defaultTheme: 'light',
    themes: { light, dark },
  },
  defaults: {
    VCard: { rounded: 'lg' },
    VBtn: { rounded: 'lg' },
  },
})
