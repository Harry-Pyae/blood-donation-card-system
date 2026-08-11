<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useDisplay, useTheme } from 'vuetify'
import { setLocale } from '../i18n'

const { t, locale } = useI18n()
const theme = useTheme()
const { mdAndUp } = useDisplay()

const drawer = ref(false)

const languages = [
  { code: 'en', short: 'EN', label: 'English' },
  { code: 'my', short: 'MY', label: 'မြန်မာ' },
]

const currentLanguage = computed(
  () => languages.find((item) => item.code === locale.value) ?? languages[0],
)

const links = computed(() => [
  { to: { name: 'home' }, label: t('nav.home') },
  { to: { name: 'centres' }, label: t('nav.centres') },
  { to: { name: 'donor-register' }, label: t('nav.register') },
  { to: { name: 'appointment-book' }, label: t('nav.book') },
  { to: { name: 'lookup' }, label: t('nav.lookup') },
])

const isDark = computed(() => theme.global.current.value.dark)
const themeLabel = computed(() =>
  isDark.value ? t('nav.theme_light') : t('nav.theme_dark'),
)

function toggleTheme() {
  const next = isDark.value ? 'light' : 'dark'
  theme.change(next)
  localStorage.setItem('bloodcare.theme', next)
}
</script>

<template>
  <v-app>
    <!-- Height is pinned so switching to Myanmar, whose taller glyph clusters
         would otherwise grow the bar, cannot shift the brand mark. -->
    <v-app-bar flat :height="72" :elevation="0" class="bc-bar">
      <v-container class="d-flex align-center py-0 px-4 px-sm-6">
        <RouterLink :to="{ name: 'home' }" class="bc-brand">
          <span class="bc-brand__mark" aria-hidden="true"></span>
          <span class="bc-brand__text">{{ t('brand') }}</span>
        </RouterLink>

        <nav v-if="mdAndUp" class="bc-nav" :aria-label="t('nav.menu')">
          <RouterLink
            v-for="link in links"
            :key="link.label"
            :to="link.to"
            class="bc-nav__link"
          >
            {{ link.label }}
          </RouterLink>
        </nav>

        <v-spacer />

        <v-menu location="bottom end" :offset="10">
          <template #activator="{ props }">
            <v-btn
              v-bind="props"
              variant="text"
              density="comfortable"
              class="bc-lang"
              :aria-label="`${t('nav.language')}: ${currentLanguage.label}`"
            >
              <v-icon icon="mdi-translate" size="20" />
              <span class="bc-lang__code">{{ currentLanguage.short }}</span>
              <v-icon icon="mdi-chevron-down" size="15" class="bc-lang__caret" />
            </v-btn>
          </template>

          <v-card class="bc-menu" rounded="lg" elevation="6" min-width="196">
            <v-list class="pa-2" bg-color="transparent" density="comfortable">
              <v-list-item
                v-for="option in languages"
                :key="option.code"
                class="bc-menu__item"
                rounded="lg"
                :active="locale === option.code"
                @click="setLocale(option.code)"
              >
                <v-list-item-title class="bc-menu__label">
                  {{ option.label }}
                </v-list-item-title>
                <template #append>
                  <v-icon
                    v-if="locale === option.code"
                    icon="mdi-check"
                    size="18"
                    color="primary"
                  />
                </template>
              </v-list-item>
            </v-list>
          </v-card>
        </v-menu>

        <v-btn
          variant="text"
          density="comfortable"
          :icon="isDark ? 'mdi-weather-sunny' : 'mdi-weather-night'"
          :aria-label="`${t('nav.switch_theme')}: ${themeLabel}`"
          :title="themeLabel"
          @click="toggleTheme"
        />

        <v-btn
          v-if="!mdAndUp"
          variant="text"
          density="comfortable"
          icon="mdi-menu"
          :aria-label="t('nav.menu')"
          :aria-expanded="drawer"
          @click="drawer = !drawer"
        />
      </v-container>
    </v-app-bar>

    <v-navigation-drawer v-model="drawer" location="right" temporary width="264">
      <v-list class="pa-2" density="comfortable" :aria-label="t('nav.menu')">
        <v-list-item
          v-for="link in links"
          :key="link.label"
          :to="link.to"
          rounded="lg"
          class="bc-menu__item"
          @click="drawer = false"
        >
          <v-list-item-title class="bc-menu__label">{{ link.label }}</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>

    <v-main>
      <RouterView />
    </v-main>

    <footer class="bc-footer">
      <v-container class="px-4 px-sm-6">
        <p class="bc-footer__text">{{ t('brand') }}</p>
      </v-container>
    </footer>
  </v-app>
</template>

<style scoped>
.bc-bar {
  border-bottom: 1px solid rgba(var(--v-border-color), 0.14);
  /* Latin line spacing regardless of locale, so the bar never reflows. */
  line-height: 1.4;
}

.bc-brand {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  text-decoration: none;
  color: inherit;
  border-radius: 8px;
  flex-shrink: 0;
}

.bc-brand:focus-visible,
.bc-nav__link:focus-visible {
  outline: 3px solid rgb(var(--v-theme-primary));
  outline-offset: 4px;
}

.bc-brand__mark {
  width: 12px;
  height: 12px;
  border-radius: 50% 50% 50% 0;
  transform: rotate(-45deg);
  background: rgb(var(--v-theme-primary));
  flex: 0 0 auto;
}

.bc-brand__text {
  font-family: var(--bc-font-display);
  font-size: 1.22rem;
  font-weight: 700;
  letter-spacing: -0.015em;
  line-height: 1.3;
}

.bc-nav {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin-inline-start: 2rem;
}

.bc-nav__link {
  padding: 0.45rem 0.7rem;
  border-radius: 8px;
  text-decoration: none;
  color: inherit;
  opacity: 0.74;
  font-size: 0.95rem;
  line-height: 1.5;
}

.bc-nav__link:hover {
  opacity: 1;
  background: rgba(var(--v-theme-on-surface), 0.06);
}

.bc-nav__link.router-link-active {
  opacity: 1;
  font-weight: 600;
  color: rgb(var(--v-theme-primary));
}

.bc-lang {
  min-width: 0;
  padding-inline: 0.6rem;
  gap: 0.35rem;
}

.bc-lang__code {
  font-size: 0.82rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  line-height: 1.5;
}

.bc-lang__caret {
  opacity: 0.6;
}

.bc-menu {
  border: 1px solid rgba(var(--v-border-color), 0.16);
}

.bc-menu__item {
  min-height: 44px;
}

.bc-menu__label {
  font-size: 0.95rem;
  line-height: 1.6;
}

.bc-footer {
  border-top: 1px solid rgba(var(--v-border-color), 0.14);
  padding: 1.75rem 0;
  margin-top: 3rem;
}

.bc-footer__text {
  font-size: 0.9rem;
  opacity: 0.6;
  margin: 0;
}
</style>
