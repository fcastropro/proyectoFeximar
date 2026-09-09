<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<template>
    <div class="feximar-auth-shell">
        <div class="feximar-auth-brand">
            <Link href="/">
                <img
                    src="/home/images/flores/logo-oscuro.png"
                    alt="FEXIMAR"
                    class="feximar-auth-logo"
                >
            </Link>
            <p class="feximar-auth-tagline">
                Sistema Inteligente para Gestión y Análisis de Exportaciones Florícolas
            </p>
        </div>

        <div class="feximar-auth-card">
            <slot />
        </div>
    </div>
</template>

<style scoped>
.feximar-auth-shell {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
    background:
        radial-gradient(circle at top, rgba(215, 25, 75, 0.18), transparent 45%),
        linear-gradient(180deg, #141318 0%, #1c1a22 100%);
}

.feximar-auth-brand {
    text-align: center;
    margin-bottom: 1.25rem;
}

.feximar-auth-logo {
    height: 64px;
    width: auto;
    display: inline-block;
}

.feximar-auth-tagline {
    margin: 0.75rem auto 0;
    max-width: 28rem;
    color: #c9c4cf;
    font-size: 0.85rem;
    line-height: 1.4;
}

.feximar-auth-card {
    width: 100%;
    max-width: 28rem;
    background: #ffffff;
    border-radius: 10px;
    padding: 1.75rem 1.5rem;
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.28);
}
</style>
