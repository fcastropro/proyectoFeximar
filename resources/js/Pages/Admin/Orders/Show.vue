<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
    statusLabels: {
        type: Object,
        default: () => ({}),
    },
})

const statusLabel = (status) => props.statusLabels[status] ?? status
</script>

<template>
    <Head :title="`Pedido #${order.id}`" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Pedido #{{ order.id }}</h4>
                    <span>Detalle de cabecera y líneas</span>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <Link :href="route('admin.orders.edit', order.id)" class="btn btn-primary me-2">
                    Editar
                </Link>
                <Link :href="route('admin.orders.index')" class="btn btn-light">
                    Volver
                </Link>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Información del pedido</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>Comprador</strong>
                                <div>{{ order.buyer_name || '—' }}</div>
                                <small class="text-muted">
                                    {{ order.buyer_contact || '—' }} · {{ order.buyer_email || '—' }}
                                </small>
                            </div>
                            <div class="col-md-3 mb-3">
                                <strong>Fecha</strong>
                                <div>{{ order.created_at || '—' }}</div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <strong>Estado</strong>
                                <div>
                                    <span class="badge badge-primary light">
                                        {{ statusLabel(order.status) }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-9 mb-3">
                                <strong>Notas</strong>
                                <div>{{ order.notes || '—' }}</div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <strong>Total</strong>
                                <div class="h4 mb-0">{{ Number(order.total).toFixed(2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Líneas del pedido</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-responsive-md">
                                <thead>
                                    <tr>
                                        <th><strong>Semana</strong></th>
                                        <th><strong>Finca</strong></th>
                                        <th><strong>Producto</strong></th>
                                        <th><strong>Variedad</strong></th>
                                        <th><strong>Longitud</strong></th>
                                        <th><strong>Tipo de caja</strong></th>
                                        <th><strong>Cantidad</strong></th>
                                        <th><strong>Tallos/caja</strong></th>
                                        <th><strong>Total tallos</strong></th>
                                        <th><strong>Precio unitario</strong></th>
                                        <th><strong>Subtotal</strong></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!order.details?.length">
                                        <td colspan="11" class="text-center text-muted">
                                            Sin líneas.
                                        </td>
                                    </tr>
                                    <tr v-for="detail in order.details" :key="detail.id">
                                        <td>{{ detail.week_label }}</td>
                                        <td>{{ detail.farm_name || '—' }}</td>
                                        <td>{{ detail.product_name || '—' }}</td>
                                        <td>{{ detail.variety_name || '—' }}</td>
                                        <td>{{ detail.stem_length_cm ? `${detail.stem_length_cm} cm` : '—' }}</td>
                                        <td>{{ detail.box_type || '—' }}</td>
                                        <td>{{ detail.quantity }}</td>
                                        <td>{{ detail.stems_per_box ?? '—' }}</td>
                                        <td>{{ detail.total_stems ?? '—' }}</td>
                                        <td>{{ Number(detail.unit_price).toFixed(2) }}</td>
                                        <td>{{ Number(detail.subtotal).toFixed(2) }}</td>
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
