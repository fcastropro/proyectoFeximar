<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
    buyers: {
        type: Array,
        default: () => [],
    },
    availabilities: {
        type: Array,
        default: () => [],
    },
    statuses: {
        type: Array,
        default: () => [],
    },
})

const emptyDetail = () => ({
    farm_product_availability_id: '',
    box_type_id: '',
    quantity: 1,
    unit_price: '',
})

const form = useForm({
    buyer_id: props.order.buyer_id ?? '',
    status: props.order.status ?? 'pending',
    notes: props.order.notes ?? '',
    details: (props.order.details?.length ? props.order.details : [emptyDetail()]).map((detail) => ({
        farm_product_availability_id: detail.farm_product_availability_id ?? '',
        box_type_id: detail.box_type_id ?? '',
        quantity: detail.quantity ?? 1,
        unit_price: detail.unit_price ?? '',
    })),
})

const availabilityById = (id) =>
    props.availabilities.find((item) => item.id === Number(id))

const boxConfigsForDetail = (detail) =>
    availabilityById(detail.farm_product_availability_id)?.box_configs ?? []

const boxConfigForDetail = (detail) =>
    boxConfigsForDetail(detail).find(
        (config) => Number(config.box_type_id) === Number(detail.box_type_id),
    ) ?? null

const lineStems = (detail) => {
    const config = boxConfigForDetail(detail)
    if (!config) {
        return 0
    }

    return (Number(detail.quantity) || 0) * Number(config.stems_per_box || 0)
}

const stemsRequestedForAvailability = (availabilityId) =>
    form.details.reduce((sum, detail) => {
        if (Number(detail.farm_product_availability_id) !== Number(availabilityId)) {
            return sum
        }

        return sum + lineStems(detail)
    }, 0)

const lineSummary = (detail) => {
    const availability = availabilityById(detail.farm_product_availability_id)
    const config = boxConfigForDetail(detail)

    if (!availability || !config) {
        return null
    }

    const requestedLine = lineStems(detail)
    const requestedTotal = stemsRequestedForAvailability(detail.farm_product_availability_id)
    const available = Number(availability.available_stems) || 0

    return {
        stemsPerBox: config.stems_per_box,
        bunchesPerBox: config.bunches_per_box,
        quantity: Number(detail.quantity) || 0,
        requestedLine,
        available,
        requestedTotal,
        remaining: available - requestedTotal,
        exceeds: requestedTotal > available,
    }
}

const lineSubtotal = (detail) => {
    const quantity = Number(detail.quantity) || 0
    const unitPrice = Number(detail.unit_price) || 0
    return quantity * unitPrice
}

const orderTotal = computed(() =>
    form.details.reduce((sum, detail) => sum + lineSubtotal(detail), 0),
)

const hasStemOverages = computed(() =>
    form.details.some((detail) => lineSummary(detail)?.exceeds),
)

const onAvailabilityChange = (index) => {
    const detail = form.details[index]
    const availability = availabilityById(detail.farm_product_availability_id)

    detail.box_type_id = ''

    if (availability?.reference_price != null && availability.reference_price !== '') {
        detail.unit_price = Number(availability.reference_price)
    }
}

const onBoxTypeChange = (index) => {
    const detail = form.details[index]
    const configs = boxConfigsForDetail(detail)
    const stillValid = configs.some(
        (config) => Number(config.box_type_id) === Number(detail.box_type_id),
    )

    if (!stillValid) {
        detail.box_type_id = ''
    }
}

const addDetail = () => {
    form.details.push(emptyDetail())
}

const removeDetail = (index) => {
    if (form.details.length === 1) {
        return
    }

    form.details.splice(index, 1)
}

const detailError = (index, field) => form.errors[`details.${index}.${field}`]

const submit = () => {
    if (hasStemOverages.value) {
        return
    }

    form.put(route('admin.orders.update', props.order.id))
}
</script>

