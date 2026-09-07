<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    buyer: {
        type: Object,
        required: true,
    },
    countries: {
        type: Array,
        default: () => [],
    },
})

const form = useForm({
    company_name: props.buyer.company_name ?? '',
    contact_name: props.buyer.contact_name ?? '',
    email: props.buyer.email ?? '',
    phone: props.buyer.phone ?? '',
    country_id: props.buyer.country_id ?? '',
    city: props.buyer.city ?? '',
    address: props.buyer.address ?? '',
    active: Boolean(props.buyer.active),
})

const submit = () => {
    form.put(route('admin.buyers.update', props.buyer.id))
}
</script>

<template>
    <Head title="Editar Comprador" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Editar Comprador</h4>
                    <span>Actualizar datos del cliente internacional</span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Datos del comprador</h4>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Empresa <span class="text-danger">*</span></label>
                                    <input
                                        v-model="form.company_name"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.company_name }"
                                    >
                                    <div v-if="form.errors.company_name" class="invalid-feedback d-block">
                                        {{ form.errors.company_name }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Persona de contacto <span class="text-danger">*</span></label>
                                    <input
                                        v-model="form.contact_name"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.contact_name }"
                                    >
                                    <div v-if="form.errors.contact_name" class="invalid-feedback d-block">
                                        {{ form.errors.contact_name }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.email }"
                                    >
                                    <div v-if="form.errors.email" class="invalid-feedback d-block">
                                        {{ form.errors.email }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Teléfono</label>
                                    <input
                                        v-model="form.phone"
                                        type="text"
                                        maxlength="30"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.phone }"
                                    >
                                    <div v-if="form.errors.phone" class="invalid-feedback d-block">
                                        {{ form.errors.phone }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label class="form-label">País <span class="text-danger">*</span></label>
                                    <select
                                        v-model="form.country_id"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.country_id }"
                                    >
                                        <option value="">Seleccione un país</option>
                                        <option
                                            v-for="country in countries"
                                            :key="country.id"
                                            :value="country.id"
                                        >
                                            {{ country.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.country_id" class="invalid-feedback d-block">
                                        {{ form.errors.country_id }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Ciudad</label>
                                    <input
                                        v-model="form.city"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.city }"
                                    >
                                    <div v-if="form.errors.city" class="invalid-feedback d-block">
                                        {{ form.errors.city }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-12">
                                    <label class="form-label">Dirección</label>
                                    <textarea
                                        v-model="form.address"
                                        rows="3"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.address }"
                                    ></textarea>
                                    <div v-if="form.errors.address" class="invalid-feedback d-block">
                                        {{ form.errors.address }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-12">
                                    <div class="form-check">
                                        <input
                                            id="active"
                                            v-model="form.active"
                                            type="checkbox"
                                            class="form-check-input"
                                            :class="{ 'is-invalid': form.errors.active }"
                                        >
                                        <label class="form-check-label" for="active">
                                            Estado activo
                                        </label>
                                    </div>
                                    <div v-if="form.errors.active" class="invalid-feedback d-block">
                                        {{ form.errors.active }}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button
                                    type="submit"
                                    class="btn btn-primary me-2"
                                    :disabled="form.processing"
                                >
                                    Actualizar
                                </button>
                                <Link :href="route('admin.buyers.index')" class="btn btn-light">
                                    Cancelar
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
