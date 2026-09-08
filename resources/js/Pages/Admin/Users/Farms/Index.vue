<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    farmUsers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    farms: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)
const rows = computed(() => props.farmUsers.data ?? [])

const form = reactive({
    q: props.filters.q ?? '',
    farm_id: props.filters.farm_id ?? '',
    role: props.filters.role ?? '',
    active: props.filters.active ?? '',
})

const apply = () => {
    const query = {}
    Object.entries(form).forEach(([k, v]) => {
        if (v !== '' && v !== null && v !== undefined) query[k] = v
    })
    router.get(route('admin.users.farms.index'), query, { preserveState: true, replace: true })
}

const toggle = (item) => router.post(route('admin.users.farms.toggle-active', item.id))
const unlink = (item) => {
    if (confirm('¿Eliminar solo el vínculo con la finca? (preferible desactivar)')) {
        router.delete(route('admin.users.farms.destroy', item.id))
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
                    <span>Acceso al portal /farm</span>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 d-flex justify-content-sm-end">
                <Link :href="route('admin.users.farms.create')" class="btn btn-primary">Nuevo usuario finca</Link>
            </div>
        </div>

        <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>

        <div class="card mb-3">
            <div class="card-body">
                <form class="row" @submit.prevent="apply">
                    <div class="col-md-3 mb-2">
                        <input v-model="form.q" class="form-control" placeholder="Nombre o email">
                    </div>
                    <div class="col-md-3 mb-2">
                        <select v-model="form.farm_id" class="form-control">
                            <option value="">Todas las fincas</option>
                            <option v-for="farm in farms" :key="farm.id" :value="farm.id">{{ farm.name }}</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select v-model="form.role" class="form-control">
                            <option value="">Todos los roles</option>
                            <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select v-model="form.active" class="form-control">
                            <option value="">Estado</option>
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
                            <th>Finca</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Creado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!rows.length">
                            <td colspan="7" class="text-center text-muted">Sin usuarios</td>
                        </tr>
                        <tr v-for="item in rows" :key="item.id">
                            <td>{{ item.user_name }}</td>
                            <td>{{ item.user_email }}</td>
                            <td>{{ item.farm_name }}</td>
                            <td>{{ item.role }}</td>
                            <td>
                                <span class="badge" :class="item.active ? 'badge-success' : 'badge-danger'">
                                    {{ item.active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td>{{ item.created_at }}</td>
                            <td class="admin-actions-column">
                                <div class="admin-actions">
                                    <Link :href="route('admin.users.farms.show', item.id)" class="btn btn-info btn-xs shadow">Ver</Link>
                                    <Link :href="route('admin.users.farms.edit', item.id)" class="btn btn-success btn-xs shadow">Editar</Link>
                                    <button type="button" class="btn btn-warning btn-xs shadow" @click="toggle(item)">
                                        {{ item.active ? 'Desactivar' : 'Activar' }}
                                    </button>
                                    <button type="button" class="btn btn-danger btn-xs shadow" @click="unlink(item)">Quitar vínculo</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
