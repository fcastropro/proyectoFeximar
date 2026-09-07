<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    boxConfig: {
        type: Object,
        required: true,
    },
    presentations: {
        type: Array,
        default: () => [],
    },
    boxTypes: {
        type: Array,
        default: () => [],
    },
})

const form = useForm({
    farm_product_presentation_id: props.boxConfig.farm_product_presentation_id ?? '',
    box_type_id: props.boxConfig.box_type_id ?? '',
    stems_per_box: props.boxConfig.stems_per_box ?? '',
    bunches_per_box: props.boxConfig.bunches_per_box ?? '',
    active: Boolean(props.boxConfig.active),
})

const submit = () => {
    form.put(route('admin.box-configs.update', props.boxConfig.id))
}
</script>

<template>
    <Head title="Editar configuración de caja" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Editar configuración de caja</h4>
                    <span>Actualizar tallos por caja según presentación y tipo</span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Datos de empaque</h4>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <div class="row">
                                <div class="mb-3 col-md-12">
                                    <label class="form-label">Presentación <span class="text-danger">*</span></label>
                                    <select
                                        v-model="form.farm_product_presentation_id"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.farm_product_presentation_id }"
                                    >
                                        <option value="">Seleccione una presentación</option>
                                        <option
                                            v-for="presentation in presentations"
                                            :key="presentation.id"
                                            :value="presentation.id"
                                        >
                                            {{ presentation.label }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.farm_product_presentation_id" class="invalid-feedback d-block">
                                        {{ form.errors.farm_product_presentation_id }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Tipo de caja <span class="text-danger">*</span></label>
                                    <select
                                        v-model="form.box_type_id"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.box_type_id }"
                                    >
                                        <option value="">Seleccione</option>
                                        <option
                                            v-for="boxType in boxTypes"
                                            :key="boxType.id"
                                            :value="boxType.id"
                                        >
                                            {{ boxType.code }} — {{ boxType.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.box_type_id" class="invalid-feedback d-block">
                                        {{ form.errors.box_type_id }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Tallos por caja <span class="text-danger">*</span></label>
                                    <input
                                        v-model="form.stems_per_box"
                                        type="number"
                                        min="1"
                                        step="1"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.stems_per_box }"
                                    >
                                    <div v-if="form.errors.stems_per_box" class="invalid-feedback d-block">
                                        {{ form.errors.stems_per_box }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Bunches por caja</label>
                                    <input
                                        v-model="form.bunches_per_box"
                                        type="number"
                                        min="1"
                                        step="1"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.bunches_per_box }"
                                    >
                                    <div v-if="form.errors.bunches_per_box" class="invalid-feedback d-block">
                                        {{ form.errors.bunches_per_box }}
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
                                <Link :href="route('admin.box-configs.index')" class="btn btn-light">
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
