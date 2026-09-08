<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import BiFilters from '@/Components/Admin/BiFilters.vue'
import BiChart from '@/Components/Admin/BiChart.vue'

defineProps({
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    period: { type: Object, required: true },
    finance: { type: Object, required: true },
    charts: { type: Object, required: true },
    decisionIndicators: { type: Object, default: () => ({}) },
})
</script>

<template>
    <Head title="BI · Finanzas" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-8 p-md-0">
                <div class="welcome-text">
                    <h4>Finanzas</h4>
                    <span>Período {{ period.from }} → {{ period.to }}</span>
                </div>
            </div>
            <div class="col-sm-4 p-md-0 d-flex justify-content-sm-end gap-2">
                <Link :href="route('admin.bi.executive')" class="btn btn-outline-primary btn-sm">Resumen</Link>
                <a :href="route('admin.reports.farm-payables.pdf')" class="btn btn-outline-success btn-sm">PDF por pagar</a>
                <a :href="route('admin.reports.buyer-sales.pdf')" class="btn btn-outline-success btn-sm">PDF ventas</a>
            </div>
        </div>

        <BiFilters :filters="filters" :filter-options="filterOptions" route-name="admin.bi.finance" />

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">9. Contado vs crédito</h4></div>
                    <div class="card-body">
                        <BiChart type="doughnut" :labels="charts.cash_vs_credit.labels" :data="charts.cash_vs_credit.data" label="USD" />
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">10. Cobrado vs pendiente</h4></div>
                    <div class="card-body">
                        <BiChart type="doughnut" :labels="charts.collected_vs_pending.labels" :data="charts.collected_vs_pending.data" label="USD" />
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Resumen financiero</h4></div>
                    <div class="card-body">
                        <p>Cobrado: <strong>{{ Number(finance.collected).toFixed(2) }}</strong></p>
                        <p>Pendiente: <strong>{{ Number(finance.pending_balance).toFixed(2) }}</strong></p>
                        <p>Vencido: <strong>{{ Number(finance.overdue_amount).toFixed(2) }}</strong></p>
                        <p>
                            Días promedio de pago:
                            <strong v-if="finance.avg_payment_days !== null">{{ finance.avg_payment_days }}</strong>
                            <span v-else class="text-muted">{{ finance.avg_payment_days_message }}</span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Indicadores</h4></div>
                    <div class="card-body">
                        <div v-for="(kpi, key) in decisionIndicators" :key="key" class="mb-2">
                            <strong>{{ kpi.label }}:</strong>
                            <span v-if="kpi.message" class="text-muted"> {{ kpi.message }}</span>
                            <span v-else> {{ kpi.value }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
