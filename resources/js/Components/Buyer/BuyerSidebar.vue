<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<template>
    <div class="deznav">
        <div class="deznav-scroll">
            <ul class="metismenu" id="menu">

                <li class="nav-label first">Portal Comprador</li>

                <li>
                    <Link :href="route('buyer.dashboard')">
                        <i class="flaticon-381-networking"></i>
                        <span class="nav-text">Dashboard</span>
                    </Link>
                </li>

                <li>
                    <Link :href="route('buyer.catalog.index')">
                        <i class="flaticon-381-database"></i>
                        <span class="nav-text">Catálogo semanal</span>
                    </Link>
                </li>

                <li>
                    <Link :href="route('buyer.cart.index')">
                        <i class="flaticon-381-list"></i>
                        <span class="nav-text">Mi carrito</span>
                    </Link>
                </li>

                <li>
                    <Link :href="route('buyer.orders.index')">
                        <i class="flaticon-381-export"></i>
                        <span class="nav-text">Mis pedidos</span>
                    </Link>
                </li>

                <li>
                    <Link :href="route('buyer.profile')">
                        <i class="flaticon-381-user-7"></i>
                        <span class="nav-text">Mi perfil</span>
                    </Link>
                </li>

            </ul>
        </div>
    </div>
</template>
