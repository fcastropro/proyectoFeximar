<script setup>
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
    farmsCount: {
        type: Number,
        default: 0,
    },
    productsCount: {
        type: Number,
        default: 0,
    },
    buyersCount: {
        type: Number,
        default: 0,
    },
    ordersCount: {
        type: Number,
        default: 0,
    },
})
</script>

<template>
    <Head title="Panel Administrativo FEXIMAR" />

    <AdminLayout>
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Panel Administrativo</h4>
                    <span>
                        Sistema de Gestión y Análisis de Exportaciones Florícolas
                    </span>
                </div>
            </div>
        </div>

        <div class="row">

            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-primary">
                    <div class="card-body p-4">
                        <p class="mb-1 text-white">Fincas</p>
                        <h3 class="text-white">{{ farmsCount }}</h3>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-success">
                    <div class="card-body p-4">
                        <p class="mb-1 text-white">Productos</p>
                        <h3 class="text-white">{{ productsCount }}</h3>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-warning">
                    <div class="card-body p-4">
                        <p class="mb-1 text-white">Compradores</p>
                        <h3 class="text-white">{{ buyersCount }}</h3>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card bg-info">
                    <div class="card-body p-4">
                        <p class="mb-1 text-white">Pedidos</p>
                        <h3 class="text-white">{{ ordersCount }}</h3>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
