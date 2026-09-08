<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    active: true,
})

const submit = () => form.post(route('admin.users.admins.store'))
</script>

<template>
    <Head title="Nuevo administrador" />
    <AdminLayout>
        <h4 class="mb-3">Nuevo administrador FEXIMAR</h4>
        <div class="card">
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
                            <label class="form-label">Contraseña *</label>
                            <input v-model="form.password" type="password" class="form-control" autocomplete="new-password">
                            <div v-if="form.errors.password" class="text-danger">{{ form.errors.password }}</div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Confirmar contraseña *</label>
                            <input v-model="form.password_confirmation" type="password" class="form-control" autocomplete="new-password">
                        </div>
                        <div class="mb-3 col-md-12">
                            <label class="form-check-label">
                                <input v-model="form.active" type="checkbox" class="form-check-input me-2"> Activo
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary me-2" :disabled="form.processing">Guardar</button>
                    <Link :href="route('admin.users.admins.index')" class="btn btn-light">Cancelar</Link>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
