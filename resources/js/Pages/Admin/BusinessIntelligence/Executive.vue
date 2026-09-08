<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import BiFilters from '@/Components/Admin/BiFilters.vue'
import BiChart from '@/Components/Admin/BiChart.vue'

defineProps({
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    summary: { type: Object, required: true },
    finance: { type: Object, required: true },
    salesByMonth: { type: Object, required: true },
    topBuyers: { type: Object, required: true },
    insights: { type: Array, default: () => [] },
    decisionIndicators: { type: Object, default: () => ({}) },
})

const formatValue = (kpi) => {
    if (kpi.format === 'currency') {
        return Number(kpi.value).toLocaleString('en-US', { style: 'currency', currency: 'USD' })
    }
    return Number(kpi.value).toLocaleString('en-US')
}

const variationClass = (kpi) => {
    if (kpi.variation === null || kpi.variation === undefined) return 'text-muted'
    return kpi.variation >= 0 ? 'text-success' : 'text-danger'
}

const variationText = (kpi) => {
    if (!kpi.has_comparison || kpi.previous === 0 && kpi.value === 0) {
        return 'Sin variación comparable'
    }
    if (kpi.variation === null || kpi.variation === undefined) {
        return 'Sin base en período anterior'
    }
    const sign = kpi.variation > 0 ? '+' : ''
    return `${sign}${kpi.variation}% vs período anterior`
}

const kpiColors = ['bg-primary', 'bg-success', 'bg-warning', 'bg-info', 'bg-danger', 'bg-secondary', 'bg-dark', 'bg-primary']
</script>

<template>
    <Head title="BI · Resumen Ejecutivo" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-8 p-md-0">
                <div class="welcome-text">
                    <h4>Resumen Ejecutivo</h4>
                    <span>
                        Período {{ summary.period.from }} → {{ summary.period.to }}
                    </span>
                </div>
            </div>
            <div class="col-sm-4 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex gap-2 flex-wrap">
                <Link :href="route('admin.bi.sales')" class="btn btn-outline-primary btn-sm">Ventas</Link>
                <Link :href="route('admin.bi.trends')" class="btn btn-outline-primary btn-sm">Tendencias</Link>
                <Link :href="route('admin.bi.finance')" class="btn btn-outline-primary btn-sm">Finanzas</Link>
                <Link :href="route('admin.bi.operations')" class="btn btn-outline-primary btn-sm">Operaciones</Link>
                <Link :href="route('admin.bi.prediction')" class="btn btn-outline-primary btn-sm">Predicción</Link>
            </div>
        </div>

        <BiFilters :filters="filters" :filter-options="filterOptions" route-name="admin.bi.executive" />

        <div class="row">
            <div
                v-for="(kpi, index) in summary.kpis"
                :key="kpi.key"
                class="col-xl-3 col-lg-6 col-sm-6"
            >
                <div class="widget-stat card" :class="kpiColors[index % kpiColors.length]">
                    <div class="card-body p-4">
                        <p class="mb-1 text-white">{{ kpi.label }}</p>
                        <h3 class="text-white">{{ formatValue(kpi) }}</h3>
                        <small class="text-white-50" :class="variationClass(kpi)">
                            {{ variationText(kpi) }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Evolución de ventas (USD)</h4>
                    </div>
                    <div class="card-body">
                        <BiChart
                            type="line"
                            :labels="salesByMonth.labels"
                            :data="salesByMonth.data"
                            label="Ventas USD"
                        />
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Trazabilidad financiera</h4>
                    </div>
                    <div class="card-body">
                        <p><strong>Contado:</strong> {{ Number(finance.cash_sales).toFixed(2) }}</p>
                        <p><strong>Crédito:</strong> {{ Number(finance.credit_sales).toFixed(2) }}</p>
                        <p><strong>Cuentas por cobrar:</strong> {{ Number(finance.accounts_receivable).toFixed(2) }}</p>
                        <p><strong>Monto cobrado:</strong> {{ Number(finance.collected).toFixed(2) }}</p>
                        <p><strong>Saldo pendiente:</strong> {{ Number(finance.pending_balance).toFixed(2) }}</p>
                        <p>
                            <strong>Días promedio de pago:</strong>
                            <span v-if="finance.avg_payment_days !== null">{{ finance.avg_payment_days }}</span>
                            <span v-else class="text-muted">{{ finance.avg_payment_days_message }}</span>
                        </p>
                        <small class="text-muted d-block" v-for="(note, i) in finance.notes" :key="i">{{ note }}</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Top compradores</h4>
                    </div>
                    <div class="card-body">
                        <BiChart
                            type="bar"
                            horizontal
                            :labels="topBuyers.labels"
                            :data="topBuyers.data"
                            label="Ventas USD"
                            :height="320"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Indicadores de decisión</h4></div>
                    <div class="card-body">
                        <div v-for="(kpi, key) in decisionIndicators" :key="key" class="mb-2">
                            <strong>{{ kpi.label }}:</strong>
                            <span v-if="kpi.message" class="text-muted"> {{ kpi.message }}</span>
                            <span v-else> {{ kpi.value }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
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
