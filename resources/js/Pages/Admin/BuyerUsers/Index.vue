<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    buyerUsers: { type: Array, default: () => [] },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)

const destroy = (item) => {
    if (confirm(`¿Eliminar asociación de ${item.user_email}?`)) {
        router.delete(route('admin.users.buyers.destroy', item.id))
    }
}
</script>

<template>
    <Head title="Usuarios compradores" />
    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Usuarios compradores</h4>
                    <span>Asociación usuarios ↔ compradores</span>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <Link :href="route('admin.users.buyers.create')" class="btn btn-primary">
                    Nuevo usuario comprador
                </Link>
            </div>
        </div>
        <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Comprador</th>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Crédito</th>
                                <th>Estado</th>
                                <th class="admin-actions-column">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="buyerUsers.length === 0">
                                <td colspan="7" class="text-center text-muted">
                                    No hay usuarios compradores registrados.
                                </td>
                            </tr>
                            <tr v-for="item in buyerUsers" :key="item.id">
                                <td>{{ item.buyer_name }}</td>
                                <td>{{ item.user_name }}</td>
                                <td>{{ item.user_email }}</td>
                                <td>{{ item.role }}</td>
                                <td>
                                    {{ item.credit_allowed ? 'Sí' : 'No' }}
                                    <span v-if="item.credit_allowed && item.credit_days_default">
                                        ({{ item.credit_days_default }} días)
                                    </span>
                                </td>
                                <td>{{ item.active ? 'Activo' : 'Inactivo' }}</td>
                                <td class="admin-actions-column">
                                    <div class="admin-actions">
                                        <Link
                                            :href="route('admin.users.buyers.edit', item.id)"
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
