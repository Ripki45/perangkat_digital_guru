<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Bot,
    Braces,
    Database,
    LoaderCircle,
    RefreshCw,
    Search,
    Send,
    Sparkles,
} from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import PageHeader from '@/components/app/PageHeader.vue';
import StatCard from '@/components/app/StatCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type JsonApiEndpoint = {
    label: string;
    url: string;
};

type SemanticResult = {
    id: number;
    source_type: string;
    source_id: number | null;
    title: string;
    content: string;
    similarity: number;
};

const props = defineProps<{
    indexedCount: number;
    dbDriver: string;
    supportsNativeVector: boolean;
    aiConfigured: boolean;
    embeddingConfigured: boolean;
    jsonApiEndpoints: JsonApiEndpoint[];
}>();

defineOptions({
    layout: () => ({
        breadcrumbs: [
            { title: 'Beranda', href: '/beranda' },
            { title: 'Asisten AI & Pencarian Vektor', href: '/asisten-ai' },
        ],
    }),
});

const getXsrfToken = (): string => {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
};

/* ------------------------- AI Agent Chat ---------------------------------- */

const aiPrompt = ref('');
const aiAnswer = ref('');
const askingAi = ref(false);

const submitAiPrompt = async () => {
    const text = aiPrompt.value.trim();
    if (!text) return;

    askingAi.value = true;
    aiAnswer.value = '';
    try {
        const res = await fetch('/asisten-ai/tanya', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': getXsrfToken(),
            },
            body: JSON.stringify({ prompt: text }),
        });
        const data = (await res.json()) as {
            ok?: boolean;
            answer?: string;
        };
        aiAnswer.value = data.answer || 'Tidak ada respons.';
        if (!data.ok) {
            toast.info(
                'Perlu konfigurasi API key provider AI di .env untuk jawaban live.',
            );
        }
    } catch {
        toast.error('Gagal menghubungi endpoint Asisten AI.');
    } finally {
        askingAi.value = false;
    }
};

/* ------------------------- Semantic Vector Search ------------------------- */

const currentIndexedCount = ref(props.indexedCount);
const semanticQuery = ref('');
const searchingSemantic = ref(false);
const syncingVectors = ref(false);
const searchEngine = ref('');
const semanticResults = ref<SemanticResult[]>([]);

const runSemanticSearch = async () => {
    const q = semanticQuery.value.trim();
    if (!q) return;

    searchingSemantic.value = true;
    try {
        const res = await fetch(
            `/asisten-ai/pencarian?query=${encodeURIComponent(q)}`,
            { headers: { Accept: 'application/json' } },
        );
        const data = (await res.json()) as {
            engine?: string;
            results?: SemanticResult[];
        };
        searchEngine.value = data.engine || '';
        semanticResults.value = data.results || [];
    } catch {
        toast.error('Gagal melakukan pencarian semantik.');
    } finally {
        searchingSemantic.value = false;
    }
};

const syncVectors = async (queued: boolean) => {
    syncingVectors.value = true;
    try {
        const res = await fetch('/asisten-ai/sinkron', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': getXsrfToken(),
            },
            body: JSON.stringify({ queued }),
        });
        const data = (await res.json()) as {
            queued?: boolean;
            indexed?: number;
            message?: string;
        };
        if (typeof data.indexed === 'number') {
            currentIndexedCount.value = data.indexed;
        }
        toast.success(data.message || 'Sinkronisasi selesai.');
    } catch {
        toast.error('Gagal menyinkronkan indeks vektor.');
    } finally {
        syncingVectors.value = false;
    }
};

/* ------------------------- JSON:API v1.1 Inspector ------------------------ */

const jsonApiPreview = ref('');
const loadingJsonApi = ref(false);
const activeJsonApiUrl = ref('');

const inspectJsonApi = async (url: string) => {
    activeJsonApiUrl.value = url;
    loadingJsonApi.value = true;
    try {
        const res = await fetch(url, {
            headers: { Accept: 'application/vnd.api+json' },
        });
        const data = await res.json();
        jsonApiPreview.value = JSON.stringify(data, null, 2);
    } catch {
        jsonApiPreview.value = 'Gagal memuat endpoint JSON:API.';
    } finally {
        loadingJsonApi.value = false;
    }
};
</script>

