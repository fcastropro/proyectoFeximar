<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { watch } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    buyers: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
})

const form = useForm({
    buyer_id: '',
    name: '',
    email: '',
    password: '',
    role: 'buyer',
    active: true,
    credit_allowed: false,
    credit_days_default: null,
})

watch(
    () => form.buyer_id,
    (buyerId) => {
        const buyer = props.buyers.find((b) => Number(b.id) === Number(buyerId))
        if (!buyer) return
        form.credit_allowed = Boolean(buyer.credit_allowed)
        form.credit_days_default = buyer.credit_days_default ?? null
    },
)

watch(
    () => form.credit_allowed,
    (allowed) => {
        if (!allowed) {
            form.credit_days_default = null
        } else if (!form.credit_days_default) {
            form.credit_days_default = 30
        }
    },
)

const submit = () => form.post(route('admin.users.buyers.store'))
</script>

<template>
    <Head title="Nuevo usuario comprador" />
    <AdminLayout>
        <h4 class="mb-3">Nuevo usuario comprador</h4>
        <div class="card">
            <div class="card-body">
                <form @submit.prevent="submit">
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Comprador *</label>
                            <select v-model="form.buyer_id" class="form-control">
                                <option value="">Seleccione</option>
                                <option
                                    v-for="buyer in buyers"
                                    :key="buyer.id"
                                    :value="buyer.id"
                                >
                                    {{ buyer.company_name }}
                                </option>
                            </select>
                            <div v-if="form.errors.buyer_id" class="text-danger">{{ form.errors.buyer_id }}</div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Rol *</label>
                            <select v-model="form.role" class="form-control">
                                <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                            </select>
                            <div v-if="form.errors.role" class="text-danger">{{ form.errors.role }}</div>
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
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Días de crédito</label>
                            <input
                                v-model="form.credit_days_default"
                                type="number"
                                class="form-control"
                                min="1"
                                max="365"
                                :disabled="!form.credit_allowed"
                            >
                            <div v-if="form.errors.credit_days_default" class="text-danger">
                                {{ form.errors.credit_days_default }}
                            </div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-check-label">
                                <input v-model="form.active" type="checkbox" class="form-check-input me-2">
                                Activo
                            </label>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-check-label">
                                <input
                                    v-model="form.credit_allowed"
                                    type="checkbox"
                                    class="form-check-input me-2"
                                >
                                Crédito permitido
                            </label>
                            <div v-if="form.errors.credit_allowed" class="text-danger">
                                {{ form.errors.credit_allowed }}
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary me-2" :disabled="form.processing">
                        Guardar
                    </button>
                    <Link :href="route('admin.users.buyers.index')" class="btn btn-light">Cancelar</Link>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
