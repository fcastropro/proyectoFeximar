<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { onMounted, ref, watch } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    countries: {
        type: Array,
        default: () => [],
    },
    defaultCountryId: {
        type: Number,
        default: null,
    },
    initialProvinces: {
        type: Array,
        default: () => [],
    },
})

const provinces = ref([...props.initialProvinces])
const cities = ref([])
const loadingProvinces = ref(false)
const loadingCities = ref(false)

const form = useForm({
    name: '',
    commercial_name: '',
    ruc: '',
    email: '',
    phone: '',
    country_id: props.defaultCountryId ?? '',
    province_id: '',
    city_id: '',
    address: '',
    description: '',
    active: true,
})

const fetchProvinces = async (countryId, { resetSelection = true } = {}) => {
    if (!countryId) {
        provinces.value = []
        cities.value = []
        if (resetSelection) {
            form.province_id = ''
            form.city_id = ''
        }
        return
    }

    loadingProvinces.value = true

    try {
        const response = await fetch(route('admin.locations.provinces', countryId), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })

        provinces.value = await response.json()
    } finally {
        loadingProvinces.value = false
    }

    if (resetSelection) {
        form.province_id = ''
        form.city_id = ''
        cities.value = []
    }
}

const fetchCities = async (provinceId, { resetSelection = true } = {}) => {
    if (!provinceId) {
        cities.value = []
        if (resetSelection) {
            form.city_id = ''
        }
        return
    }

    loadingCities.value = true

    try {
        const response = await fetch(route('admin.locations.cities', provinceId), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })

        cities.value = await response.json()
    } finally {
        loadingCities.value = false
    }

    if (resetSelection) {
        form.city_id = ''
    }
}

watch(
    () => form.country_id,
    (countryId) => {
        fetchProvinces(countryId)
    },
)

watch(
    () => form.province_id,
    (provinceId) => {
        fetchCities(provinceId)
    },
)

onMounted(() => {
    if (form.country_id && provinces.value.length === 0) {
        fetchProvinces(form.country_id, { resetSelection: false })
    }
})

const submit = () => {
    form.post(route('admin.farms.store'))
}
</script>

<template>
    <Head title="Nueva Finca" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Nueva Finca</h4>
                    <span>Registrar una finca productora</span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Datos de la finca</h4>
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
                                    <label class="form-label">Nombre comercial</label>
                                    <input
                                        v-model="form.commercial_name"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.commercial_name }"
                                    >
                                    <div v-if="form.errors.commercial_name" class="invalid-feedback d-block">
                                        {{ form.errors.commercial_name }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-4">
                                    <label class="form-label">RUC</label>
                                    <input
                                        v-model="form.ruc"
                                        type="text"
                                        maxlength="13"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.ruc }"
                                    >
                                    <div v-if="form.errors.ruc" class="invalid-feedback d-block">
                                        {{ form.errors.ruc }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Email</label>
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

                                <div class="mb-3 col-md-4">
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

                                <div class="mb-3 col-md-4">
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

                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Provincia <span class="text-danger">*</span></label>
                                    <select
                                        v-model="form.province_id"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.province_id }"
                                        :disabled="!form.country_id || loadingProvinces"
                                    >
                                        <option value="">
                                            {{ loadingProvinces ? 'Cargando...' : 'Seleccione una provincia' }}
                                        </option>
                                        <option
                                            v-for="province in provinces"
                                            :key="province.id"
                                            :value="province.id"
                                        >
                                            {{ province.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.province_id" class="invalid-feedback d-block">
                                        {{ form.errors.province_id }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Ciudad <span class="text-danger">*</span></label>
                                    <select
                                        v-model="form.city_id"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.city_id }"
                                        :disabled="!form.province_id || loadingCities"
                                    >
                                        <option value="">
                                            {{ loadingCities ? 'Cargando...' : 'Seleccione una ciudad' }}
                                        </option>
                                        <option
                                            v-for="city in cities"
                                            :key="city.id"
                                            :value="city.id"
                                        >
                                            {{ city.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.city_id" class="invalid-feedback d-block">
                                        {{ form.errors.city_id }}
                                    </div>
                                </div>

                                <div class="mb-3 col-md-12">
                                    <label class="form-label">Dirección</label>
                                    <textarea
                                        v-model="form.address"
                                        rows="2"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.address }"
                                    ></textarea>
                                    <div v-if="form.errors.address" class="invalid-feedback d-block">
                                        {{ form.errors.address }}
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
                                    Guardar
                                </button>
                                <Link :href="route('admin.farms.index')" class="btn btn-light">
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
