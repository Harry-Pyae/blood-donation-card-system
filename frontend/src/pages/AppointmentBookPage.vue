<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { bookAppointment, fetchBookingOptions, fetchCentres } from '../api/endpoints'
import { useApiForm } from '../composables/useApiForm'
import { useAsyncData } from '../composables/useAsyncData'
import { journey } from '../stores/journey'
import PageHeader from '../components/PageHeader.vue'
import FormAlert from '../components/FormAlert.vue'
import CopyButton from '../components/CopyButton.vue'
import ResultSummary from '../components/ResultSummary.vue'

const { t } = useI18n()
const route = useRoute()

const { data: centres, status: centresStatus } = useAsyncData(fetchCentres, {
  initial: [],
})
const { data: options, status: optionsStatus } = useAsyncData(fetchBookingOptions)

// Prefill from the in-memory journey (registration) or from the centre the
// donor picked on the centres page. Never from persistent storage.
const form = ref({
  donor_reference: journey.donorReference ?? '',
  phone: journey.donorPhone ?? '',
  centre: route.query.centre ?? null,
  appointment_date: '',
  appointment_time: null,
  requested_region: '',
  requested_township: '',
  notes: '',
  booking_acknowledgement: false,
})

const result = ref(null)
const { submit, submitting, formError, errorsFor } = useApiForm(bookAppointment)

const requestLocationValue = computed(
  () => options.value?.request_location_value ?? '__request__',
)

const isLocationRequest = computed(() => form.value.centre === requestLocationValue.value)

/**
 * The submitted value is always the canonical centre name, which is what the
 * backend validates against. The label adds the township only for the reader.
 */
const centreItems = computed(() => [
  ...(centres.value ?? []).map((centre) => ({
    value: centre.name,
    title: `${centre.name} · ${centre.township}`,
  })),
  { value: requestLocationValue.value, title: t('book.request_location') },
])

const timeItems = computed(() => options.value?.times ?? [])
const minDate = computed(() => options.value?.min_date ?? '')
const maxDate = computed(() => options.value?.max_date ?? '')

const loadingOptions = computed(
  () => centresStatus.value === 'loading' || optionsStatus.value === 'loading',
)

