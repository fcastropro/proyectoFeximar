<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import BuyerLayout from '@/Layouts/BuyerLayout.vue'

const props = defineProps({
    order: { type: Object, required: true },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)

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

const shippingMethodLabel = (method) => ({
    air: 'Aéreo',
    sea: 'Marítimo',
}[method] ?? (method || '—'))

const fulfillmentTimeline = (fulfillment) => [
    { label: 'Aceptado', at: fulfillment.accepted_at },
    { label: 'Rechazado', at: fulfillment.rejected_at },
    { label: 'Listo', at: fulfillment.ready_at },
]
</script>

<template>
    <Head :title="`Pedido #${order.id}`" />

    <BuyerLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Pedido #{{ order.id }}</h4>
                    <span>{{ statusLabel(order.status) }} · {{ order.created_at }}</span>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex gap-2">
                <a :href="route('buyer.orders.pdf', order.id)" class="btn btn-success">
                    Descargar PDF
                </a>
                <Link :href="route('buyer.orders.index')" class="btn btn-light">
                    Volver
                </Link>
            </div>
        </div>

        <div v-if="successMessage" class="alert alert-success alert-dismissible fade show">
            {{ successMessage }}
        </div>

        <div class="row">
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Resumen</h4>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Fecha:</strong> {{ order.created_at || '—' }}</p>
                        <p class="mb-2"><strong>Estado:</strong> {{ statusLabel(order.status) }}</p>
                        <p class="mb-2">
                            <strong>Pago:</strong> {{ paymentLabel(order.payment_condition) }}
                            <span v-if="order.payment_condition === 'credit' && order.credit_days">
                                ({{ order.credit_days }} días)
                            </span>
                        </p>
                        <p class="mb-2"><strong>Total:</strong> {{ Number(order.total).toFixed(2) }} USD</p>
                        <p class="mb-0"><strong>Notas:</strong> {{ order.notes || '—' }}</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Envío y logística</h4>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <strong>Agencia:</strong> {{ order.shipping?.cargo_agency || '—' }}
                        </p>
                        <p class="mb-2">
                            <strong>Método:</strong>
                            {{ shippingMethodLabel(order.shipping?.shipping_method) }}
                        </p>
                        <p class="mb-2">
                            <strong>País:</strong> {{ order.shipping?.destination_country || '—' }}
                        </p>
                        <p class="mb-2">
                            <strong>Ciudad:</strong> {{ order.shipping?.destination_city || '—' }}
                        </p>
                        <p v-if="order.shipping?.shipping_method === 'air'" class="mb-2">
                            <strong>Aeropuerto:</strong> {{ order.shipping?.destination_airport || '—' }}
                        </p>
                        <p v-if="order.shipping?.shipping_method === 'sea'" class="mb-2">
                            <strong>Puerto:</strong> {{ order.shipping?.destination_port || '—' }}
                        </p>
                        <p class="mb-0">
                            <strong>Marcación:</strong>
                        </p>
                        <pre class="mb-0 mt-1 small text-muted buyer-marking">{{ order.shipping?.marking || '—' }}</pre>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Cumplimiento por finca</h4>
                    </div>
                    <div class="card-body">
                        <div v-if="!order.fulfillments?.length" class="text-muted">
                            Sin cumplimientos registrados.
                        </div>
                        <div
                            v-for="(fulfillment, idx) in order.fulfillments"
                            :key="idx"
                            class="mb-4"
                            :class="{ 'mb-0': idx === order.fulfillments.length - 1 }"
                        >
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>{{ fulfillment.farm_name || 'Finca' }}</strong>
                                <span class="badge badge-primary light">
                                    {{ statusLabel(fulfillment.status) }}
                                </span>
                            </div>
                            <ul class="list-group list-group-flush">
                                <li
                                    v-for="step in fulfillmentTimeline(fulfillment)"
                                    :key="step.label"
                                    class="list-group-item px-0 d-flex justify-content-between"
                                >
                                    <span>{{ step.label }}</span>
                                    <span :class="step.at ? 'text-success' : 'text-muted'">
                                        {{ step.at || 'Pendiente' }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Detalle de productos</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-responsive-md">
                        <thead>
                            <tr>
                                <th></th>
                                <th><strong>Producto</strong></th>
                                <th><strong>Finca</strong></th>
                                <th><strong>Longitud</strong></th>
                                <th><strong>Bunches</strong></th>
                                <th><strong>Tallos/bunch</strong></th>
                                <th><strong>Tipo caja</strong></th>
                                <th><strong>Cajas</strong></th>
                                <th><strong>Total tallos</strong></th>
                                <th><strong>Precio/tallo</strong></th>
                                <th><strong>Subtotal</strong></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(line, idx) in order.details" :key="idx">
                                <td style="width: 64px;">
                                    <img
                                        v-if="line.image_url"
                                        :src="line.image_url"
                                        alt=""
                                        class="rounded"
                                        style="width: 48px; height: 48px; object-fit: cover;"
                                    >
                                    <div v-else class="buyer-order-placeholder rounded">
                                        <i class="fa fa-leaf"></i>
                                    </div>
                                </td>
                                <td>
                                    {{ line.product_name }}
                                    <div class="small text-muted">{{ line.variety || '—' }}</div>
                                </td>
                                <td>{{ line.farm_name || '—' }}</td>
                                <td>{{ line.stem_length_cm }} cm</td>
                                <td>{{ line.bunches }}</td>
                                <td>{{ line.stems_per_bunch }}</td>
                                <td>{{ line.box_type || '—' }}</td>
                                <td>{{ line.boxes }}</td>
                                <td>{{ line.total_stems }}</td>
                                <td>{{ Number(line.price_per_stem).toFixed(4) }}</td>
                                <td>{{ Number(line.subtotal).toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </BuyerLayout>
</template>

<style scoped>
.buyer-order-placeholder {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(145deg, #1a1a22 0%, #2a2a35 55%, #1e1e28 100%);
    color: rgba(215, 25, 75, 0.85);
    font-size: 1rem;
}

.buyer-marking {
    white-space: pre-wrap;
    font-family: inherit;
}
</style>
