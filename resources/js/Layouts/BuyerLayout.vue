<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import BuyerSidebar from '@/Components/Buyer/BuyerSidebar.vue'

const page = usePage()

const buyerName = computed(() => page.props.buyerPortal?.name ?? 'Portal Comprador')
</script>

<template>
    <AdminLayout
        :brand-href="route('buyer.dashboard')"
        header-title="Portal Comprador"
        :header-subtitle="buyerName"
        :buyer-mode="true"
    >
        <template #sidebar>
            <BuyerSidebar />
        </template>

        <slot />
    </AdminLayout>
</template>
