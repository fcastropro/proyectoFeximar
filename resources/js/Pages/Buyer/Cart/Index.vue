<script setup>
import { computed, reactive, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import BuyerLayout from '@/Layouts/BuyerLayout.vue'

const props = defineProps({
    items: { type: Array, default: () => [] },
    total: { type: [Number, String], default: 0 },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)
const errorMessage = computed(() => page.props.flash?.error ?? null)

const bunchEdits = reactive({})

const syncBunchEdits = () => {
    props.items.forEach((item) => {
        bunchEdits[item.id] = item.bunches
    })
}

syncBunchEdits()
watch(() => props.items, syncBunchEdits, { deep: true })

const updateBunches = (item) => {
    router.put(route('buyer.cart.items.update', item.id), {
        bunches: Number(bunchEdits[item.id]),
    })
}

const removeItem = (item) => {
    if (confirm('¿Eliminar esta línea del carrito?')) {
        router.delete(route('buyer.cart.items.destroy', item.id))
    }
}

const clearCart = () => {
    if (confirm('¿Vaciar todo el carrito?')) {
        router.delete(route('buyer.cart.clear'))
    }
}
</script>

<template>
    <Head title="Mi carrito" />

    <BuyerLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Mi carrito</h4>
                    <span>Revisa bunches y continúa al checkout</span>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex gap-2">
                <Link :href="route('buyer.catalog.index')" class="btn btn-light">
                    Seguir comprando
                </Link>
                <button
                    v-if="items.length"
                    type="button"
                    class="btn btn-danger"
                    @click="clearCart"
                >
                    Vaciar carrito
                </button>
            </div>
        </div>

        <div v-if="successMessage" class="alert alert-success alert-dismissible fade show">
            {{ successMessage }}
        </div>
        <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show">
            {{ errorMessage }}
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title">Líneas del carrito</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-responsive-md">
                        <thead>
                            <tr>
                                <th></th>
                                <th><strong>Producto</strong></th>
                                <th><strong>Variedad</strong></th>
                                <th><strong>Finca</strong></th>
                                <th><strong>Longitud</strong></th>
                                <th><strong>Bunches</strong></th>
                                <th><strong>Tallos/bunch</strong></th>
                                <th><strong>Total tallos</strong></th>
                                <th><strong>Precio/tallo</strong></th>
                                <th><strong>Subtotal</strong></th>
                                <th class="admin-actions-column"><strong>Acciones</strong></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="items.length === 0">
                                <td colspan="11" class="text-center text-muted">
                                    El carrito está vacío.
                                </td>
                            </tr>
                            <tr v-for="item in items" :key="item.id">
                                <td style="width: 64px;">
                                    <img
                                        v-if="item.image_url"
                                        :src="item.image_url"
                                        alt=""
                                        class="rounded"
                                        style="width: 48px; height: 48px; object-fit: cover;"
                                    >
                                    <div
                                        v-else
                                        class="buyer-cart-placeholder rounded"
                                    >
                                        <i class="fa fa-leaf"></i>
                                    </div>
                                </td>
                                <td>{{ item.product_name }}</td>
                                <td>{{ item.variety || '—' }}</td>
                                <td>{{ item.farm_name || '—' }}</td>
                                <td>{{ item.stem_length_cm }} cm</td>
                                <td style="min-width: 120px;">
                                    <div class="d-flex gap-1 align-items-center">
                                        <input
                                            v-model.number="bunchEdits[item.id]"
                                            type="number"
                                            class="form-control form-control-sm"
                                            min="1"
                                            style="width: 72px;"
                                        >
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-success"
                                            @click="updateBunches(item)"
                                        >
                                            OK
                                        </button>
                                    </div>
                                </td>
                                <td>{{ item.stems_per_bunch }}</td>
                                <td>{{ item.total_stems }}</td>
                                <td>{{ Number(item.price_per_stem).toFixed(4) }}</td>
                                <td>{{ Number(item.subtotal).toFixed(2) }}</td>
                                <td class="admin-actions-column">
                                    <button
                                        type="button"
                                        class="btn btn-danger btn-xs"
                                        @click="removeItem(item)"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <p class="mb-0">
                        <strong>Total:</strong> {{ Number(total).toFixed(2) }} USD
                    </p>
                    <Link
                        v-if="items.length"
                        :href="route('buyer.checkout')"
                        class="btn btn-primary"
                    >
                        Continuar al checkout
                    </Link>
                </div>
            </div>
        </div>
    </BuyerLayout>
</template>

<style scoped>
.buyer-cart-placeholder {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(145deg, #1a1a22 0%, #2a2a35 55%, #1e1e28 100%);
    color: rgba(215, 25, 75, 0.85);
    font-size: 1rem;
}
</style>
