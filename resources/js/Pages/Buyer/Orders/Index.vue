<script setup>
import { Head, Link } from '@inertiajs/vue3'
import BuyerLayout from '@/Layouts/BuyerLayout.vue'

defineProps({
    orders: { type: Array, default: () => [] },
})

const statusLabel = (status) => ({
    pending: 'Pendiente',
    confirmed: 'Confirmado',
    processing: 'En proceso',
    shipped: 'Enviado',
    cancelled: 'Cancelado',
    accepted: 'Aceptado',
    rejected: 'Rechazado',
    preparing: 'En preparación',
    ready: 'Listo',
    dispatched: 'Despachado',
    completed: 'Completado',
}[status] ?? status)

const paymentLabel = (condition) => ({
    cash: 'Contado',
    credit: 'Crédito',
}[condition] ?? (condition || '—'))
</script>

<template>
    <Head title="Mis pedidos" />

    <BuyerLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Mis pedidos</h4>
                    <span>Historial de compras confirmadas</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Listado</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-responsive-md">
                        <thead>
                            <tr>
                                <th><strong>Pedido</strong></th>
                                <th><strong>Fecha</strong></th>
                                <th><strong>Total</strong></th>
                                <th><strong>Pago</strong></th>
                                <th><strong>Estado</strong></th>
                                <th><strong>Fincas</strong></th>
                                <th class="admin-actions-column"><strong>Acciones</strong></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="orders.length === 0">
                                <td colspan="7" class="text-center text-muted">
                                    Aún no tienes pedidos.
                                </td>
                            </tr>
                            <tr v-for="order in orders" :key="order.id">
                                <td>#{{ order.id }}</td>
                                <td>{{ order.created_at || '—' }}</td>
                                <td>{{ Number(order.total).toFixed(2) }}</td>
                                <td>
                                    {{ paymentLabel(order.payment_condition) }}
                                    <span v-if="order.payment_condition === 'credit' && order.credit_days">
                                        ({{ order.credit_days }} días)
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-primary light">
                                        {{ statusLabel(order.status) }}
                                    </span>
                                </td>
                                <td>
                                    <div
                                        v-for="(farm, idx) in order.farms"
                                        :key="`${order.id}-${idx}`"
                                        class="small"
                                    >
                                        {{ farm.farm_name || '—' }}:
                                        {{ statusLabel(farm.status) }}
                                    </div>
                                    <span v-if="!order.farms?.length">—</span>
                                </td>
                                <td class="admin-actions-column">
                                    <Link
                                        :href="route('buyer.orders.show', order.id)"
                                        class="btn btn-sm btn-info"
                                    >
                                        Ver
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </BuyerLayout>
</template>
