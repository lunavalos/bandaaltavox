<script setup>
import { watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const form = useForm({
    title: '',
    url: '',
    type: 'video',
    format: 'landscape',
    platform: 'youtube',
    is_active: true,
    sort_order: 0,
});

// Auto-detect platform, type and format from URL
watch(() => form.url, (url) => {
    if (!url) return;
    if (url.includes('youtube.com') || url.includes('youtu.be')) {
        form.platform = 'youtube';
        form.type = 'video';
        // YouTube Shorts are portrait
        form.format = url.includes('/shorts/') ? 'portrait' : 'landscape';
    } else if (url.includes('facebook.com') || url.includes('fb.watch')) {
        form.platform = 'facebook';
        const isVideo = url.includes('/videos/') || url.includes('/reel/') || url.includes('fb.watch');
        form.type = isVideo ? 'video' : 'photo';
        form.format = url.includes('/reel/') ? 'portrait' : 'landscape';
    } else if (url.includes('instagram.com')) {
        form.platform = 'instagram';
        form.type = url.includes('/reel/') ? 'video' : 'photo';
        form.format = url.includes('/reel/') ? 'portrait' : 'square';
    } else {
        form.platform = 'other';
        form.type = /\.(jpg|jpeg|png|gif|webp)(\?|$)/i.test(url) ? 'photo' : 'video';
    }
});

const submit = () => form.post(route('admin.gallery.store'));
</script>

<template>
    <Head title="Agregar a Galería" />

    <AdminLayout title="Agregar a Galería">
        <nav class="mb-6 flex items-center gap-2 text-sm text-gray-500">
            <Link :href="route('admin.gallery.index')" class="hover:text-amber-600 transition-colors">Galería</Link>
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
            <span class="text-gray-800 font-medium">Nuevo elemento</span>
        </nav>

        <div class="mx-auto max-w-2xl">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-5">
                    <h3 class="text-base font-semibold text-gray-800">Información del elemento</h3>

                    <!-- URL -->
                    <div>
                        <InputLabel for="url" value="URL del contenido *" />
                        <TextInput
                            id="url"
                            v-model="form.url"
                            type="url"
                            class="mt-1 block w-full"
                            placeholder="https://www.youtube.com/watch?v=... o https://www.facebook.com/..."
                            required
                        />
                        <p class="mt-1 text-xs text-gray-400">Pega el link de YouTube, Facebook o Instagram. La plataforma y tipo se detectan automáticamente.</p>
                        <InputError class="mt-1" :message="form.errors.url" />
                    </div>

                    <!-- Title -->
                    <div>
                        <InputLabel for="title" value="Título (opcional)" />
                        <TextInput id="title" v-model="form.title" type="text" class="mt-1 block w-full" placeholder="Ej: Boda Martínez – Vals de entrada" />
                        <InputError class="mt-1" :message="form.errors.title" />
                    </div>

                    <!-- Platform + Type + Format (auto-detected, overridable) -->
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="platform" value="Plataforma" />
                            <select id="platform" v-model="form.platform" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm">
                                <option value="youtube">YouTube</option>
                                <option value="facebook">Facebook</option>
                                <option value="instagram">Instagram</option>
                                <option value="other">Otro</option>
                            </select>
                            <InputError class="mt-1" :message="form.errors.platform" />
                        </div>
                        <div>
                            <InputLabel for="type" value="Tipo" />
                            <select id="type" v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm">
                                <option value="video">Video</option>
                                <option value="photo">Foto / Publicación</option>
                            </select>
                            <InputError class="mt-1" :message="form.errors.type" />
                        </div>
                        <div>
                            <InputLabel for="format" value="Formato" />
                            <select id="format" v-model="form.format" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm">
                                <option value="landscape">Horizontal (16:9)</option>
                                <option value="portrait">Vertical / Reel (9:16)</option>
                                <option value="square">Cuadrado (1:1)</option>
                            </select>
                            <InputError class="mt-1" :message="form.errors.format" />
                        </div>
                    </div>

                    <!-- Sort order + Active -->
                    <div class="grid grid-cols-2 gap-4 items-end">
                        <div>
                            <InputLabel for="sort_order" value="Orden (menor = primero)" />
                            <TextInput id="sort_order" v-model="form.sort_order" type="number" min="0" class="mt-1 block w-full" />
                            <InputError class="mt-1" :message="form.errors.sort_order" />
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer pb-2">
                            <input type="checkbox" v-model="form.is_active" class="h-4 w-4 rounded border-gray-300 text-amber-500 focus:ring-amber-400" />
                            <span class="text-sm text-gray-700">Visible en el sitio</span>
                        </label>
                    </div>

                    <!-- Hint for Facebook -->
                    <div v-if="form.platform === 'facebook'" class="rounded-lg bg-blue-50 border border-blue-100 p-4 text-sm text-blue-700">
                        <strong>Tip Facebook:</strong> Después de guardar podrás subir una miniatura desde la pantalla de edición. Recomendamos usar una captura del video/foto como miniatura.
                    </div>
                    <div v-if="form.platform === 'youtube'" class="rounded-lg bg-gray-50 border border-gray-100 p-3 text-xs text-gray-500">
                        La miniatura de YouTube se genera automáticamente a partir del link.
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('admin.gallery.index')" class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-600 transition-colors disabled:opacity-50"
                    >
                        {{ form.processing ? 'Guardando...' : 'Guardar' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
