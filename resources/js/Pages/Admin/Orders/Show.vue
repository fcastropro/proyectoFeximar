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

const shippingMethodLabel = (method) => ({
    air: 'Aéreo',
    sea: 'Marítimo',
}[method] ?? (method || '—'))

const paymentLabel = (condition) => ({
    cash: 'Contado',
    credit: 'Crédito',
}[condition] ?? (condition || '—'))
</script>

<template>
    <Head :title="`Pedido #${order.id}`" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Pedido #{{ order.id }}</h4>
                    <span>Detalle operativo, financiero y de trazabilidad</span>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex flex-wrap gap-2">
                <a :href="route('admin.reports.orders.pdf', order.id)" class="btn btn-success">
                    Descargar pedido PDF
                </a>
                <Link :href="route('admin.orders.edit', order.id)" class="btn btn-primary">
                    Editar
                </Link>
                <Link :href="route('admin.orders.index')" class="btn btn-light">
                    Volver
                </Link>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Resumen</h4>
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
                            <div class="col-md-6 mb-3">
                                <strong>Pago comprador</strong>
                                <div>
                                    {{ paymentLabel(order.payment_condition) }}
                                    <span v-if="order.payment_condition === 'credit' && order.credit_days">
                                        ({{ order.credit_days }} días)
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
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
                        <p class="mb-0"><strong>Marcación:</strong></p>
                        <pre class="mb-0 mt-1 small text-muted" style="white-space: pre-wrap; font-family: inherit;">{{ order.shipping?.marking || '—' }}</pre>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Finanzas (liquidación FEXIMAR → finca)</h4>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            Total liquidaciones: <strong>{{ Number(order.finance?.farm_total || 0).toFixed(2) }}</strong>
                            · Pagado: <strong>{{ Number(order.finance?.farm_paid || 0).toFixed(2) }}</strong>
                            · Saldo: <strong>{{ Number(order.finance?.farm_balance || 0).toFixed(2) }}</strong>
                        </p>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Finca</th>
                                        <th>Condición</th>
                                        <th>Vence</th>
                                        <th>Monto</th>
                                        <th>Pagado</th>
                                        <th>Saldo</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!order.finance?.lines?.length">
                                        <td colspan="7" class="text-muted text-center">Sin liquidaciones registradas</td>
                                    </tr>
                                    <tr v-for="(line, idx) in order.finance?.lines || []" :key="idx">
                                        <td>{{ line.farm_name }}</td>
                                        <td>{{ paymentLabel(line.payment_condition) }}</td>
                                        <td>{{ line.due_date || '—' }}</td>
                                        <td>{{ Number(line.amount).toFixed(2) }}</td>
                                        <td>{{ Number(line.paid).toFixed(2) }}</td>
                                        <td>{{ Number(line.balance).toFixed(2) }}</td>
                                        <td>{{ line.status }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" v-if="order.fulfillments?.length">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Timeline / fulfillments por finca</h4>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Finca</th>
                                    <th>Estado</th>
                                    <th>Recibido</th>
                                    <th>Aceptado</th>
                                    <th>Rechazado</th>
                                    <th>Preparación</th>
                                    <th>Listo</th>
                                    <th>Despachado</th>
                                    <th>Completado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in order.fulfillments" :key="item.id">
                                    <td>{{ item.farm_name }}</td>
                                    <td>{{ item.status }}</td>
                                    <td>{{ item.received_at || '—' }}</td>
                                    <td>{{ item.accepted_at || '—' }}</td>
                                    <td>
                                        {{ item.rejected_at || '—' }}
                                        <small v-if="item.rejection_reason" class="d-block text-muted">{{ item.rejection_reason }}</small>
                                    </td>
                                    <td>{{ item.prepared_at || '—' }}</td>
                                    <td>{{ item.ready_at || '—' }}</td>
                                    <td>{{ item.dispatched_at || '—' }}</td>
                                    <td>{{ item.completed_at || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Flores / líneas del pedido</h4>
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
                                        <th><strong>Bunches</strong></th>
                                        <th><strong>Tallos/bunch</strong></th>
                                        <th><strong>Total tallos</strong></th>
                                        <th><strong>Tipo de caja</strong></th>
                                        <th><strong>Cajas estimadas</strong></th>
                                        <th><strong>Precio/tallo</strong></th>
                                        <th><strong>Subtotal</strong></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!order.details?.length">
                                        <td colspan="12" class="text-center text-muted">
                                            Sin líneas.
                                        </td>
                                    </tr>
                                    <tr v-for="detail in order.details" :key="detail.id">
                                        <td>{{ detail.week_label }}</td>
                                        <td>{{ detail.farm_name || '—' }}</td>
                                        <td>{{ detail.product_name || '—' }}</td>
                                        <td>{{ detail.variety_name || '—' }}</td>
                                        <td>{{ detail.stem_length_cm ? `${detail.stem_length_cm} cm` : '—' }}</td>
                                        <td>{{ detail.bunches ?? '—' }}</td>
                                        <td>{{ detail.stems_per_bunch ?? '—' }}</td>
                                        <td>{{ detail.total_stems ?? '—' }}</td>
                                        <td>{{ detail.box_type || '—' }}</td>
                                        <td>{{ detail.quantity ?? '—' }}</td>
                                        <td>{{ detail.price_per_stem != null ? Number(detail.price_per_stem).toFixed(4) : Number(detail.unit_price).toFixed(2) }}</td>
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
