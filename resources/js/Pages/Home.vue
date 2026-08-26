<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ProductCard from '@/Components/Products/ProductCard.vue';
import { useLocale } from '@/Composables/useLocale';
import type { ProductApiResource, CategoryApiResource } from '@/types/api';

interface FlashSaleProduct {
    id: number;
    name: string;
    slug: string;
    price_cents: number;
    image: string | null;
    in_stock: boolean;
    discounted_price_cents: number;
}

interface FlashSale {
    id: number;
    name: string;
    discount_type: 'fixed' | 'percentage';
    discount_value: number;
    ends_at: string;
    seconds_remaining: number;
    products: FlashSaleProduct[];
}

interface StorefrontData {
    name: string;
    description: string | null;
    banner_path: string | null;
    logo_path: string | null;
    accent_color: string | null;
    social_links: Record<string, string>;
}

interface Props {
    featuredProducts?: ProductApiResource[];
    categories?: CategoryApiResource[];
    flashSales?: FlashSale[];
    storefront?: StorefrontData | null;
}

const props = withDefaults(defineProps<Props>(), {
    featuredProducts: () => [],
    categories: () => [],
    flashSales: () => [],
    storefront: null,
});

const page = usePage();
const isAuthenticated = computed(() => page.props.auth?.user !== null);
const Layout = computed(() => isAuthenticated.value ? AuthenticatedLayout : GuestLayout);
const { localePath, isRtl } = useLocale();

// Flash sale countdown state: saleId → seconds remaining
const flashCountdowns = ref<Record<number, number>>({});
let flashTimer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    props.flashSales.forEach(s => {
        flashCountdowns.value[s.id] = s.seconds_remaining;
    });
    if (props.flashSales.length > 0) {
        flashTimer = setInterval(() => {
            for (const id of Object.keys(flashCountdowns.value)) {
                const current = flashCountdowns.value[Number(id)];
                if (current > 0) {
                    flashCountdowns.value[Number(id)] = current - 1;
                }
            }
        }, 1000);
    }
});

onUnmounted(() => {
    if (flashTimer) {
        clearInterval(flashTimer);
    }
});

function formatFlashCountdown(seconds: number): string {
    if (seconds <= 0) { return '00:00:00'; }
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    return [h, m, s].map(n => String(n).padStart(2, '0')).join(':');
}

function discountLabel(sale: FlashSale): string {
    return sale.discount_type === 'percentage'
        ? `${(sale.discount_value / 100).toFixed(0)}% OFF`
        : `$${(sale.discount_value / 100).toFixed(2)} OFF`;
}

function flashPrice(cents: number): string {
    return '$' + (cents / 100).toFixed(2);
}

// Quick-access shortcut row (mirrors the mockup's icon strip under the banner)
const quickLinks = computed(() => [
    {
        label: 'Wishlist',
        href: localePath('/wishlist'),
        icon: 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z',
    },
    {
        label: 'Categories',
        href: localePath('/products'),
        icon: 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
    },
    {
        label: 'Offers',
        href: localePath('/products?on_sale=1'),
        icon: 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z M6 6h.008v.008H6V6z',
    },
    {
        label: 'Orders',
        href: localePath(isAuthenticated.value ? '/orders' : '/login'),
        icon: 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z',
    },
    {
        label: 'Cart',
        href: localePath('/cart'),
        icon: 'M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z',
    },
]);

const trustBadges = [
    {
        icon: 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
        title: 'Secure Payment',
        description: '100% secure checkout',
    },
    {
        icon: 'M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12',
        title: 'Fast Delivery',
        description: 'To all destinations',
    },
    {
        icon: 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5.25 0a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z',
        title: '24/7 Support',
        description: "We're here to help",
    },
];
</script>

