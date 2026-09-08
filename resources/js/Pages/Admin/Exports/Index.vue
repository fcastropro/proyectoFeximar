<script setup>
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const exports = [
    { label: 'Fincas', route: 'admin.exports.farms' },
    { label: 'Compradores', route: 'admin.exports.buyers' },
    { label: 'Pedidos', route: 'admin.exports.orders' },
    { label: 'Disponibilidades', route: 'admin.exports.availabilities' },
    { label: 'Productos', route: 'admin.exports.products' },
    { label: 'Ventas', route: 'admin.exports.sales' },
    { label: 'Pagos a fincas', route: 'admin.exports.payments' },
]

const reports = [
    { label: 'PDF fincas', route: 'admin.reports.farms.pdf' },
    { label: 'PDF compradores', route: 'admin.reports.buyers.pdf' },
    { label: 'PDF oferta semanal', route: 'admin.reports.availability.pdf' },
    { label: 'PDF cuentas por pagar fincas', route: 'admin.reports.farm-payables.pdf' },
    { label: 'PDF ventas compradores', route: 'admin.reports.buyer-sales.pdf' },
]
</script>

<template>
    <Head title="Exportaciones y reportes" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-12 p-md-0">
                <div class="welcome-text">
                    <h4>Exportaciones y reportes</h4>
                    <span>CSV y PDF administrativos FEXIMAR</span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">CSV</h4></div>
                    <div class="card-body d-flex flex-wrap gap-2">
                        <a
                            v-for="item in exports"
                            :key="item.route"
                            :href="route(item.route)"
                            class="btn btn-outline-primary btn-sm"
                        >
                            {{ item.label }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">PDF</h4></div>
                    <div class="card-body d-flex flex-wrap gap-2">
                        <a
                            v-for="item in reports"
                            :key="item.route"
                            :href="route(item.route)"
                            class="btn btn-outline-success btn-sm"
                        >
                            {{ item.label }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
