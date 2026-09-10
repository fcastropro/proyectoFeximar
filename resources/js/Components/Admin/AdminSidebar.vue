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
                            <Link :href="route('admin.varieties.index')">Variedades</Link>
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
                    <Link :href="route('admin.cargo-agencies.index')">
                        <i class="flaticon-381-network"></i>
                        <span class="nav-text">Agencias de carga</span>
                    </Link>
                </li>

                <li>
                    <Link :href="route('admin.orders.index')">
                        <i class="flaticon-381-list"></i>
                        <span class="nav-text">Pedidos</span>
                    </Link>
                </li>

                <li>
                    <a href="javascript:void(0)" class="has-arrow">
                        <i class="flaticon-381-user"></i>
                        <span class="nav-text">Usuarios</span>
                    </a>
                    <ul>
                        <li>
                            <Link :href="route('admin.users.admins.index')">Administradores</Link>
                        </li>
                        <li>
                            <Link :href="route('admin.users.farms.index')">Usuarios de finca</Link>
                        </li>
                        <li>
                            <Link :href="route('admin.users.buyers.index')">Usuarios compradores</Link>
                        </li>
                    </ul>
                </li>

                <li>
                    <Link :href="route('admin.farm-finances.index')">
                        <i class="flaticon-381-controls-3"></i>
                        <span class="nav-text">Finanzas fincas</span>
                    </Link>
                </li>

                <li>
                    <Link :href="route('admin.exports.index')">
                        <i class="flaticon-381-export"></i>
                        <span class="nav-text">Exportaciones</span>
                    </Link>
                </li>

                <li>
                    <a href="javascript:void(0)" class="has-arrow" aria-expanded="false">
                        <i class="flaticon-381-controls-3"></i>
                        <span class="nav-text">Inteligencia de Negocios</span>
                    </a>
                    <ul aria-expanded="false">
                        <li>
                            <Link :href="route('admin.bi.executive')">Resumen Ejecutivo</Link>
                        </li>
                        <li>
                            <Link :href="route('admin.bi.sales')">Ventas y Exportaciones</Link>
                        </li>
                        <li>
                            <Link :href="route('admin.bi.trends')">Análisis y Tendencias</Link>
                        </li>
                        <li>
                            <Link :href="route('admin.bi.finance')">Finanzas</Link>
                        </li>
                        <li>
                            <Link :href="route('admin.bi.operations')">Operaciones</Link>
                        </li>
                        <li>
                            <Link :href="route('admin.bi.prediction')">Predicción de demanda</Link>
                        </li>
                    </ul>
                </li>

                <li>
                    <Link :href="route('admin.activity.index')">
                        <i class="flaticon-381-notepad"></i>
                        <span class="nav-text">Auditoría</span>
                    </Link>
                </li>

            </ul>
        </div>
    </div>
</template>
