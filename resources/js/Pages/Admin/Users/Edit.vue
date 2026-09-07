<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Editar usuario" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Editar usuario
            </h2>
        </template>

        <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
            <p class="text-gray-900">Formulario de edición de usuario</p>
        </div>
    </AdminLayout>
</template>
