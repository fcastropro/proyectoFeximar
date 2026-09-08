<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    finance: { type: Object, required: true },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)

const form = useForm({
    amount: '',
    payment_date: new Date().toISOString().slice(0, 10),
    payment_method: '',
    reference: '',
    notes: '',
})

const submit = () => form.post(route('admin.farm-finances.payments.store', props.finance.id), {
    onSuccess: () => form.reset('amount', 'payment_method', 'reference', 'notes'),
})
</script>

<template>
    <Head :title="`Finanza pedido #${finance.order_id}`" />
    <AdminLayout>
        <div class="d-flex justify-content-between mb-3">
            <h4 class="mb-0">Finanza · Pedido #{{ finance.order_id }} · {{ finance.farm_name }}</h4>
            <Link :href="route('admin.farm-finances.index')" class="btn btn-light">Volver</Link>
        </div>
        <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>

        <div class="card mb-3">
            <div class="card-body row">
                <div class="col-md-3"><strong>Monto:</strong> {{ Number(finance.amount).toFixed(2) }}</div>
                <div class="col-md-3"><strong>Condición:</strong> {{ finance.payment_condition }}</div>
                <div class="col-md-3"><strong>Vence:</strong> {{ finance.due_date || '—' }}</div>
                <div class="col-md-3"><strong>Estado:</strong> {{ finance.status }}</div>
                <div class="col-md-3"><strong>Pagado:</strong> {{ Number(finance.paid_amount).toFixed(2) }}</div>
                <div class="col-md-3"><strong>Saldo:</strong> {{ Number(finance.balance).toFixed(2) }}</div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Registrar abono</h5></div>
            <div class="card-body">
                <form class="row g-2" @submit.prevent="submit">
                    <div class="col-md-3">
                        <input v-model="form.amount" type="number" min="0.01" step="0.01" class="form-control" placeholder="Monto">
                    </div>
                    <div class="col-md-3">
                        <input v-model="form.payment_date" type="date" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <input v-model="form.payment_method" type="text" class="form-control" placeholder="Método">
                    </div>
                    <div class="col-md-2">
                        <input v-model="form.reference" type="text" class="form-control" placeholder="Referencia">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100" :disabled="form.processing">Guardar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">Historial de pagos</h5></div>
            <div class="card-body table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Monto</th>
                            <th>Método</th>
                            <th>Referencia</th>
                            <th>Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="payment in finance.payments" :key="payment.id">
                            <td>{{ payment.payment_date }}</td>
                            <td>{{ Number(payment.amount).toFixed(2) }}</td>
                            <td>{{ payment.payment_method || '—' }}</td>
                            <td>{{ payment.reference || '—' }}</td>
                            <td>{{ payment.notes || '—' }}</td>
                        </tr>
                        <tr v-if="!finance.payments?.length">
                            <td colspan="5" class="text-muted text-center">Sin pagos.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
