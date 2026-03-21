<template>
  <transition name="onboarding-fade">
    <div v-show="value" class="onboarding-tour" aria-live="polite">
      <!-- overlay escuro com recorte no alvo (pointer-events: none — não bloqueia a app) -->
      <div v-if="!rect" class="onboarding-tour__full-dim" aria-hidden="true" />
      <div v-else class="onboarding-tour__spotlight" :style="spotlightStyle" aria-hidden="true" />

      <v-card class="onboarding-tour__card rounded-xl elevation-12" :style="cardStyle">
        <v-card-text class="pb-2 pt-5 px-5">
          <div class="text-overline secondary--text mb-1">Passo {{ stepIndex + 1 }} de {{ steps.length }}</div>
          <h2 class="text-h6 font-weight-bold mb-2">{{ currentStep.title }}</h2>
          <p class="text-body-2 mb-0 onboarding-tour__text">{{ currentStep.text }}</p>
        </v-card-text>
        <v-card-actions class="px-4 pb-4 pt-0 flex-wrap">
          <v-btn text small color="secondary" class="mr-1" :loading="completing" @click="skip">Pular</v-btn>
          <v-spacer />
          <v-btn v-if="stepIndex > 0" text color="secondary" class="mr-1" :disabled="completing" @click="prev">
            Voltar
          </v-btn>
          <v-btn v-if="stepIndex < steps.length - 1" color="primary" depressed rounded :disabled="completing" @click="next">
            Próximo
          </v-btn>
          <v-btn v-else color="primary" depressed rounded :loading="completing" @click="finish">Concluir</v-btn>
        </v-card-actions>
      </v-card>
    </div>
  </transition>
</template>

<script>
const STEPS = [
  {
    title: 'Bem-vindo',
    text: 'Em poucos passos você vê como lançar transações, usar categorias e ler entradas e saídas no painel.',
    selector: null,
  },
  {
    title: 'Nova transação',
    text: 'Toque no botão vermelho com + para registrar uma receita ou despesa.',
    selector: '[data-tour="finance-fab-transaction"]',
  },
  {
    title: 'Categorias',
    text: 'No menu, abra Categorias para agrupar seus lançamentos (ex.: Alimentação, Salário).',
    selector: '[data-tour="nav-categories"]',
  },
  {
    title: 'Entradas e saídas',
    text: 'Em Movimentações, o saldo em caixa é a soma do que já acumulou até o mês anterior com o resultado à vista do mês escolhido. O cartão entra só nas despesas marcadas como cartão.',
    selector: '[data-tour="dashboard-income-expense"]',
  },
  {
    title: 'Pronto',
    text: 'Explore o menu quando quiser. Bom uso do painel!',
    selector: null,
  },
]

