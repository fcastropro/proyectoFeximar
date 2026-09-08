<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import { onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

onMounted(() => {
    router.visit(route('admin.bi.executive'), { replace: true })
})
</script>

<template>
    <Head title="Reportes" />
    <AdminLayout>
        <div class="card">
            <div class="card-body">Redirigiendo a Inteligencia de Negocios…</div>
        </div>
    </AdminLayout>
</template>
