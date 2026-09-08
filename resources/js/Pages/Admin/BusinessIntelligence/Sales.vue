<script setup>
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import BiFilters from '@/Components/Admin/BiFilters.vue'
import BiChart from '@/Components/Admin/BiChart.vue'

const props = defineProps({
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    period: { type: Object, required: true },
    charts: { type: Object, required: true },
    historicalTable: { type: Array, default: () => [] },
    currentAvailability: { type: Object, required: true },
})

const sortKey = ref('sales')
const sortDir = ref('desc')

const sortedHistorical = computed(() => {
    const rows = [...props.historicalTable]
    rows.sort((a, b) => {
        const av = a[sortKey.value] ?? -Infinity
        const bv = b[sortKey.value] ?? -Infinity
        if (av === bv) return 0
        if (sortDir.value === 'asc') return av > bv ? 1 : -1
        return av < bv ? 1 : -1
    })
    return rows
})

const sortBy = (key) => {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortDir.value = 'desc'
    }
}
</script>

<template>
    <Head title="BI · Ventas y Exportaciones" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-8 p-md-0">
                <div class="welcome-text">
                    <h4>Ventas y Exportaciones</h4>
                    <span>Período {{ period.from }} → {{ period.to }}</span>
                </div>
            </div>
            <div class="col-sm-4 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex gap-2 flex-wrap">
                <Link :href="route('admin.bi.executive')" class="btn btn-outline-primary btn-sm">Resumen</Link>
                <Link :href="route('admin.bi.trends')" class="btn btn-outline-primary btn-sm">Tendencias</Link>
                <Link :href="route('admin.bi.finance')" class="btn btn-outline-primary btn-sm">Finanzas</Link>
                <Link :href="route('admin.bi.operations')" class="btn btn-outline-primary btn-sm">Operaciones</Link>
            </div>
        </div>

        <BiFilters :filters="filters" :filter-options="filterOptions" route-name="admin.bi.sales" />

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">1. Ventas por mes</h4></div>
                    <div class="card-body">
                        <BiChart type="line" :labels="charts.sales_by_month.labels" :data="charts.sales_by_month.data" label="USD" />
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">2. Ventas por año</h4></div>
                    <div class="card-body">
                        <BiChart type="bar" :labels="charts.sales_by_year.labels" :data="charts.sales_by_year.data" label="USD" />
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">3. Tallos por variedad</h4></div>
                    <div class="card-body">
                        <BiChart type="bar" :labels="charts.stems_by_variety.labels" :data="charts.stems_by_variety.data" label="Tallos" />
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">4. Bunches por variedad</h4></div>
                    <div class="card-body">
                        <BiChart type="bar" :labels="charts.bunches_by_variety.labels" :data="charts.bunches_by_variety.data" label="Bunches" />
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">5. Top 10 compradores</h4></div>
                    <div class="card-body">
                        <BiChart type="bar" horizontal :labels="charts.top_buyers.labels" :data="charts.top_buyers.data" label="USD" :height="320" />
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">6. Ventas por finca</h4></div>
                    <div class="card-body">
                        <BiChart type="bar" :labels="charts.sales_by_farm.labels" :data="charts.sales_by_farm.data" label="USD" />
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">7. Participación país destino</h4></div>
                    <div class="card-body">
                        <BiChart type="doughnut" :labels="charts.destination_countries.labels" :data="charts.destination_countries.data" label="Pedidos" />
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">11. Precio promedio / tallo</h4></div>
                    <div class="card-body">
                        <BiChart type="line" :labels="charts.avg_stem_price.labels" :data="charts.avg_stem_price.data" label="USD/tallo" />
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">12. Disponibilidad vs venta</h4></div>
                    <div class="card-body">
                        <BiChart
                            type="bar"
                            :labels="charts.availability_vs_sales.labels"
                            :datasets="charts.availability_vs_sales.datasets"
                            label="Tallos"
                        />
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
                    <div class="card-header"><h4 class="card-title">14. Top agencias de carga</h4></div>
                    <div class="card-body">
                        <BiChart type="bar" :labels="charts.top_cargo_agencies.labels" :data="charts.top_cargo_agencies.data" label="Pedidos" />
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Ventas USD por variedad</h4></div>
                    <div class="card-body">
                        <BiChart type="bar" :labels="charts.sales_by_variety.labels" :data="charts.sales_by_variety.data" label="USD" />
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Análisis histórico por variedad</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-responsive-md">
                                <thead>
                                    <tr>
                                        <th @click="sortBy('variety')" style="cursor:pointer">Variedad</th>
                                        <th @click="sortBy('stems')" style="cursor:pointer">Tallos</th>
                                        <th @click="sortBy('boxes')" style="cursor:pointer">Cajas</th>
                                        <th @click="sortBy('sales')" style="cursor:pointer">Ventas</th>
                                        <th @click="sortBy('avg_price')" style="cursor:pointer">Precio prom.</th>
                                        <th @click="sortBy('share')" style="cursor:pointer">Participación %</th>
                                        <th @click="sortBy('growth')" style="cursor:pointer">Crecimiento %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="sortedHistorical.length === 0">
                                        <td colspan="7" class="text-center text-muted">Sin datos para el período.</td>
                                    </tr>
                                    <tr v-for="row in sortedHistorical" :key="row.variety">
                                        <td>{{ row.variety }}</td>
                                        <td>{{ row.stems }}</td>
                                        <td>{{ row.boxes }}</td>
                                        <td>{{ Number(row.sales).toFixed(2) }}</td>
                                        <td>{{ Number(row.avg_price).toFixed(4) }}</td>
                                        <td>{{ row.share }}%</td>
                                        <td>{{ row.growth === null ? '—' : `${row.growth}%` }}</td>
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
                    <div class="card-header">
                        <h4 class="card-title">
                            Análisis actual — Semana {{ currentAvailability.week }}/{{ currentAvailability.year }}
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-responsive-md">
                                <thead>
                                    <tr>
                                        <th>Variedad</th>
                                        <th>Disponibles</th>
                                        <th>Reservados</th>
                                        <th>Efectivos</th>
                                        <th>Precio actual</th>
                                        <th>Pedidos pendientes (tallos)</th>
                                        <th>Pedidos aceptados (tallos)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="currentAvailability.rows.length === 0">
                                        <td colspan="7" class="text-center text-muted">
                                            No hay disponibilidad reportada para la semana actual.
                                        </td>
                                    </tr>
                                    <tr v-for="row in currentAvailability.rows" :key="row.variety">
                                        <td>{{ row.variety }}</td>
                                        <td>{{ row.available_stems }}</td>
                                        <td>{{ row.reserved_stems }}</td>
                                        <td>{{ row.effective_stems }}</td>
                                        <td>{{ row.current_price ?? '—' }}</td>
                                        <td>{{ row.pending_stems }}</td>
                                        <td>{{ row.accepted_stems }}</td>
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
