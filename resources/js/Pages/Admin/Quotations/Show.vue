<script setup>
import { parseDate, todayISO, toDateInput } from '@/Composables/useDates';
import { ref, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    quotation: Object,
    depositPercentage: Number,
    statuses: Object,
    defaultClauses: Array,
    eventTypes: Array,
});

const isSuperAdmin = computed(() => usePage().props.auth.roles.includes('Super Admin'));

const showEditModal = ref(false);
const editData = () => {
    const q = props.quotation;
    return {
        event_type_id: q.event_type_id ?? '',
        event_date: toDateInput(q.event_date),
        event_time_start: q.event_time_start ?? '',
        event_time_end: q.event_time_end ?? '',
        event_venue: q.event_venue ?? '',
        event_city: q.event_city ?? '',
        event_is_outdoor: !!q.event_is_outdoor,
        guest_count: q.guest_count ?? '',
        hours_contracted: q.hours_contracted,
        client_name: q.client_name,
        client_email: q.client_email ?? '',
        client_phone: q.client_phone ?? '',
        client_address: q.client_address ?? '',
        observations: q.observations ?? '',
    };
};

const editForm = useForm(editData());

const openEditModal = () => {
    editForm.defaults(editData());
    editForm.reset();
    editForm.clearErrors();
    showEditModal.value = true;
};

const saveEdit = () => {
    editForm
        .transform((data) => ({
            ...data,
            event_type_id: data.event_type_id || null,
            guest_count: data.guest_count || null,
        }))
        .put(route('admin.quotations.update', props.quotation.id), {
            preserveScroll: true,
            onSuccess: () => { showEditModal.value = false; },
        });
};

const showStatusModal = ref(false);
const showContractModal = ref(false);
const selectedStatus = ref('');

const contractForm = useForm({
    quotation_id: props.quotation.id,
    first_payment_amount: Math.round(parseFloat(props.quotation.total) * (props.depositPercentage / 100)),
    first_payment_date: todayISO(),
    clauses: [...props.defaultClauses],
    representative_name: '',
    observations: props.quotation.observations || '',
});

const changeStatus = (status) => {
    selectedStatus.value = status;
    showStatusModal.value = true;
};

const confirmStatusChange = () => {
    router.patch(route('admin.quotations.update-status', props.quotation.id), {
        status: selectedStatus.value,
    }, {
        onFinish: () => {
            showStatusModal.value = false;
        },
    });
};

const openContractModal = () => {
    showContractModal.value = true;
};

const createContract = () => {
    contractForm.post(route('admin.contracts.store'));
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 0 }).format(price);
};

const formatDate = (date) => {
    if (!date) return '—';
    return parseDate(date).toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: 'numeric' });
};

const statusColors = {
    draft: 'bg-gray-100 text-gray-700',
    sent: 'bg-blue-100 text-blue-700',
    accepted: 'bg-emerald-100 text-emerald-700',
    rejected: 'bg-red-100 text-red-700',
    expired: 'bg-orange-100 text-orange-700',
    converted: 'bg-purple-100 text-purple-700',
};

const q = computed(() => props.quotation);

const depositAmount = computed(() => {
    return Math.round(parseFloat(q.value.total) * (props.depositPercentage / 100));
});

// Allowed status transitions
const allowedTransitions = computed(() => {
    const map = {
        draft: ['sent'],
        sent: ['accepted', 'rejected'],
        accepted: ['expired'],
        rejected: [],
        expired: [],
        converted: [],
    };
    return map[q.value.status] || [];
});

const canConvert = computed(() => {
    return q.value.status === 'accepted' && !q.value.contract;
});
</script>

