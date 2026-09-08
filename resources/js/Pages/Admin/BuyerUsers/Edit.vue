<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { watch } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    buyerUser: { type: Object, required: true },
    buyers: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
})

const form = useForm({
    buyer_id: props.buyerUser.buyer_id,
    role: props.buyerUser.role,
    active: Boolean(props.buyerUser.active),
    password: '',
    credit_allowed: Boolean(props.buyerUser.credit_allowed),
    credit_days_default: props.buyerUser.credit_days_default ?? null,
})

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

const submit = () => form.put(route('admin.buyer-users.update', props.buyerUser.id))
</script>

<template>
    <Head title="Editar usuario comprador" />
    <AdminLayout>
        <h4 class="mb-3">Editar usuario comprador</h4>
        <p>{{ buyerUser.user_name }} · {{ buyerUser.user_email }}</p>
        <div class="card">
            <div class="card-body">
                <form @submit.prevent="submit">
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Comprador *</label>
                            <select v-model="form.buyer_id" class="form-control">
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
                            <label class="form-label">Nueva contraseña (opcional)</label>
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
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary me-2" :disabled="form.processing">
                        Actualizar
                    </button>
                    <Link :href="route('admin.buyer-users.index')" class="btn btn-light">Cancelar</Link>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
