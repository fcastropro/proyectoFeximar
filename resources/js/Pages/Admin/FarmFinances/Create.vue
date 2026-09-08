<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    orders: { type: Array, default: () => [] },
    selectedOrderId: { type: Number, default: null },
    farmOptions: { type: Array, default: () => [] },
    conditions: { type: Array, default: () => [] },
})

const farmOptionsLocal = ref([...props.farmOptions])

const form = useForm({
    order_id: props.selectedOrderId ?? '',
    farm_id: '',
    payment_condition: 'cash',
    credit_days: '',
})

watch(
    () => form.order_id,
    async (orderId) => {
        form.farm_id = ''
        if (!orderId) {
            farmOptionsLocal.value = []
            return
        }
        const response = await fetch(route('admin.farm-finances.order-options', orderId), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
        farmOptionsLocal.value = await response.json()
    },
)

const submit = () => form.post(route('admin.farm-finances.store'))
</script>

<template>
    <Head title="Nueva condición financiera" />
    <AdminLayout>
        <h4 class="mb-3">Registrar condición de pago</h4>
        <div class="card">
            <div class="card-body">
                <form @submit.prevent="submit">
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Pedido *</label>
                            <select v-model="form.order_id" class="form-control">
                                <option value="">Seleccione</option>
                                <option v-for="order in orders" :key="order.id" :value="order.id">
                                    #{{ order.id }} — {{ order.created_at }}
                                </option>
                            </select>
                            <div v-if="form.errors.order_id" class="text-danger">{{ form.errors.order_id }}</div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Finca *</label>
                            <select v-model="form.farm_id" class="form-control">
                                <option value="">Seleccione</option>
                                <option v-for="opt in farmOptionsLocal" :key="opt.farm_id" :value="opt.farm_id">
                                    {{ opt.farm_name }} ({{ Number(opt.amount).toFixed(2) }})
                                </option>
                            </select>
                            <div v-if="form.errors.farm_id" class="text-danger">{{ form.errors.farm_id }}</div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Condición *</label>
                            <select v-model="form.payment_condition" class="form-control">
                                <option value="cash">Contado</option>
                                <option value="credit">Crédito</option>
                            </select>
                        </div>
                        <div v-if="form.payment_condition === 'credit'" class="mb-3 col-md-6">
                            <label class="form-label">Días de crédito *</label>
                            <input v-model="form.credit_days" type="number" min="1" class="form-control">
                            <div v-if="form.errors.credit_days" class="text-danger">{{ form.errors.credit_days }}</div>
                        </div>
                    </div>
                    <p class="text-muted">El monto se calcula automáticamente con las líneas de esa finca.</p>
                    <button type="submit" class="btn btn-primary me-2" :disabled="form.processing">Guardar</button>
                    <Link :href="route('admin.farm-finances.index')" class="btn btn-light">Cancelar</Link>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
