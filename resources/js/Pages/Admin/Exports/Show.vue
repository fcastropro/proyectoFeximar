<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Detalle de exportación" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Detalle de exportación
            </h2>
        </template>

        <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
            <p class="text-gray-900">Detalle de la exportación</p>
        </div>
    </AdminLayout>
</template>
