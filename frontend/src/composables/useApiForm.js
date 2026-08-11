import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ApiError } from '../api/client'

/**
 * Shared submit handling for every public form.
 *
 * Owns the four things each form would otherwise reimplement: the in-flight
 * flag, the duplicate-submission guard, mapping Laravel's 422 `errors` object
 * onto individual fields, and turning transport failures into one translated
 * sentence.
 */
export function useApiForm(submitFn) {
  const { t } = useI18n()

  const submitting = ref(false)
  const fieldErrors = ref({})
  const formError = ref(null)

  function reset() {
    fieldErrors.value = {}
    formError.value = null
  }

  /** Vuetify expects an array of messages per field. */
  function errorsFor(field) {
    return fieldErrors.value[field] ?? []
  }

  const hasErrors = computed(
    () => Boolean(formError.value) || Object.keys(fieldErrors.value).length > 0,
  )

  async function submit(payload) {
    // Guard: a second click while the first request is open would create a
    // duplicate donor or a duplicate appointment.
    if (submitting.value) return { ok: false, duplicate: true }

    submitting.value = true
    reset()

    try {
      const data = await submitFn(payload)

      return { ok: true, data }
    } catch (error) {
      if (error instanceof ApiError && error.isValidation) {
        fieldErrors.value = error.errors ?? {}

        if (Object.keys(fieldErrors.value).length === 0) {
          formError.value = t('errors.validation')
        }
      } else if (error instanceof ApiError) {
        formError.value = t(`errors.${error.kind}`)
      } else if (error?.name === 'AbortError') {
        return { ok: false, aborted: true }
      } else {
        formError.value = t('errors.unknown')
      }

      return { ok: false }
    } finally {
      submitting.value = false
    }
  }

  return { submit, submitting, fieldErrors, formError, errorsFor, hasErrors, reset }
}
