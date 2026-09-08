<script setup>
import { Head } from '@inertiajs/vue3'
import FarmLayout from '@/Layouts/FarmLayout.vue'

defineProps({
    farm: { type: Object, required: true },
})
</script>

<template>
    <Head title="Mi finca" />

    <FarmLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Mi finca</h4>
                    <span>Datos generales de tu finca</span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Información</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <strong>Nombre:</strong> {{ farm.name }}
                            </div>
                            <div class="mb-3 col-md-6">
                                <strong>Nombre comercial:</strong> {{ farm.commercial_name || '—' }}
                            </div>
                            <div class="mb-3 col-md-6">
                                <strong>RUC:</strong> {{ farm.ruc || '—' }}
                            </div>
                            <div class="mb-3 col-md-6">
                                <strong>Email:</strong> {{ farm.email || '—' }}
                            </div>
                            <div class="mb-3 col-md-6">
                                <strong>Teléfono:</strong> {{ farm.phone || '—' }}
                            </div>
                            <div class="mb-3 col-md-6">
                                <strong>Ciudad:</strong> {{ farm.city || '—' }}
                            </div>
                            <div class="mb-3 col-md-6">
                                <strong>Provincia:</strong> {{ farm.province || '—' }}
                            </div>
                            <div class="mb-3 col-md-6">
                                <strong>País:</strong> {{ farm.country || '—' }}
                            </div>
                            <div class="mb-3 col-md-12">
                                <strong>Dirección:</strong> {{ farm.address || '—' }}
                            </div>
                            <div class="mb-0 col-md-12">
                                <strong>Descripción:</strong> {{ farm.description || '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </FarmLayout>
</template>
