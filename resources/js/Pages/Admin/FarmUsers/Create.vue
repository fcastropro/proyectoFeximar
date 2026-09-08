<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    farms: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
})

const form = useForm({
    farm_id: '',
    name: '',
    email: '',
    password: '',
    role: 'manager',
    active: true,
})

const submit = () => form.post(route('admin.farm-users.store'))
</script>

<template>
    <Head title="Nuevo usuario finca" />
    <AdminLayout>
        <h4 class="mb-3">Nuevo usuario de finca</h4>
        <div class="card">
            <div class="card-body">
                <form @submit.prevent="submit">
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Finca *</label>
                            <select v-model="form.farm_id" class="form-control">
                                <option value="">Seleccione</option>
                                <option v-for="farm in farms" :key="farm.id" :value="farm.id">{{ farm.name }}</option>
                            </select>
                            <div v-if="form.errors.farm_id" class="text-danger">{{ form.errors.farm_id }}</div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Rol *</label>
                            <select v-model="form.role" class="form-control">
                                <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                            </select>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Nombre *</label>
                            <input v-model="form.name" type="text" class="form-control">
                            <div v-if="form.errors.name" class="text-danger">{{ form.errors.name }}</div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Email *</label>
                            <input v-model="form.email" type="email" class="form-control">
                            <div v-if="form.errors.email" class="text-danger">{{ form.errors.email }}</div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Contraseña *</label>
                            <input v-model="form.password" type="password" class="form-control">
                            <div v-if="form.errors.password" class="text-danger">{{ form.errors.password }}</div>
                        </div>
                        <div class="mb-3 col-md-12">
                            <label class="form-check-label">
                                <input v-model="form.active" type="checkbox" class="form-check-input me-2"> Activo
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary me-2" :disabled="form.processing">Guardar</button>
                    <Link :href="route('admin.farm-users.index')" class="btn btn-light">Cancelar</Link>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
