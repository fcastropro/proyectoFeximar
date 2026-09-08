<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    generated_at: { type: String, default: '' },
    week: { type: Object, default: () => ({}) },
    commercial: { type: Object, default: () => ({}) },
    operations: { type: Object, default: () => ({}) },
    offer: { type: Object, default: () => ({}) },
    clients: { type: Object, default: () => ({}) },
    finance: { type: Object, default: () => ({}) },
    logistics: { type: Object, default: () => ({}) },
    users: { type: Object, default: () => ({}) },
    alerts: { type: Array, default: () => [] },
})

const money = (value) => {
    if (value === null || value === undefined) return '—'
    return Number(value).toLocaleString('en-US', { style: 'currency', currency: 'USD' })
}

const num = (value) => {
    if (value === null || value === undefined) return '—'
    return Number(value).toLocaleString('en-US')
}
</script>

<template>
    <Head title="Panel Administrativo FEXIMAR" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-8 p-md-0">
                <div class="welcome-text">
                    <h4>Panel de control gerencial</h4>
                    <span>
                        Semana {{ week.year }}-W{{ week.week }} · Actualizado {{ generated_at }}
                    </span>
                </div>
            </div>
            <div class="col-sm-4 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex gap-2 flex-wrap">
                <Link :href="route('admin.bi.executive')" class="btn btn-outline-primary btn-sm">BI</Link>
                <a :href="route('admin.reports.availability.pdf')" class="btn btn-outline-success btn-sm">Oferta PDF</a>
                <Link :href="route('admin.exports.index')" class="btn btn-outline-secondary btn-sm">Exportaciones</Link>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Alertas del día</h4>
                    </div>
                    <div class="card-body">
                        <div v-if="!alerts.length" class="text-muted">Sin alertas críticas en este momento.</div>
                        <ul v-else class="mb-0">
                            <li v-for="(alert, idx) in alerts" :key="idx">
                                {{ alert.message }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <h5 class="mt-2 mb-3">Comercial</h5>
        <div class="row">
            <div class="col-xl-3 col-md-6"><div class="widget-stat card bg-primary"><div class="card-body p-4"><p class="mb-1 text-white">Ventas del mes</p><h4 class="text-white">{{ money(commercial.sales_month) }}</h4></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="widget-stat card bg-primary"><div class="card-body p-4"><p class="mb-1 text-white">Ventas del año</p><h4 class="text-white">{{ money(commercial.sales_year) }}</h4></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="widget-stat card bg-info"><div class="card-body p-4"><p class="mb-1 text-white">Pedidos del mes</p><h4 class="text-white">{{ num(commercial.orders_month) }}</h4></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="widget-stat card bg-info"><div class="card-body p-4"><p class="mb-1 text-white">Ticket promedio</p><h4 class="text-white">{{ money(commercial.avg_ticket) }}</h4></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="widget-stat card"><div class="card-body p-4"><p class="mb-1">Tallos vendidos</p><h4>{{ num(commercial.stems_sold) }}</h4></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="widget-stat card"><div class="card-body p-4"><p class="mb-1">Bunches vendidos</p><h4>{{ num(commercial.bunches_sold) }}</h4></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="widget-stat card"><div class="card-body p-4"><p class="mb-1">Cajas requeridas</p><h4>{{ num(commercial.boxes_required) }}</h4></div></div></div>
        </div>

        <h5 class="mt-2 mb-3">Operación (fulfillments)</h5>
        <div class="row">
            <div v-for="item in [
                ['Pendientes', operations.pending],
                ['Aceptados', operations.accepted],
                ['En preparación', operations.preparing],
                ['Listos', operations.ready],
                ['Despachados', operations.dispatched],
                ['Rechazados', operations.rejected],
            ]" :key="item[0]" class="col-xl-2 col-md-4">
                <div class="widget-stat card">
                    <div class="card-body p-3">
                        <p class="mb-1 small">{{ item[0] }}</p>
                        <h4 class="mb-0">{{ num(item[1]) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <h5 class="mt-2 mb-3">Oferta · Clientes · Finanzas · Logística</h5>
        <div class="row">
            <div class="col-xl-3">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Oferta</h5></div>
                    <div class="card-body">
                        <p class="mb-1">Fincas activas: <strong>{{ num(offer.active_farms) }}</strong></p>
                        <p class="mb-1">Productos activos: <strong>{{ num(offer.active_products) }}</strong></p>
                        <p class="mb-1">Tallos semana: <strong>{{ num(offer.stems_available_week) }}</strong></p>
                        <p class="mb-1">Reservados: <strong>{{ num(offer.stems_reserved) }}</strong></p>
                        <p class="mb-0">Efectivos: <strong>{{ num(offer.stems_effective) }}</strong></p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Clientes</h5></div>
                    <div class="card-body">
                        <p class="mb-1">Activos: <strong>{{ num(clients.active_buyers) }}</strong></p>
                        <p class="mb-1">Nuevos (mes): <strong>{{ num(clients.new_buyers) }}</strong></p>
                        <p class="mb-0">Top: <strong>{{ clients.top_buyer || '—' }}</strong>
                            <span v-if="clients.top_buyer_sales != null">({{ money(clients.top_buyer_sales) }})</span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Finanzas (→ fincas)</h5></div>
                    <div class="card-body">
                        <p class="mb-1">Por pagar: <strong>{{ money(finance.total_receivable) }}</strong></p>
                        <p class="mb-1">Pagado: <strong>{{ money(finance.total_paid) }}</strong></p>
                        <p class="mb-1">Saldo: <strong>{{ money(finance.pending_balance) }}</strong></p>
                        <p class="mb-1">Vencido: <strong>{{ money(finance.overdue_payments) }}</strong></p>
                        <p class="mb-0">Pedidos crédito/contado: <strong>{{ num(finance.credit_orders) }}</strong> / <strong>{{ num(finance.cash_orders) }}</strong></p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Logística</h5></div>
                    <div class="card-body">
                        <p class="mb-1">Aéreo: <strong>{{ num(logistics.air_orders) }}</strong></p>
                        <p class="mb-1">Marítimo: <strong>{{ num(logistics.sea_orders) }}</strong></p>
                        <p class="mb-1">Top agencia: <strong>{{ logistics.top_cargo_agency || '—' }}</strong></p>
                        <p class="mb-0">Top destino: <strong>{{ logistics.top_destination_country || '—' }}</strong></p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Usuarios / accesos</h5></div>
                    <div class="card-body">
                        <p class="mb-1">Admins activos: <strong>{{ num(users.active_admins) }}</strong></p>
                        <p class="mb-1">Usuarios finca: <strong>{{ num(users.active_farm_users) }}</strong></p>
                        <p class="mb-1">Usuarios comprador: <strong>{{ num(users.active_buyer_users) }}</strong></p>
                        <p class="mb-0">Inactivos: <strong>{{ num(users.inactive_users) }}</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
