<script setup>
defineProps({
  message: { type: String, default: null },
  type: { type: String, default: 'error' },
})
</script>

<template>
  <!-- role=alert so screen readers announce a submit failure immediately. -->
  <v-alert
    v-if="message"
    :type="type"
    variant="tonal"
    class="mb-5"
    role="alert"
    density="comfortable"
  >
    {{ message }}
  </v-alert>
</template>
