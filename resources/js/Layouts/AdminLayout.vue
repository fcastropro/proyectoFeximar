<script setup>
import { onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminHeader from '@/Components/Admin/AdminHeader.vue'
import AdminSidebar from '@/Components/Admin/AdminSidebar.vue'
import AdminFooter from '@/Components/Admin/AdminFooter.vue'
import { initializeAdminTheme } from '@/admin/initializeAdminTheme'

let removeInertiaListener = null

onMounted(() => {
    initializeAdminTheme()

    removeInertiaListener = router.on('success', () => {
        if (document.getElementById('main-wrapper')) {
            initializeAdminTheme()
        }
    })
})

onUnmounted(() => {
    if (typeof removeInertiaListener === 'function') {
        removeInertiaListener()
        removeInertiaListener = null
    }
})
</script>

<template>
    <div id="main-wrapper" class="show feximar-admin">
        <div class="nav-header">
            <a href="/admin" class="brand-logo">
                <span class="feximar-brand">
                    FEXIMAR
                </span>
            </a>

            <div class="nav-control">
                <div class="hamburger">
                    <span class="line"></span>
                    <span class="line"></span>
                    <span class="line"></span>
                </div>
            </div>
        </div>

        <AdminHeader />
        <AdminSidebar />

        <div class="content-body">
            <div class="container-fluid">
                <slot />
            </div>
        </div>

        <AdminFooter />
    </div>
</template>
