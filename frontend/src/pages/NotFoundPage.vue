<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>

<template>
  <v-container class="py-16 px-4 px-sm-6 text-center" style="max-width: 620px">
    <v-icon icon="mdi-map-search-outline" size="48" color="primary" class="mb-4" />
    <h1 class="bc-title">{{ t('not_found.title') }}</h1>
    <p class="bc-body">{{ t('not_found.message') }}</p>
    <v-btn :to="{ name: 'home' }" color="primary" variant="flat" size="large">
      {{ t('not_found.home') }}
    </v-btn>
  </v-container>
</template>

<style scoped>
.bc-title {
  font-family: var(--bc-font-display);
  font-size: clamp(1.6rem, 4vw, 2.1rem);
  font-weight: 700;
  margin-bottom: 0.75rem;
}

.bc-body {
  line-height: 1.7;
  opacity: 0.78;
  margin-bottom: 1.75rem;
}
</style>
