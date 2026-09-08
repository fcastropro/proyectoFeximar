<script setup>
import { Head, Link } from '@inertiajs/vue3'
import FarmLayout from '@/Layouts/FarmLayout.vue'
import BiFilters from '@/Components/Admin/BiFilters.vue'
import BiChart from '@/Components/Admin/BiChart.vue'

defineProps({
    farm: { type: Object, required: true },
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    period: { type: Object, required: true },
    trends: { type: Object, required: true },
    finance: { type: Object, required: true },
    yearOverYear: { type: Object, required: true },
    salesByMonth: { type: Object, required: true },
})

const formatPct = (value) => {
    if (value === null || value === undefined) return null
    const sign = value > 0 ? '+' : ''
    return `${sign}${value}%`
}
</script>

<template>
    <Head title="BI Finca · Tendencias" />

    <FarmLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-8 p-md-0">
                <div class="welcome-text">
                    <h4>Tendencias</h4>
                    <span>{{ farm.name }} · {{ period.from }} → {{ period.to }}</span>
                </div>
            </div>
            <div class="col-sm-4 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex gap-2 flex-wrap">
                <Link :href="route('farm.bi.executive')" class="btn btn-outline-primary btn-sm">Resumen</Link>
                <Link :href="route('farm.bi.sales')" class="btn btn-outline-primary btn-sm">Ventas</Link>
                <a :href="route('farm.bi.prediction-dataset')" class="btn btn-primary btn-sm">Dataset IA (CSV)</a>
            </div>
        </div>

        <BiFilters
            :filters="filters"
            :filter-options="filterOptions"
            route-name="farm.bi.trends"
            hide-farm-filter
        />

        <div class="row">
            <div v-for="item in trends.indicators" :key="item.key" class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-2">{{ item.label }}</h5>
                        <h3 v-if="item.value !== null" class="mb-0">{{ formatPct(item.value) }}</h3>
                        <p v-else class="text-muted mb-0">{{ item.message || trends.insufficient_message }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Comparativo año a año</h4></div>
                    <div class="card-body">
                        <BiChart
                            type="bar"
                            :labels="yearOverYear.labels"
                            :datasets="[
                                { label: 'Ventas USD', data: yearOverYear.sales },
                                { label: 'Tallos', data: yearOverYear.stems },
                                { label: 'Cajas', data: yearOverYear.boxes },
                                { label: 'Pedidos', data: yearOverYear.orders },
                            ]"
                            :height="320"
                        />
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Demanda en tallos</h4></div>
                    <div class="card-body">
                        <BiChart type="line" :labels="trends.stems_demand.labels" :data="trends.stems_demand.data" label="Tallos" />
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Mayor crecimiento</h4></div>
                    <div class="card-body">
                        <div v-if="!trends.top_growing_varieties.length" class="text-muted">{{ trends.insufficient_message }}</div>
                        <table v-else class="table mb-0">
                            <thead><tr><th>Variedad</th><th>Ventas</th><th>Crec.</th></tr></thead>
                            <tbody>
                                <tr v-for="row in trends.top_growing_varieties" :key="row.variety">
                                    <td>{{ row.variety }}</td>
                                    <td>{{ Number(row.sales).toFixed(2) }}</td>
                                    <td>{{ formatPct(row.growth) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Menor movimiento</h4></div>
                    <div class="card-body">
                        <table class="table mb-0">
                            <thead><tr><th>Variedad</th><th>Ventas</th></tr></thead>
                            <tbody>
                                <tr v-if="!trends.least_movement_varieties.length">
                                    <td colspan="2" class="text-muted">Sin datos.</td>
                                </tr>
                                <tr v-for="row in trends.least_movement_varieties" :key="row.variety">
                                    <td>{{ row.variety }}</td>
                                    <td>{{ Number(row.sales).toFixed(2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Compradores con mayor participación</h4></div>
                    <div class="card-body">
                        <table class="table mb-0">
                            <thead><tr><th>Comprador</th><th>Ventas</th><th>%</th></tr></thead>
                            <tbody>
                                <tr v-for="row in trends.top_buyers_share" :key="row.buyer">
                                    <td>{{ row.buyer }}</td>
                                    <td>{{ Number(row.sales).toFixed(2) }}</td>
                                    <td>{{ row.share }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Países con mayor participación</h4></div>
                    <div class="card-body">
                        <table class="table mb-0">
                            <thead><tr><th>País</th><th>Ventas</th><th>%</th></tr></thead>
                            <tbody>
                                <tr v-for="row in trends.top_countries_share" :key="row.country">
                                    <td>{{ row.country }}</td>
                                    <td>{{ Number(row.sales).toFixed(2) }}</td>
                                    <td>{{ row.share }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </FarmLayout>
</template>
