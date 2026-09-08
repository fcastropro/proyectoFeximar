<script setup>
import { Head } from '@inertiajs/vue3'
import FarmLayout from '@/Layouts/FarmLayout.vue'

const props = defineProps({
    finances: { type: Array, default: () => [] },
    conditionLabels: { type: Object, default: () => ({}) },
    statusLabels: { type: Object, default: () => ({}) },
})

const condition = (value) => props.conditionLabels[value] ?? value
const status = (value) => props.statusLabels[value] ?? value
</script>

<template>
    <Head title="Pagos" />

    <FarmLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Pagos y crédito</h4>
                    <span>Consulta únicamente. Los pagos los registra FEXIMAR.</span>
                </div>
            </div>
        </div>

        <div v-if="finances.length === 0" class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-muted">
                        Sin registros financieros.
                    </div>
                </div>
            </div>
        </div>

        <div v-for="item in finances" :key="item.id" class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Pedido #{{ item.order_id }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3 mb-2">
                                <strong>Total:</strong> {{ Number(item.amount).toFixed(2) }}
                            </div>
                            <div class="col-md-3 mb-2">
                                <strong>Condición:</strong> {{ condition(item.payment_condition) }}
                            </div>
                            <div class="col-md-3 mb-2">
                                <strong>Estado:</strong>
                                <span class="badge badge-primary light">{{ status(item.status) }}</span>
                            </div>
                            <div class="col-md-3 mb-2">
                                <strong>Crédito (días):</strong> {{ item.credit_days ?? '—' }}
                            </div>
                            <div class="col-md-3 mb-2">
                                <strong>Vencimiento:</strong> {{ item.due_date || '—' }}
                            </div>
                            <div class="col-md-3 mb-2">
                                <strong>Pagado:</strong> {{ Number(item.paid_amount).toFixed(2) }}
                            </div>
                            <div class="col-md-3 mb-2">
                                <strong>Saldo:</strong> {{ Number(item.balance).toFixed(2) }}
                            </div>
                            <div class="col-md-3 mb-2">
                                <strong>Días transcurridos:</strong> {{ item.days_elapsed }}
                            </div>
                            <div class="col-md-3 mb-2">
                                <strong>Días atraso:</strong> {{ item.days_overdue }}
                            </div>
                        </div>

                        <h5 class="mb-2">Historial de abonos</h5>
                        <div class="table-responsive">
                            <table class="table table-responsive-md mb-0">
                                <thead>
                                    <tr>
                                        <th><strong>Fecha</strong></th>
                                        <th><strong>Monto</strong></th>
                                        <th><strong>Método</strong></th>
                                        <th><strong>Referencia</strong></th>
                                        <th><strong>Notas</strong></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!item.payments?.length">
                                        <td colspan="5" class="text-muted">Sin abonos registrados.</td>
                                    </tr>
                                    <tr v-for="payment in item.payments" :key="payment.id">
                                        <td>{{ payment.payment_date }}</td>
                                        <td>{{ Number(payment.amount).toFixed(2) }}</td>
                                        <td>{{ payment.payment_method || '—' }}</td>
                                        <td>{{ payment.reference || '—' }}</td>
                                        <td>{{ payment.notes || '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </FarmLayout>
</template>
