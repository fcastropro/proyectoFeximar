<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    varieties: { type: Array, default: () => [] },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)
const errorMessage = computed(() => page.props.flash?.error ?? null)

const destroy = (item) => {
    if (confirm(`¿Eliminar la variedad "${item.name}"?`)) {
        router.delete(route('admin.varieties.destroy', item.id))
    }
}
</script>

<template>
    <Head title="Variedades" />
    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Variedades</h4>
                    <span>Catálogo de variedades por tipo de flor</span>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <Link :href="route('admin.varieties.create')" class="btn btn-primary">
                    Nueva variedad
                </Link>
            </div>
        </div>

        <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>
        <div v-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tipo de flor</th>
                                <th>Variedad</th>
                                <th>Color</th>
                                <th>Estado</th>
                                <th class="admin-actions-column">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="varieties.length === 0">
                                <td colspan="6" class="text-center text-muted">
                                    No hay variedades registradas.
                                </td>
                            </tr>
                            <tr v-for="item in varieties" :key="item.id">
                                <td>{{ item.id }}</td>
                                <td>{{ item.flower_type || '—' }}</td>
                                <td>{{ item.name }}</td>
                                <td>{{ item.color || '—' }}</td>
                                <td>{{ item.active ? 'Activo' : 'Inactivo' }}</td>
                                <td class="admin-actions-column">
                                    <div class="admin-actions">
                                        <Link
                                            :href="route('admin.varieties.edit', item.id)"
                                            class="btn btn-success btn-xs"
                                        >
                                            Editar
                                        </Link>
                                        <button
                                            type="button"
                                            class="btn btn-danger btn-xs"
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
    </AdminLayout>
</template>
