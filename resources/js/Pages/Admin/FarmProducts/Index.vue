<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    farmProducts: {
        type: Array,
        default: () => [],
    },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)

const destroy = (item) => {
    if (confirm(`¿Seguro que deseas eliminar la asociación #${item.id}?`)) {
        router.delete(route('admin.farm-products.destroy', item.id))
    }
}
</script>

<template>
    <Head title="Productos por finca" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Productos por finca</h4>
                    <span>Asociación de productos del catálogo a fincas</span>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <Link :href="route('admin.farm-products.create')" class="btn btn-primary">
                    Nueva asociación
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
                        <h4 class="card-title">Listado</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-responsive-md">
                                <thead>
                                    <tr>
                                        <th><strong>ID</strong></th>
                                        <th><strong>Finca</strong></th>
                                        <th><strong>Producto</strong></th>
                                        <th><strong>Tipo de flor</strong></th>
                                        <th><strong>Variedad</strong></th>
                                        <th><strong>Color</strong></th>
                                        <th><strong>Estado</strong></th>
                                        <th class="admin-actions-column"><strong>Acciones</strong></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="farmProducts.length === 0">
                                        <td colspan="8" class="text-center text-muted">
                                            No hay productos asociados a fincas.
                                        </td>
                                    </tr>
                                    <tr v-for="item in farmProducts" :key="item.id">
                                        <td>{{ item.id }}</td>
                                        <td>{{ item.farm_name || '—' }}</td>
                                        <td>{{ item.product_name || '—' }}</td>
                                        <td>{{ item.flower_type || '—' }}</td>
                                        <td>{{ item.variety_name || '—' }}</td>
                                        <td>{{ item.color || '—' }}</td>
                                        <td>
                                            <span v-if="item.active" class="badge badge-success">Activo</span>
                                            <span v-else class="badge badge-danger">Inactivo</span>
                                        </td>
                                        <td class="admin-actions-column">
                                            <div class="admin-actions">
                                                <Link
                                                    :href="route('admin.farm-products.edit', item.id)"
                                                    class="btn btn-success shadow btn-xs"
                                                >
                                                    Editar
                                                </Link>
                                                <button
                                                    type="button"
                                                    class="btn btn-danger shadow btn-xs"
                                                    @click="destroy(item)"
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