export default {
  name: 'OnboardingTour',
  props: {
    value: { type: Boolean, default: false },
    /** POST web (path relativo) — grava onboarding_completed na BD e redireciona */
    onboardingCompleteUrl: { type: String, required: true },
    returnMonth: { type: String, default: '' },
  },
  data() {
    return {
      steps: STEPS,
      stepIndex: 0,
      rect: null,
      completing: false,
      winW: typeof window !== 'undefined' ? window.innerWidth : 360,
      winH: typeof window !== 'undefined' ? window.innerHeight : 640,
    }
  },
  computed: {
    currentStep() {
      return this.steps[this.stepIndex] || this.steps[0]
    },
    spotlightStyle() {
      if (!this.rect) return {}
      const p = 8
      return {
        top: `${this.rect.top - p}px`,
        left: `${this.rect.left - p}px`,
        width: `${this.rect.width + 2 * p}px`,
        height: `${this.rect.height + 2 * p}px`,
      }
    },
    cardStyle() {
      const pad = 16
      const mw = Math.min(360, this.winW - 32)
      if (!this.rect) {
        return {
          position: 'fixed',
          left: '50%',
          top: '50%',
          transform: 'translate(-50%, -50%)',
          width: `${mw}px`,
          maxWidth: 'calc(100vw - 32px)',
        }
      }
      const w = mw
      let top = this.rect.bottom + 14
      let left = this.rect.left + this.rect.width / 2 - w / 2
      left = Math.max(pad, Math.min(left, this.winW - w - pad))
      const estCardH = 220
      if (top + estCardH > this.winH - pad) {
        top = this.rect.top - estCardH - 14
      }
      top = Math.max(pad, Math.min(top, this.winH - estCardH - pad))
      return {
        position: 'fixed',
        top: `${top}px`,
        left: `${left}px`,
        width: `${w}px`,
        maxWidth: 'calc(100vw - 32px)',
        maxHeight: `${Math.max(160, this.winH - top - pad)}px`,
        overflowY: 'auto',
      }
    },
  },
  watch: {
    value(v) {
      if (v) {
        this.stepIndex = 0
        this.setupListeners()
        this.emitStepAndMeasure()
      } else {
        this.rect = null
        this.teardownListeners()
      }
    },
    stepIndex() {
      if (this.value) this.emitStepAndMeasure()
    },
  },
  mounted() {
    if (this.value) {
      this.setupListeners()
      this.emitStepAndMeasure()
    }
  },
  beforeDestroy() {
    this.teardownListeners()
  },
  methods: {
    emitStepAndMeasure() {
      this.$emit('step', this.stepIndex)
      this.$nextTick(() => {
        requestAnimationFrame(() => {
          setTimeout(() => this.measureTarget(), 280)
        })
      })
    },
    measureTarget() {
      if (!this.value) return
      const sel = this.currentStep.selector
      if (!sel) {
        this.rect = null
        this.updateWinSize()
        return
      }
      const el = document.querySelector(sel)
      if (!el) {
        this.rect = null
        return
      }
      try {
        el.scrollIntoView({ block: 'center', behavior: 'smooth' })
      } catch (e) {
        el.scrollIntoView()
      }
      setTimeout(() => {
        const r = el.getBoundingClientRect()
        if (r.width < 2 && r.height < 2) {
          this.rect = null
          return
        }
        this.rect = { top: r.top, left: r.left, width: r.width, height: r.height, bottom: r.bottom }
        this.updateWinSize()
      }, 380)
    },
    updateWinSize() {
      if (typeof window === 'undefined') return
      this.winW = window.innerWidth
      this.winH = window.innerHeight
    },
    onResize() {
      this.updateWinSize()
      this.measureTarget()
    },
    setupListeners() {
      if (typeof window === 'undefined') return
      window.addEventListener('resize', this.onResize, { passive: true })
      window.addEventListener('orientationchange', this.onResize, { passive: true })
    },
    teardownListeners() {
      if (typeof window === 'undefined') return
      window.removeEventListener('resize', this.onResize)
      window.removeEventListener('orientationchange', this.onResize)
    },
    next() {
      if (this.stepIndex < this.steps.length - 1) this.stepIndex += 1
    },
    prev() {
      if (this.stepIndex > 0) this.stepIndex -= 1
    },
    skip() {
      this.submitOnboardingComplete()
    },
    finish() {
      this.submitOnboardingComplete()
    },
    submitOnboardingComplete() {
      const url = (this.onboardingCompleteUrl || '').trim()
      const token = typeof document !== 'undefined'
        ? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        : null
      if (!url || !token) {
        return
      }
      this.completing = true
      const f = document.createElement('form')
      f.method = 'POST'
      f.action = url
      const t = document.createElement('input')
      t.type = 'hidden'
      t.name = '_token'
      t.value = token
      f.appendChild(t)
      if (this.returnMonth) {
        const m = document.createElement('input')
        m.type = 'hidden'
        m.name = 'month'
        m.value = this.returnMonth
        f.appendChild(m)
      }
      document.body.appendChild(f)
      f.submit()
    },
  },
}
</script>

<style scoped>
.onboarding-tour {
  position: fixed;
  inset: 0;
  z-index: 10040;
  pointer-events: none;
}

.onboarding-tour__full-dim {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.62);
  pointer-events: none;
  transition: opacity 0.35s ease;
}

.onboarding-tour__spotlight {
  position: fixed;
  z-index: 1;
  border-radius: 12px;
  pointer-events: none;
  box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.68);
  transition:
    top 0.35s ease,
    left 0.35s ease,
    width 0.35s ease,
    height 0.35s ease,
    box-shadow 0.35s ease;
}

.onboarding-tour__card {
  z-index: 2;
  pointer-events: auto;
  transition:
    top 0.3s ease,
    left 0.3s ease,
    transform 0.3s ease,
    opacity 0.3s ease;
}

.onboarding-tour__text {
  line-height: 1.45;
}

.onboarding-fade-enter-active,
.onboarding-fade-leave-active {
  transition: opacity 0.28s ease;
}

.onboarding-fade-enter,
.onboarding-fade-leave-to {
  opacity: 0;
}
</style>
