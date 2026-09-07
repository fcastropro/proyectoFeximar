<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<template>
    <div class="deznav">
        <div class="deznav-scroll">
            <ul class="metismenu" id="menu">

                <li class="nav-label first">FEXIMAR</li>

                <li>
                    <Link href="/admin">
                        <i class="flaticon-381-networking"></i>
                        <span class="nav-text">Dashboard</span>
                    </Link>
                </li>

                <li>
                    <a href="javascript:void(0)" class="has-arrow">
                        <i class="flaticon-381-database"></i>
                        <span class="nav-text">Comercialización</span>
                    </a>

                    <ul>
                        <li>
                            <Link :href="route('admin.farms.index')">Fincas</Link>
                        </li>
                        <li>
                            <Link :href="route('admin.products.index')">Productos</Link>
                        </li>
                        <li>
                            <Link :href="route('admin.farm-products.index')">Productos por finca</Link>
                        </li>
                        <li>
                            <Link :href="route('admin.presentations.index')">Presentaciones</Link>
                        </li>
                        <li>
                            <Link :href="route('admin.box-configs.index')">Configuración de cajas</Link>
                        </li>
                        <li>
                            <Link :href="route('admin.availabilities.index')">Disponibilidad semanal</Link>
                        </li>
                    </ul>
                </li>

                <li>
                    <Link :href="route('admin.buyers.index')">
                        <i class="flaticon-381-user-7"></i>
                        <span class="nav-text">Compradores</span>
                    </Link>
                </li>

                <li>
                    <Link :href="route('admin.orders.index')">
                        <i class="flaticon-381-list"></i>
                        <span class="nav-text">Pedidos</span>
                    </Link>
                </li>

                <li>
                    <Link href="/admin/exports">
                        <i class="flaticon-381-export"></i>
                        <span class="nav-text">Exportaciones</span>
                    </Link>
                </li>

                <li>
                    <Link href="/admin/reports">
                        <i class="flaticon-381-controls-3"></i>
                        <span class="nav-text">Inteligencia de Negocios</span>
                    </Link>
                </li>

                <li>
                    <Link href="/admin/users">
                        <i class="flaticon-381-user"></i>
                        <span class="nav-text">Usuarios</span>
                    </Link>
                </li>

            </ul>
        </div>
    </div>
</template>
