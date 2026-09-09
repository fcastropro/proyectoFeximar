<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    buyers: {
        type: Array,
        default: () => [],
    },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)
const errorMessage = computed(() => page.props.flash?.error ?? null)

const destroy = (buyer) => {
    if (confirm(`¿Seguro que deseas eliminar el comprador "${buyer.company_name}"?`)) {
        router.delete(route('admin.buyers.destroy', buyer.id))
    }
}
</script>

<template>
    <Head title="Compradores" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Compradores</h4>
                    <span>Clientes internacionales y contactos comerciales</span>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <Link :href="route('admin.buyers.create')" class="btn btn-primary">
                    Nuevo comprador
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
                        <h4 class="card-title">Listado de compradores</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-responsive-md">
                                <thead>
                                    <tr>
                                        <th><strong>ID</strong></th>
                                        <th><strong>Empresa</strong></th>
                                        <th><strong>Contacto</strong></th>
                                        <th><strong>Email</strong></th>
                                        <th><strong>Teléfono</strong></th>
                                        <th><strong>País</strong></th>
                                        <th><strong>Ciudad</strong></th>
                                        <th><strong>Estado</strong></th>
                                        <th class="admin-actions-column"><strong>Acciones</strong></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="buyers.length === 0">
                                        <td colspan="9" class="text-center text-muted">
                                            No hay compradores registrados.
                                        </td>
                                    </tr>
                                    <tr v-for="buyer in buyers" :key="buyer.id">
                                        <td>{{ buyer.id }}</td>
                                        <td>{{ buyer.company_name }}</td>
                                        <td>{{ buyer.contact_name }}</td>
                                        <td>{{ buyer.email }}</td>
                                        <td>{{ buyer.phone || '—' }}</td>
                                        <td>{{ buyer.country || '—' }}</td>
                                        <td>{{ buyer.city || '—' }}</td>
                                        <td>
                                            <span
                                                v-if="buyer.active"
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
                                                    :href="route('admin.buyers.edit', buyer.id)"
                                                    class="btn btn-success shadow btn-xs"
                                                >
                                                    Editar
                                                </Link>
                                                <button
                                                    type="button"
                                                    class="btn btn-danger shadow btn-xs"
                                                    @click="destroy(buyer)"
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
