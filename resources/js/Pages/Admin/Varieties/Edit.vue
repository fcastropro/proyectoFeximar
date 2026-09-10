<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    variety: { type: Object, required: true },
    flowerTypes: { type: Array, default: () => [] },
})

const form = useForm({
    flower_type_id: props.variety.flower_type_id ?? '',
    name: props.variety.name ?? '',
    color: props.variety.color ?? '',
    active: Boolean(props.variety.active),
})

const submit = () => form.put(route('admin.varieties.update', props.variety.id))
</script>

<template>
    <Head title="Editar variedad" />
    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Editar variedad</h4>
                    <span>{{ variety.name }}</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form @submit.prevent="submit">
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Tipo de flor *</label>
                            <select v-model="form.flower_type_id" class="form-control" required>
                                <option value="">Seleccione…</option>
                                <option
                                    v-for="type in flowerTypes"
                                    :key="type.id"
                                    :value="type.id"
                                >
                                    {{ type.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.flower_type_id" class="text-danger">
                                {{ form.errors.flower_type_id }}
                            </div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Nombre de variedad *</label>
                            <input v-model="form.name" type="text" class="form-control" required>
                            <div v-if="form.errors.name" class="text-danger">{{ form.errors.name }}</div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Color *</label>
                            <input v-model="form.color" type="text" class="form-control" required>
                            <div v-if="form.errors.color" class="text-danger">{{ form.errors.color }}</div>
                        </div>
                        <div class="mb-3 col-md-12">
                            <label class="form-check-label">
                                <input v-model="form.active" type="checkbox" class="form-check-input me-2">
                                Activo
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary me-2" :disabled="form.processing">
                        Actualizar
                    </button>
                    <Link :href="route('admin.varieties.index')" class="btn btn-light">
                        Cancelar
                    </Link>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
