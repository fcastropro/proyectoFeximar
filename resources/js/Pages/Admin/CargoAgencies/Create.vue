<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const form = useForm({
    name: '',
    code: '',
    contact_name: '',
    phone: '',
    email: '',
    active: true,
})

const submit = () => form.post(route('admin.cargo-agencies.store'))
</script>

<template>
    <Head title="Nueva agencia de carga" />
    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Nueva agencia de carga</h4>
                    <span>Registrar agencia para logística de pedidos</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form @submit.prevent="submit">
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Nombre *</label>
                            <input v-model="form.name" type="text" class="form-control" required>
                            <div v-if="form.errors.name" class="text-danger">{{ form.errors.name }}</div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Código</label>
                            <input v-model="form.code" type="text" class="form-control">
                            <div v-if="form.errors.code" class="text-danger">{{ form.errors.code }}</div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Contacto</label>
                            <input v-model="form.contact_name" type="text" class="form-control">
                            <div v-if="form.errors.contact_name" class="text-danger">{{ form.errors.contact_name }}</div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input v-model="form.phone" type="text" class="form-control">
                            <div v-if="form.errors.phone" class="text-danger">{{ form.errors.phone }}</div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Email</label>
                            <input v-model="form.email" type="email" class="form-control">
                            <div v-if="form.errors.email" class="text-danger">{{ form.errors.email }}</div>
                        </div>
                        <div class="mb-3 col-md-12">
                            <label class="form-check-label">
                                <input v-model="form.active" type="checkbox" class="form-check-input me-2">
                                Activo
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary me-2" :disabled="form.processing">
                        Guardar
                    </button>
                    <Link :href="route('admin.cargo-agencies.index')" class="btn btn-light">
                        Cancelar
                    </Link>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
