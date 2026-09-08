<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    admins: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)
const rows = computed(() => props.admins.data ?? [])

const form = reactive({
    q: props.filters.q ?? '',
    active: props.filters.active ?? '',
})

const apply = () => {
    const query = {}
    if (form.q) query.q = form.q
    if (form.active !== '' && form.active !== null) query.active = form.active
    router.get(route('admin.users.admins.index'), query, { preserveState: true, replace: true })
}

const toggle = (admin) => {
    router.post(route('admin.users.admins.toggle-active', admin.id))
}
</script>

<template>
    <Head title="Administradores FEXIMAR" />
    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Administradores</h4>
                    <span>Acceso al panel /admin</span>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 d-flex justify-content-sm-end">
                <Link :href="route('admin.users.admins.create')" class="btn btn-primary">Nuevo administrador</Link>
            </div>
        </div>

        <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>

        <div class="card mb-3">
            <div class="card-body">
                <form class="row" @submit.prevent="apply">
                    <div class="col-md-4 mb-2">
                        <input v-model="form.q" class="form-control" placeholder="Buscar nombre o email">
                    </div>
                    <div class="col-md-3 mb-2">
                        <select v-model="form.active" class="form-control">
                            <option value="">Todos</option>
                            <option value="1">Activos</option>
                            <option value="0">Inactivos</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button class="btn btn-primary" type="submit">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Estado</th>
                            <th>Creado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!rows.length">
                            <td colspan="5" class="text-center text-muted">Sin administradores</td>
                        </tr>
                        <tr v-for="admin in rows" :key="admin.id">
                            <td>{{ admin.name }}</td>
                            <td>{{ admin.email }}</td>
                            <td>
                                <span class="badge" :class="admin.active ? 'badge-success' : 'badge-danger'">
                                    {{ admin.active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td>{{ admin.created_at }}</td>
                            <td class="admin-actions-column">
                                <div class="admin-actions">
                                    <Link :href="route('admin.users.admins.show', admin.id)" class="btn btn-info btn-xs shadow">Ver</Link>
                                    <Link :href="route('admin.users.admins.edit', admin.id)" class="btn btn-success btn-xs shadow">Editar</Link>
                                    <button type="button" class="btn btn-warning btn-xs shadow" @click="toggle(admin)">
                                        {{ admin.active ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
