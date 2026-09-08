<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    buyerUser: { type: Object, required: true },
    buyers: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
})

const form = useForm({
    buyer_id: props.buyerUser.buyer_id,
    name: props.buyerUser.user_name,
    email: props.buyerUser.user_email,
    role: props.buyerUser.role,
    active: props.buyerUser.active,
    credit_allowed: props.buyerUser.credit_allowed,
    credit_days_default: props.buyerUser.credit_days_default,
    password: '',
    password_confirmation: '',
})

const resetForm = useForm({ password: '', password_confirmation: '' })

const submit = () => form.put(route('admin.users.buyers.update', props.buyerUser.id))
const resetPassword = () => resetForm.post(route('admin.users.buyers.reset-password', props.buyerUser.id), {
    onSuccess: () => resetForm.reset(),
})
</script>

<template>
    <Head title="Editar usuario comprador" />
    <AdminLayout>
        <h4 class="mb-3">Editar usuario comprador</h4>
        <div class="card mb-3">
            <div class="card-body">
                <form @submit.prevent="submit">
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Empresa *</label>
                            <select v-model="form.buyer_id" class="form-control">
                                <option v-for="buyer in buyers" :key="buyer.id" :value="buyer.id">{{ buyer.company_name }}</option>
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
                        <div class="mb-3 col-md-6">
                            <label class="form-check-label">
                                <input v-model="form.credit_allowed" type="checkbox" class="form-check-input me-2"> Crédito permitido
                            </label>
                        </div>
                        <div v-if="form.credit_allowed" class="mb-3 col-md-6">
                            <label class="form-label">Días crédito</label>
                            <input v-model="form.credit_days_default" type="number" min="1" class="form-control">
                        </div>
                        <div class="mb-3 col-md-12">
                            <label class="form-check-label">
                                <input v-model="form.active" type="checkbox" class="form-check-input me-2"> Activo
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary me-2" :disabled="form.processing">Guardar</button>
                    <Link :href="route('admin.users.buyers.index')" class="btn btn-light">Cancelar</Link>
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
