import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ApiError } from '../api/client'

/**
 * Loads data on mount with first-class loading / error / ready states and an
 * abort on unmount, so navigating away mid-request cannot set state on a
 * destroyed component.
 */
export function useAsyncData(loader, { immediate = true, initial = null } = {}) {
  const { t } = useI18n()

  const data = ref(initial)
  const status = ref(immediate ? 'loading' : 'idle')
  const error = ref(null)

  let controller = null

  async function load() {
    controller?.abort()
    controller = new AbortController()

    status.value = 'loading'
    error.value = null

    try {
      data.value = await loader({ signal: controller.signal })
      status.value = 'ready'
    } catch (caught) {
      if (caught?.name === 'AbortError') return

      error.value =
        caught instanceof ApiError ? t(`errors.${caught.kind}`) : t('errors.unknown')
      status.value = 'error'
    }
  }

  if (immediate) onMounted(load)
  onBeforeUnmount(() => controller?.abort())

  return { data, status, error, reload: load }
}
