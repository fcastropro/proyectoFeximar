<script setup>
import { computed, reactive, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import BuyerLayout from '@/Layouts/BuyerLayout.vue'

const props = defineProps({
    items: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)

const filterForm = reactive({
    year: props.filters.year ?? '',
    week: props.filters.week ?? '',
    flower_type_id: props.filters.flower_type_id ?? '',
    variety_id: props.filters.variety_id ?? '',
    farm_id: props.filters.farm_id ?? '',
    stem_length_cm: props.filters.stem_length_cm ?? '',
    search: props.filters.search ?? '',
})

const selections = reactive({})

const ensureSelection = (item) => {
    if (!selections[item.availability_id]) {
        selections[item.availability_id] = { bunches: 1 }
    }
    return selections[item.availability_id]
}

props.items.forEach(ensureSelection)

watch(
    () => props.items,
    (items) => {
        items.forEach(ensureSelection)
    },
    { deep: true },
)

const filteredVarieties = computed(() => {
    const varieties = props.filterOptions.varieties ?? []
    if (!filterForm.flower_type_id) {
        return varieties
    }
    return varieties.filter(
        (v) => Number(v.flower_type_id) === Number(filterForm.flower_type_id),
    )
})

const applyFilters = () => {
    router.get(route('buyer.catalog.index'), { ...filterForm }, {
        preserveState: true,
        replace: true,
    })
}

const previewStems = (item) => {
    const bunches = Number(selections[item.availability_id]?.bunches || 0)
    const stemsPerBunch = Number(item.stems_per_bunch || 0)
    if (!bunches || !stemsPerBunch) return 0
    return bunches * stemsPerBunch
}

const previewSubtotal = (item) => {
    const stems = previewStems(item)
    if (!stems || item.price_per_stem == null) return null
    return stems * Number(item.price_per_stem)
}

const addToCart = (item) => {
    const sel = selections[item.availability_id]
    if (!sel?.bunches) return

    router.post(route('buyer.cart.store'), {
        farm_product_availability_id: item.availability_id,
        bunches: sel.bunches,
    })
}
</script>

<template>
    <Head title="Catálogo semanal" />

    <BuyerLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Catálogo semanal</h4>
                    <span>Disponibilidad activa para comprar por bunches</span>
                </div>
            </div>
        </div>

        <div v-if="successMessage" class="alert alert-success alert-dismissible fade show">
            {{ successMessage }}
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title">Filtros</h4>
            </div>
            <div class="card-body">
                <form class="row g-3" @submit.prevent="applyFilters">
                    <div class="col-md-2">
                        <label class="form-label">Año</label>
                        <input v-model="filterForm.year" type="number" class="form-control" min="2020">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Semana</label>
                        <input v-model="filterForm.week" type="number" class="form-control" min="1" max="53">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tipo de flor</label>
                        <select v-model="filterForm.flower_type_id" class="form-control">
                            <option value="">Todos</option>
                            <option
                                v-for="ft in filterOptions.flower_types"
                                :key="ft.id"
                                :value="ft.id"
                            >
                                {{ ft.name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Variedad</label>
                        <select v-model="filterForm.variety_id" class="form-control">
                            <option value="">Todas</option>
                            <option
                                v-for="v in filteredVarieties"
                                :key="v.id"
                                :value="v.id"
                            >
                                {{ v.name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Finca</label>
                        <select v-model="filterForm.farm_id" class="form-control">
                            <option value="">Todas</option>
                            <option
                                v-for="farm in filterOptions.farms"
                                :key="farm.id"
                                :value="farm.id"
                            >
                                {{ farm.name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Longitud (cm)</label>
                        <select v-model="filterForm.stem_length_cm" class="form-control">
                            <option value="">Todas</option>
                            <option
                                v-for="len in filterOptions.stem_lengths"
                                :key="len"
                                :value="len"
                            >
                                {{ len }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Buscar</label>
                        <input
                            v-model="filterForm.search"
                            type="text"
                            class="form-control"
                            placeholder="Producto o variedad"
                        >
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="items.length === 0" class="card">
            <div class="card-body text-center text-muted py-5">
                No hay disponibilidad para los filtros seleccionados.
            </div>
        </div>

        <div class="row">
            <div
                v-for="item in items"
                :key="item.availability_id"
                class="col-xl-4 col-lg-6 col-md-6"
            >
                <div class="card buyer-catalog-card">
                    <div class="buyer-catalog-media">
                        <img
                            v-if="item.image_url"
                            :src="item.image_url"
                            :alt="item.product_name"
                            class="buyer-catalog-image"
                        >
                        <div v-else class="buyer-catalog-placeholder">
                            <i class="fa fa-leaf"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="mb-1">{{ item.product_name }}</h5>
                        <p class="mb-1 text-muted small">
                            {{ item.flower_type || '—' }} · {{ item.variety || '—' }}
                        </p>
                        <p class="mb-1 small">
                            <strong>Finca:</strong> {{ item.farm_name || '—' }}
                        </p>
                        <p class="mb-1 small">
                            <strong>Longitud:</strong> {{ item.stem_length_cm }} cm
                        </p>
                        <p class="mb-1 small">
                            <strong>Semana:</strong> {{ item.week_number }}/{{ item.year }}
                        </p>
                        <p class="mb-1 small">
                            <strong>Disponibles:</strong> {{ item.effective_stems }} tallos
                        </p>
                        <p class="mb-1 small">
                            <strong>Tallos/bunch:</strong> {{ item.stems_per_bunch }}
                        </p>
                        <p class="mb-3 small">
                            <strong>Precio/tallo:</strong>
                            <span v-if="item.price_per_stem != null">
                                {{ Number(item.price_per_stem).toFixed(4) }} USD
                            </span>
                            <span v-else>—</span>
                        </p>

                        <div class="mb-2">
                            <label class="form-label">Bunches</label>
                            <input
                                v-model.number="ensureSelection(item).bunches"
                                type="number"
                                class="form-control"
                                min="1"
                            >
                        </div>

                        <p class="mb-3 small text-muted">
                            Vista previa:
                            {{ previewStems(item) }} tallos
                            <span v-if="previewSubtotal(item) != null">
                                · {{ previewSubtotal(item).toFixed(2) }} USD
                            </span>
                            <span v-else> · —</span>
                        </p>

                        <button
                            type="button"
                            class="btn btn-primary w-100"
                            @click="addToCart(item)"
                        >
                            Agregar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </BuyerLayout>
</template>

<style scoped>
.buyer-catalog-media {
    height: 180px;
    overflow: hidden;
    border-radius: 0.75rem 0.75rem 0 0;
}

.buyer-catalog-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.buyer-catalog-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(145deg, #1a1a22 0%, #2a2a35 55%, #1e1e28 100%);
    color: rgba(215, 25, 75, 0.85);
    font-size: 2.5rem;
}
</style>
