<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    farms: {
        type: Array,
        default: () => [],
    },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)
const errorMessage = computed(() => page.props.flash?.error ?? null)

const destroy = (farm) => {
    if (confirm(`¿Seguro que deseas eliminar la finca "${farm.name}"?`)) {
        router.delete(route('admin.farms.destroy', farm.id))
    }
}
</script>

<template>
    <Head title="Fincas" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Fincas</h4>
                    <span>Gestión de fincas productoras</span>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex gap-2 flex-wrap">
                <a :href="route('admin.reports.farms.pdf')" class="btn btn-outline-success">PDF</a>
                <a :href="route('admin.exports.farms')" class="btn btn-outline-primary">CSV</a>
                <Link :href="route('admin.farms.create')" class="btn btn-primary">
                    Nueva Finca
                </Link>
            </div>
        </div>

        <div v-if="successMessage" class="alert alert-success alert-dismissible fade show">
            {{ successMessage }}
        </div>

        <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show">
            {{ errorMessage }}
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Listado de fincas</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-responsive-md">
                                <thead>
                                    <tr>
                                        <th><strong>ID</strong></th>
                                        <th><strong>Nombre</strong></th>
                                        <th><strong>Nombre comercial</strong></th>
                                        <th><strong>RUC</strong></th>
                                        <th><strong>Ciudad</strong></th>
                                        <th><strong>Provincia</strong></th>
                                        <th><strong>Estado</strong></th>
                                        <th class="admin-actions-column"><strong>Acciones</strong></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="farms.length === 0">
                                        <td colspan="8" class="text-center text-muted">
                                            No hay fincas registradas.
                                        </td>
                                    </tr>
                                    <tr v-for="farm in farms" :key="farm.id">
                                        <td>{{ farm.id }}</td>
                                        <td>{{ farm.name }}</td>
                                        <td>{{ farm.commercial_name || '—' }}</td>
                                        <td>{{ farm.ruc || '—' }}</td>
                                        <td>{{ farm.city?.name || '—' }}</td>
                                        <td>{{ farm.province?.name || '—' }}</td>
                                        <td>
                                            <span
                                                v-if="farm.active"
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
                                                <a
                                                    :href="route('admin.reports.farms.show-pdf', farm.id)"
                                                    class="btn btn-info shadow btn-xs"
                                                >
                                                    PDF
                                                </a>
                                                <Link
                                                    :href="route('admin.farms.edit', farm.id)"
                                                    class="btn btn-success shadow btn-xs"
                                                >
                                                    Editar
                                                </Link>
                                                <button
                                                    type="button"
                                                    class="btn btn-danger shadow btn-xs"
                                                    @click="destroy(farm)"
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
