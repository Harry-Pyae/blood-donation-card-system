<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { fetchCentres } from '../api/endpoints'
import { useAsyncData } from '../composables/useAsyncData'
import { localeNumber } from '../i18n'
import PageHeader from '../components/PageHeader.vue'
import StateBlock from '../components/StateBlock.vue'

const { t } = useI18n()
const router = useRouter()

const { data: centres, status, error, reload } = useAsyncData(fetchCentres, {
  initial: [],
})

const blockStatus = computed(() => {
  if (status.value === 'loading') return 'loading'
  if (status.value === 'error') return 'error'
  if (centres.value.length === 0) return 'empty'

  return 'ready'
})

/**
 * The backend already orders centres by region, township, then name, so
 * grouping preserves that order without re-sorting.
 */
const groupedByRegion = computed(() => {
  const groups = []

  for (const centre of centres.value) {
    const last = groups[groups.length - 1]

    if (last && last.region === centre.region) {
      last.centres.push(centre)
    } else {
      groups.push({ region: centre.region, centres: [centre] })
    }
  }

  return groups
})

const countLabel = computed(() => {
  const total = centres.value.length
  const key = total === 0 ? 'count_zero' : total === 1 ? 'count_one' : 'count_other'

  return t(`centres.${key}`, { count: localeNumber(total) })
})

/** Carries the canonical English centre name into the booking form. */
function bookAt(centre) {
  router.push({ name: 'appointment-book', query: { centre: centre.name } })
}
</script>

<template>
  <v-container class="py-8 py-sm-12 px-4 px-sm-6" style="max-width: 1080px">
    <PageHeader
      :eyebrow="t('centres.eyebrow')"
      :title="t('centres.heading')"
      :intro="t('centres.intro')"
    />

    <StateBlock
      v-if="blockStatus !== 'ready'"
      :status="blockStatus"
      :loading-label="t('centres.loading')"
      :error-title="t('centres.error_title')"
      :error-message="error"
      :empty-title="t('centres.empty_title')"
      :empty-message="t('centres.empty_body')"
      :skeleton-count="4"
      @retry="reload"
    />

    <template v-else>
      <p class="bc-count" aria-live="polite">{{ countLabel }}</p>

      <section v-for="group in groupedByRegion" :key="group.region" class="mb-8">
        <h2 class="bc-region bc-tracked">{{ group.region }}</h2>

        <v-row>
          <v-col v-for="centre in group.centres" :key="centre.name" cols="12" sm="6">
            <v-card class="bc-centre h-100 pa-5 d-flex flex-column" variant="outlined">
              <h3 class="bc-centre__name">{{ centre.name }}</h3>
              <p class="bc-centre__township">{{ centre.township }}</p>

              <p v-if="centre.address" class="bc-centre__line">{{ centre.address }}</p>

              <p v-if="centre.hours" class="bc-centre__line bc-centre__hours">
                <v-icon icon="mdi-clock-outline" size="16" class="mr-1" aria-hidden="true" />
                <span class="d-sr-only">{{ t('centres.hours') }}: </span>
                {{ centre.hours }}
              </p>

              <div class="flex-grow-1" />

              <v-btn
                color="primary"
                variant="tonal"
                class="mt-4 align-self-start"
                :aria-label="`${t('centres.select')}: ${centre.name}`"
                @click="bookAt(centre)"
              >
                {{ t('centres.select') }}
              </v-btn>
            </v-card>
          </v-col>
        </v-row>
      </section>
    </template>
  </v-container>
</template>

<style scoped>
.bc-count {
  font-size: 0.9rem;
  opacity: 0.7;
  margin-bottom: 1.5rem;
}

.bc-region {
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: 0.09em;
  text-transform: uppercase;
  opacity: 0.6;
  padding-bottom: 0.6rem;
  margin-bottom: 1rem;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.16);
}

.bc-centre {
  transition: border-color 140ms ease;
}

.bc-centre:hover {
  border-color: rgb(var(--v-theme-primary));
}

.bc-centre__name {
  font-family: var(--bc-font-display);
  font-size: 1.18rem;
  font-weight: 700;
  line-height: 1.35;
  letter-spacing: -0.01em;
  margin-bottom: 0.2rem;
}

.bc-centre__township {
  font-size: 0.95rem;
  opacity: 0.72;
  margin-bottom: 0.85rem;
}

.bc-centre__line {
  font-size: 0.92rem;
  line-height: 1.65;
  opacity: 0.82;
  margin-bottom: 0.35rem;
}

.bc-centre__hours {
  display: flex;
  align-items: center;
  margin-bottom: 0;
}

@media (prefers-reduced-motion: reduce) {
  .bc-centre {
    transition: none;
  }
}
</style>
