<script setup>
import { Head } from '@inertiajs/vue3'
import BuyerLayout from '@/Layouts/BuyerLayout.vue'
import BiChart from '@/Components/Admin/BiChart.vue'

defineProps({
    kpis: { type: Object, required: true },
    charts: { type: Object, required: true },
})
</script>

<template>
    <Head title="Portal Comprador" />

    <BuyerLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Dashboard</h4>
                    <span>Resumen de compras y pedidos</span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-primary">
                    <div class="card-body p-4">
                        <p class="mb-1 text-white">Pedidos</p>
                        <h3 class="text-white">{{ kpis.orders }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-warning">
                    <div class="card-body p-4">
                        <p class="mb-1 text-white">En curso</p>
                        <h3 class="text-white">{{ kpis.in_progress }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-success">
                    <div class="card-body p-4">
                        <p class="mb-1 text-white">Completados</p>
                        <h3 class="text-white">{{ kpis.completed }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-info">
                    <div class="card-body p-4">
                        <p class="mb-1 text-white">Total compras (USD)</p>
                        <h3 class="text-white">{{ Number(kpis.total_purchases).toFixed(2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-danger">
                    <div class="card-body p-4">
                        <p class="mb-1 text-white">Tallos comprados</p>
                        <h3 class="text-white">{{ kpis.stems_bought }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-secondary">
                    <div class="card-body p-4">
                        <p class="mb-1 text-white">Variedad top</p>
                        <h3 class="text-white" style="font-size: 1.25rem;">
                            {{ kpis.top_variety || '—' }}
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-7">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Compras por mes</h4>
                    </div>
                    <div class="card-body">
                        <BiChart
                            type="line"
                            :labels="charts.purchases_by_month.labels"
                            :data="charts.purchases_by_month.data"
                            label="USD"
                        />
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Tallos por variedad</h4>
                    </div>
                    <div class="card-body">
                        <BiChart
                            type="bar"
                            :labels="charts.stems_by_variety.labels"
                            :data="charts.stems_by_variety.data"
                            label="Tallos"
                        />
                    </div>
                </div>
            </div>
        </div>
    </BuyerLayout>
</template>
