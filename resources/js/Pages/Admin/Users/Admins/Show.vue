<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    admin: { type: Object, required: true },
    logs: { type: Array, default: () => [] },
})
</script>

<template>
    <Head :title="`Admin ${admin.name}`" />
    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-8 p-md-0">
                <div class="welcome-text">
                    <h4>{{ admin.name }}</h4>
                    <span>{{ admin.profile?.label || 'Administrador' }}</span>
                </div>
            </div>
            <div class="col-sm-4 p-md-0 d-flex justify-content-sm-end gap-2">
                <Link :href="route('admin.users.admins.edit', admin.id)" class="btn btn-primary">Editar</Link>
                <Link :href="route('admin.users.admins.index')" class="btn btn-light">Volver</Link>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <p><strong>Email:</strong> {{ admin.email }}</p>
                <p><strong>Tipo:</strong> {{ admin.profile?.label }}</p>
                <p><strong>Rol:</strong> {{ admin.profile?.role || 'admin' }}</p>
                <p>
                    <strong>Estado:</strong>
                    <span class="badge" :class="admin.active ? 'badge-success' : 'badge-danger'">
                        {{ admin.active ? 'Activo' : 'Inactivo' }}
                    </span>
                </p>
                <p><strong>Creado:</strong> {{ admin.created_at }}</p>
                <p class="mb-0"><strong>Actualizado:</strong> {{ admin.updated_at }}</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Últimas acciones</h4></div>
            <div class="card-body">
                <ul v-if="logs.length" class="mb-0">
                    <li v-for="(log, idx) in logs" :key="idx">
                        <small class="text-muted">{{ log.created_at }}</small> — {{ log.description }}
                    </li>
                </ul>
                <p v-else class="text-muted mb-0">Sin actividad registrada</p>
            </div>
        </div>
    </AdminLayout>
</template>
