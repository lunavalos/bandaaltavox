<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    items: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');

let debounce;
watch(search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(route('admin.gallery.index'), { search: search.value || undefined }, { preserveState: true, replace: true });
    }, 300);
});

const showDeleteModal = ref(false);
const itemToDelete = ref(null);

const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    router.delete(route('admin.gallery.destroy', itemToDelete.value.id), {
        onFinish: () => {
            showDeleteModal.value = false;
            itemToDelete.value = null;
        },
    });
};

const toggleActive = (item) => {
    router.put(route('admin.gallery.update', item.id), {
        ...item,
        is_active: !item.is_active,
    }, { preserveScroll: true });
};

function youtubeThumb(url) {
    const m = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/);
    return m ? `https://img.youtube.com/vi/${m[1]}/hqdefault.jpg` : null;
}

function thumbnailFor(item) {
    if (item.thumbnail) return `/storage/${item.thumbnail}`;
    if (item.platform === 'youtube') return youtubeThumb(item.url);
    return null;
}

const platformLabel = { youtube: 'YouTube', facebook: 'Facebook', instagram: 'Instagram', other: 'Otro' };
const platformColor = {
    youtube: 'bg-red-100 text-red-700',
    facebook: 'bg-blue-100 text-blue-700',
    instagram: 'bg-pink-100 text-pink-700',
    other: 'bg-gray-100 text-gray-600',
};
</script>

<template>
    <Head title="Galería" />

    <AdminLayout title="Galería">
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Buscar..."
                    class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 focus:border-amber-400 focus:ring-amber-400 w-64"
                />
            </div>
            <Link
                :href="route('admin.gallery.create')"
                class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-600 transition-colors"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Agregar elemento
            </Link>
        </div>

        <!-- Empty state -->
        <div v-if="!items.length" class="rounded-xl border-2 border-dashed border-gray-200 p-16 text-center">
            <svg class="h-12 w-12 mx-auto text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 0 0 1.5-1.5V5.25a1.5 1.5 0 0 0-1.5-1.5H3.75a1.5 1.5 0 0 0-1.5 1.5v14.25a1.5 1.5 0 0 0 1.5 1.5Z" />
            </svg>
            <p class="text-gray-500 text-sm">No hay elementos en la galería todavía.</p>
            <Link :href="route('admin.gallery.create')" class="mt-3 inline-block text-amber-600 text-sm font-medium hover:underline">Agregar el primero →</Link>
        </div>

        <!-- Grid -->
        <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <div
                v-for="item in items"
                :key="item.id"
                class="group relative rounded-xl overflow-hidden border border-gray-200 bg-white shadow-sm"
            >
                <!-- Thumbnail -->
                <div class="relative aspect-video bg-gray-100">
                    <img
                        v-if="thumbnailFor(item)"
                        :src="thumbnailFor(item)"
                        :alt="item.title || item.platform"
                        class="w-full h-full object-cover"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center">
                        <svg class="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 0 0 1.5-1.5V5.25a1.5 1.5 0 0 0-1.5-1.5H3.75a1.5 1.5 0 0 0-1.5 1.5v14.25a1.5 1.5 0 0 0 1.5 1.5Z" />
                        </svg>
                    </div>

                    <!-- Video play badge -->
                    <div v-if="item.type === 'video'" class="absolute inset-0 flex items-center justify-center">
                        <div class="h-9 w-9 rounded-full bg-black/50 flex items-center justify-center">
                            <svg class="h-4 w-4 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Inactive overlay -->
                    <div v-if="!item.is_active" class="absolute inset-0 bg-gray-900/60 flex items-center justify-center">
                        <span class="text-white text-xs font-semibold bg-gray-800/80 px-2 py-1 rounded">Inactivo</span>
                    </div>

                    <!-- Hover actions -->
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-colors flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100">
                        <Link
                            :href="route('admin.gallery.edit', item.id)"
                            class="rounded-full bg-white p-2 text-gray-700 hover:bg-amber-500 hover:text-white transition-colors shadow"
                            title="Editar"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Z" />
                            </svg>
                        </Link>
                        <button
                            @click="toggleActive(item)"
                            class="rounded-full bg-white p-2 text-gray-700 hover:bg-blue-500 hover:text-white transition-colors shadow"
                            :title="item.is_active ? 'Desactivar' : 'Activar'"
                        >
                            <svg v-if="item.is_active" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <svg v-else class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                        <button
                            @click="confirmDelete(item)"
                            class="rounded-full bg-white p-2 text-gray-700 hover:bg-red-500 hover:text-white transition-colors shadow"
                            title="Eliminar"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Info -->
                <div class="p-2.5">
                    <p class="text-xs font-medium text-gray-800 truncate">{{ item.title || '(Sin título)' }}</p>
                    <div class="mt-1 flex items-center gap-1.5">
                        <span :class="['text-[10px] font-semibold px-1.5 py-0.5 rounded', platformColor[item.platform]]">
                            {{ platformLabel[item.platform] }}
                        </span>
                        <span class="text-[10px] text-gray-400">{{ item.type === 'video' ? 'Video' : 'Foto' }}</span>
                        <span class="ml-auto text-[10px]" :class="item.sort_order > 0 ? 'text-gray-400' : 'text-gray-200'">#{{ item.sort_order }}</span>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="showDeleteModal"
            title="Eliminar elemento"
            :message="`¿Eliminar '${itemToDelete?.title || 'este elemento'}' de la galería? Esta acción no se puede deshacer.`"
            confirm-label="Eliminar"
            @confirm="deleteItem"
            @cancel="showDeleteModal = false"
        />
    </AdminLayout>
</template>
