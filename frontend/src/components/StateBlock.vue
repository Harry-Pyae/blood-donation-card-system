<script setup>
import { useI18n } from 'vue-i18n'

defineProps({
  status: { type: String, required: true }, // loading | error | empty
  loadingLabel: { type: String, default: null },
  errorTitle: { type: String, default: null },
  errorMessage: { type: String, default: null },
  emptyTitle: { type: String, default: null },
  emptyMessage: { type: String, default: null },
  skeleton: { type: String, default: 'article' },
  skeletonCount: { type: Number, default: 3 },
})

defineEmits(['retry'])

const { t } = useI18n()
</script>

<template>
  <!-- Loading, error and empty are first-class states, not afterthoughts. -->
  <div v-if="status === 'loading'" role="status" aria-live="polite">
    <span class="d-sr-only">{{ loadingLabel ?? t('common.loading') }}</span>
    <v-row>
      <v-col v-for="n in skeletonCount" :key="n" cols="12" sm="6">
        <v-skeleton-loader :type="skeleton" class="rounded-lg" />
      </v-col>
    </v-row>
  </div>

  <v-card
    v-else-if="status === 'error'"
    variant="tonal"
    color="error"
    class="pa-6"
    role="alert"
  >
    <h2 class="text-title-large mb-2">{{ errorTitle ?? t('errors.title') }}</h2>
    <p class="mb-4">{{ errorMessage }}</p>
    <v-btn color="error" variant="flat" @click="$emit('retry')">
      {{ t('common.retry') }}
    </v-btn>
  </v-card>

  <v-card v-else-if="status === 'empty'" variant="tonal" class="pa-6" role="status">
    <h2 class="text-title-large mb-2">{{ emptyTitle }}</h2>
    <p class="mb-0">{{ emptyMessage }}</p>
  </v-card>
</template>
