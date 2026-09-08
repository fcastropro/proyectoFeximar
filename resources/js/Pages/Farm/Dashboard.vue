<script setup>
import { Head } from '@inertiajs/vue3'
import FarmLayout from '@/Layouts/FarmLayout.vue'

defineProps({
    farm: { type: Object, required: true },
    kpis: { type: Object, required: true },
})
</script>

<template>
    <Head title="Portal Finca" />

    <FarmLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ farm.name }}</h4>
                    <span>
                        Semana {{ kpis.current_week }}/{{ kpis.current_year }}
                    </span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-primary">
                    <div class="card-body p-4">
                        <p class="mb-1 text-white">Productos activos</p>
                        <h3 class="text-white">{{ kpis.products_active }}</h3>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-success">
                    <div class="card-body p-4">
                        <p class="mb-1 text-white">Tallos disponibles (semana)</p>
                        <h3 class="text-white">{{ kpis.stems_available_week }}</h3>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-warning">
                    <div class="card-body p-4">
                        <p class="mb-1 text-white">Pedidos pendientes</p>
                        <h3 class="text-white">{{ kpis.orders_pending }}</h3>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-info">
                    <div class="card-body p-4">
                        <p class="mb-1 text-white">Pedidos aceptados</p>
                        <h3 class="text-white">{{ kpis.orders_accepted }}</h3>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-danger">
                    <div class="card-body p-4">
                        <p class="mb-1 text-white">Valor pendiente de cobro</p>
                        <h3 class="text-white">{{ Number(kpis.pending_collection).toFixed(2) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </FarmLayout>
</template>
