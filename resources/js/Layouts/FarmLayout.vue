<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import FarmSidebar from '@/Components/Farm/FarmSidebar.vue'

const page = usePage()

const farmName = computed(() => page.props.farmPortal?.name ?? 'Portal Finca')
</script>

<template>
    <AdminLayout
        :brand-href="route('farm.dashboard')"
        header-title="Portal de Fincas"
        :header-subtitle="farmName"
        :farm-mode="true"
    >
        <template #sidebar>
            <FarmSidebar />
        </template>

        <slot />
    </AdminLayout>
</template>
