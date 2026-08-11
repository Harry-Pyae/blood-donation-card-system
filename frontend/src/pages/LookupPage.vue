<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { checkAppointment, checkCard } from '../api/endpoints'
import { useApiForm } from '../composables/useApiForm'
import PageHeader from '../components/PageHeader.vue'
import FormAlert from '../components/FormAlert.vue'
import ResultSummary from '../components/ResultSummary.vue'

const { t } = useI18n()

const mode = ref('appointment')
const form = ref({ reference: '', phone: '' })
const result = ref(null)

const lookup = (payload) =>
  mode.value === 'appointment' ? checkAppointment(payload) : checkCard(payload)

const { submit, submitting, formError, errorsFor, reset } = useApiForm(lookup)

// Switching tab starts a clean lookup rather than leaving a stale result under
// the wrong heading.
watch(mode, () => {
  result.value = null
  form.value = { reference: '', phone: '' }
  reset()
})

async function onSubmit() {
  const outcome = await submit({ ...form.value })

  if (outcome.ok) {
    result.value = outcome.data
  }
}

function again() {
  result.value = null
  form.value = { reference: '', phone: '' }
  reset()
}

const referenceLabel = computed(() =>
  mode.value === 'appointment'
    ? t('lookup.appointment_reference')
    : t('lookup.card_reference'),
)

const referencePlaceholder = computed(() =>
  mode.value === 'appointment'
    ? t('lookup.appointment_placeholder')
    : t('lookup.card_placeholder'),
)

const resultTitle = computed(() =>
  mode.value === 'appointment'
    ? t('lookup.appointment_result_title')
    : t('lookup.card_result_title'),
)

const summaryRows = computed(() => {
  const data = result.value
  if (!data) return []

  if (mode.value === 'appointment') {
    const place =
      data.centre_name ??
      [data.requested_region, data.requested_township].filter(Boolean).join(' / ')

    const when = data.appointment_date
      ? `${data.appointment_date}${data.appointment_time ? ` · ${data.appointment_time}` : ''}`
      : t('lookup.result_pending_location')

    return [
      { label: t('lookup.result_centre'), value: place || t('lookup.none') },
      { label: t('lookup.result_date_time'), value: when },
      { label: t('lookup.result_purpose'), value: data.purpose },
      { label: t('lookup.result_status'), value: data.status },
    ]
  }

  return [
    { label: t('lookup.card_name'), value: data.donor_name },
    { label: t('lookup.card_group'), value: data.blood_group },
    { label: t('lookup.card_last'), value: data.last_donation_date ?? t('lookup.none') },
    {
      label: t('lookup.card_next'),
      value: data.next_eligible_date ?? t('lookup.eligible_now'),
    },
    { label: t('lookup.card_total'), value: String(data.total_donations) },
    { label: t('lookup.card_status'), value: data.status },
  ]
})
</script>

<template>
  <v-container class="py-8 py-sm-12 px-4 px-sm-6" style="max-width: 680px">
    <PageHeader
      :eyebrow="t('lookup.eyebrow')"
      :title="t('lookup.heading')"
      :intro="t('lookup.intro')"
    />

    <v-tabs v-model="mode" class="mb-6" color="primary" density="comfortable">
      <v-tab value="appointment">{{ t('lookup.tab_appointment') }}</v-tab>
      <v-tab value="card">{{ t('lookup.tab_card') }}</v-tab>
    </v-tabs>

    <!-- Result -->
    <template v-if="result">
      <h2 class="bc-result-title">{{ resultTitle }}</h2>

      <v-card variant="outlined" class="pa-5 mb-4">
        <ResultSummary :rows="summaryRows" />
      </v-card>

      <p class="bc-notice">{{ t('lookup.notice') }}</p>

      <v-btn variant="outlined" size="large" @click="again">
        {{ t('lookup.again') }}
      </v-btn>
    </template>

    <!-- Form -->
    <template v-else>
      <FormAlert :message="formError" />

      <form novalidate @submit.prevent="onSubmit">
        <v-text-field
          v-model="form.reference"
          :label="`${referenceLabel} *`"
          :placeholder="referencePlaceholder"
          :error-messages="errorsFor('reference')"
          variant="outlined"
          class="mb-3"
        />

        <v-text-field
          v-model="form.phone"
          :label="`${t('lookup.phone')} *`"
          :error-messages="errorsFor('phone')"
          variant="outlined"
          inputmode="tel"
          class="mb-4"
        />

        <v-btn
          type="submit"
          color="primary"
          variant="flat"
          size="large"
          :loading="submitting"
          :disabled="submitting"
        >
          {{ t('lookup.submit') }}
        </v-btn>
      </form>
    </template>
  </v-container>
</template>

<style scoped>
.bc-result-title {
  font-family: var(--bc-font-display);
  font-size: 1.35rem;
  font-weight: 700;
  margin-bottom: 1rem;
}

.bc-notice {
  font-size: 0.88rem;
  opacity: 0.7;
  margin-bottom: 1.5rem;
}
</style>
