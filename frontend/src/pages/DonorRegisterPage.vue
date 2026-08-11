<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { fetchNrcReference, registerDonor } from '../api/endpoints'
import { useApiForm } from '../composables/useApiForm'
import { useAsyncData } from '../composables/useAsyncData'
import { rememberDonor } from '../stores/journey'
import PageHeader from '../components/PageHeader.vue'
import FormAlert from '../components/FormAlert.vue'
import CopyButton from '../components/CopyButton.vue'
import ResultSummary from '../components/ResultSummary.vue'

const { t, locale } = useI18n()

const { data: nrc, status: nrcStatus } = useAsyncData(fetchNrcReference)

const form = ref({
  full_name: '',
  date_of_birth: '',
  gender: null,
  identity_document_type: 'nrc',
  nrc_state: null,
  nrc_township: null,
  nrc_type: null,
  nrc_serial: '',
  passport_number: '',
  phone_local: '',
  email: '',
  address: '',
  blood_group: null,
  emergency_contact: '',
  previous_donation: 'no',
  health_notes: '',
  consent: false,
})

const result = ref(null)
const { submit, submitting, formError, errorsFor } = useApiForm(registerDonor)

const isNrc = computed(() => form.value.identity_document_type === 'nrc')

const maxBirthDate = computed(() => {
  const date = new Date()
  date.setFullYear(date.getFullYear() - 18)

  return date.toISOString().slice(0, 10)
})

/** Localised option labels, but the submitted value stays the backend code. */
const stateItems = computed(() =>
  (nrc.value?.states ?? []).map((state) => ({
    value: state.code,
    title: `${state.code} — ${locale.value === 'my' ? state.my : state.en}`,
  })),
)

const typeItems = computed(() =>
  (nrc.value?.types ?? []).map((type) => ({
    value: type.code,
    title: `${type.code} — ${locale.value === 'my' ? type.my : type.en}`,
  })),
)

const townshipItems = computed(() => {
  const list = nrc.value?.townships?.[form.value.nrc_state] ?? []

  return list.map((township) => ({ value: township.value, title: township.display }))
})

// Townships belong to a state, so a state change invalidates the selection.
watch(
  () => form.value.nrc_state,
  () => {
    form.value.nrc_township = null
  },
)

const nrcPreview = computed(() => {
  const { nrc_state: state, nrc_township: township, nrc_type: type, nrc_serial: serial } = form.value
  if (!state || !township || !type || serial.length !== 6) return null

  const match = (nrc.value?.townships?.[state] ?? []).find((item) => item.value === township)
  if (!match) return null

  return `${state}/${match.display}(${type})${serial}`
})

const genders = computed(() => [
  { value: 'female', title: t('register.gender_female') },
  { value: 'male', title: t('register.gender_male') },
  { value: 'other', title: t('register.gender_other') },
])

const bloodGroups = computed(() => [
  { value: 'unknown', title: t('register.blood_group_unknown') },
  ...['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'].map((g) => ({
    value: g,
    title: g,
  })),
])

