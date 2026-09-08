<script setup>
import { Head } from '@inertiajs/vue3'
import FarmLayout from '@/Layouts/FarmLayout.vue'

defineProps({
    products: { type: Array, default: () => [] },
})
</script>

<template>
    <Head title="Mis productos" />

    <FarmLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Mis productos</h4>
                    <span>Productos y presentaciones de tu finca</span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Listado</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-responsive-md">
                                <thead>
                                    <tr>
                                        <th><strong>Producto</strong></th>
                                        <th><strong>Tipo</strong></th>
                                        <th><strong>Variedad</strong></th>
                                        <th><strong>Color</strong></th>
                                        <th><strong>Presentaciones</strong></th>
                                        <th><strong>Estado</strong></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="products.length === 0">
                                        <td colspan="6" class="text-center text-muted">
                                            Sin productos.
                                        </td>
                                    </tr>
                                    <tr v-for="item in products" :key="item.id">
                                        <td>{{ item.product_name || '—' }}</td>
                                        <td>{{ item.flower_type || '—' }}</td>
                                        <td>{{ item.variety_name || '—' }}</td>
                                        <td>{{ item.color || '—' }}</td>
                                        <td>
                                            <span
                                                v-for="p in item.presentations"
                                                :key="p.id"
                                                class="badge badge-primary light me-1"
                                            >
                                                {{ p.stem_length_cm }} cm
                                            </span>
                                            <span v-if="!item.presentations?.length">—</span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge"
                                                :class="item.active ? 'badge-success light' : 'badge-danger light'"
                                            >
                                                {{ item.active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </FarmLayout>
</template>
