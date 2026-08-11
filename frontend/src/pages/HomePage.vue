<script setup>
import { useI18n } from 'vue-i18n'
import PageHeader from '../components/PageHeader.vue'

const { t } = useI18n()

const services = [
  { key: 'centres', icon: 'mdi-map-marker-outline', to: { name: 'centres' } },
  { key: 'register', icon: 'mdi-account-plus-outline', to: { name: 'donor-register' } },
  { key: 'book', icon: 'mdi-calendar-check-outline', to: { name: 'appointment-book' } },
  { key: 'lookup', icon: 'mdi-magnify', to: { name: 'lookup' } },
]

const steps = ['step_1', 'step_2', 'step_3', 'step_4']
</script>

<template>
  <v-container class="py-8 py-sm-12 px-4 px-sm-6" style="max-width: 1080px">
    <PageHeader
      :eyebrow="t('home.eyebrow')"
      :title="t('home.title')"
      :intro="t('home.intro')"
    />

    <div class="d-flex ga-3 flex-wrap mb-10">
      <v-btn :to="{ name: 'donor-register' }" color="primary" variant="flat" size="large">
        {{ t('home.cta_register') }}
      </v-btn>
      <v-btn :to="{ name: 'centres' }" variant="outlined" size="large">
        {{ t('home.cta_centres') }}
      </v-btn>
    </div>

    <h2 class="bc-section">{{ t('home.services') }}</h2>

    <v-row class="mb-8">
      <v-col v-for="service in services" :key="service.key" cols="12" sm="6">
        <v-card :to="service.to" variant="outlined" class="bc-service pa-5 h-100">
          <v-icon :icon="service.icon" size="26" color="primary" class="mb-3" />
          <h3 class="bc-service__title">{{ t(`home.card_${service.key}_title`) }}</h3>
          <p class="bc-service__body">{{ t(`home.card_${service.key}_body`) }}</p>
        </v-card>
      </v-col>
    </v-row>

    <h2 class="bc-section">{{ t('home.steps_title') }}</h2>

    <ol class="bc-steps">
      <li v-for="(step, index) in steps" :key="step" class="bc-steps__item">
        <span class="bc-steps__num" aria-hidden="true">{{ index + 1 }}</span>
        <span>{{ t(`home.${step}`) }}</span>
      </li>
    </ol>
  </v-container>
</template>

<style scoped>
.bc-section {
  font-family: var(--bc-font-display);
  font-size: 1.35rem;
  font-weight: 700;
  letter-spacing: -0.01em;
  margin-bottom: 1.1rem;
}

.bc-service {
  transition: border-color 140ms ease;
  text-decoration: none;
}

.bc-service:hover {
  border-color: rgb(var(--v-theme-primary));
}

.bc-service__title {
  font-family: var(--bc-font-display);
  font-size: 1.1rem;
  font-weight: 700;
  line-height: 1.4;
  margin-bottom: 0.3rem;
}

.bc-service__body {
  font-size: 0.95rem;
  line-height: 1.65;
  opacity: 0.78;
  margin: 0;
}

.bc-steps {
  list-style: none;
  padding: 0;
  margin: 0;
}

.bc-steps__item {
  display: flex;
  align-items: flex-start;
  gap: 0.9rem;
  padding: 0.75rem 0;
  line-height: 1.7;
}

.bc-steps__num {
  flex: 0 0 auto;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  font-size: 0.85rem;
  font-weight: 700;
  color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.12);
  margin-top: 0.15rem;
}

@media (prefers-reduced-motion: reduce) {
  .bc-service {
    transition: none;
  }
}
</style>
