<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    title: {
        type: String,
        default: 'Administración FEXIMAR',
    },
    subtitle: {
        type: String,
        default: 'FEXIMAR',
    },
    farmMode: {
        type: Boolean,
        default: false,
    },
    buyerMode: {
        type: Boolean,
        default: false,
    },
})

const page = usePage()

const userName = computed(() => page.props.auth?.user?.name ?? 'Usuario')
const farmName = computed(() => page.props.farmPortal?.name ?? props.subtitle)
const buyerName = computed(() => page.props.buyerPortal?.name ?? props.subtitle)

const primaryLabel = computed(() => {
    if (props.buyerMode) return buyerName.value
    if (props.farmMode) return farmName.value
    return userName.value
})

const secondaryLabel = computed(() => {
    if (props.buyerMode || props.farmMode) return userName.value
    return props.subtitle
})

/** Token CSRF del layout Blade; el form es nativo para forzar navegación completa a /. */
const csrfToken = typeof document !== 'undefined'
    ? (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '')
    : ''
</script>

<template>
    <div class="header">
        <div class="header-content">
            <div class="feximar-header-inner">
                <div class="header-left">
                    <h4 class="mb-0">{{ title }}</h4>
                </div>
            </div>
        </div>

        <!--
          Barra de sesión fuera del flex interno de Eres.
          position:fixed + estilos inline para que no la oculte
          .collapse / header-info / media queries del template.
        -->
        <div class="feximar-session-bar" aria-label="Sesión">
            <div class="feximar-session-text">
                <div class="feximar-session-primary">{{ primaryLabel }}</div>
                <div class="feximar-session-secondary">{{ secondaryLabel }}</div>
            </div>

            <!--
              Form HTML nativo (NO Inertia Link/router.post):
              el redirect a "/" es Blade y debe ser navegación completa del navegador.
            -->
            <form method="POST" action="/logout" class="feximar-session-logout-form">
                <input type="hidden" name="_token" :value="csrfToken">
                <button
                    type="submit"
                    class="btn btn-sm btn-primary feximar-session-logout"
                >
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</template>
