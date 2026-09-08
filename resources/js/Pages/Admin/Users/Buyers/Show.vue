<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    buyerUser: { type: Object, required: true },
    logs: { type: Array, default: () => [] },
})
</script>

<template>
    <Head :title="buyerUser.name" />
    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-8 p-md-0">
                <div class="welcome-text">
                    <h4>{{ buyerUser.name }}</h4>
                    <span>Tipo: Usuario comprador</span>
                </div>
            </div>
            <div class="col-sm-4 p-md-0 d-flex justify-content-sm-end gap-2">
                <Link :href="route('admin.users.buyers.edit', buyerUser.id)" class="btn btn-primary">Editar</Link>
                <Link :href="route('admin.users.buyers.index')" class="btn btn-light">Volver</Link>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <p><strong>Email:</strong> {{ buyerUser.email }}</p>
                <p><strong>Empresa:</strong> {{ buyerUser.buyer_name }}</p>
                <p><strong>Rol:</strong> {{ buyerUser.role }}</p>
                <p><strong>Crédito:</strong> {{ buyerUser.credit_allowed ? 'Sí' : 'No' }}
                    <span v-if="buyerUser.credit_allowed">({{ buyerUser.credit_days_default }} días)</span>
                </p>
                <p>
                    <strong>Estado:</strong>
                    <span class="badge" :class="buyerUser.active ? 'badge-success' : 'badge-danger'">
                        {{ buyerUser.active ? 'Activo' : 'Inactivo' }}
                    </span>
                </p>
                <p><strong>Creado:</strong> {{ buyerUser.created_at }}</p>
                <p class="mb-0"><strong>Actualizado:</strong> {{ buyerUser.updated_at }}</p>
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
