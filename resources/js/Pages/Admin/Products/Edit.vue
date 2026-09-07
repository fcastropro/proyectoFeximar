<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    flowerTypes: {
        type: Array,
        default: () => [],
    },
    initialVarieties: {
        type: Array,
        default: () => [],
    },
})

const varieties = ref([...props.initialVarieties])
const loadingVarieties = ref(false)
const skipFlowerTypeWatch = ref(true)
const skipVarietyWatch = ref(true)

const form = useForm({
    name: props.product.name ?? '',
    flower_type_id: props.product.flower_type_id ?? '',
    variety_id: props.product.variety_id ?? '',
    color: props.product.color ?? '',
    description: props.product.description ?? '',
    active: Boolean(props.product.active),
})

const fetchVarieties = async (flowerTypeId) => {
    if (!flowerTypeId) {
        varieties.value = []
        form.variety_id = ''
        form.color = ''
        return
    }

    loadingVarieties.value = true

    try {
        const response = await fetch(route('admin.catalog.varieties', flowerTypeId), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })

        varieties.value = await response.json()
    } finally {
        loadingVarieties.value = false
    }

    form.variety_id = ''
    form.color = ''
}

watch(
    () => form.flower_type_id,
    (flowerTypeId) => {
        if (skipFlowerTypeWatch.value) {
            skipFlowerTypeWatch.value = false
            return
        }

        fetchVarieties(flowerTypeId)
    },
)

watch(
    () => form.variety_id,
    (varietyId) => {
        if (skipVarietyWatch.value) {
            skipVarietyWatch.value = false
            return
        }

        const selected = varieties.value.find((item) => item.id === Number(varietyId))
        form.color = selected?.color ?? ''
    },
)

const submit = () => {
    form.put(route('admin.products.update', props.product.id))
}
</script>

<template>
    <Head title="Editar Producto" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Editar Producto</h4>
                    <span>Actualizar datos del producto</span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Datos del producto</h4>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.name }"
                                    >
                                    <div v-if="form.errors.name" class="invalid-feedback d-block">
                                        {{ form.errors.name }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Tipo de flor <span class="text-danger">*</span></label>
                                    <select
                                        v-model="form.flower_type_id"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.flower_type_id }"
                                    >
                                        <option value="">Seleccione un tipo</option>
                                        <option
                                            v-for="flowerType in flowerTypes"
                                            :key="flowerType.id"
                                            :value="flowerType.id"
                                        >
                                            {{ flowerType.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.flower_type_id" class="invalid-feedback d-block">
                                        {{ form.errors.flower_type_id }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Variedad <span class="text-danger">*</span></label>
                                    <select
                                        v-model="form.variety_id"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.variety_id }"
                                        :disabled="!form.flower_type_id || loadingVarieties"
                                    >
                                        <option value="">
                                            {{ loadingVarieties ? 'Cargando...' : 'Seleccione una variedad' }}
                                        </option>
                                        <option
                                            v-for="variety in varieties"
                                            :key="variety.id"
                                            :value="variety.id"
                                        >
                                            {{ variety.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.variety_id" class="invalid-feedback d-block">
                                        {{ form.errors.variety_id }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Color</label>
                                    <input
                                        v-model="form.color"
                                        type="text"
                                        maxlength="100"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.color }"
                                    >
                                    <div v-if="form.errors.color" class="invalid-feedback d-block">
                                        {{ form.errors.color }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-12">
                                    <label class="form-label">Descripción</label>
                                    <textarea
                                        v-model="form.description"
                                        rows="3"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.description }"
                                    ></textarea>
                                    <div v-if="form.errors.description" class="invalid-feedback d-block">
                                        {{ form.errors.description }}
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
                                <Link :href="route('admin.products.index')" class="btn btn-light">
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
