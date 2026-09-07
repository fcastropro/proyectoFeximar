<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    orders: {
        type: Array,
        default: () => [],
    },
    statusLabels: {
        type: Object,
        default: () => ({}),
    },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)

const statusLabel = (status) => props.statusLabels[status] ?? status

const destroy = (order) => {
    if (confirm(`¿Seguro que deseas eliminar el pedido #${order.id}?`)) {
        router.delete(route('admin.orders.destroy', order.id))
    }
}
</script>

<template>
    <Head title="Pedidos" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Pedidos</h4>
                    <span>Pedidos de compradores internacionales</span>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <Link :href="route('admin.orders.create')" class="btn btn-primary">
                    Nuevo pedido
                </Link>
            </div>
        </div>

        <div v-if="successMessage" class="alert alert-success alert-dismissible fade show">
            {{ successMessage }}
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Listado de pedidos</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-responsive-md">
                                <thead>
                                    <tr>
                                        <th><strong>ID</strong></th>
                                        <th><strong>Comprador</strong></th>
                                        <th><strong>Fecha</strong></th>
                                        <th><strong>Estado</strong></th>
                                        <th><strong>Total</strong></th>
                                        <th class="admin-actions-column"><strong>Acciones</strong></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="orders.length === 0">
                                        <td colspan="6" class="text-center text-muted">
                                            No hay pedidos registrados.
                                        </td>
                                    </tr>
                                    <tr v-for="order in orders" :key="order.id">
                                        <td>{{ order.id }}</td>
                                        <td>{{ order.buyer_name || '—' }}</td>
                                        <td>{{ order.created_at || '—' }}</td>
                                        <td>
                                            <span class="badge badge-primary light">
                                                {{ statusLabel(order.status) }}
                                            </span>
                                        </td>
                                        <td>{{ Number(order.total).toFixed(2) }}</td>
                                        <td class="admin-actions-column">
                                            <div class="admin-actions">
                                                <Link
                                                    :href="route('admin.orders.show', order.id)"
                                                    class="btn btn-info shadow btn-xs"
                                                >
                                                    Ver
                                                </Link>
                                                <Link
                                                    :href="route('admin.orders.edit', order.id)"
                                                    class="btn btn-success shadow btn-xs"
                                                >
                                                    Editar
                                                </Link>
                                                <button
                                                    type="button"
                                                    class="btn btn-danger shadow btn-xs"
                                                    @click="destroy(order)"
                                                >
                                                    Eliminar
                                                </button>
                                            </div>
                                        </td>
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
