<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    farmUser: { type: Object, required: true },
    logs: { type: Array, default: () => [] },
})
</script>

<template>
    <Head :title="farmUser.name" />
    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-8 p-md-0">
                <div class="welcome-text">
                    <h4>{{ farmUser.name }}</h4>
                    <span>Tipo: Usuario de finca</span>
                </div>
            </div>
            <div class="col-sm-4 p-md-0 d-flex justify-content-sm-end gap-2">
                <Link :href="route('admin.users.farms.edit', farmUser.id)" class="btn btn-primary">Editar</Link>
                <Link :href="route('admin.users.farms.index')" class="btn btn-light">Volver</Link>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <p><strong>Email:</strong> {{ farmUser.email }}</p>
                <p><strong>Finca:</strong> {{ farmUser.farm_name }}</p>
                <p><strong>Rol:</strong> {{ farmUser.role }}</p>
                <p>
                    <strong>Estado:</strong>
                    <span class="badge" :class="farmUser.active ? 'badge-success' : 'badge-danger'">
                        {{ farmUser.active ? 'Activo' : 'Inactivo' }}
                    </span>
                </p>
                <p><strong>Creado:</strong> {{ farmUser.created_at }}</p>
                <p class="mb-0"><strong>Actualizado:</strong> {{ farmUser.updated_at }}</p>
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
