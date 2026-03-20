/**
 * Tema claro/escuro — páginas de login/cadastro CMS (separado do módulo Finanças).
 */
export const CMS_AUTH_THEME_KEY = 'cms-auth-theme-preference'

export function getStoredTheme() {
  try {
    const v = localStorage.getItem(CMS_AUTH_THEME_KEY)
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

export function getInitialDark() {
  const stored = getStoredTheme()
  if (stored === 'dark') return true
  if (stored === 'light') return false
  return getSystemPrefersDark()
}

export function persistTheme(isDark) {
  try {
    localStorage.setItem(CMS_AUTH_THEME_KEY, isDark ? 'dark' : 'light')
  } catch (e) {
    /* ignore */
  }
}

export function applyAuthBodyClass(isDark) {
  if (typeof document === 'undefined') return
  document.body.classList.toggle('cms-auth--dark', isDark)
  document.body.classList.toggle('cms-auth--light', !isDark)
}
