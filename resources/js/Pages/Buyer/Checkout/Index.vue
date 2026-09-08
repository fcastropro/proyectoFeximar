<script setup>
import { computed, watch } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import BuyerLayout from '@/Layouts/BuyerLayout.vue'

const props = defineProps({
    items: { type: Array, default: () => [] },
    total: { type: [Number, String], default: 0 },
    paymentOptions: { type: Object, required: true },
    cargoAgencies: { type: Array, default: () => [] },
    countries: { type: Array, default: () => [] },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)
const errorMessage = computed(() => page.props.flash?.error ?? null)

const defaultBoxTypeId = (item) => item.box_options?.[0]?.id ?? ''

const form = useForm({
    payment_condition: 'cash',
    credit_days: props.paymentOptions.credit_days_default || 1,
    cargo_agency_id: props.cargoAgencies[0]?.id ?? '',
    shipping_method: 'air',
    destination_country_id: '',
    destination_city: '',
    destination_airport: '',
    destination_port: '',
    marking: '',
    packaging: props.items.map((item) => ({
        cart_item_id: item.id,
        box_type_id: defaultBoxTypeId(item),
    })),
})

const creditDaysOptions = computed(() => {
    const max = Number(props.paymentOptions.credit_days_default || 0)
    if (max < 1) return []
    return Array.from({ length: max }, (_, i) => i + 1)
})

watch(
    () => form.payment_condition,
    (value) => {
        if (value !== 'credit') {
            form.credit_days = null
        } else if (!form.credit_days) {
            form.credit_days = props.paymentOptions.credit_days_default || 1
        }
    },
)

watch(
    () => form.shipping_method,
    (method) => {
        if (method === 'air') {
            form.destination_port = ''
        } else {
            form.destination_airport = ''
        }
    },
)

const packagingFor = (itemId) => {
    let row = form.packaging.find((p) => Number(p.cart_item_id) === Number(itemId))
    if (!row) {
        const item = props.items.find((i) => Number(i.id) === Number(itemId))
        row = {
            cart_item_id: itemId,
            box_type_id: item ? defaultBoxTypeId(item) : '',
        }
        form.packaging.push(row)
    }
    return row
}

const selectedBoxOption = (item) => {
    const selectedId = packagingFor(item.id).box_type_id
    return (item.box_options ?? []).find((b) => Number(b.id) === Number(selectedId)) ?? null
}

const shippingMethodLabel = (method) => ({
    air: 'Aéreo',
    sea: 'Marítimo',
}[method] ?? method)

const submit = () => {
    if (form.payment_condition !== 'credit') {
        form.credit_days = null
    }
    if (form.shipping_method === 'air') {
        form.destination_port = null
    } else {
        form.destination_airport = null
    }
    form.post(route('buyer.checkout.store'))
}
</script>

<template>
    <Head title="Checkout" />

    <BuyerLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Checkout</h4>
                    <span>Empaque, logística, marcación y pago</span>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <Link :href="route('buyer.cart.index')" class="btn btn-light">
                    Volver al carrito
                </Link>
            </div>
        </div>

        <div v-if="successMessage" class="alert alert-success alert-dismissible fade show">
            {{ successMessage }}
        </div>
        <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show">
            {{ errorMessage }}
        </div>
        <div
            v-for="(msg, key) in form.errors"
            :key="key"
            class="alert alert-danger"
        >
            {{ msg }}
        </div>

        <form @submit.prevent="submit">
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="card-title">A. Resumen flores</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-responsive-md">
                            <thead>
                                <tr>
                                    <th><strong>Producto</strong></th>
                                    <th><strong>Variedad</strong></th>
                                    <th><strong>Finca</strong></th>
                                    <th><strong>Longitud</strong></th>
                                    <th><strong>Bunches</strong></th>
                                    <th><strong>Tallos/bunch</strong></th>
                                    <th><strong>Total tallos</strong></th>
                                    <th><strong>Precio/tallo</strong></th>
                                    <th><strong>Subtotal</strong></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items" :key="item.id">
                                    <td>{{ item.product_name }}</td>
                                    <td>{{ item.variety || '—' }}</td>
                                    <td>{{ item.farm_name || '—' }}</td>
                                    <td>{{ item.stem_length_cm }} cm</td>
                                    <td>{{ item.bunches }}</td>
                                    <td>{{ item.stems_per_bunch }}</td>
                                    <td>{{ item.total_stems }}</td>
                                    <td>{{ Number(item.price_per_stem).toFixed(4) }}</td>
                                    <td>{{ Number(item.subtotal).toFixed(2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="card-title">B. Empaque</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-responsive-md">
                            <thead>
                                <tr>
                                    <th><strong>Producto</strong></th>
                                    <th><strong>Tallos</strong></th>
                                    <th><strong>Tipo de caja</strong></th>
                                    <th><strong>Tallos/caja</strong></th>
                                    <th><strong>Bunches/caja</strong></th>
                                    <th><strong>Cajas estimadas</strong></th>
                                    <th><strong>Parcial</strong></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items" :key="`pack-${item.id}`">
                                    <td>
                                        {{ item.product_name }}
                                        <div class="small text-muted">{{ item.variety || '—' }}</div>
                                    </td>
                                    <td>{{ item.total_stems }}</td>
                                    <td style="min-width: 180px;">
                                        <select
                                            v-model="packagingFor(item.id).box_type_id"
                                            class="form-control"
                                            required
                                        >
                                            <option value="" disabled>Seleccione</option>
                                            <option
                                                v-for="box in item.box_options"
                                                :key="box.id"
                                                :value="box.id"
                                            >
                                                {{ box.code || box.name }}
                                            </option>
                                        </select>
                                    </td>
                                    <td>{{ selectedBoxOption(item)?.stems_per_box ?? '—' }}</td>
                                    <td>{{ selectedBoxOption(item)?.bunches_per_box ?? '—' }}</td>
                                    <td>{{ selectedBoxOption(item)?.estimated_boxes ?? '—' }}</td>
                                    <td>
                                        <span v-if="selectedBoxOption(item)?.partial" class="badge badge-warning light">
                                            Sí
                                        </span>
                                        <span v-else class="text-muted">No</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="card-title">C. Logística</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="mb-3 col-md-4">
                            <label class="form-label">Agencia de carga *</label>
                            <select v-model="form.cargo_agency_id" class="form-control" required>
                                <option value="" disabled>Seleccione</option>
                                <option
                                    v-for="agency in cargoAgencies"
                                    :key="agency.id"
                                    :value="agency.id"
                                >
                                    {{ agency.name }}
                                    <template v-if="agency.code"> ({{ agency.code }})</template>
                                </option>
                            </select>
                        </div>
                        <div class="mb-3 col-md-4">
                            <label class="form-label">Método de envío *</label>
                            <select v-model="form.shipping_method" class="form-control" required>
                                <option value="air">{{ shippingMethodLabel('air') }}</option>
                                <option value="sea">{{ shippingMethodLabel('sea') }}</option>
                            </select>
                        </div>
                        <div class="mb-3 col-md-4">
                            <label class="form-label">País destino *</label>
                            <select v-model="form.destination_country_id" class="form-control" required>
                                <option value="" disabled>Seleccione</option>
                                <option
                                    v-for="country in countries"
                                    :key="country.id"
                                    :value="country.id"
                                >
                                    {{ country.name }}
                                </option>
                            </select>
                        </div>
                        <div class="mb-3 col-md-4">
                            <label class="form-label">Ciudad destino</label>
                            <input v-model="form.destination_city" type="text" class="form-control">
                        </div>
                        <div v-if="form.shipping_method === 'air'" class="mb-3 col-md-4">
                            <label class="form-label">Aeropuerto destino</label>
                            <input v-model="form.destination_airport" type="text" class="form-control">
                        </div>
                        <div v-if="form.shipping_method === 'sea'" class="mb-3 col-md-4">
                            <label class="form-label">Puerto destino</label>
                            <input v-model="form.destination_port" type="text" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="card-title">D. Marcación</h4>
                </div>
                <div class="card-body">
                    <label class="form-label">Marcación / instrucciones de etiqueta</label>
                    <textarea
                        v-model="form.marking"
                        class="form-control"
                        rows="4"
                        placeholder="Ej. nombre del cliente, PO, instrucciones especiales"
                    ></textarea>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="card-title">E. Pago</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="mb-3 col-md-4">
                            <label class="form-label">Condición de pago *</label>
                            <select v-model="form.payment_condition" class="form-control">
                                <option value="cash">Contado</option>
                                <option
                                    v-if="paymentOptions.credit_allowed"
                                    value="credit"
                                >
                                    Crédito
                                </option>
                            </select>
                        </div>
                        <div
                            v-if="form.payment_condition === 'credit'"
                            class="mb-3 col-md-4"
                        >
                            <label class="form-label">Días de crédito</label>
                            <select v-model="form.credit_days" class="form-control">
                                <option
                                    v-for="day in creditDaysOptions"
                                    :key="day"
                                    :value="day"
                                >
                                    {{ day }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="card-title">F. Total</h4>
                </div>
                <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <p class="mb-0 h4">
                        Total: {{ Number(total).toFixed(2) }} USD
                    </p>
                    <button
                        type="submit"
                        class="btn btn-primary"
                        :disabled="form.processing"
                    >
                        Confirmar pedido
                    </button>
                </div>
            </div>
        </form>
    </BuyerLayout>
</template>
