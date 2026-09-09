<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

defineProps({
    status: {
        type: String,
    },
})

const form = useForm({
    email: '',
})

const submit = () => {
    form.post(route('password.email'))
}
</script>

<template>
    <GuestLayout>
        <Head title="Recuperar contraseña · FEXIMAR" />

        <h1 class="mb-2 text-xl font-semibold text-gray-900">¿Olvidaste tu contraseña?</h1>
        <p class="mb-4 text-sm text-gray-600">
            Ingresa el correo de tu cuenta FEXIMAR y te enviaremos un enlace seguro para restablecerla.
        </p>

        <div
            v-if="status"
            class="mb-4 rounded border border-green-200 bg-green-50 px-3 py-2 text-sm font-medium text-green-700"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Correo electrónico" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-6 flex flex-col gap-3">
                <PrimaryButton
                    class="w-full justify-center"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Enviar enlace de recuperación
                </PrimaryButton>

                <Link
                    :href="route('login')"
                    class="text-center text-sm font-medium text-[#D7194B] underline hover:text-[#b0143d]"
                >
                    Volver al inicio de sesión
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
