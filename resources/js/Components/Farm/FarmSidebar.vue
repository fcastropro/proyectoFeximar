<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<template>
    <div class="deznav">
        <div class="deznav-scroll">
            <ul class="metismenu" id="menu">

                <li class="nav-label first">Portal Finca</li>

                <li>
                    <Link :href="route('farm.dashboard')">
                        <i class="flaticon-381-networking"></i>
                        <span class="nav-text">Dashboard</span>
                    </Link>
                </li>

                <li>
                    <Link :href="route('farm.profile')">
                        <i class="flaticon-381-user-7"></i>
                        <span class="nav-text">Mi finca</span>
                    </Link>
                </li>

                <li>
                    <Link :href="route('farm.products.index')">
                        <i class="flaticon-381-database"></i>
                        <span class="nav-text">Mis productos</span>
                    </Link>
                </li>

                <li>
                    <Link :href="route('farm.availabilities.index')">
                        <i class="flaticon-381-export"></i>
                        <span class="nav-text">Disponibilidad semanal</span>
                    </Link>
                </li>

                <li>
                    <Link :href="route('farm.orders.index')">
                        <i class="flaticon-381-list"></i>
                        <span class="nav-text">Pedidos</span>
                    </Link>
                </li>

                <li>
                    <Link :href="route('farm.payments.index')">
                        <i class="flaticon-381-controls-3"></i>
                        <span class="nav-text">Pagos</span>
                    </Link>
                </li>

                <li>
                    <a href="javascript:void(0)" class="has-arrow" aria-expanded="false">
                        <i class="flaticon-381-networking"></i>
                        <span class="nav-text">Inteligencia de Negocios</span>
                    </a>
                    <ul aria-expanded="false">
                        <li>
                            <Link :href="route('farm.bi.executive')">Resumen</Link>
                        </li>
                        <li>
                            <Link :href="route('farm.bi.sales')">Ventas</Link>
                        </li>
                        <li>
                            <Link :href="route('farm.bi.trends')">Tendencias</Link>
                        </li>
                    </ul>
                </li>

            </ul>
        </div>
    </div>
</template>
