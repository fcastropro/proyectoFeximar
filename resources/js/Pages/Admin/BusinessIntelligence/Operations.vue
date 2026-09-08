<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import BiFilters from '@/Components/Admin/BiFilters.vue'
import BiChart from '@/Components/Admin/BiChart.vue'

defineProps({
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    period: { type: Object, required: true },
    charts: { type: Object, required: true },
    cycleTimes: { type: Object, required: true },
    decisionIndicators: { type: Object, default: () => ({}) },
    insights: { type: Array, default: () => [] },
})
</script>

<template>
    <Head title="BI · Operaciones" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-8 p-md-0">
                <div class="welcome-text">
                    <h4>Operaciones</h4>
                    <span>Período {{ period.from }} → {{ period.to }}</span>
                </div>
            </div>
            <div class="col-sm-4 p-md-0 d-flex justify-content-sm-end gap-2">
                <Link :href="route('admin.bi.executive')" class="btn btn-outline-primary btn-sm">Resumen</Link>
            </div>
        </div>

        <BiFilters :filters="filters" :filter-options="filterOptions" route-name="admin.bi.operations" />

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">8. Pedidos por estado</h4></div>
                    <div class="card-body">
                        <BiChart type="doughnut" :labels="charts.orders_by_status.labels" :data="charts.orders_by_status.data" label="Pedidos" />
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">13. Aéreo vs marítimo</h4></div>
                    <div class="card-body">
                        <BiChart type="doughnut" :labels="charts.shipping_mix.labels" :data="charts.shipping_mix.data" label="Pedidos" />
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">14. Top agencias</h4></div>
                    <div class="card-body">
                        <BiChart type="bar" :labels="charts.top_cargo_agencies.labels" :data="charts.top_cargo_agencies.data" label="Pedidos" />
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">16. Ciclos operativos</h4></div>
                    <div class="card-body">
                        <p>
                            Pedido → aceptación:
                            <strong v-if="cycleTimes.accept_hours !== null">{{ cycleTimes.accept_hours }} h</strong>
                            <span v-else class="text-muted">{{ cycleTimes.message || 'Información histórica insuficiente' }}</span>
                        </p>
                        <p class="mb-0">
                            Aceptación → despacho:
                            <strong v-if="cycleTimes.dispatch_hours !== null">{{ cycleTimes.dispatch_hours }} h</strong>
                            <span v-else class="text-muted">Información histórica insuficiente</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Insights gerenciales</h4></div>
                    <div class="card-body">
                        <ul v-if="insights.length" class="mb-0">
                            <li v-for="item in insights" :key="item.key">{{ item.message }}</li>
                        </ul>
                        <p v-else class="text-muted mb-0">Información histórica insuficiente</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
