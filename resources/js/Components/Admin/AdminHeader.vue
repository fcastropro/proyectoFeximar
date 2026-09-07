<script setup>
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
</script>

<template>
    <div class="header">
        <div class="header-content">
            <nav class="navbar navbar-expand">

                <div class="collapse navbar-collapse justify-content-between">

                    <div class="header-left">
                        <h4 class="mb-0">Administración FEXIMAR</h4>
                    </div>

                    <ul class="navbar-nav header-right">
                        <li class="nav-item dropdown header-profile">

                            <a
                                class="nav-link"
                                href="javascript:void(0)"
                                role="button"
                                data-bs-toggle="dropdown"
                            >
                                <div class="header-info">
                                    <span>
                                        {{ page.props.auth?.user?.name ?? 'Administrador' }}
                                    </span>
                                    <small>FEXIMAR</small>
                                </div>
                            </a>

                            <div class="dropdown-menu dropdown-menu-end">
                                <Link href="/profile" class="dropdown-item">
                                    Perfil
                                </Link>

                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    class="dropdown-item"
                                >
                                    Cerrar sesión
                                </Link>
                            </div>

                        </li>
                    </ul>

                </div>
            </nav>
        </div>
    </div>
</template>