<template>
    <Head title="Editar Pedido" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Editar Pedido #{{ order.id }}</h4>
                    <span>Actualizar cabecera y líneas del pedido</span>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit">
            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Cabecera del pedido</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Comprador <span class="text-danger">*</span></label>
                                    <select
                                        v-model="form.buyer_id"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.buyer_id }"
                                    >
                                        <option value="">Seleccione un comprador</option>
                                        <option
                                            v-for="buyer in buyers"
                                            :key="buyer.id"
                                            :value="buyer.id"
                                        >
                                            {{ buyer.company_name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.buyer_id" class="invalid-feedback d-block">
                                        {{ form.errors.buyer_id }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Estado <span class="text-danger">*</span></label>
                                    <select
                                        v-model="form.status"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.status }"
                                    >
                                        <option
                                            v-for="status in statuses"
                                            :key="status.value"
                                            :value="status.value"
                                        >
                                            {{ status.label }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.status" class="invalid-feedback d-block">
                                        {{ form.errors.status }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-12">
                                    <label class="form-label">Notas</label>
                                    <textarea
                                        v-model="form.notes"
                                        rows="3"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.notes }"
                                    ></textarea>
                                    <div v-if="form.errors.notes" class="invalid-feedback d-block">
                                        {{ form.errors.notes }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header d-sm-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-2 mb-sm-0">Detalles del pedido</h4>
                            <button type="button" class="btn btn-sm btn-primary" @click="addDetail">
                                Agregar línea
                            </button>
                        </div>
                        <div class="card-body">
                            <div v-if="form.errors.details" class="alert alert-danger">
                                {{ form.errors.details }}
                            </div>

                            <div v-if="hasStemOverages" class="alert alert-warning">
                                Hay líneas que superan los tallos disponibles (sumando todas las líneas
                                de la misma disponibilidad). Corrige las cantidades antes de guardar.
                            </div>

                            <div
                                v-for="(detail, index) in form.details"
                                :key="index"
                                class="border rounded p-3 mb-3"
                            >
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">Línea {{ index + 1 }}</h5>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        :disabled="form.details.length === 1"
                                        @click="removeDetail(index)"
                                    >
                                        Quitar
                                    </button>
                                </div>

                                <div class="row">
                                    <div class="mb-3 col-md-12">
                                        <label class="form-label">Disponibilidad <span class="text-danger">*</span></label>
                                        <select
                                            v-model="detail.farm_product_availability_id"
                                            class="form-control"
                                            :class="{ 'is-invalid': detailError(index, 'farm_product_availability_id') }"
                                            @change="onAvailabilityChange(index)"
                                        >
                                            <option value="">Seleccione disponibilidad semanal</option>
                                            <option
                                                v-for="availability in availabilities"
                                                :key="availability.id"
                                                :value="availability.id"
                                            >
                                                {{ availability.label }}
                                            </option>
                                        </select>
                                        <div
                                            v-if="detailError(index, 'farm_product_availability_id')"
                                            class="invalid-feedback d-block"
                                        >
                                            {{ detailError(index, 'farm_product_availability_id') }}
                                        </div>
                                        <small
                                            v-if="availabilityById(detail.farm_product_availability_id)?.reference_price != null"
                                            class="text-muted"
                                        >
                                            Precio semanal de referencia:
                                            {{ Number(availabilityById(detail.farm_product_availability_id).reference_price).toFixed(2) }}
                                        </small>
                                    </div>

                                    <div class="mb-3 col-md-4">
                                        <label class="form-label">Tipo de caja <span class="text-danger">*</span></label>
                                        <select
                                            v-model="detail.box_type_id"
                                            class="form-control"
                                            :class="{ 'is-invalid': detailError(index, 'box_type_id') }"
                                            :disabled="!detail.farm_product_availability_id"
                                            @change="onBoxTypeChange(index)"
                                        >
                                            <option value="">
                                                {{
                                                    !detail.farm_product_availability_id
                                                        ? 'Seleccione disponibilidad primero'
                                                        : (boxConfigsForDetail(detail).length
                                                            ? 'Seleccione'
                                                            : 'Sin cajas configuradas')
                                                }}
                                            </option>
                                            <option
                                                v-for="boxConfig in boxConfigsForDetail(detail)"
                                                :key="boxConfig.box_type_id"
                                                :value="boxConfig.box_type_id"
                                            >
                                                {{ boxConfig.code }} — {{ boxConfig.name }}
                                                ({{ boxConfig.stems_per_box }} tallos/caja)
                                            </option>
                                        </select>
                                        <div
                                            v-if="detailError(index, 'box_type_id')"
                                            class="invalid-feedback d-block"
                                        >
                                            {{ detailError(index, 'box_type_id') }}
                                        </div>
                                    </div>

                                    <div class="mb-3 col-md-4">
                                        <label class="form-label">Cantidad de cajas <span class="text-danger">*</span></label>
                                        <input
                                            v-model.number="detail.quantity"
                                            type="number"
                                            min="1"
                                            step="1"
                                            class="form-control"
                                            :class="{ 'is-invalid': detailError(index, 'quantity') || lineSummary(detail)?.exceeds }"
                                        >
                                        <div
                                            v-if="detailError(index, 'quantity')"
                                            class="invalid-feedback d-block"
                                        >
                                            {{ detailError(index, 'quantity') }}
                                        </div>
                                    </div>

                                    <div class="mb-3 col-md-4">
                                        <label class="form-label">Precio unitario <span class="text-danger">*</span></label>
                                        <input
                                            v-model.number="detail.unit_price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="form-control"
                                            :class="{ 'is-invalid': detailError(index, 'unit_price') }"
                                        >
                                        <div
                                            v-if="detailError(index, 'unit_price')"
                                            class="invalid-feedback d-block"
                                        >
                                            {{ detailError(index, 'unit_price') }}
                                        </div>
                                    </div>

                                    <div
                                        v-if="lineSummary(detail)"
                                        class="mb-3 col-md-12"
                                    >
                                        <div class="bg-light rounded p-3">
                                            <div class="row">
                                                <div class="col-md-4 mb-2">
                                                    <strong>Tallos por caja:</strong>
                                                    {{ lineSummary(detail).stemsPerBox }}
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <strong>Bunches por caja:</strong>
                                                    {{ lineSummary(detail).bunchesPerBox ?? '—' }}
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <strong>Cantidad de cajas:</strong>
                                                    {{ lineSummary(detail).quantity }}
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <strong>Tallos solicitados (línea):</strong>
                                                    {{ lineSummary(detail).requestedLine }}
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <strong>Tallos disponibles (semana):</strong>
                                                    {{ lineSummary(detail).available }}
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <strong>Tallos solicitados (todas las líneas):</strong>
                                                    {{ lineSummary(detail).requestedTotal }}
                                                </div>
                                                <div class="col-md-4 mb-0">
                                                    <strong>Tallos restantes estimados:</strong>
                                                    <span :class="lineSummary(detail).exceeds ? 'text-danger' : 'text-success'">
                                                        {{ lineSummary(detail).remaining }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div
                                                v-if="lineSummary(detail).exceeds"
                                                class="text-danger mt-2"
                                            >
                                                Esta disponibilidad queda sobrepasada al sumar todas las líneas del pedido.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-0 col-md-12">
                                        <strong>Subtotal línea:</strong>
                                        {{ lineSubtotal(detail).toFixed(2) }}
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-3">
                                <h5 class="mb-0">Total pedido: {{ orderTotal.toFixed(2) }}</h5>
                                <small class="text-muted">El total definitivo se recalcula en el servidor.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <button
                    type="submit"
                    class="btn btn-primary me-2"
                    :disabled="form.processing || hasStemOverages"
                >
                    Actualizar pedido
                </button>
                <Link :href="route('admin.orders.index')" class="btn btn-light">
                    Cancelar
                </Link>
            </div>
        </form>
    </AdminLayout>
</template>
