<script setup>
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import FarmLayout from '@/Layouts/FarmLayout.vue'
import BiFilters from '@/Components/Admin/BiFilters.vue'
import BiChart from '@/Components/Admin/BiChart.vue'

const props = defineProps({
    farm: { type: Object, required: true },
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    period: { type: Object, required: true },
    charts: { type: Object, required: true },
    historicalTable: { type: Array, default: () => [] },
    currentOperations: { type: Object, required: true },
    finance: { type: Object, required: true },
})

const sortKey = ref('sales')
const sortDir = ref('desc')

const sortedHistorical = computed(() => {
    const rows = [...props.historicalTable]
    rows.sort((a, b) => {
        const av = a[sortKey.value] ?? -Infinity
        const bv = b[sortKey.value] ?? -Infinity
        if (av === bv) return 0
        return sortDir.value === 'asc' ? (av > bv ? 1 : -1) : (av < bv ? 1 : -1)
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

const availabilityDatasets = computed(() => [
    { label: 'Reportados', data: props.charts.availability_vs_sold.reported },
    { label: 'Reservados', data: props.charts.availability_vs_sold.reserved },
    { label: 'Efectivos', data: props.charts.availability_vs_sold.effective },
    { label: 'Vendidos', data: props.charts.availability_vs_sold.sold },
])
</script>

<template>
    <Head title="BI Finca · Ventas" />

    <FarmLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-8 p-md-0">
                <div class="welcome-text">
                    <h4>Ventas</h4>
                    <span>{{ farm.name }} · {{ period.from }} → {{ period.to }}</span>
                </div>
            </div>
            <div class="col-sm-4 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex gap-2">
                <Link :href="route('farm.bi.executive')" class="btn btn-outline-primary btn-sm">Resumen</Link>
                <Link :href="route('farm.bi.trends')" class="btn btn-outline-primary btn-sm">Tendencias</Link>
            </div>
        </div>

        <BiFilters
            :filters="filters"
            :filter-options="filterOptions"
            route-name="farm.bi.sales"
            hide-farm-filter
        />

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">A. Ventas por mes</h4></div>
                    <div class="card-body">
                        <BiChart type="line" :labels="charts.sales_by_month.labels" :data="charts.sales_by_month.data" label="USD" />
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">G. Precio promedio / tallo</h4></div>
                    <div class="card-body">
                        <BiChart
                            type="line"
                            :labels="charts.avg_stem_price.labels"
                            :datasets="charts.avg_stem_price.datasets?.length ? charts.avg_stem_price.datasets : null"
                            :data="charts.avg_stem_price.data"
                            label="USD/tallo"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">B. Tallos por variedad</h4></div>
                    <div class="card-body">
                        <BiChart type="bar" :labels="charts.stems_by_variety.labels" :data="charts.stems_by_variety.data" label="Tallos" />
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">C. Ventas por variedad</h4></div>
                    <div class="card-body">
                        <BiChart type="bar" :labels="charts.sales_by_variety.labels" :data="charts.sales_by_variety.data" label="USD" />
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">D. Top compradores</h4></div>
                    <div class="card-body">
                        <BiChart type="bar" horizontal :labels="charts.top_buyers.labels" :data="charts.top_buyers.data" label="USD" :height="320" />
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">E. Ventas por país</h4></div>
                    <div class="card-body">
                        <BiChart type="doughnut" :labels="charts.sales_by_country.labels" :data="charts.sales_by_country.data" label="USD" />
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">F. Cajas por tipo</h4></div>
                    <div class="card-body">
                        <BiChart type="bar" :labels="charts.boxes_by_type.labels" :data="charts.boxes_by_type.data" label="Cajas" />
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Pagos</h4></div>
                    <div class="card-body">
                        <BiChart
                            type="doughnut"
                            :labels="finance.collected_vs_pending.labels"
                            :data="finance.collected_vs_pending.data"
                        />
                        <p class="mt-3 mb-0"><strong>Vencidos:</strong> {{ Number(finance.overdue_amount).toFixed(2) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">H. Disponibilidad vs vendido</h4></div>
                    <div class="card-body">
                        <BiChart
                            type="bar"
                            :labels="charts.availability_vs_sold.labels"
                            :datasets="availabilityDatasets"
                            :height="320"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Tabla analítica por variedad</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-responsive-md">
                                <thead>
                                    <tr>
                                        <th style="cursor:pointer" @click="sortBy('variety')">Variedad</th>
                                        <th style="cursor:pointer" @click="sortBy('stem_length_cm')">Longitud</th>
                                        <th style="cursor:pointer" @click="sortBy('stems')">Tallos</th>
                                        <th style="cursor:pointer" @click="sortBy('boxes')">Cajas</th>
                                        <th style="cursor:pointer" @click="sortBy('sales')">Ventas</th>
                                        <th style="cursor:pointer" @click="sortBy('avg_price')">Precio prom.</th>
                                        <th style="cursor:pointer" @click="sortBy('orders')">Pedidos</th>
                                        <th style="cursor:pointer" @click="sortBy('share')">Participación %</th>
                                        <th style="cursor:pointer" @click="sortBy('growth')">Crecimiento %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="sortedHistorical.length === 0">
                                        <td colspan="9" class="text-center text-muted">Sin datos.</td>
                                    </tr>
                                    <tr v-for="row in sortedHistorical" :key="`${row.variety}-${row.stem_length_cm}`">
                                        <td>{{ row.variety }}</td>
                                        <td>{{ row.stem_length_cm }} cm</td>
                                        <td>{{ row.stems }}</td>
                                        <td>{{ row.boxes }}</td>
                                        <td>{{ Number(row.sales).toFixed(2) }}</td>
                                        <td>{{ Number(row.avg_price).toFixed(4) }}</td>
                                        <td>{{ row.orders }}</td>
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
                            Operación actual — Semana {{ currentOperations.week }}/{{ currentOperations.year }}
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-responsive-md">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Variedad</th>
                                        <th>Longitud</th>
                                        <th>Disponibles</th>
                                        <th>Reservados</th>
                                        <th>Efectivos</th>
                                        <th>Precio</th>
                                        <th>Pendientes</th>
                                        <th>Aceptados</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="currentOperations.rows.length === 0">
                                        <td colspan="9" class="text-center text-muted">Sin disponibilidad en la semana actual.</td>
                                    </tr>
                                    <tr v-for="(row, idx) in currentOperations.rows" :key="idx">
                                        <td>{{ row.product_name }}</td>
                                        <td>{{ row.variety }}</td>
                                        <td>{{ row.stem_length_cm }} cm</td>
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
    </FarmLayout>
</template>
