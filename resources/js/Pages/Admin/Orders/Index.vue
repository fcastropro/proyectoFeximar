<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    orders: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    statusLabels: {
        type: Object,
        default: () => ({}),
    },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)
const errorMessage = computed(() => page.props.flash?.error ?? null)
const rows = computed(() => props.orders.data ?? [])

const form = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
})

const statusLabel = (status) => props.statusLabels[status] ?? status

const apply = () => {
    const query = {}
    if (form.q) query.q = form.q
    if (form.status) query.status = form.status
    router.get(route('admin.orders.index'), query, { preserveState: true, replace: true })
}

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
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex gap-2">
                <a :href="route('admin.exports.orders')" class="btn btn-outline-primary">CSV</a>
                <Link :href="route('admin.orders.create')" class="btn btn-primary">
                    Nuevo pedido
                </Link>
            </div>
        </div>

        <div v-if="successMessage" class="alert alert-success alert-dismissible fade show">
            {{ successMessage }}
        </div>

        <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show">
            {{ errorMessage }}
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form class="row" @submit.prevent="apply">
                    <div class="col-md-4 mb-2">
                        <input v-model="form.q" class="form-control" placeholder="Buscar ID o comprador">
                    </div>
                    <div class="col-md-3 mb-2">
                        <select v-model="form.status" class="form-control">
                            <option value="">Todos los estados</option>
                            <option v-for="(label, key) in statusLabels" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button class="btn btn-primary" type="submit">Filtrar</button>
                    </div>
                </form>
            </div>
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
                                    <tr v-if="rows.length === 0">
                                        <td colspan="6" class="text-center text-muted">
                                            No hay pedidos registrados.
                                        </td>
                                    </tr>
                                    <tr v-for="order in rows" :key="order.id">
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
                                                <a
                                                    :href="route('admin.reports.orders.pdf', order.id)"
                                                    class="btn btn-secondary shadow btn-xs"
                                                >
                                                    PDF
                                                </a>
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
