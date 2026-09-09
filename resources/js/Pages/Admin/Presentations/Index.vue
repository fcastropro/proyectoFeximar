<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    presentations: {
        type: Array,
        default: () => [],
    },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)
const errorMessage = computed(() => page.props.flash?.error ?? null)

const destroy = (item) => {
    if (confirm(`¿Seguro que deseas eliminar la presentación #${item.id}?`)) {
        router.delete(route('admin.presentations.destroy', item.id))
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
    <Head title="Presentaciones" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Presentaciones</h4>
                    <span>Configuración estable de longitud y precios base (disponibilidad semanal aparte)</span>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <Link :href="route('admin.presentations.create')" class="btn btn-primary">
                    Nueva presentación
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
                                        <th><strong>Tallos/bunch</strong></th>
                                        <th><strong>Precio base tallo</strong></th>
                                        <th><strong>Precio base bunch</strong></th>
                                        <th><strong>Estado</strong></th>
                                        <th class="admin-actions-column"><strong>Acciones</strong></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="presentations.length === 0">
                                        <td colspan="9" class="text-center text-muted">
                                            No hay presentaciones registradas.
                                        </td>
                                    </tr>
                                    <tr v-for="item in presentations" :key="item.id">
                                        <td>{{ item.farm_name || '—' }}</td>
                                        <td>{{ item.product_name || '—' }}</td>
                                        <td>{{ item.variety_name || '—' }}</td>
                                        <td>{{ item.stem_length_cm }} cm</td>
                                        <td>{{ item.stems_per_bunch ?? '—' }}</td>
                                        <td>{{ formatMoney(item.price_per_stem) }}</td>
                                        <td>{{ formatMoney(item.price_per_bunch) }}</td>
                                        <td>
                                            <span v-if="item.active" class="badge badge-success">Activo</span>
                                            <span v-else class="badge badge-danger">Inactivo</span>
                                        </td>
                                        <td class="admin-actions-column">
                                            <div class="admin-actions">
                                                <Link
                                                    :href="route('admin.presentations.edit', item.id)"
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
