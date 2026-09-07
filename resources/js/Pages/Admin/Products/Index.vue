<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    products: {
        type: Array,
        default: () => [],
    },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)

const destroy = (product) => {
    if (confirm(`¿Seguro que deseas eliminar el producto "${product.name}"?`)) {
        router.delete(route('admin.products.destroy', product.id))
    }
}
</script>

<template>
    <Head title="Productos" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Productos</h4>
                    <span>Catálogo maestro de productos y variedades</span>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <Link :href="route('admin.products.create')" class="btn btn-primary">
                    Nuevo Producto
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
                        <h4 class="card-title">Listado de productos</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-responsive-md">
                                <thead>
                                    <tr>
                                        <th><strong>ID</strong></th>
                                        <th><strong>Nombre</strong></th>
                                        <th><strong>Tipo de flor</strong></th>
                                        <th><strong>Variedad</strong></th>
                                        <th><strong>Color</strong></th>
                                        <th><strong>Estado</strong></th>
                                        <th class="admin-actions-column"><strong>Acciones</strong></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="products.length === 0">
                                        <td colspan="7" class="text-center text-muted">
                                            No hay productos registrados.
                                        </td>
                                    </tr>
                                    <tr v-for="product in products" :key="product.id">
                                        <td>{{ product.id }}</td>
                                        <td>{{ product.name }}</td>
                                        <td>{{ product.flower_type || '—' }}</td>
                                        <td>{{ product.variety_name || '—' }}</td>
                                        <td>{{ product.color || '—' }}</td>
                                        <td>
                                            <span
                                                v-if="product.active"
                                                class="badge badge-success"
                                            >
                                                Activo
                                            </span>
                                            <span
                                                v-else
                                                class="badge badge-danger"
                                            >
                                                Inactivo
                                            </span>
                                        </td>
                                        <td class="admin-actions-column">
                                            <div class="admin-actions">
                                                <Link
                                                    :href="route('admin.products.edit', product.id)"
                                                    class="btn btn-success shadow btn-xs"
                                                >
                                                    Editar
                                                </Link>
                                                <button
                                                    type="button"
                                                    class="btn btn-danger shadow btn-xs"
                                                    @click="destroy(product)"
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
