<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import FarmLayout from '@/Layouts/FarmLayout.vue'

defineProps({
    availabilities: { type: Array, default: () => [] },
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)

const destroy = (item) => {
    if (confirm('¿Eliminar esta disponibilidad?')) {
        router.delete(route('farm.availabilities.destroy', item.id))
    }
}
</script>

<template>
    <Head title="Disponibilidad semanal" />

    <FarmLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Disponibilidad semanal</h4>
                    <span>Tallos y precios de tu finca por semana</span>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <Link :href="route('farm.availabilities.create')" class="btn btn-primary">
                    Nueva disponibilidad
                </Link>
            </div>
        </div>

        <div v-if="successMessage" class="alert alert-success alert-dismissible fade show">
            {{ successMessage }}
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
                                        <th><strong>Semana</strong></th>
                                        <th><strong>Producto</strong></th>
                                        <th><strong>Variedad</strong></th>
                                        <th><strong>Longitud</strong></th>
                                        <th><strong>Disponibles</strong></th>
                                        <th><strong>Reservados</strong></th>
                                        <th><strong>Restantes</strong></th>
                                        <th><strong>Precio tallo</strong></th>
                                        <th><strong>Estado</strong></th>
                                        <th class="admin-actions-column"><strong>Acciones</strong></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="availabilities.length === 0">
                                        <td colspan="10" class="text-center text-muted">
                                            Sin registros.
                                        </td>
                                    </tr>
                                    <tr v-for="item in availabilities" :key="item.id">
                                        <td>{{ item.week_number }}/{{ item.year }}</td>
                                        <td>{{ item.product_name || '—' }}</td>
                                        <td>{{ item.variety_name || '—' }}</td>
                                        <td>{{ item.stem_length_cm }} cm</td>
                                        <td>{{ item.available_stems }}</td>
                                        <td>{{ item.reserved_stems }}</td>
                                        <td>{{ item.remaining_stems }}</td>
                                        <td>{{ item.price_per_stem ?? '—' }}</td>
                                        <td>
                                            <span
                                                class="badge"
                                                :class="item.active ? 'badge-success light' : 'badge-danger light'"
                                            >
                                                {{ item.active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td class="admin-actions">
                                            <Link
                                                :href="route('farm.availabilities.edit', item.id)"
                                                class="btn btn-sm btn-success"
                                            >
                                                Editar
                                            </Link>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-danger"
                                                @click="destroy(item)"
                                            >
                                                Eliminar
                                            </button>
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
