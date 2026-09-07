<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    presentation: {
        type: Object,
        required: true,
    },
    farmProducts: {
        type: Array,
        default: () => [],
    },
})

const form = useForm({
    farm_product_id: props.presentation.farm_product_id ?? '',
    stem_length_cm: props.presentation.stem_length_cm ?? '',
    stems_per_bunch: props.presentation.stems_per_bunch ?? '',
    price_per_stem: props.presentation.price_per_stem ?? '',
    price_per_bunch: props.presentation.price_per_bunch ?? '',
    active: Boolean(props.presentation.active),
})

const submit = () => {
    form.put(route('admin.presentations.update', props.presentation.id))
}
</script>

<template>
    <Head title="Editar presentación" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Editar presentación</h4>
                    <span>Actualizar longitud y precios base</span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Datos de la presentación</h4>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <div class="row">
                                <div class="mb-3 col-md-8">
                                    <label class="form-label">Producto por finca <span class="text-danger">*</span></label>
                                    <select
                                        v-model="form.farm_product_id"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.farm_product_id }"
                                    >
                                        <option value="">Seleccione producto por finca</option>
                                        <option
                                            v-for="item in farmProducts"
                                            :key="item.id"
                                            :value="item.id"
                                        >
                                            {{ item.label }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.farm_product_id" class="invalid-feedback d-block">
                                        {{ form.errors.farm_product_id }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Longitud del tallo (cm) <span class="text-danger">*</span></label>
                                    <input
                                        v-model="form.stem_length_cm"
                                        type="number"
                                        min="20"
                                        max="200"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.stem_length_cm }"
                                    >
                                    <div v-if="form.errors.stem_length_cm" class="invalid-feedback d-block">
                                        {{ form.errors.stem_length_cm }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Tallos por bunch</label>
                                    <input
                                        v-model="form.stems_per_bunch"
                                        type="number"
                                        min="1"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.stems_per_bunch }"
                                    >
                                    <div v-if="form.errors.stems_per_bunch" class="invalid-feedback d-block">
                                        {{ form.errors.stems_per_bunch }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Precio base por tallo</label>
                                    <input
                                        v-model="form.price_per_stem"
                                        type="number"
                                        min="0"
                                        step="0.0001"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.price_per_stem }"
                                    >
                                    <div v-if="form.errors.price_per_stem" class="invalid-feedback d-block">
                                        {{ form.errors.price_per_stem }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Precio base por bunch</label>
                                    <input
                                        v-model="form.price_per_bunch"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.price_per_bunch }"
                                    >
                                    <div v-if="form.errors.price_per_bunch" class="invalid-feedback d-block">
                                        {{ form.errors.price_per_bunch }}
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
                                    Actualizar
                                </button>
                                <Link :href="route('admin.presentations.index')" class="btn btn-light">
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
