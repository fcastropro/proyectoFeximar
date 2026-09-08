<script setup>
import { router } from '@inertiajs/vue3'
import { reactive, watch } from 'vue'

const props = defineProps({
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    routeName: { type: String, required: true },
    hideFarmFilter: { type: Boolean, default: false },
})

const form = reactive({
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    year: props.filters.year ?? '',
    month: props.filters.month ?? '',
    week: props.filters.week ?? '',
    farm_id: props.filters.farm_id ?? '',
    country_id: props.filters.country_id ?? '',
    buyer_id: props.filters.buyer_id ?? '',
    flower_type_id: props.filters.flower_type_id ?? '',
    variety_id: props.filters.variety_id ?? '',
    stem_length_cm: props.filters.stem_length_cm ?? '',
    order_status: props.filters.order_status ?? '',
    variety_filter_id: props.filters.variety_filter_id ?? '',
    shipping_method: props.filters.shipping_method ?? '',
    cargo_agency_id: props.filters.cargo_agency_id ?? '',
    payment_condition: props.filters.payment_condition ?? '',
    product_id: props.filters.product_id ?? '',
})

watch(
    () => props.filters,
    (value) => {
        Object.assign(form, {
            date_from: value.date_from ?? '',
            date_to: value.date_to ?? '',
            year: value.year ?? '',
            month: value.month ?? '',
            week: value.week ?? '',
            farm_id: value.farm_id ?? '',
            country_id: value.country_id ?? '',
            buyer_id: value.buyer_id ?? '',
            flower_type_id: value.flower_type_id ?? '',
            variety_id: value.variety_id ?? '',
            stem_length_cm: value.stem_length_cm ?? '',
            order_status: value.order_status ?? '',
            variety_filter_id: value.variety_filter_id ?? '',
            shipping_method: value.shipping_method ?? '',
            cargo_agency_id: value.cargo_agency_id ?? '',
            payment_condition: value.payment_condition ?? '',
            product_id: value.product_id ?? '',
        })
    },
    { deep: true },
)

const apply = () => {
    const query = {}
    Object.entries(form).forEach(([key, value]) => {
        if (props.hideFarmFilter && key === 'farm_id') {
            return
        }
        if (value !== '' && value !== null && value !== undefined) {
            query[key] = value
        }
    })
    router.get(route(props.routeName), query, { preserveState: true, replace: true })
}

const clear = () => {
    router.get(route(props.routeName), {}, { preserveState: false, replace: true })
}

const varieties = () => {
    if (!form.flower_type_id) {
        return props.filterOptions.varieties ?? []
    }

    return (props.filterOptions.varieties ?? []).filter(
        (item) => Number(item.flower_type_id) === Number(form.flower_type_id),
    )
}
</script>