async function onSubmit() {
  const payload = {
    donor_reference: form.value.donor_reference,
    phone: form.value.phone,
    centre: form.value.centre,
    notes: form.value.notes || null,
    booking_acknowledgement: form.value.booking_acknowledgement ? '1' : '',
  }

  if (isLocationRequest.value) {
    payload.requested_region = form.value.requested_region
    payload.requested_township = form.value.requested_township
  } else {
    payload.appointment_date = form.value.appointment_date
    payload.appointment_time = form.value.appointment_time
  }

  const outcome = await submit(payload)

  if (outcome.ok) {
    result.value = outcome.data
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const summaryRows = computed(() => {
  if (!result.value) return []

  if (result.value.is_location_request) {
    return [
      { label: t('book.result_region'), value: result.value.requested_region },
      { label: t('book.result_township'), value: result.value.requested_township },
      { label: t('book.result_status'), value: result.value.status },
    ]
  }

  return [
    { label: t('book.result_centre'), value: result.value.centre_name },
    { label: t('book.result_date'), value: result.value.appointment_date },
    { label: t('book.result_time'), value: result.value.appointment_time },
    { label: t('book.result_status'), value: result.value.status },
  ]
})
</script>

<template>
  <v-container class="py-8 py-sm-12 px-4 px-sm-6" style="max-width: 780px">
    <!-- Confirmation -->
    <template v-if="result">
      <PageHeader
        :eyebrow="t('book.eyebrow')"
        :title="
          result.is_location_request
            ? t('book.location_result_title')
            : t('book.result_title')
        "
        :intro="
          result.is_location_request
            ? t('book.location_result_message')
            : t('book.result_message')
        "
      />

      <CopyButton
        :value="result.reference"
        :label="t('book.result_reference')"
        class="mb-6"
      />

      <v-card variant="outlined" class="pa-5 mb-6">
        <ResultSummary :rows="summaryRows" />
      </v-card>

      <div class="d-flex ga-3 flex-wrap">
        <v-btn :to="{ name: 'lookup' }" color="primary" variant="flat" size="large">
          {{ t('book.result_primary') }}
        </v-btn>
        <v-btn :to="{ name: 'home' }" variant="outlined" size="large">
          {{ t('book.result_secondary') }}
        </v-btn>
      </div>
    </template>

    <!-- Form -->
    <template v-else>
      <PageHeader
        :eyebrow="t('book.eyebrow')"
        :title="t('book.heading')"
        :intro="t('book.intro')"
      />

      <FormAlert :message="formError" />

      <p class="bc-required">{{ t('common.required_note') }}</p>

      <v-progress-linear v-if="loadingOptions" indeterminate class="mb-6" />

      <form novalidate @submit.prevent="onSubmit">
        <fieldset class="bc-fieldset">
          <legend class="bc-legend">{{ t('book.section_donor') }}</legend>

          <v-text-field
            v-model="form.donor_reference"
            :label="`${t('book.donor_reference')} *`"
            :hint="t('book.donor_reference_hint')"
            :error-messages="errorsFor('donor_reference')"
            variant="outlined"
            persistent-hint
            class="mb-4"
          />

          <v-text-field
            v-model="form.phone"
            :label="`${t('book.phone')} *`"
            :hint="t('book.phone_hint')"
            :error-messages="errorsFor('phone')"
            variant="outlined"
            inputmode="tel"
            persistent-hint
            class="mb-2"
          />
        </fieldset>

        <fieldset class="bc-fieldset">
          <legend class="bc-legend">{{ t('book.section_where') }}</legend>

          <v-select
            v-model="form.centre"
            :items="centreItems"
            :label="`${t('book.centre')} *`"
            :error-messages="errorsFor('centre')"
            :disabled="centresStatus !== 'ready'"
            variant="outlined"
            class="mb-2"
          />

          <template v-if="isLocationRequest">
            <v-alert type="info" variant="tonal" density="comfortable" class="mb-4">
              {{ t('book.request_location_hint') }}
            </v-alert>

            <v-row>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="form.requested_region"
                  :label="`${t('book.requested_region')} *`"
                  :error-messages="errorsFor('requested_region')"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="form.requested_township"
                  :label="`${t('book.requested_township')} *`"
                  :error-messages="errorsFor('requested_township')"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </template>
        </fieldset>

        <fieldset v-if="!isLocationRequest" class="bc-fieldset">
          <legend class="bc-legend">{{ t('book.section_when') }}</legend>

          <v-row>
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="form.appointment_date"
                :label="`${t('book.date')} *`"
                :error-messages="errorsFor('appointment_date')"
                :min="minDate"
                :max="maxDate"
                type="date"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" sm="6">
              <v-select
                v-model="form.appointment_time"
                :items="timeItems"
                :label="`${t('book.time')} *`"
                :error-messages="errorsFor('appointment_time')"
                variant="outlined"
              />
            </v-col>
          </v-row>
        </fieldset>

        <fieldset class="bc-fieldset">
          <v-textarea
            v-model="form.notes"
            :label="t('book.notes')"
            :error-messages="errorsFor('notes')"
            variant="outlined"
            rows="2"
            auto-grow
            class="mb-2"
          />

          <v-checkbox
            v-model="form.booking_acknowledgement"
            :label="t('book.acknowledgement')"
            :error-messages="errorsFor('booking_acknowledgement')"
          />
        </fieldset>

        <v-btn
          type="submit"
          color="primary"
          variant="flat"
          size="large"
          :loading="submitting"
          :disabled="submitting"
        >
          {{ t('book.submit') }}
        </v-btn>
      </form>
    </template>
  </v-container>
</template>

<style scoped>
.bc-required {
  font-size: 0.88rem;
  opacity: 0.7;
  margin-bottom: 1.5rem;
}

.bc-fieldset {
  border: 0;
  padding: 0;
  margin: 0 0 2rem;
}

.bc-legend {
  font-family: var(--bc-font-display);
  font-size: 1.1rem;
  font-weight: 700;
  padding: 0 0 0.9rem;
}
</style>
