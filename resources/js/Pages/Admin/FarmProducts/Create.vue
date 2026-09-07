<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    farms: {
        type: Array,
        default: () => [],
    },
    products: {
        type: Array,
        default: () => [],
    },
})

const form = useForm({
    farm_id: '',
    product_id: '',
    active: true,
})

const submit = () => {
    form.post(route('admin.farm-products.store'))
}
</script>

<template>
    <Head title="Nueva asociación" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Nueva asociación</h4>
                    <span>Asociar un producto del catálogo a una finca</span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Datos de la asociación</h4>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Finca <span class="text-danger">*</span></label>
                                    <select
                                        v-model="form.farm_id"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.farm_id }"
                                    >
                                        <option value="">Seleccione una finca</option>
                                        <option
                                            v-for="farm in farms"
                                            :key="farm.id"
                                            :value="farm.id"
                                        >
                                            {{ farm.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.farm_id" class="invalid-feedback d-block">
                                        {{ form.errors.farm_id }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Producto <span class="text-danger">*</span></label>
                                    <select
                                        v-model="form.product_id"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.product_id }"
                                    >
                                        <option value="">Seleccione un producto</option>
                                        <option
                                            v-for="product in products"
                                            :key="product.id"
                                            :value="product.id"
                                        >
                                            {{ product.label }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.product_id" class="invalid-feedback d-block">
                                        {{ form.errors.product_id }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-12">
                                    <div class="form-check">
                                        <input
                                            id="active"
                                            v-model="form.active"
                                            type="checkbox"
                                            class="form-check-input"
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
                                    Guardar
                                </button>
                                <Link :href="route('admin.farm-products.index')" class="btn btn-light">
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
