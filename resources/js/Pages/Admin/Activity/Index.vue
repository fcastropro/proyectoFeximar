<script setup>
import { Head, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    actions: { type: Array, default: () => [] },
})

const form = reactive({
    action: props.filters.action ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
})

const apply = () => {
    const query = {}
    Object.entries(form).forEach(([k, v]) => {
        if (v) query[k] = v
    })
    router.get(route('admin.activity.index'), query, { preserveState: true, replace: true })
}
</script>

<template>
    <Head title="Actividad / Auditoría" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-12 p-md-0">
                <div class="welcome-text">
                    <h4>Trazabilidad / Auditoría</h4>
                    <span>Historial simple de acciones importantes</span>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form class="row" @submit.prevent="apply">
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Acción</label>
                        <select v-model="form.action" class="form-control">
                            <option value="">Todas</option>
                            <option v-for="action in actions" :key="action" :value="action">{{ action }}</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Desde</label>
                        <input v-model="form.date_from" type="date" class="form-control">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Hasta</label>
                        <input v-model="form.date_to" type="date" class="form-control">
                    </div>
                    <div class="col-md-3 mb-2 d-flex align-items-end">
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
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Entidad</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!logs.data?.length">
                            <td colspan="5" class="text-center text-muted">Sin registros</td>
                        </tr>
                        <tr v-for="log in logs.data" :key="log.id">
                            <td>{{ log.created_at }}</td>
                            <td>{{ log.user || '—' }}</td>
                            <td>{{ log.action }}</td>
                            <td>{{ log.entity_type }} #{{ log.entity_id }}</td>
                            <td>{{ log.description }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
