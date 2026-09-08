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
    name: props.farmUser.user_name,
    email: props.farmUser.user_email,
    role: props.farmUser.role,
    active: props.farmUser.active,
    password: '',
    password_confirmation: '',
})

const resetForm = useForm({ password: '', password_confirmation: '' })

const submit = () => form.put(route('admin.users.farms.update', props.farmUser.id))
const resetPassword = () => resetForm.post(route('admin.users.farms.reset-password', props.farmUser.id), {
    onSuccess: () => resetForm.reset(),
})
</script>

<template>
    <Head title="Editar usuario finca" />
    <AdminLayout>
        <h4 class="mb-3">Editar usuario de finca</h4>
        <div class="card mb-3">
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
                            <label class="form-label">Nombre *</label>
                            <input v-model="form.name" type="text" class="form-control">
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Email *</label>
                            <input v-model="form.email" type="email" class="form-control">
                            <div v-if="form.errors.email" class="text-danger">{{ form.errors.email }}</div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Nueva contraseña (opcional)</label>
                            <input v-model="form.password" type="password" class="form-control" autocomplete="new-password">
                            <div v-if="form.errors.password" class="text-danger">{{ form.errors.password }}</div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Confirmar</label>
                            <input v-model="form.password_confirmation" type="password" class="form-control" autocomplete="new-password">
                        </div>
                        <div class="mb-3 col-md-12">
                            <label class="form-check-label">
                                <input v-model="form.active" type="checkbox" class="form-check-input me-2"> Activo
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary me-2" :disabled="form.processing">Guardar</button>
                    <Link :href="route('admin.users.farms.index')" class="btn btn-light">Cancelar</Link>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Restablecer contraseña</h4></div>
            <div class="card-body">
                <form class="row" @submit.prevent="resetPassword">
                    <div class="mb-3 col-md-4">
                        <input v-model="resetForm.password" type="password" class="form-control" placeholder="Nueva contraseña" autocomplete="new-password">
                        <div v-if="resetForm.errors.password" class="text-danger">{{ resetForm.errors.password }}</div>
                    </div>
                    <div class="mb-3 col-md-4">
                        <input v-model="resetForm.password_confirmation" type="password" class="form-control" placeholder="Confirmar" autocomplete="new-password">
                    </div>
                    <div class="mb-3 col-md-4">
                        <button class="btn btn-warning" type="submit">Restablecer</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
