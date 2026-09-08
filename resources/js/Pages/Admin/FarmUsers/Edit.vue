<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    farmUser: { type: Object, required: true },
    farms: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
})

const form = useForm({
    farm_id: props.farmUser.farm_id,
    role: props.farmUser.role,
    active: Boolean(props.farmUser.active),
    password: '',
})

const submit = () => form.put(route('admin.farm-users.update', props.farmUser.id))
</script>

<template>
    <Head title="Editar usuario finca" />
    <AdminLayout>
        <h4 class="mb-3">Editar usuario de finca</h4>
        <p>{{ farmUser.user_name }} · {{ farmUser.user_email }}</p>
        <div class="card">
            <div class="card-body">
                <form @submit.prevent="submit">
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Finca *</label>
                            <select v-model="form.farm_id" class="form-control">
                                <option v-for="farm in farms" :key="farm.id" :value="farm.id">{{ farm.name }}</option>
                            </select>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Rol *</label>
                            <select v-model="form.role" class="form-control">
                                <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                            </select>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Nueva contraseña (opcional)</label>
                            <input v-model="form.password" type="password" class="form-control">
                        </div>
                        <div class="mb-3 col-md-12">
                            <label class="form-check-label">
                                <input v-model="form.active" type="checkbox" class="form-check-input me-2"> Activo
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary me-2" :disabled="form.processing">Actualizar</button>
                    <Link :href="route('admin.farm-users.index')" class="btn btn-light">Cancelar</Link>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
