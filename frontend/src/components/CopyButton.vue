<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  value: { type: String, required: true },
  label: { type: String, default: null },
})

const { t } = useI18n()
const copied = ref(false)

async function copy() {
  try {
    await navigator.clipboard.writeText(props.value)
  } catch {
    // Clipboard access can be denied or unavailable over plain HTTP. Fall back
    // to selecting the text so the donor can still copy it manually.
    const range = document.createRange()
    const node = document.getElementById(`bc-copy-${props.value}`)
    if (node) {
      range.selectNodeContents(node)
      const selection = window.getSelection()
      selection?.removeAllRanges()
      selection?.addRange(range)
    }

    return
  }

  copied.value = true
  setTimeout(() => (copied.value = false), 2200)
}
</script>

<template>
  <div class="bc-copy">
    <div class="bc-copy__body">
      <p v-if="label" class="bc-copy__label bc-tracked">{{ label }}</p>
      <p :id="`bc-copy-${value}`" class="bc-copy__value">{{ value }}</p>
    </div>

    <v-btn
      variant="tonal"
      color="primary"
      :prepend-icon="copied ? 'mdi-check' : 'mdi-content-copy'"
      :aria-label="`${t('common.copy')}: ${value}`"
      @click="copy"
    >
      {{ copied ? t('common.copied') : t('common.copy') }}
    </v-btn>

    <span aria-live="polite" class="d-sr-only">
      {{ copied ? t('common.copied') : '' }}
    </span>
  </div>
</template>

<style scoped>
.bc-copy {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
  padding: 1.1rem 1.25rem;
  border-radius: 12px;
  border: 1px dashed rgba(var(--v-border-color), 0.34);
  background: rgba(var(--v-theme-primary), 0.05);
}

.bc-copy__body {
  flex-grow: 1;
  min-width: 0;
}

.bc-copy__label {
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.09em;
  text-transform: uppercase;
  opacity: 0.65;
  margin-bottom: 0.2rem;
}

.bc-copy__value {
  font-family: var(--bc-font-display);
  font-size: 1.3rem;
  font-weight: 700;
  letter-spacing: 0.01em;
  word-break: break-all;
  margin-bottom: 0;
}
</style>
