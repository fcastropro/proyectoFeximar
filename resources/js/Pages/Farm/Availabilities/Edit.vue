<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import FarmLayout from '@/Layouts/FarmLayout.vue'

const props = defineProps({
    availability: { type: Object, required: true },
    presentations: { type: Array, default: () => [] },
})

const form = useForm({
    farm_product_presentation_id: props.availability.farm_product_presentation_id,
    year: props.availability.year,
    week_number: props.availability.week_number,
    available_stems: props.availability.available_stems,
    price_per_stem: props.availability.price_per_stem ?? '',
    price_per_bunch: props.availability.price_per_bunch ?? '',
    active: Boolean(props.availability.active),
})

const submit = () => form.put(route('farm.availabilities.update', props.availability.id))
</script>

<template>
    <Head title="Editar disponibilidad" />

    <FarmLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Editar disponibilidad</h4>
                    <span>Reservados actuales: {{ availability.reserved_stems }}</span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Datos de disponibilidad</h4>
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
                                        <option
                                            v-for="p in presentations"
                                            :key="p.id"
                                            :value="p.id"
                                        >
                                            {{ p.label }}
                                        </option>
                                    </select>
                                    <div
                                        v-if="form.errors.farm_product_presentation_id"
                                        class="invalid-feedback d-block"
                                    >
                                        {{ form.errors.farm_product_presentation_id }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Año <span class="text-danger">*</span></label>
                                    <input v-model="form.year" type="number" class="form-control">
                                </div>

                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Semana <span class="text-danger">*</span></label>
                                    <input
                                        v-model="form.week_number"
                                        type="number"
                                        min="1"
                                        max="53"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.week_number }"
                                    >
                                    <div v-if="form.errors.week_number" class="invalid-feedback d-block">
                                        {{ form.errors.week_number }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Tallos disponibles <span class="text-danger">*</span></label>
                                    <input
                                        v-model="form.available_stems"
                                        type="number"
                                        min="0"
                                        class="form-control"
                                    >
                                </div>

                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Precio por tallo</label>
                                    <input
                                        v-model="form.price_per_stem"
                                        type="number"
                                        min="0"
                                        step="0.0001"
                                        class="form-control"
                                    >
                                </div>

                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Precio por bunch</label>
                                    <input
                                        v-model="form.price_per_bunch"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="form-control"
                                    >
                                </div>

                                <div class="mb-3 col-md-12">
                                    <div class="form-check">
                                        <input
                                            id="active"
                                            v-model="form.active"
                                            type="checkbox"
                                            class="form-check-input"
                                        >
                                        <label class="form-check-label" for="active">Activo</label>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary me-2" :disabled="form.processing">
                                Actualizar
                            </button>
                            <Link :href="route('farm.availabilities.index')" class="btn btn-light">
                                Cancelar
                            </Link>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </FarmLayout>
</template>
