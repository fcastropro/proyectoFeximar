<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    farmUsers: { type: Array, default: () => [] },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)

const destroy = (item) => {
    if (confirm(`¿Eliminar asociación de ${item.user_email}?`)) {
        router.delete(route('admin.farm-users.destroy', item.id))
    }
}
</script>

<template>
    <Head title="Usuarios de finca" />
    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Usuarios de finca</h4>
                    <span>Asociación usuarios ↔ fincas</span>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <Link :href="route('admin.farm-users.create')" class="btn btn-primary">Nuevo usuario finca</Link>
            </div>
        </div>
        <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Finca</th>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th class="admin-actions-column">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in farmUsers" :key="item.id">
                                <td>{{ item.farm_name }}</td>
                                <td>{{ item.user_name }}</td>
                                <td>{{ item.user_email }}</td>
                                <td>{{ item.role }}</td>
                                <td>{{ item.active ? 'Activo' : 'Inactivo' }}</td>
                                <td class="admin-actions-column">
                                    <div class="admin-actions">
                                        <Link :href="route('admin.farm-users.edit', item.id)" class="btn btn-success btn-xs">Editar</Link>
                                        <button type="button" class="btn btn-danger btn-xs" @click="destroy(item)">Eliminar</button>
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
