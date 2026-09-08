<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import FarmLayout from '@/Layouts/FarmLayout.vue'

const props = defineProps({
    fulfillment: { type: Object, required: true },
    statusLabels: { type: Object, default: () => ({}) },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)
const rejectionReason = ref('')
const label = (status) => props.statusLabels[status] ?? status

const transition = (status) => {
    const payload = { status }
    if (status === 'rejected') {
        payload.rejection_reason = rejectionReason.value
    }
    router.post(route('farm.orders.transition', props.fulfillment.id), payload)
}

const actionLabel = {
    accepted: 'Aceptar',
    rejected: 'Rechazar',
    preparing: 'Marcar en preparación',
    ready: 'Marcar listo',
    dispatched: 'Marcar despachado',
    completed: 'Completar',
    cancelled: 'Cancelar',
}
</script>

<template>
    <Head :title="`Pedido #${fulfillment.order_id}`" />

    <FarmLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Pedido #{{ fulfillment.order_id }}</h4>
                    <span>Estado: {{ label(fulfillment.status) }}</span>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <Link :href="route('farm.orders.index')" class="btn btn-light">
                    Volver
                </Link>
            </div>
        </div>

        <div v-if="successMessage" class="alert alert-success alert-dismissible fade show">
            {{ successMessage }}
        </div>
        <div v-if="page.props.errors?.status" class="alert alert-danger">
            {{ page.props.errors.status }}
        </div>
        <div v-if="page.props.errors?.rejection_reason" class="alert alert-danger">
            {{ page.props.errors.rejection_reason }}
        </div>

        <div class="row">
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Resumen</h4>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Comprador:</strong> {{ fulfillment.buyer_name }}</p>
                        <p class="mb-2"><strong>Contacto:</strong> {{ fulfillment.buyer_contact || '—' }}</p>
                        <p class="mb-2"><strong>Fecha:</strong> {{ fulfillment.order_date }}</p>
                        <p class="mb-2"><strong>Estado:</strong> {{ label(fulfillment.status) }}</p>
                        <p v-if="fulfillment.rejection_reason" class="mb-0">
                            <strong>Motivo rechazo:</strong> {{ fulfillment.rejection_reason }}
                        </p>
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
                            <strong>Agencia:</strong> {{ fulfillment.shipping?.cargo_agency || '—' }}
                        </p>
                        <p class="mb-2">
                            <strong>Método:</strong>
                            {{
                                fulfillment.shipping?.shipping_method === 'air'
                                    ? 'Aéreo'
                                    : fulfillment.shipping?.shipping_method === 'sea'
                                        ? 'Marítimo'
                                        : (fulfillment.shipping?.shipping_method || '—')
                            }}
                        </p>
                        <p class="mb-2">
                            <strong>País:</strong> {{ fulfillment.shipping?.destination_country || '—' }}
                        </p>
                        <p class="mb-2">
                            <strong>Ciudad:</strong> {{ fulfillment.shipping?.destination_city || '—' }}
                        </p>
                        <p v-if="fulfillment.shipping?.shipping_method === 'air'" class="mb-2">
                            <strong>Aeropuerto:</strong>
                            {{ fulfillment.shipping?.destination_airport || '—' }}
                        </p>
                        <p v-if="fulfillment.shipping?.shipping_method === 'sea'" class="mb-2">
                            <strong>Puerto:</strong> {{ fulfillment.shipping?.destination_port || '—' }}
                        </p>
                        <p class="mb-2">
                            <strong>Pago:</strong>
                            {{
                                fulfillment.shipping?.payment_condition === 'credit'
                                    ? `Crédito${fulfillment.shipping?.credit_days ? ` (${fulfillment.shipping.credit_days} días)` : ''}`
                                    : fulfillment.shipping?.payment_condition === 'cash'
                                        ? 'Contado'
                                        : (fulfillment.shipping?.payment_condition || '—')
                            }}
                        </p>
                        <p class="mb-0"><strong>Marcación:</strong></p>
                        <pre class="mb-0 mt-1 small text-muted" style="white-space: pre-wrap; font-family: inherit;">{{ fulfillment.shipping?.marking || '—' }}</pre>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Línea de tiempo</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li
                                v-for="step in fulfillment.timeline"
                                :key="step.key"
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

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Productos de tu finca</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-responsive-md">
                                <thead>
                                    <tr>
                                        <th><strong>Semana</strong></th>
                                        <th><strong>Producto</strong></th>
                                        <th><strong>Variedad</strong></th>
                                        <th><strong>Longitud</strong></th>
                                        <th><strong>Bunches</strong></th>
                                        <th><strong>Tallos/bunch</strong></th>
                                        <th><strong>Total tallos</strong></th>
                                        <th><strong>Tipo caja</strong></th>
                                        <th><strong>Cajas estimadas</strong></th>
                                        <th><strong>Precio</strong></th>
                                        <th><strong>Subtotal</strong></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="line in fulfillment.lines" :key="line.id">
                                        <td>{{ line.week_label }}</td>
                                        <td>{{ line.product_name }}</td>
                                        <td>{{ line.variety_name }}</td>
                                        <td>{{ line.stem_length_cm }} cm</td>
                                        <td>{{ line.bunches }}</td>
                                        <td>{{ line.stems_per_bunch }}</td>
                                        <td>{{ line.total_stems }}</td>
                                        <td>{{ line.box_type || '—' }}</td>
                                        <td>{{ line.boxes }}</td>
                                        <td>{{ Number(line.unit_price).toFixed(2) }}</td>
                                        <td>{{ Number(line.subtotal).toFixed(2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="mt-3 mb-0">
                            <strong>Subtotal finca:</strong> {{ Number(fulfillment.subtotal).toFixed(2) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Acciones</h4>
                    </div>
                    <div class="card-body">
                        <div
                            v-if="fulfillment.allowed_transitions.includes('rejected')"
                            class="mb-3"
                        >
                            <label class="form-label">Motivo de rechazo (si aplica)</label>
                            <textarea v-model="rejectionReason" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button
                                v-for="status in fulfillment.allowed_transitions"
                                :key="status"
                                type="button"
                                class="btn"
                                :class="status === 'rejected' || status === 'cancelled' ? 'btn-danger' : 'btn-primary'"
                                @click="transition(status)"
                            >
                                {{ actionLabel[status] || status }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </FarmLayout>
</template>
