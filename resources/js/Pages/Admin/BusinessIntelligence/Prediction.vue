<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    filters: { type: Object, default: () => ({}) },
    filterOptions: { type: Object, default: () => ({}) },
    message: { type: String, required: true },
    datasetReady: { type: Boolean, default: true },
})
</script>

<template>
    <Head title="BI · Predicción de demanda" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-8 p-md-0">
                <div class="welcome-text">
                    <h4>Predicción de demanda</h4>
                    <span>Preparación del componente de IA</span>
                </div>
            </div>
            <div class="col-sm-4 p-md-0 d-flex justify-content-sm-end gap-2">
                <Link :href="route('admin.bi.executive')" class="btn btn-outline-primary btn-sm">Resumen</Link>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <p class="mb-3">{{ message }}</p>
                <p class="text-muted">No se muestran valores inventados. El dataset histórico ya puede descargarse para entrenar un modelo futuro.</p>
                <a
                    v-if="datasetReady"
                    :href="route('admin.bi.prediction-dataset')"
                    class="btn btn-primary"
                >
                    Descargar dataset CSV
                </a>
            </div>
        </div>
    </AdminLayout>
</template>