<template>
    <Head title="Asisten AI & Pencarian Vektor" />

    <div class="space-y-6">
        <PageHeader
            badge="Laravel 13 AI SDK · Native Vector · JSON:API"
            title="Asisten AI EMIS & Pencarian Semantik"
            subtitle="Integrasi resmi laravel/ai Agent, pencarian kemiripan vektor (native Postgres/MariaDB & fallback SQLite cosine), Queue::route, dan endpoint JSON:API v1.1."
        >
            <template #actions>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="syncingVectors"
                    @click="syncVectors(false)"
                >
                    <RefreshCw
                        class="size-4"
                        :class="syncingVectors ? 'animate-spin' : ''"
                    />
                    Sinkron Indeks Langsung
                </Button>
                <Button
                    size="sm"
                    :disabled="syncingVectors"
                    @click="syncVectors(true)"
                >
                    <Send class="size-4" />
                    Antrekan via Queue::route
                </Button>
            </template>
        </PageHeader>

        <!-- Runtime Architecture Summary -->
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
                label="Dokumen Terindeks"
                :value="currentIndexedCount"
                hint="Tabel emis_semantic_documents"
                :icon="Database"
                tone="emerald"
            />
            <StatCard
                label="Driver Vektor DB"
                :value="dbDriver.toUpperCase()"
                :hint="
                    supportsNativeVector
                        ? 'whereVectorSimilarTo aktif'
                        : 'Cosine fallback (SQLite)'
                "
                :icon="Search"
                tone="sky"
            />
            <StatCard
                label="Laravel AI SDK"
                :value="aiConfigured ? 'Aktif' : 'Siap (.env)'"
                :hint="
                    embeddingConfigured
                        ? 'Embeddings provider terkonfigurasi'
                        : 'Fallback lokal trigram v1'
                "
                :icon="Bot"
                tone="gold"
            />
            <StatCard
                label="JSON:API Spec"
                value="v1.1"
                hint="application/vnd.api+json"
                :icon="Braces"
                tone="emerald"
            />
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <!-- Panel 1: Laravel AI SDK Agent -->
            <section
                class="flex flex-col space-y-4 rounded-2xl border border-border/80 bg-card p-5"
            >
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <h2
                            class="flex items-center gap-2 text-sm font-bold text-foreground"
                        >
                            <Sparkles class="size-4 text-[#107c41]" />
                            Asisten Agent EMIS 4.0 (Laravel AI SDK)
                        </h2>
                        <p class="text-xs text-muted-foreground">
                            Menggunakan <code>EmisAssistantAgent</code> dengan
                            tool <code>EmisDirectoryLookupTool</code>.
                        </p>
                    </div>
                </div>

                <form class="space-y-3" @submit.prevent="submitAiPrompt">
                    <textarea
                        v-model="aiPrompt"
                        rows="3"
                        placeholder="Contoh: Cek data siswa kelas 4-A penerima PIP, atau buatkan catatan Panca Cinta..."
                        class="w-full rounded-xl border border-input bg-background px-3 py-2 text-xs outline-none focus:border-[#107c41] focus:ring-2 focus:ring-[#107c41]/20"
                    />
                    <div class="flex items-center justify-end">
                        <Button type="submit" size="sm" :disabled="askingAi">
                            <LoaderCircle
                                v-if="askingAi"
                                class="size-4 animate-spin"
                            />
                            <Send v-else class="size-4" />
                            Kirim ke Agent AI
                        </Button>
                    </div>
                </form>

                <div
                    v-if="aiAnswer"
                    class="rounded-xl border border-[#107c41]/20 bg-[#107c41]/[0.04] p-3.5 text-xs leading-relaxed whitespace-pre-line text-foreground"
                >
                    {{ aiAnswer }}
                </div>
            </section>

            <!-- Panel 2: Semantic Vector Search -->
            <section
                class="flex flex-col space-y-4 rounded-2xl border border-border/80 bg-card p-5"
            >
                <div>
                    <h2
                        class="flex items-center gap-2 text-sm font-bold text-foreground"
                    >
                        <Search class="size-4 text-[#107c41]" />
                        Pencarian Semantik & Vektor
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Mencari kemiripan biodata siswa & GTK (menggunakan
                        native
                        <code>whereVectorSimilarTo</code> di Postgres/MariaDB
                        atau cosine vector di SQLite).
                    </p>
                </div>

                <form class="flex gap-2" @submit.prevent="runSemanticSearch">
                    <Input
                        v-model="semanticQuery"
                        placeholder="Cari makna: siswa santri penerima PIP kelas 6..."
                        class="h-9 text-xs"
                    />
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="searchingSemantic"
                    >
                        <LoaderCircle
                            v-if="searchingSemantic"
                            class="size-4 animate-spin"
                        />
                        <Search v-else class="size-4" />
                        Cari
                    </Button>
                </form>

                <div
                    v-if="searchEngine"
                    class="text-[11px] text-muted-foreground"
                >
                    Mesin kueri:
                    <span class="font-mono font-semibold text-foreground">{{
                        searchEngine
                    }}</span>
                    ({{ semanticResults.length }} hasil)
                </div>

                <div
                    v-if="semanticResults.length > 0"
                    class="max-h-64 space-y-2 overflow-y-auto pr-1"
                >
                    <div
                        v-for="item in semanticResults"
                        :key="item.id"
                        class="rounded-xl border border-border bg-muted/20 p-3 text-xs"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-bold text-foreground">{{
                                item.title
                            }}</span>
                            <span
                                class="rounded-full bg-[#107c41]/10 px-2 py-0.5 font-mono text-[10px] font-semibold text-[#107c41]"
                            >
                                {{ Math.round(item.similarity * 100) }}% cocok
                            </span>
                        </div>
                        <p class="mt-1 text-[11px] text-muted-foreground">
                            {{ item.content }}
                        </p>
                    </div>
                </div>
            </section>
        </div>

        <!-- Panel 3: JSON:API v1.1 Endpoint Inspector -->
        <section
            class="space-y-3 rounded-2xl border border-border/80 bg-card p-5"
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-sm font-bold text-foreground">
                        Inspektur Endpoint JSON:API v1.1 Resmi
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Mendukung <code>application/vnd.api+json</code>, sparse
                        fieldsets (<code>?fields[students]=nama,nisn</code>),
                        dan <code>?include=rdmScores</code>.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button
                    v-for="ep in jsonApiEndpoints"
                    :key="ep.url"
                    variant="outline"
                    size="sm"
                    @click="inspectJsonApi(ep.url)"
                >
                    {{ ep.label }}
                </Button>
            </div>

            <div v-if="loadingJsonApi" class="text-xs text-muted-foreground">
                Memuat {{ activeJsonApiUrl }}...
            </div>

            <pre
                v-if="jsonApiPreview"
                class="max-h-72 overflow-auto rounded-xl border border-border bg-slate-950 p-4 font-mono text-[11px] text-emerald-300"
                >{{ jsonApiPreview }}</pre>
        </section>
    </div>
</template>