<template>
    <Head :title="`Cotización ${q.quotation_number}`" />

    <AdminLayout :title="`Cotización ${q.quotation_number}`">
        <!-- Breadcrumb -->
        <nav class="mb-6 flex items-center gap-2 text-sm text-gray-500">
            <Link :href="route('admin.quotations.index')" class="hover:text-amber-600 transition-colors">Cotizaciones</Link>
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
            <span class="text-gray-800 font-medium">{{ q.quotation_number }}</span>
        </nav>

        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Status + Actions bar -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <span :class="['inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium', statusColors[q.status]]">
                        <span class="h-2 w-2 rounded-full" :class="statusColors[q.status].replace('bg-', 'bg-').replace('100', '500').replace('text-', 'hidden ')"></span>
                        {{ statuses[q.status] }}
                    </span>
                    <span class="text-sm text-gray-500">Creada {{ formatDate(q.created_at) }}</span>
                    <span v-if="q.creator" class="text-sm text-gray-400">por {{ q.creator.name }}</span>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Edit (Super Admin) -->
                    <button
                        v-if="isSuperAdmin"
                        @click="openEditModal"
                        class="rounded-lg border border-amber-200 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-50 transition-colors"
                    >
                        Editar datos
                    </button>

                    <!-- Status transitions -->
                    <button
                        v-for="statusKey in allowedTransitions"
                        :key="statusKey"
                        @click="changeStatus(statusKey)"
                        :class="[
                            'rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors',
                            statusKey === 'accepted' ? 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' :
                            statusKey === 'rejected' ? 'border-red-200 text-red-700 hover:bg-red-50' :
                            statusKey === 'sent' ? 'border-blue-200 text-blue-700 hover:bg-blue-50' :
                            'border-gray-200 text-gray-700 hover:bg-gray-50'
                        ]"
                    >
                        {{ statuses[statusKey] }}
                    </button>

                    <!-- Convert to Contract -->
                    <button
                        v-if="canConvert"
                        @click="openContractModal"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 transition-colors"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Cerrar Venta → Contrato
                    </button>

                    <!-- View contract link -->
                    <Link
                        v-if="q.contract"
                        :href="route('admin.contracts.show', q.contract.id)"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-purple-100 px-4 py-1.5 text-xs font-semibold text-purple-700 hover:bg-purple-200 transition-colors"
                    >
                        Ver Contrato {{ q.contract.contract_number }}
                    </Link>
                </div>
            </div>

            <!-- Two-column layout -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Event Info -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-4">Evento</h3>
                    <dl class="space-y-3 text-sm">
                        <div v-if="q.event_type" class="flex justify-between">
                            <dt class="text-gray-500">Tipo</dt>
                            <dd class="font-medium text-gray-800">{{ q.event_type.name }}</dd>
                        </div>
                        <div v-if="q.event_date" class="flex justify-between">
                            <dt class="text-gray-500">Fecha</dt>
                            <dd class="font-medium text-gray-800">{{ formatDate(q.event_date) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Horario</dt>
                            <dd class="font-medium text-gray-800">{{ q.event_time_start || '—' }} - {{ q.event_time_end || '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Horas</dt>
                            <dd class="font-medium text-gray-800">{{ q.hours_contracted }} hrs</dd>
                        </div>
                        <div v-if="q.event_venue" class="flex justify-between">
                            <dt class="text-gray-500">Lugar</dt>
                            <dd class="font-medium text-gray-800">{{ q.event_venue }}</dd>
                        </div>
                        <div v-if="q.event_city" class="flex justify-between">
                            <dt class="text-gray-500">Ciudad</dt>
                            <dd class="font-medium text-gray-800">{{ q.event_city }}</dd>
                        </div>
                        <div v-if="q.guest_count" class="flex justify-between">
                            <dt class="text-gray-500">Invitados</dt>
                            <dd class="font-medium text-gray-800">~{{ q.guest_count }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Ubicación</dt>
                            <dd class="font-medium text-gray-800">{{ q.event_is_outdoor ? 'Aire libre' : 'Interior' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Client Info -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-4">Cliente</h3>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Nombre</dt>
                            <dd class="font-medium text-gray-800">{{ q.client_name }}</dd>
                        </div>
                        <div v-if="q.client_email" class="flex justify-between">
                            <dt class="text-gray-500">Email</dt>
                            <dd class="font-medium text-gray-800">{{ q.client_email }}</dd>
                        </div>
                        <div v-if="q.client_phone" class="flex justify-between">
                            <dt class="text-gray-500">Teléfono</dt>
                            <dd class="font-medium text-gray-800">{{ q.client_phone }}</dd>
                        </div>
                        <div v-if="q.client_address" class="flex justify-between">
                            <dt class="text-gray-500">Dirección</dt>
                            <dd class="font-medium text-gray-800 text-right max-w-[60%]">{{ q.client_address }}</dd>
                        </div>
                    </dl>

                    <div v-if="q.observations" class="mt-4 pt-4 border-t border-gray-100">
                        <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Observaciones</h4>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ q.observations }}</p>
                    </div>
                </div>
            </div>

            <!-- Items table -->
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wide">Desglose</h3>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Tipo</th>
                            <th class="px-6 py-3 text-left">Concepto</th>
                            <th class="px-6 py-3 text-center">Cant.</th>
                            <th class="px-6 py-3 text-right">P. Unitario</th>
                            <th class="px-6 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in q.items" :key="item.id">
                            <td class="px-6 py-3">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium',
                                        item.type === 'package' ? 'bg-amber-100 text-amber-700' :
                                        item.type === 'addon' ? 'bg-blue-100 text-blue-700' :
                                        'bg-gray-100 text-gray-700',
                                    ]"
                                >
                                    {{ item.type === 'package' ? 'Paquete' : item.type === 'addon' ? 'Extra' : 'Custom' }}
                                </span>
                            </td>
                            <td class="px-6 py-3 font-medium text-gray-800">{{ item.description }}</td>
                            <td class="px-6 py-3 text-center text-gray-600">{{ item.quantity }}</td>
                            <td class="px-6 py-3 text-right text-gray-600">{{ formatPrice(item.unit_price) }}</td>
                            <td class="px-6 py-3 text-right font-bold text-gray-800">{{ formatPrice(item.total) }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="border-t border-gray-200 px-6 py-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Subtotal</span>
                        <span class="font-medium text-gray-800">{{ formatPrice(q.subtotal) }}</span>
                    </div>
                    <div v-if="parseFloat(q.discount) > 0" class="flex justify-between text-sm">
                        <span class="text-gray-500">Descuento</span>
                        <span class="text-red-500">-{{ formatPrice(q.discount) }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold border-t border-gray-200 pt-2">
                        <span class="text-gray-800">Total</span>
                        <span class="text-amber-600">{{ formatPrice(q.total) }}</span>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500">
                        <span>Anticipo sugerido ({{ depositPercentage }}%)</span>
                        <span>{{ formatPrice(depositAmount) }}</span>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500">
                        <span>Vigencia</span>
                        <span>{{ q.validity_days }} días — vence {{ formatDate(q.expires_at) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Change Modal -->
        <ConfirmModal :show="showStatusModal" @close="showStatusModal = false">
            <template #title>Cambiar estado</template>
            <template #default>
                <p class="text-sm text-gray-600">
                    ¿Cambiar estado de la cotización a <strong>{{ statuses[selectedStatus] }}</strong>?
                </p>
            </template>
            <template #footer>
                <button
                    @click="showStatusModal = false"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Cancelar
                </button>
                <button
                    @click="confirmStatusChange"
                    class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600"
                >
                    Confirmar
                </button>
            </template>
        </ConfirmModal>

        <!-- Contract Creation Modal -->
        <ConfirmModal :show="showContractModal" @close="showContractModal = false" max-width="2xl">
            <template #title>Cerrar Venta — Generar Contrato</template>
            <template #default>
                <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
                    <p class="text-sm text-gray-600">
                        Se creará un contrato digital y se registrará al cliente <strong>{{ q.client_name }}</strong> en el sistema.
                    </p>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del representante *</label>
                        <input
                            v-model="contractForm.representative_name"
                            type="text"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm"
                            placeholder="Nombre del representante de Banda Alta Vox"
                        />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Monto primer pago *</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 text-sm">$</span>
                                <input
                                    v-model="contractForm.first_payment_amount"
                                    type="number"
                                    step="100"
                                    min="0"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm pl-7"
                                />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha primer pago</label>
                            <input
                                v-model="contractForm.first_payment_date"
                                type="date"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                        <textarea
                            v-model="contractForm.observations"
                            rows="2"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm"
                        />
                    </div>

                    <!-- Clauses preview -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cláusulas del contrato</label>
                        <div class="space-y-2 max-h-48 overflow-y-auto rounded-lg border border-gray-200 p-3 bg-gray-50">
                            <div
                                v-for="(clause, idx) in contractForm.clauses"
                                :key="idx"
                                class="text-xs text-gray-600 leading-relaxed"
                            >
                                {{ clause }}
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <button
                    @click="showContractModal = false"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Cancelar
                </button>
                <button
                    @click="createContract"
                    :disabled="contractForm.processing || !contractForm.representative_name"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                >
                    {{ contractForm.processing ? 'Generando...' : 'Generar Contrato' }}
                </button>
            </template>
        </ConfirmModal>
        <!-- Edit Modal (Super Admin) -->
        <ConfirmModal :show="showEditModal" @close="showEditModal = false" max-width="2xl" title="Editar cotización">
            <div class="space-y-4 max-h-[65vh] overflow-y-auto pr-2 text-sm">
                <p v-if="q.contract" class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-xs text-amber-800">
                    Esta cotización ya tiene el contrato {{ q.contract.contract_number }}. Los cambios aquí no modifican el contrato; edítalo desde la vista del contrato.
                </p>

                <h4 class="text-xs font-semibold text-gray-500 uppercase">Cliente</h4>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block font-medium text-gray-700 mb-1">Nombre *</label>
                        <input v-model="editForm.client_name" type="text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm" />
                        <p v-if="editForm.errors.client_name" class="mt-1 text-xs text-red-600">{{ editForm.errors.client_name }}</p>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Email</label>
                        <input v-model="editForm.client_email" type="email" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm" />
                        <p v-if="editForm.errors.client_email" class="mt-1 text-xs text-red-600">{{ editForm.errors.client_email }}</p>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Teléfono</label>
                        <input v-model="editForm.client_phone" type="text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block font-medium text-gray-700 mb-1">Dirección</label>
                        <input v-model="editForm.client_address" type="text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm" />
                    </div>
                </div>

                <h4 class="text-xs font-semibold text-gray-500 uppercase pt-2">Evento</h4>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Tipo de evento</label>
                        <select v-model="editForm.event_type_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm">
                            <option value="">—</option>
                            <option v-for="t in eventTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Fecha</label>
                        <input v-model="editForm.event_date" type="date" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm" />
                        <p v-if="editForm.errors.event_date" class="mt-1 text-xs text-red-600">{{ editForm.errors.event_date }}</p>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Hora inicio</label>
                        <input v-model="editForm.event_time_start" type="time" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm" />
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Hora fin</label>
                        <input v-model="editForm.event_time_end" type="time" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm" />
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Horas contratadas *</label>
                        <input v-model="editForm.hours_contracted" type="number" min="1" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm" />
                        <p v-if="editForm.errors.hours_contracted" class="mt-1 text-xs text-red-600">{{ editForm.errors.hours_contracted }}</p>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Invitados</label>
                        <input v-model="editForm.guest_count" type="number" min="1" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm" />
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Lugar</label>
                        <input v-model="editForm.event_venue" type="text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm" />
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Ciudad</label>
                        <input v-model="editForm.event_city" type="text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm" />
                    </div>
                    <label class="flex items-center gap-2 sm:col-span-2">
                        <input v-model="editForm.event_is_outdoor" type="checkbox" class="rounded border-gray-300 text-amber-500 focus:ring-amber-400" />
                        <span class="text-gray-700">Evento al aire libre</span>
                    </label>
                </div>

                <div>
                    <label class="block font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea v-model="editForm.observations" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm" />
                </div>
            </div>
            <template #footer>
                <button
                    @click="showEditModal = false"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Cancelar
                </button>
                <button
                    @click="saveEdit"
                    :disabled="editForm.processing"
                    class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600 disabled:opacity-50"
                >
                    {{ editForm.processing ? 'Guardando...' : 'Guardar cambios' }}
                </button>
            </template>
        </ConfirmModal>
    </AdminLayout>
</template>
