<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    farm: {
        type: Object,
        required: true,
    },
    countries: {
        type: Array,
        default: () => [],
    },
    initialProvinces: {
        type: Array,
        default: () => [],
    },
    initialCities: {
        type: Array,
        default: () => [],
    },
})

const provinces = ref([...props.initialProvinces])
const cities = ref([...props.initialCities])
const loadingProvinces = ref(false)
const loadingCities = ref(false)
const skipCountryWatch = ref(true)
const skipProvinceWatch = ref(true)

const form = useForm({
    name: props.farm.name ?? '',
    commercial_name: props.farm.commercial_name ?? '',
    ruc: props.farm.ruc ?? '',
    email: props.farm.email ?? '',
    phone: props.farm.phone ?? '',
    country_id: props.farm.country_id ?? '',
    province_id: props.farm.province_id ?? '',
    city_id: props.farm.city_id ?? '',
    address: props.farm.address ?? '',
    description: props.farm.description ?? '',
    active: Boolean(props.farm.active),
})

const fetchProvinces = async (countryId) => {
    if (!countryId) {
        provinces.value = []
        cities.value = []
        form.province_id = ''
        form.city_id = ''
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

    form.province_id = ''
    form.city_id = ''
    cities.value = []
}

const fetchCities = async (provinceId) => {
    if (!provinceId) {
        cities.value = []
        form.city_id = ''
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

    form.city_id = ''
}

watch(
    () => form.country_id,
    (countryId) => {
        if (skipCountryWatch.value) {
            skipCountryWatch.value = false
            return
        }

        fetchProvinces(countryId)
    },
)

watch(
    () => form.province_id,
    (provinceId) => {
        if (skipProvinceWatch.value) {
            skipProvinceWatch.value = false
            return
        }

        fetchCities(provinceId)
    },
)

const submit = () => {
    form.put(route('admin.farms.update', props.farm.id))
}
</script>

<template>
    <Head title="Editar Finca" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Editar Finca</h4>
                    <span>Actualizar datos de la finca</span>
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
                                    Actualizar
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
