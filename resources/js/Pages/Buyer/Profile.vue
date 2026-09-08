<script setup>
import { Head } from '@inertiajs/vue3'
import BuyerLayout from '@/Layouts/BuyerLayout.vue'

defineProps({
    profile: { type: Object, required: true },
})
</script>

<template>
    <Head title="Mi perfil" />

    <BuyerLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Mi perfil</h4>
                    <span>Datos de tu empresa y usuario</span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Empresa compradora</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="mb-3 col-md-12">
                                <strong>Compañía:</strong> {{ profile.company_name }}
                            </div>
                            <div class="mb-3 col-md-6">
                                <strong>Contacto:</strong> {{ profile.contact_name || '—' }}
                            </div>
                            <div class="mb-3 col-md-6">
                                <strong>Email:</strong> {{ profile.email || '—' }}
                            </div>
                            <div class="mb-3 col-md-6">
                                <strong>Teléfono:</strong> {{ profile.phone || '—' }}
                            </div>
                            <div class="mb-3 col-md-6">
                                <strong>País:</strong> {{ profile.country || '—' }}
                            </div>
                            <div class="mb-3 col-md-6">
                                <strong>Ciudad:</strong> {{ profile.city || '—' }}
                            </div>
                            <div class="mb-3 col-md-12">
                                <strong>Dirección:</strong> {{ profile.address || '—' }}
                            </div>
                            <div class="mb-3 col-md-6">
                                <strong>Crédito:</strong>
                                {{ profile.credit_allowed ? 'Permitido' : 'No permitido' }}
                            </div>
                            <div class="mb-0 col-md-6">
                                <strong>Días de crédito:</strong>
                                {{ profile.credit_days_default ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Usuario</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Nombre:</strong> {{ profile.user_name }}
                        </div>
                        <div class="mb-0">
                            <strong>Email:</strong> {{ profile.user_email }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BuyerLayout>
</template>
