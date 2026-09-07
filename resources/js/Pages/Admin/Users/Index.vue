<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Usuarios" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Usuarios
            </h2>
        </template>

        <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
            <p class="text-gray-900">Listado de usuarios</p>
        </div>
    </AdminLayout>
</template>