async function onSubmit() {
  // Only send the branch that applies; the backend excludes the other anyway.
  const payload = {
    ...form.value,
    consent: form.value.consent ? '1' : '',
  }

  if (isNrc.value) {
    delete payload.passport_number
  } else {
    delete payload.nrc_state
    delete payload.nrc_township
    delete payload.nrc_type
    delete payload.nrc_serial
  }

  const outcome = await submit(payload)

  if (outcome.ok) {
    result.value = outcome.data
    rememberDonor({ reference: outcome.data.reference, phone: outcome.data.phone })
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const summaryRows = computed(() => [
  { label: t('register.result_name'), value: result.value?.full_name },
  { label: t('register.result_phone'), value: result.value?.phone },
  {
    label: t('register.result_group'),
    value:
      result.value?.blood_group === 'unknown'
        ? t('register.blood_group_unknown')
        : result.value?.blood_group,
  },
  { label: t('register.result_status'), value: t('register.result_status_pending') },
])
</script>

<template>
  <v-container class="py-8 py-sm-12 px-4 px-sm-6" style="max-width: 780px">
    <!-- Confirmation -->
    <template v-if="result">
      <PageHeader
        :eyebrow="t('register.eyebrow')"
        :title="t('register.result_title')"
        :intro="t('register.result_message')"
      />

      <CopyButton
        :value="result.reference"
        :label="t('register.result_reference')"
        class="mb-6"
      />

      <v-card variant="outlined" class="pa-5 mb-6">
        <ResultSummary :rows="summaryRows" />
      </v-card>

      <v-alert type="info" variant="tonal" density="comfortable" class="mb-6">
        {{ t('register.result_notice') }}
      </v-alert>

      <div class="d-flex ga-3 flex-wrap">
        <v-btn
          :to="{ name: 'appointment-book' }"
          color="primary"
          variant="flat"
          size="large"
        >
          {{ t('register.result_primary') }}
        </v-btn>
        <v-btn :to="{ name: 'home' }" variant="outlined" size="large">
          {{ t('register.result_secondary') }}
        </v-btn>
      </div>
    </template>

    <!-- Form -->
    <template v-else>
      <PageHeader
        :eyebrow="t('register.eyebrow')"
        :title="t('register.heading')"
        :intro="t('register.intro')"
      />

      <FormAlert :message="formError" />

      <p class="bc-required">{{ t('common.required_note') }}</p>

      <form novalidate @submit.prevent="onSubmit">
        <fieldset class="bc-fieldset">
          <legend class="bc-legend">{{ t('register.section_personal') }}</legend>

          <v-text-field
            v-model="form.full_name"
            :label="`${t('register.full_name')} *`"
            :error-messages="errorsFor('full_name')"
            variant="outlined"
            autocomplete="name"
            class="mb-2"
          />

          <v-text-field
            v-model="form.date_of_birth"
            :label="`${t('register.date_of_birth')} *`"
            :hint="t('register.date_of_birth_hint')"
            :error-messages="errorsFor('date_of_birth')"
            :max="maxBirthDate"
            type="date"
            variant="outlined"
            persistent-hint
            class="mb-4"
          />

          <v-select
            v-model="form.gender"
            :items="genders"
            :label="`${t('register.gender')} *`"
            :error-messages="errorsFor('gender')"
            variant="outlined"
            class="mb-2"
          />
        </fieldset>

        <fieldset class="bc-fieldset">
          <legend class="bc-legend">{{ t('register.section_identity') }}</legend>

          <v-btn-toggle
            v-model="form.identity_document_type"
            variant="outlined"
            divided
            mandatory
            class="mb-4"
            :aria-label="t('register.document_type')"
          >
            <v-btn value="nrc">{{ t('register.document_nrc') }}</v-btn>
            <v-btn value="passport">{{ t('register.document_passport') }}</v-btn>
          </v-btn-toggle>

          <div v-if="errorsFor('identity_document_type').length" class="bc-field-error">
            {{ errorsFor('identity_document_type')[0] }}
          </div>

          <template v-if="isNrc">
            <v-progress-linear v-if="nrcStatus === 'loading'" indeterminate class="mb-4" />

            <v-row>
              <v-col cols="12" sm="6">
                <v-select
                  v-model="form.nrc_state"
                  :items="stateItems"
                  :label="`${t('register.nrc_state')} *`"
                  :error-messages="errorsFor('nrc_state')"
                  :disabled="nrcStatus !== 'ready'"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-autocomplete
                  v-model="form.nrc_township"
                  :items="townshipItems"
                  :label="`${t('register.nrc_township')} *`"
                  :error-messages="errorsFor('nrc_township')"
                  :disabled="!form.nrc_state"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-select
                  v-model="form.nrc_type"
                  :items="typeItems"
                  :label="`${t('register.nrc_type')} *`"
                  :error-messages="errorsFor('nrc_type')"
                  :disabled="nrcStatus !== 'ready'"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="form.nrc_serial"
                  :label="`${t('register.nrc_serial')} *`"
                  :hint="t('register.nrc_serial_hint')"
                  :error-messages="errorsFor('nrc_serial')"
                  variant="outlined"
                  inputmode="numeric"
                  maxlength="6"
                  persistent-hint
                />
              </v-col>
            </v-row>

            <p v-if="nrcPreview" class="bc-preview">
              {{ t('register.nrc_preview') }}: <strong>{{ nrcPreview }}</strong>
            </p>
          </template>

          <v-text-field
            v-else
            v-model="form.passport_number"
            :label="`${t('register.passport_number')} *`"
            :error-messages="errorsFor('passport_number')"
            variant="outlined"
            class="mb-2"
          />
        </fieldset>

        <fieldset class="bc-fieldset">
          <legend class="bc-legend">{{ t('register.section_contact') }}</legend>

          <v-text-field
            v-model="form.phone_local"
            :label="`${t('register.phone')} *`"
            :hint="t('register.phone_hint')"
            :error-messages="[...errorsFor('phone_local'), ...errorsFor('phone')]"
            prefix="+95"
            variant="outlined"
            inputmode="tel"
            autocomplete="tel-national"
            persistent-hint
            class="mb-4"
          />

          <v-text-field
            v-model="form.email"
            :label="t('register.email')"
            :error-messages="errorsFor('email')"
            type="email"
            variant="outlined"
            autocomplete="email"
            class="mb-2"
          />

          <v-textarea
            v-model="form.address"
            :label="`${t('register.address')} *`"
            :error-messages="errorsFor('address')"
            variant="outlined"
            rows="2"
            auto-grow
            class="mb-2"
          />

          <v-text-field
            v-model="form.emergency_contact"
            :label="`${t('register.emergency_contact')} *`"
            :error-messages="errorsFor('emergency_contact')"
            variant="outlined"
            class="mb-2"
          />
        </fieldset>

        <fieldset class="bc-fieldset">
          <legend class="bc-legend">{{ t('register.section_health') }}</legend>

          <v-select
            v-model="form.blood_group"
            :items="bloodGroups"
            :label="`${t('register.blood_group')} *`"
            :hint="t('register.blood_group_hint')"
            :error-messages="errorsFor('blood_group')"
            variant="outlined"
            persistent-hint
            class="mb-4"
          />

          <v-radio-group
            v-model="form.previous_donation"
            :label="`${t('register.previous_donation')} *`"
            :error-messages="errorsFor('previous_donation')"
            inline
          >
            <v-radio :label="t('common.yes')" value="yes" />
            <v-radio :label="t('common.no')" value="no" />
          </v-radio-group>

          <v-textarea
            v-model="form.health_notes"
            :label="t('register.health_notes')"
            :hint="t('register.health_notes_hint')"
            :error-messages="errorsFor('health_notes')"
            variant="outlined"
            rows="2"
            auto-grow
            persistent-hint
            class="mb-4"
          />

          <v-checkbox
            v-model="form.consent"
            :label="t('register.consent')"
            :error-messages="errorsFor('consent')"
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
          {{ t('register.submit') }}
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

.bc-preview {
  font-size: 0.92rem;
  opacity: 0.85;
  margin-top: 0.25rem;
}

.bc-field-error {
  color: rgb(var(--v-theme-error));
  font-size: 0.82rem;
  margin: -0.75rem 0 1rem;
}
</style>
