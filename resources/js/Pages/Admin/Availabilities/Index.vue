<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    availabilities: {
        type: Array,
        default: () => [],
    },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)
const errorMessage = computed(() => page.props.flash?.error ?? null)

const destroy = (item) => {
    if (confirm(`¿Seguro que deseas eliminar la disponibilidad #${item.id}?`)) {
        router.delete(route('admin.availabilities.destroy', item.id))
    }
}

const formatMoney = (value) => {
    if (value === null || value === undefined || value === '') {
        return '—'
    }

    return Number(value).toFixed(4)
}
</script>

<template>
    <Head title="Disponibilidad semanal" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Disponibilidad semanal</h4>
                    <span>Tallos y precios reportados por finca/presentación y semana</span>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <Link :href="route('admin.availabilities.create')" class="btn btn-primary">
                    Nueva disponibilidad
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
                        <h4 class="card-title">Listado</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-responsive-md">
                                <thead>
                                    <tr>
                                        <th><strong>Finca</strong></th>
                                        <th><strong>Producto</strong></th>
                                        <th><strong>Variedad</strong></th>
                                        <th><strong>Longitud</strong></th>
                                        <th><strong>Año</strong></th>
                                        <th><strong>Semana</strong></th>
                                        <th><strong>Tallos disponibles</strong></th>
                                        <th><strong>Precio tallo</strong></th>
                                        <th><strong>Precio bunch</strong></th>
                                        <th><strong>Estado</strong></th>
                                        <th class="admin-actions-column"><strong>Acciones</strong></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="availabilities.length === 0">
                                        <td colspan="11" class="text-center text-muted">
                                            No hay disponibilidades registradas.
                                        </td>
                                    </tr>
                                    <tr v-for="item in availabilities" :key="item.id">
                                        <td>{{ item.farm_name || '—' }}</td>
                                        <td>{{ item.product_name || '—' }}</td>
                                        <td>{{ item.variety_name || '—' }}</td>
                                        <td>{{ item.stem_length_cm }} cm</td>
                                        <td>{{ item.year }}</td>
                                        <td>{{ item.week_number }}</td>
                                        <td>{{ item.available_stems }}</td>
                                        <td>{{ formatMoney(item.price_per_stem) }}</td>
                                        <td>{{ formatMoney(item.price_per_bunch) }}</td>
                                        <td>
                                            <span v-if="item.active" class="badge badge-success">Activo</span>
                                            <span v-else class="badge badge-danger">Inactivo</span>
                                        </td>
                                        <td class="admin-actions-column">
                                            <div class="admin-actions">
                                                <Link
                                                    :href="route('admin.availabilities.edit', item.id)"
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
