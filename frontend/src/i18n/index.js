import { createI18n } from 'vue-i18n'
import en from './en'
import my from './my'

export const SUPPORTED_LOCALES = ['en', 'my']
const STORAGE_KEY = 'bloodcare.locale'

function initialLocale() {
  const stored = localStorage.getItem(STORAGE_KEY)

  return SUPPORTED_LOCALES.includes(stored) ? stored : 'en'
}

const i18n = createI18n({
  legacy: false,
  locale: initialLocale(),
  fallbackLocale: 'en',
  messages: { en, my },
})

/**
 * Set the active locale and mirror it onto <html lang>, which browsers use to
 * pick Myanmar font shaping and line-breaking rules.
 */
export function setLocale(locale) {
  if (!SUPPORTED_LOCALES.includes(locale)) {
    return
  }

  i18n.global.locale.value = locale
  localStorage.setItem(STORAGE_KEY, locale)
  document.documentElement.setAttribute('lang', locale === 'my' ? 'my' : 'en')
}

setLocale(i18n.global.locale.value)

/**
 * Format an integer using the numeral system the active locale actually uses.
 * Myanmar text conventionally uses Myanmar digits (၀-၉), so a Western "4"
 * beside Myanmar copy reads as a mistake.
 */
export function localeNumber(value) {
  const locale = i18n.global.locale.value

  return new Intl.NumberFormat(
    locale === 'my' ? 'my-MM-u-nu-mymr' : 'en',
  ).format(value)
}

export default i18n
