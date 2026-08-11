<script setup>
defineProps({
  eyebrow: { type: String, default: null },
  title: { type: String, required: true },
  intro: { type: String, default: null },
})
</script>

<template>
  <header class="bc-page-header">
    <p v-if="eyebrow" class="bc-eyebrow bc-tracked">{{ eyebrow }}</p>
    <h1 class="bc-heading">{{ title }}</h1>
    <p v-if="intro" class="bc-intro">{{ intro }}</p>
  </header>
</template>

<style scoped>
.bc-page-header {
  margin-bottom: 2rem;
}

.bc-eyebrow {
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0.09em;
  text-transform: uppercase;
  color: rgb(var(--v-theme-primary));
  margin-bottom: 0.5rem;
}

.bc-heading {
  font-family: var(--bc-font-display);
  font-size: clamp(1.9rem, 4.5vw, 2.75rem);
  font-weight: 700;
  line-height: 1.2;
  letter-spacing: -0.02em;
  margin-bottom: 0.85rem;
  text-wrap: balance;
}

html[lang='my'] .bc-heading {
  letter-spacing: 0;
  line-height: 1.55;
  text-wrap: normal;
}

.bc-intro {
  font-size: 1.05rem;
  line-height: 1.7;
  max-width: 56ch;
  opacity: 0.78;
}
</style>
