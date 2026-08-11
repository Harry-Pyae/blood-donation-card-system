<script setup>
defineProps({
  rows: { type: Array, required: true }, // [{ label, value }]
})
</script>

<template>
  <dl class="bc-summary">
    <div v-for="row in rows" :key="row.label" class="bc-summary__row">
      <dt class="bc-summary__label">{{ row.label }}</dt>
      <dd class="bc-summary__value">{{ row.value || '—' }}</dd>
    </div>
  </dl>
</template>

<style scoped>
.bc-summary {
  margin: 0;
}

.bc-summary__row {
  display: flex;
  gap: 1rem;
  justify-content: space-between;
  align-items: baseline;
  padding: 0.85rem 0;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.14);
}

.bc-summary__row:last-child {
  border-bottom: 0;
}

.bc-summary__label {
  font-size: 0.9rem;
  opacity: 0.7;
  flex-shrink: 0;
}

.bc-summary__value {
  font-weight: 600;
  text-align: end;
  margin: 0;
}
</style>
