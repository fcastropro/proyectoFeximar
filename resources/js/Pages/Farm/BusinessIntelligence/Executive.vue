<script setup>
import { Head, Link } from '@inertiajs/vue3'
import FarmLayout from '@/Layouts/FarmLayout.vue'
import BiFilters from '@/Components/Admin/BiFilters.vue'
import BiChart from '@/Components/Admin/BiChart.vue'

defineProps({
    farm: { type: Object, required: true },
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    summary: { type: Object, required: true },
    finance: { type: Object, required: true },
    salesByMonth: { type: Object, required: true },
    topBuyers: { type: Object, required: true },
})

const formatValue = (kpi) => {
    if (kpi.message && kpi.key === 'avg_payment_days' && kpi.value === 0) {
        return '—'
    }
    if (kpi.format === 'currency') {
        return Number(kpi.value).toLocaleString('en-US', { style: 'currency', currency: 'USD' })
    }
    return Number(kpi.value).toLocaleString('en-US')
}

const variationText = (kpi) => {
    if (kpi.message) return kpi.message
    if (!kpi.has_comparison || (kpi.previous === 0 && kpi.value === 0)) {
        return 'Sin variación comparable'
    }
    if (kpi.variation === null || kpi.variation === undefined) {
        return 'Sin base en período anterior'
    }
    const sign = kpi.variation > 0 ? '+' : ''
    return `${sign}${kpi.variation}% vs período anterior`
}

const kpiColors = ['bg-primary', 'bg-success', 'bg-warning', 'bg-info', 'bg-danger', 'bg-secondary', 'bg-dark', 'bg-primary', 'bg-success', 'bg-info', 'bg-warning', 'bg-danger']
</script>

<template>
    <Head title="BI Finca · Resumen" />

    <FarmLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-8 p-md-0">
                <div class="welcome-text">
                    <h4>Inteligencia de Negocios</h4>
                    <span>{{ farm.name }} · {{ summary.period.from }} → {{ summary.period.to }}</span>
                </div>
            </div>
            <div class="col-sm-4 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex gap-2">
                <Link :href="route('farm.bi.sales')" class="btn btn-outline-primary btn-sm">Ventas</Link>
                <Link :href="route('farm.bi.trends')" class="btn btn-outline-primary btn-sm">Tendencias</Link>
            </div>
        </div>

        <BiFilters
            :filters="filters"
            :filter-options="filterOptions"
            route-name="farm.bi.executive"
            hide-farm-filter
        />

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
                        <small class="text-white">{{ variationText(kpi) }}</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Ventas por mes</h4></div>
                    <div class="card-body">
                        <BiChart type="line" :labels="salesByMonth.labels" :data="salesByMonth.data" label="USD" />
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Cobrado vs pendiente</h4></div>
                    <div class="card-body">
                        <BiChart
                            type="doughnut"
                            :labels="finance.collected_vs_pending.labels"
                            :data="finance.collected_vs_pending.data"
                            label="USD"
                        />
                        <p class="mt-3 mb-1"><strong>Contado:</strong> {{ Number(finance.cash_sales).toFixed(2) }}</p>
                        <p class="mb-1"><strong>Crédito:</strong> {{ Number(finance.credit_sales).toFixed(2) }}</p>
                        <p class="mb-1"><strong>Vencido:</strong> {{ Number(finance.overdue_amount).toFixed(2) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Top compradores</h4></div>
                    <div class="card-body">
                        <BiChart type="bar" horizontal :labels="topBuyers.labels" :data="topBuyers.data" label="USD" :height="320" />
                    </div>
                </div>
            </div>
        </div>
    </FarmLayout>
</template>
