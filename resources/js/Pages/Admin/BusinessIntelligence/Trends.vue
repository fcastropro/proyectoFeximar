<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import BiFilters from '@/Components/Admin/BiFilters.vue'
import BiChart from '@/Components/Admin/BiChart.vue'

defineProps({
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    period: { type: Object, required: true },
    trends: { type: Object, required: true },
    finance: { type: Object, required: true },
    salesByMonth: { type: Object, required: true },
})

const formatPct = (value) => {
    if (value === null || value === undefined) return null
    const sign = value > 0 ? '+' : ''
    return `${sign}${value}%`
}
</script>

<template>
    <Head title="BI · Análisis y Tendencias" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-8 p-md-0">
                <div class="welcome-text">
                    <h4>Análisis y Tendencias</h4>
                    <span>Período {{ period.from }} → {{ period.to }}</span>
                </div>
            </div>
            <div class="col-sm-4 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex gap-2">
                <Link :href="route('admin.bi.executive')" class="btn btn-outline-primary btn-sm">Resumen</Link>
                <Link :href="route('admin.bi.sales')" class="btn btn-outline-primary btn-sm">Ventas</Link>
                <a :href="route('admin.bi.prediction-dataset')" class="btn btn-primary btn-sm">Dataset IA (CSV)</a>
            </div>
        </div>

        <BiFilters :filters="filters" :filter-options="filterOptions" route-name="admin.bi.trends" />

        <div class="row">
            <div
                v-for="item in trends.indicators"
                :key="item.key"
                class="col-xl-3 col-md-6"
            >
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
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Evolución mensual</h4></div>
                    <div class="card-body">
                        <BiChart type="line" :labels="salesByMonth.labels" :data="salesByMonth.data" label="USD" />
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Finanzas</h4></div>
                    <div class="card-body">
                        <p><strong>Contado:</strong> {{ Number(finance.cash_sales).toFixed(2) }}</p>
                        <p><strong>Crédito:</strong> {{ Number(finance.credit_sales).toFixed(2) }}</p>
                        <p><strong>Cobrado:</strong> {{ Number(finance.collected).toFixed(2) }}</p>
                        <p><strong>Pendiente:</strong> {{ Number(finance.pending_balance).toFixed(2) }}</p>
                        <p>
                            <strong>Días prom. pago:</strong>
                            <span v-if="finance.avg_payment_days !== null">{{ finance.avg_payment_days }}</span>
                            <span v-else class="text-muted">{{ finance.avg_payment_days_message }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Variedades con mayor crecimiento</h4></div>
                    <div class="card-body">
                        <div v-if="trends.top_growing_varieties.length === 0" class="text-muted">
                            {{ trends.insufficient_message }}
                        </div>
                        <div class="table-responsive" v-else>
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Variedad</th>
                                        <th>Ventas</th>
                                        <th>Crecimiento</th>
                                    </tr>
                                </thead>
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
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Variedades con menor movimiento</h4></div>
                    <div class="card-body">
                        <div v-if="trends.least_movement_varieties.length === 0" class="text-muted">
                            Sin datos suficientes.
                        </div>
                        <div class="table-responsive" v-else>
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Variedad</th>
                                        <th>Ventas mes</th>
                                    </tr>
                                </thead>
                                <tbody>
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
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Compradores con mayor participación</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Comprador</th>
                                        <th>Ventas</th>
                                        <th>Participación %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="trends.top_buyers_share.length === 0">
                                        <td colspan="3" class="text-muted">Sin datos.</td>
                                    </tr>
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
            </div>
        </div>
    </AdminLayout>
</template>
