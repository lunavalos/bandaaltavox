<script setup>
import { watch, ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({ item: Object });

const form = useForm({
    title: props.item.title || '',
    url: props.item.url,
    type: props.item.type,
    format: props.item.format,
    platform: props.item.platform,
    is_active: props.item.is_active,
    sort_order: props.item.sort_order,
});

watch(() => form.url, (url) => {
    if (!url) return;
    if (url.includes('youtube.com') || url.includes('youtu.be')) {
        form.platform = 'youtube';
        form.type = 'video';
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

const submit = () => form.put(route('admin.gallery.update', props.item.id));

// Thumbnail upload
const uploadingThumb = ref(false);
const handleThumbUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;
    uploadingThumb.value = true;
    router.post(route('admin.gallery.upload-thumbnail', props.item.id), { thumbnail: file }, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => { uploadingThumb.value = false; event.target.value = ''; },
    });
};
const deleteThumb = () => {
    if (!confirm('¿Eliminar la miniatura?')) return;
    router.delete(route('admin.gallery.delete-thumbnail', props.item.id), { preserveScroll: true });
};

function youtubeThumb(url) {
    const m = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/);
    return m ? `https://img.youtube.com/vi/${m[1]}/hqdefault.jpg` : null;
}

const previewThumb = props.item.thumbnail
    ? `/storage/${props.item.thumbnail}`
    : (props.item.platform === 'youtube' ? youtubeThumb(props.item.url) : null);
</script>

<template>
    <Head :title="`Editar: ${item.title || 'Elemento de galería'}`" />

    <AdminLayout title="Editar elemento">
        <nav class="mb-6 flex items-center gap-2 text-sm text-gray-500">
            <Link :href="route('admin.gallery.index')" class="hover:text-amber-600 transition-colors">Galería</Link>
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
            <span class="text-gray-800 font-medium">{{ item.title || 'Editar' }}</span>
        </nav>

        <div class="mx-auto max-w-2xl space-y-6">
            <!-- Thumbnail section -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-gray-800 mb-4">Miniatura</h3>
                <div class="flex items-start gap-4">
                    <div class="relative w-48 aspect-video rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center shrink-0">
                        <img v-if="previewThumb" :src="previewThumb" alt="Miniatura" class="w-full h-full object-cover" />
                        <div v-else class="text-gray-300">
                            <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 0 0 1.5-1.5V5.25a1.5 1.5 0 0 0-1.5-1.5H3.75a1.5 1.5 0 0 0-1.5 1.5v14.25a1.5 1.5 0 0 0 1.5 1.5Z" />
                            </svg>
                        </div>
                        <button v-if="item.thumbnail" type="button" @click="deleteThumb"
                            class="absolute top-1 right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white text-xs hover:bg-red-600">×</button>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-3">
                            <template v-if="item.platform === 'youtube'">YouTube genera la miniatura automáticamente. Puedes subir una personalizada para reemplazarla.</template>
                            <template v-else>Para Facebook/Instagram sube una captura de pantalla del post o video.</template>
                        </p>
                        <label class="cursor-pointer">
                            <span class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                {{ uploadingThumb ? 'Subiendo...' : (item.thumbnail ? 'Cambiar miniatura' : 'Subir miniatura') }}
                            </span>
                            <input type="file" accept="image/*" class="hidden" :disabled="uploadingThumb" @change="handleThumbUpload" />
                        </label>
                        <p class="mt-2 text-xs text-gray-400">PNG, JPG. Máximo 2MB. Proporción 16:9 recomendada.</p>
                    </div>
                </div>
            </div>

            <!-- Info form -->
            <form @submit.prevent="submit" class="space-y-6">
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-5">
                    <h3 class="text-base font-semibold text-gray-800">Información</h3>

                    <div>
                        <InputLabel for="url" value="URL del contenido *" />
                        <TextInput id="url" v-model="form.url" type="url" class="mt-1 block w-full" required />
                        <InputError class="mt-1" :message="form.errors.url" />
                    </div>

                    <div>
                        <InputLabel for="title" value="Título (opcional)" />
                        <TextInput id="title" v-model="form.title" type="text" class="mt-1 block w-full" placeholder="Ej: Boda Martínez – Vals de entrada" />
                        <InputError class="mt-1" :message="form.errors.title" />
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="platform" value="Plataforma" />
                            <select id="platform" v-model="form.platform" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm">
                                <option value="youtube">YouTube</option>
                                <option value="facebook">Facebook</option>
                                <option value="instagram">Instagram</option>
                                <option value="other">Otro</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="type" value="Tipo" />
                            <select id="type" v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm">
                                <option value="video">Video</option>
                                <option value="photo">Foto / Publicación</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="format" value="Formato" />
                            <select id="format" v-model="form.format" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm">
                                <option value="landscape">Horizontal (16:9)</option>
                                <option value="portrait">Vertical / Reel (9:16)</option>
                                <option value="square">Cuadrado (1:1)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 items-end">
                        <div>
                            <InputLabel for="sort_order" value="Orden (menor = primero)" />
                            <TextInput id="sort_order" v-model="form.sort_order" type="number" min="0" class="mt-1 block w-full" />
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer pb-2">
                            <input type="checkbox" v-model="form.is_active" class="h-4 w-4 rounded border-gray-300 text-amber-500 focus:ring-amber-400" />
                            <span class="text-sm text-gray-700">Visible en el sitio</span>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <Link :href="route('admin.gallery.index')" class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-600 transition-colors disabled:opacity-50"
                    >
                        {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