<template>
    <Head title="Home" />

    <component :is="Layout">
        <!-- ========================================================
             BANNER
             ======================================================== -->
        <section class="relative overflow-hidden bg-gradient-to-br from-brand-700 via-brand-600 to-brand-800">
            <div class="absolute inset-0 opacity-10"
                style="background-image: radial-gradient(circle at 20% 30%, white 0, transparent 40%), radial-gradient(circle at 80% 70%, white 0, transparent 40%);">
            </div>

            <div v-if="storefront?.banner_path" class="relative">
                <img :src="storefront.banner_path" :alt="storefront.name" class="w-full object-cover" style="max-height: 320px;" />
                <div class="absolute inset-0 bg-gradient-to-t from-brand-900/70 to-transparent" />
            </div>

            <div class="relative mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8" :class="storefront?.banner_path ? 'absolute inset-0 flex items-center' : ''">
                <div class="max-w-xl text-center mx-auto sm:text-start sm:mx-0">
                    <p class="text-sm font-semibold uppercase tracking-widest text-accent-300">
                        {{ storefront?.name ?? 'Nile Market' }}
                    </p>
                    <h1 class="mt-3 text-3xl font-bold text-white sm:text-4xl lg:text-5xl leading-tight">
                        Everything you need, <span class="text-accent-400">in one place</span>
                    </h1>
                    <p v-if="storefront?.description" class="mt-4 text-brand-100 leading-relaxed">
                        {{ storefront.description }}
                    </p>
                    <Link
                        :href="localePath('/products')"
                        class="mt-8 inline-flex items-center gap-2 rounded-xl bg-accent-500 hover:bg-accent-400 px-7 py-3 text-sm font-bold text-brand-900 shadow-lg shadow-black/10 transition-all hover:-translate-y-0.5"
                    >
                        Shop Now
                        <svg class="h-4 w-4" :class="{ 'rotate-180': isRtl }" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </Link>
                </div>
            </div>
        </section>

        <!-- ========================================================
             QUICK ACCESS ICONS
             ======================================================== -->
        <section class="border-b border-slate-100 dark:border-navy-900 bg-white dark:bg-navy-950">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div class="grid grid-cols-5 gap-2 sm:flex sm:items-center sm:justify-center sm:gap-10">
                    <Link
                        v-for="link in quickLinks"
                        :key="link.label"
                        :href="link.href"
                        class="group flex flex-col items-center gap-2 text-center"
                    >
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 group-hover:bg-brand-100 dark:group-hover:bg-brand-900/50 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="link.icon" />
                            </svg>
                        </div>
                        <span class="text-[11px] font-medium text-slate-600 dark:text-navy-300 sm:text-xs">{{ link.label }}</span>
                    </Link>
                </div>
            </div>
        </section>

        <!-- ========================================================
             CATEGORIES
             ======================================================== -->
        <section v-if="categories.length > 0" class="py-10 sm:py-14 bg-slate-50 dark:bg-navy-900/50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white sm:text-xl">Shop by Category</h2>
                    <Link :href="localePath('/products')" class="text-sm font-semibold text-brand-600 dark:text-brand-400 hover:text-brand-500 transition-colors">
                        View all
                    </Link>
                </div>

                <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-8">
                    <Link
                        v-for="category in categories"
                        :key="category.id"
                        :href="localePath(`/products?category=${category.slug}`)"
                        class="group flex flex-col items-center gap-2 rounded-2xl bg-white dark:bg-navy-800/60 p-4 border border-slate-100 dark:border-navy-700/50 hover:border-brand-200 dark:hover:border-brand-800/50 shadow-sm hover:shadow-md transition-all"
                    >
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 dark:bg-brand-900/30 group-hover:bg-brand-100 dark:group-hover:bg-brand-900/50 transition-colors">
                            <svg class="h-6 w-6 text-brand-500 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                        </div>
                        <h3 class="text-xs font-semibold text-slate-900 dark:text-white text-center line-clamp-1">
                            {{ category.name }}
                        </h3>
                    </Link>
                </div>
            </div>
        </section>

        <!-- ========================================================
             FLASH SALES
             ======================================================== -->
        <section v-if="flashSales.length > 0" class="py-10 sm:py-14 bg-white dark:bg-navy-950">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div v-for="sale in flashSales" :key="sale.id" class="mb-10 last:mb-0">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">⚡</span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full bg-red-500 px-2.5 py-0.5 text-xs font-bold text-white uppercase tracking-wide">
                                        {{ discountLabel(sale) }}
                                    </span>
                                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ sale.name }}</h2>
                                </div>
                                <p class="text-sm text-slate-500 dark:text-navy-400 mt-0.5">Limited-time deal — grab it before it's gone</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2 rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-2">
                                <span class="text-xs font-medium text-red-600 dark:text-red-400 uppercase tracking-wide">Ends in</span>
                                <span class="text-lg font-mono font-bold text-red-600 dark:text-red-400 tabular-nums">
                                    {{ formatFlashCountdown(flashCountdowns[sale.id] ?? 0) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <Link
                            v-for="product in sale.products"
                            :key="product.id"
                            :href="localePath(`/products/${product.slug}`)"
                            class="group relative rounded-2xl border border-slate-100 dark:border-navy-800 bg-white dark:bg-navy-900/60 overflow-hidden hover:shadow-md transition-all"
                        >
                            <div class="absolute top-2 z-10 rounded-full bg-red-500 px-2 py-0.5 text-xs font-bold text-white" :class="isRtl ? 'right-2' : 'left-2'">
                                {{ discountLabel(sale) }}
                            </div>

                            <div
                                v-if="!product.in_stock"
                                class="absolute inset-0 bg-white/60 dark:bg-black/60 flex items-center justify-center z-20 rounded-2xl"
                            >
                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Sold Out</span>
                            </div>

                            <div class="aspect-square bg-slate-100 dark:bg-navy-800 overflow-hidden">
                                <img
                                    v-if="product.image"
                                    :src="product.image"
                                    :alt="product.name"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-slate-300 dark:text-navy-600">
                                    <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M13.5 12h.008v.008H13.5V12zm0 0H9m4.06-7.19l-4.125-4.125a1.802 1.802 0 00-2.557 0L3 8.25m0 0v11.25A2.25 2.25 0 005.25 21.75h13.5A2.25 2.25 0 0021 19.5V8.25m-18 0h18" />
                                    </svg>
                                </div>
                            </div>

                            <div class="p-3">
                                <p class="text-sm font-medium text-slate-900 dark:text-white line-clamp-2">{{ product.name }}</p>
                                <div class="mt-1.5 flex items-baseline gap-2">
                                    <span class="text-base font-bold text-red-600 dark:text-red-400">
                                        {{ flashPrice(product.discounted_price_cents) }}
                                    </span>
                                    <span class="text-xs text-slate-400 line-through">
                                        {{ flashPrice(product.price_cents) }}
                                    </span>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================
             FEATURED PRODUCTS
             ======================================================== -->
        <section v-if="featuredProducts.length > 0" class="py-10 sm:py-14 bg-slate-50 dark:bg-navy-900/50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white sm:text-xl">Featured Products</h2>
                    <Link :href="localePath('/products?featured=1')" class="text-sm font-semibold text-brand-600 dark:text-brand-400 hover:text-brand-500 transition-colors">
                        View all
                    </Link>
                </div>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    <ProductCard
                        v-for="product in featuredProducts"
                        :key="product.id"
                        :product="product"
                    />
                </div>
            </div>
        </section>

        <!-- ========================================================
             CTA (shown when no featured products)
             ======================================================== -->
        <section v-if="featuredProducts.length === 0" class="py-14 sm:py-20 bg-slate-50 dark:bg-navy-900/50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-700 to-brand-900 px-6 py-14 sm:px-12 sm:py-16 text-center">
                    <h2 class="text-2xl font-bold text-white sm:text-3xl">Ready to explore?</h2>
                    <p class="mx-auto mt-4 max-w-xl text-brand-100">
                        Browse our complete collection and find exactly what you're looking for.
                    </p>
                    <Link
                        :href="localePath('/products')"
                        class="mt-8 inline-flex items-center gap-2 rounded-xl bg-accent-500 hover:bg-accent-400 px-7 py-3 text-sm font-bold text-brand-900 shadow-lg transition-all"
                    >
                        Browse Products
                        <svg class="h-4 w-4" :class="{ 'rotate-180': isRtl }" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </Link>
                </div>
            </div>
        </section>

        <!-- ========================================================
             TRUST BADGES
             ======================================================== -->
        <section class="border-t border-slate-100 dark:border-navy-900 bg-white dark:bg-navy-950 py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3 sm:gap-4">
                    <div v-for="badge in trustBadges" :key="badge.title" class="flex items-center gap-3 justify-center sm:justify-start">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-accent-50 dark:bg-accent-900/20 text-accent-600 dark:text-accent-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="badge.icon" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ badge.title }}</div>
                            <div class="text-xs text-slate-500 dark:text-navy-400">{{ badge.description }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </component>
</template>
