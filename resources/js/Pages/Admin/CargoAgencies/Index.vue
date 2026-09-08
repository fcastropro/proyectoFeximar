<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    agencies: { type: Array, default: () => [] },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)

const destroy = (item) => {
    if (confirm(`¿Eliminar la agencia "${item.name}"?`)) {
        router.delete(route('admin.cargo-agencies.destroy', item.id))
    }
}
</script>

<template>
    <Head title="Agencias de carga" />
    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Agencias de carga</h4>
                    <span>Catálogo de agencias para checkout</span>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <Link :href="route('admin.cargo-agencies.create')" class="btn btn-primary">
                    Nueva agencia
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
                                <th>Nombre</th>
                                <th>Código</th>
                                <th>Contacto</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Estado</th>
                                <th class="admin-actions-column">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="agencies.length === 0">
                                <td colspan="7" class="text-center text-muted">
                                    No hay agencias registradas.
                                </td>
                            </tr>
                            <tr v-for="item in agencies" :key="item.id">
                                <td>{{ item.name }}</td>
                                <td>{{ item.code || '—' }}</td>
                                <td>{{ item.contact_name || '—' }}</td>
                                <td>{{ item.phone || '—' }}</td>
                                <td>{{ item.email || '—' }}</td>
                                <td>{{ item.active ? 'Activo' : 'Inactivo' }}</td>
                                <td class="admin-actions-column">
                                    <div class="admin-actions">
                                        <Link
                                            :href="route('admin.cargo-agencies.edit', item.id)"
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
