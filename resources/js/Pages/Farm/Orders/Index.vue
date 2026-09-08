<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import FarmLayout from '@/Layouts/FarmLayout.vue'

const props = defineProps({
    fulfillments: { type: Array, default: () => [] },
    statusLabels: { type: Object, default: () => ({}) },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)
const label = (status) => props.statusLabels[status] ?? status
</script>

<template>
    <Head title="Pedidos de finca" />

    <FarmLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Pedidos</h4>
                    <span>Líneas y estados correspondientes a tu finca</span>
                </div>
            </div>
        </div>

        <div v-if="successMessage" class="alert alert-success alert-dismissible fade show">
            {{ successMessage }}
        </div>

        <div class="row">
            <div class="col-12">
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
                                        <th><strong>Comprador</strong></th>
                                        <th><strong>Fecha</strong></th>
                                        <th><strong>Líneas</strong></th>
                                        <th><strong>Tallos</strong></th>
                                        <th><strong>Subtotal</strong></th>
                                        <th><strong>Estado</strong></th>
                                        <th class="admin-actions-column"><strong>Acciones</strong></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="fulfillments.length === 0">
                                        <td colspan="8" class="text-center text-muted">
                                            Sin pedidos.
                                        </td>
                                    </tr>
                                    <tr v-for="item in fulfillments" :key="item.id">
                                        <td>#{{ item.order_id }}</td>
                                        <td>{{ item.buyer_name || '—' }}</td>
                                        <td>{{ item.order_date || '—' }}</td>
                                        <td>{{ item.lines_count }}</td>
                                        <td>{{ item.total_stems }}</td>
                                        <td>{{ Number(item.subtotal).toFixed(2) }}</td>
                                        <td>
                                            <span class="badge badge-primary light">
                                                {{ label(item.status) }}
                                            </span>
                                        </td>
                                        <td class="admin-actions">
                                            <Link
                                                :href="route('farm.orders.show', item.id)"
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
            </div>
        </div>
    </FarmLayout>
</template>
