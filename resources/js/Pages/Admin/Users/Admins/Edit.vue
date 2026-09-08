<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    admin: { type: Object, required: true },
})

const form = useForm({
    name: props.admin.name,
    email: props.admin.email,
    password: '',
    password_confirmation: '',
    active: props.admin.active,
})

const resetForm = useForm({
    password: '',
    password_confirmation: '',
})

const submit = () => form.put(route('admin.users.admins.update', props.admin.id))
const resetPassword = () => resetForm.post(route('admin.users.admins.reset-password', props.admin.id), {
    onSuccess: () => resetForm.reset(),
})
</script>

<template>
    <Head title="Editar administrador" />
    <AdminLayout>
        <h4 class="mb-3">Editar administrador</h4>
        <div class="card mb-3">
            <div class="card-body">
                <form @submit.prevent="submit">
                    <div class="row">
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
                            <label class="form-label">Nueva contraseña (opcional)</label>
                            <input v-model="form.password" type="password" class="form-control" autocomplete="new-password">
                            <div v-if="form.errors.password" class="text-danger">{{ form.errors.password }}</div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Confirmar contraseña</label>
                            <input v-model="form.password_confirmation" type="password" class="form-control" autocomplete="new-password">
                        </div>
                        <div class="mb-3 col-md-12">
                            <label class="form-check-label">
                                <input v-model="form.active" type="checkbox" class="form-check-input me-2"> Activo
                            </label>
                            <div v-if="form.errors.active" class="text-danger">{{ form.errors.active }}</div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary me-2" :disabled="form.processing">Guardar</button>
                    <Link :href="route('admin.users.admins.index')" class="btn btn-light">Cancelar</Link>
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
                        <button class="btn btn-warning" type="submit" :disabled="resetForm.processing">Restablecer</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
