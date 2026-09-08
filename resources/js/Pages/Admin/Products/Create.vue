<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    flowerTypes: {
        type: Array,
        default: () => [],
    },
})

const varieties = ref([])
const loadingVarieties = ref(false)

const imagePreview = ref(null)

const form = useForm({
    name: '',
    flower_type_id: '',
    variety_id: '',
    color: '',
    description: '',
    active: true,
    image: null,
})

const onImageChange = (event) => {
    const file = event.target.files?.[0] ?? null
    form.image = file
    if (imagePreview.value) {
        URL.revokeObjectURL(imagePreview.value)
        imagePreview.value = null
    }
    if (file) {
        imagePreview.value = URL.createObjectURL(file)
    }
}

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
        fetchVarieties(flowerTypeId)
    },
)

watch(
    () => form.variety_id,
    (varietyId) => {
        const selected = varieties.value.find((item) => item.id === Number(varietyId))
        form.color = selected?.color ?? ''
    },
)

const submit = () => {
    form.post(route('admin.products.store'), { forceFormData: true })
}
</script>

<template>
    <Head title="Nuevo Producto" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Nuevo Producto</h4>
                    <span>Registrar producto o variedad del catálogo</span>
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
                                        placeholder="Se completa al elegir la variedad"
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

                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Imagen</label>
                                    <input
                                        type="file"
                                        class="form-control"
                                        accept="image/jpeg,image/png,image/webp"
                                        :class="{ 'is-invalid': form.errors.image }"
                                        @change="onImageChange"
                                    >
                                    <div v-if="form.errors.image" class="invalid-feedback d-block">
                                        {{ form.errors.image }}
                                    </div>
                                    <div class="mt-3 product-image-preview-wrap">
                                        <img
                                            v-if="imagePreview"
                                            :src="imagePreview"
                                            alt="Vista previa"
                                            class="product-image-thumb"
                                        >
                                        <div v-else class="product-image-placeholder">
                                            <i class="fa fa-leaf"></i>
                                        </div>
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
                                    Guardar
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

<style scoped>
.product-image-preview-wrap {
    width: 140px;
    height: 140px;
    overflow: hidden;
    border-radius: 0.75rem;
}

.product-image-thumb {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.product-image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(145deg, #1a1a22 0%, #2a2a35 55%, #1e1e28 100%);
    color: rgba(215, 25, 75, 0.85);
    font-size: 2rem;
}
</style>
