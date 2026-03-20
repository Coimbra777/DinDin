/**
 * Tema claro/escuro — preferência persistida + sync com prefers-color-scheme.
 */

export const FINANCE_THEME_STORAGE_KEY = 'finance-theme-preference'

export function getStoredTheme() {
  try {
    const v = localStorage.getItem(FINANCE_THEME_STORAGE_KEY)
    if (v === 'dark' || v === 'light') return v
  } catch (e) {
    /* ignore */
  }
  return null
}

export function getSystemPrefersDark() {
  if (typeof window === 'undefined' || !window.matchMedia) return false
  return window.matchMedia('(prefers-color-scheme: dark)').matches
}

/** Valor inicial de `$vuetify.theme.dark` antes de montar o Vue */
export function getInitialDark() {
  const stored = getStoredTheme()
  if (stored === 'dark') return true
  if (stored === 'light') return false
  return getSystemPrefersDark()
}

export function persistTheme(isDark) {
  try {
    localStorage.setItem(FINANCE_THEME_STORAGE_KEY, isDark ? 'dark' : 'light')
  } catch (e) {
    /* ignore */
  }
}

/** Sincroniza fundo da página com o tema (transição no CSS do body) */
export function applyBodyThemeClass(isDark) {
  if (typeof document === 'undefined') return
  document.body.classList.toggle('finance-theme-dark', isDark)
  document.body.classList.toggle('finance-theme-light', !isDark)
}