<template>
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Filtros</h4>
        </div>
        <div class="card-body">
            <form class="row" @submit.prevent="apply">
                <div class="mb-3 col-md-3">
                    <label class="form-label">Desde</label>
                    <input v-model="form.date_from" type="date" class="form-control">
                </div>
                <div class="mb-3 col-md-3">
                    <label class="form-label">Hasta</label>
                    <input v-model="form.date_to" type="date" class="form-control">
                </div>
                <div class="mb-3 col-md-2">
                    <label class="form-label">Año</label>
                    <select v-model="form.year" class="form-control">
                        <option value="">Todos</option>
                        <option v-for="year in filterOptions.years" :key="year" :value="year">{{ year }}</option>
                    </select>
                </div>
                <div class="mb-3 col-md-2">
                    <label class="form-label">Mes</label>
                    <select v-model="form.month" class="form-control">
                        <option value="">Todos</option>
                        <option v-for="m in 12" :key="m" :value="m">{{ m }}</option>
                    </select>
                </div>
                <div class="mb-3 col-md-2">
                    <label class="form-label">Semana ISO</label>
                    <input v-model="form.week" type="number" min="1" max="53" class="form-control" placeholder="1-53">
                </div>

                <div v-if="!hideFarmFilter" class="mb-3 col-md-3">
                    <label class="form-label">Finca</label>
                    <select v-model="form.farm_id" class="form-control">
                        <option value="">Todas</option>
                        <option v-for="farm in filterOptions.farms" :key="farm.id" :value="farm.id">{{ farm.name }}</option>
                    </select>
                </div>
                <div class="mb-3 col-md-3">
                    <label class="form-label">País</label>
                    <select v-model="form.country_id" class="form-control">
                        <option value="">Todos</option>
                        <option v-for="country in filterOptions.countries" :key="country.id" :value="country.id">{{ country.name }}</option>
                    </select>
                </div>
                <div class="mb-3 col-md-3">
                    <label class="form-label">Comprador</label>
                    <select v-model="form.buyer_id" class="form-control">
                        <option value="">Todos</option>
                        <option v-for="buyer in filterOptions.buyers" :key="buyer.id" :value="buyer.id">{{ buyer.company_name }}</option>
                    </select>
                </div>
                <div class="mb-3 col-md-3">
                    <label class="form-label">Estado pedido</label>
                    <select v-model="form.order_status" class="form-control">
                        <option value="">Activos (sin cancelados)</option>
                        <option v-for="status in filterOptions.order_statuses" :key="status" :value="status">{{ status }}</option>
                    </select>
                </div>

                <div class="mb-3 col-md-3">
                    <label class="form-label">Tipo de flor</label>
                    <select v-model="form.flower_type_id" class="form-control">
                        <option value="">Todos</option>
                        <option v-for="item in filterOptions.flower_types" :key="item.id" :value="item.id">{{ item.name }}</option>
                    </select>
                </div>
                <div class="mb-3 col-md-3">
                    <label class="form-label">Variedad</label>
                    <select v-model="form.variety_id" class="form-control">
                        <option value="">Todas</option>
                        <option v-for="item in varieties()" :key="item.id" :value="item.id">{{ item.name }}</option>
                    </select>
                </div>
                <div class="mb-3 col-md-3">
                    <label class="form-label">Longitud (cm)</label>
                    <select v-model="form.stem_length_cm" class="form-control">
                        <option value="">Todas</option>
                        <option v-for="len in filterOptions.stem_lengths" :key="len" :value="len">{{ len }}</option>
                    </select>
                </div>
                <div class="mb-3 col-md-3">
                    <label class="form-label">Variedad (precio promedio)</label>
                    <select v-model="form.variety_filter_id" class="form-control">
                        <option value="">Todas</option>
                        <option v-for="item in filterOptions.varieties" :key="`avg-${item.id}`" :value="item.id">{{ item.name }}</option>
                    </select>
                </div>
                <div class="mb-3 col-md-3">
                    <label class="form-label">Producto</label>
                    <select v-model="form.product_id" class="form-control">
                        <option value="">Todos</option>
                        <option v-for="item in filterOptions.products || []" :key="item.id" :value="item.id">{{ item.name }}</option>
                    </select>
                </div>
                <div class="mb-3 col-md-3">
                    <label class="form-label">Vía transporte</label>
                    <select v-model="form.shipping_method" class="form-control">
                        <option value="">Todas</option>
                        <option v-for="method in filterOptions.shipping_methods || []" :key="method" :value="method">
                            {{ method === 'air' ? 'Aéreo' : method === 'sea' ? 'Marítimo' : method }}
                        </option>
                    </select>
                </div>
                <div class="mb-3 col-md-3">
                    <label class="form-label">Agencia de carga</label>
                    <select v-model="form.cargo_agency_id" class="form-control">
                        <option value="">Todas</option>
                        <option v-for="item in filterOptions.cargo_agencies || []" :key="item.id" :value="item.id">{{ item.name }}</option>
                    </select>
                </div>
                <div class="mb-3 col-md-3">
                    <label class="form-label">Contado / Crédito</label>
                    <select v-model="form.payment_condition" class="form-control">
                        <option value="">Todos</option>
                        <option v-for="item in filterOptions.payment_conditions || []" :key="item" :value="item">
                            {{ item === 'cash' ? 'Contado' : item === 'credit' ? 'Crédito' : item }}
                        </option>
                    </select>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Aplicar filtros</button>
                    <button type="button" class="btn btn-light" @click="clear">Limpiar filtros</button>
                </div>
            </form>
        </div>
    </div>
</template>
