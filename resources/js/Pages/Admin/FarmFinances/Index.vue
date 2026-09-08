<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    finances: { type: Array, default: () => [] },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)
</script>

<template>
    <Head title="Finanzas por finca" />
    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Finanzas por finca</h4>
                    <span>Condiciones de pago y saldos</span>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <Link :href="route('admin.farm-finances.create')" class="btn btn-primary">Nueva condición</Link>
            </div>
        </div>
        <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>
        <div class="card">
            <div class="card-body table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Finca</th>
                            <th>Monto</th>
                            <th>Condición</th>
                            <th>Vence</th>
                            <th>Pagado</th>
                            <th>Saldo</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in finances" :key="item.id">
                            <td>#{{ item.order_id }}</td>
                            <td>{{ item.farm_name }}</td>
                            <td>{{ Number(item.amount).toFixed(2) }}</td>
                            <td>{{ item.payment_condition }}</td>
                            <td>{{ item.due_date || '—' }}</td>
                            <td>{{ Number(item.paid_amount).toFixed(2) }}</td>
                            <td>{{ Number(item.balance).toFixed(2) }}</td>
                            <td>{{ item.status }}</td>
                            <td>
                                <Link :href="route('admin.farm-finances.show', item.id)" class="btn btn-sm btn-info">Ver</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
