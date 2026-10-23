import { parseNumber, rupiah, uuid } from './format';

const CART_KEY = 'tokokasir.cart';

/**
 * Halaman transaksi kasir (UC-02, UC-03): pencarian / scan barcode,
 * keranjang, diskon, dan pembayaran multi-metode.
 */
export default (config) => ({
    products: config.products,
    categoryId: null,
    query: '',
    cart: [],
    diskonMode: 'nominal',
    diskonInput: '',

    showPayment: false,
    metode: 'tunai',
    uangInput: '',
    referensi: '',
    processing: false,
    error: '',
    clientRef: null,

    toast: { show: false, message: '', type: 'info' },
    lastAddedId: null,

    init() {
        this.restoreCart();
        this.$watch('cart', () => this.saveCart(), { deep: true });
        this.$watch('diskonInput', () => this.saveCart());
        this.$nextTick(() => this.focusSearch());

        window.addEventListener('keydown', (e) => {
            if (e.key === 'F2') {
                e.preventDefault();
                this.focusSearch();
            } else if (e.key === 'F9' || e.key === 'F8') {
                e.preventDefault();
                this.openPayment();
            } else if (e.key === 'Escape' && this.showPayment && !this.processing) {
                this.closePayment();
            }
        });
    },

    rupiah,

    /** Menampilkan input nominal dengan pemisah ribuan, mis. "50000" -> "50.000". */
    formatNominal(value) {
        const n = parseNumber(value);
        return n ? n.toLocaleString('id-ID') : '';
    },

    // ---- Produk -------------------------------------------------------------

    get filteredProducts() {
        const q = this.query.trim().toLowerCase();
        let list = this.products;

        if (this.categoryId) {
            list = list.filter((p) => p.category_id === this.categoryId);
        }

        if (q) {
            list = list.filter((p) => p.nama.toLowerCase().includes(q) || p.kode.toLowerCase().includes(q));
        }

        return list.slice(0, 120);
    },

    focusSearch() {
        this.$refs.search?.focus();
    },

    /** Enter pada kolom cari: barcode scanner mengetik kode lalu menekan Enter. */
    searchEnter() {
        const q = this.query.trim();
        if (!q) {
            return;
        }

        const exact = this.products.find((p) => p.kode.toLowerCase() === q.toLowerCase());
        const match = exact ?? (this.filteredProducts.length === 1 ? this.filteredProducts[0] : null);

        if (match) {
            this.add(match);
            this.query = '';
        } else if (this.filteredProducts.length === 0) {
            this.notify(`Produk "${q}" tidak ditemukan.`, 'error');
            this.query = '';
        }
    },

    stockLeft(product) {
        const inCart = this.cart.find((i) => i.id === product.id);
        return product.stok - (inCart ? inCart.qty : 0);
    },

    // ---- Keranjang ----------------------------------------------------------

    add(product) {
        const item = this.cart.find((i) => i.id === product.id);
        const qty = (item?.qty ?? 0) + 1;

        if (qty > product.stok) {
            this.notify(product.stok <= 0 ? `Stok ${product.nama} habis.` : `Stok ${product.nama} hanya ${product.stok}.`, 'error');
            return;
        }

        if (item) {
            item.qty = qty;
        } else {
            this.cart.push({ id: product.id, kode: product.kode, nama: product.nama, harga: product.harga, stok: product.stok, qty: 1 });
        }

        this.lastAddedId = product.id;
        setTimeout(() => (this.lastAddedId === product.id ? (this.lastAddedId = null) : null), 600);
    },

    increment(item) {
        if (item.qty + 1 > item.stok) {
            this.notify(`Stok ${item.nama} hanya ${item.stok}.`, 'error');
            return;
        }
        item.qty++;
    },

    decrement(item) {
        if (item.qty <= 1) {
            this.remove(item);
        } else {
            item.qty--;
        }
    },

    setQty(item, value) {
        let qty = parseNumber(value);
        if (qty <= 0) {
            this.remove(item);
            return;
        }
        if (qty > item.stok) {
            this.notify(`Stok ${item.nama} hanya ${item.stok}.`, 'error');
            qty = item.stok;
        }
        item.qty = qty;
    },

    remove(item) {
        this.cart = this.cart.filter((i) => i.id !== item.id);
    },

    clearCart() {
        if (this.cart.length && confirm('Kosongkan keranjang belanja?')) {
            this.resetSale();
        }
    },

    resetSale() {
        this.cart = [];
        this.diskonInput = '';
        this.diskonMode = 'nominal';
        this.clientRef = null;
        this.focusSearch();
    },

    get itemCount() {
        return this.cart.reduce((sum, i) => sum + i.qty, 0);
    },

    get subtotal() {
        return this.cart.reduce((sum, i) => sum + i.harga * i.qty, 0);
    },

    get diskon() {
        const value = this.diskonMode === 'persen'
            ? Math.round((this.subtotal * Math.min(parseFloat(this.diskonInput) || 0, 100)) / 100)
            : parseNumber(this.diskonInput);

        return Math.min(Math.max(value, 0), this.subtotal);
    },

    get total() {
        return this.subtotal - this.diskon;
    },

    // ---- Pembayaran ---------------------------------------------------------

    get uangDiterima() {
        return parseNumber(this.uangInput);
    },

    get kembalian() {
        return Math.max(this.uangDiterima - this.total, 0);
    },

    get canSubmit() {
        if (!this.cart.length || this.processing) {
            return false;
        }
        return this.metode !== 'tunai' || this.uangDiterima >= this.total;
    },

    /** Pilihan nominal cepat: uang pas lalu pembulatan ke pecahan uang terdekat. */
    get quickCash() {
        const total = this.total;
        const options = new Set([total]);

        for (const step of [5000, 10000, 20000, 50000, 100000]) {
            const rounded = Math.ceil(total / step) * step;
            if (rounded > total) {
                options.add(rounded);
            }
        }

        return [...options].filter((v) => v > 0).slice(0, 5);
    },

    openPayment() {
        if (!this.cart.length) {
            this.notify('Keranjang masih kosong.', 'error');
            return;
        }
        this.showPayment = true;
        this.metode = 'tunai';
        this.uangInput = '';
        this.referensi = '';
        this.error = '';
        this.clientRef ??= uuid();
        this.$nextTick(() => this.$refs.cash?.focus());
    },

    closePayment() {
        this.showPayment = false;
        this.focusSearch();
    },

    selectMetode(metode) {
        this.metode = metode;
        this.error = '';
        this.$nextTick(() => ({ tunai: this.$refs.cash, transfer: this.$refs.refTransfer, qris: this.$refs.refQris })[metode]?.focus());
    },

    async submit() {
        if (!this.canSubmit) {
            return;
        }

        this.processing = true;
        this.error = '';

        try {
            const response = await fetch(config.storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': config.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    items: this.cart.map((i) => ({ product_id: i.id, qty: i.qty })),
                    diskon: this.diskon,
                    metode_bayar: this.metode,
                    uang_diterima: this.metode === 'tunai' ? this.uangDiterima : null,
                    referensi_bayar: this.metode === 'tunai' ? null : this.referensi || null,
                    client_ref: this.clientRef,
                }),
            });

            const data = await response.json().catch(() => ({}));

            if (response.ok) {
                this.cart = [];
                this.clearSavedCart();
                window.location.href = data.struk_url;
                return;
            }

            if (response.status === 422) {
                this.error = Object.values(data.errors ?? {}).flat().join(' ') || data.message;
                await this.refreshProducts();
            } else if (response.status === 419) {
                this.error = 'Sesi telah berakhir. Muat ulang halaman lalu coba lagi.';
            } else if (response.status === 409) {
                window.location.href = config.shiftUrl;
            } else {
                this.error = data.message || 'Terjadi kesalahan pada server. Coba lagi.';
            }
        } catch {
            // client_ref tetap sama sehingga percobaan ulang tidak membuat transaksi ganda.
            this.error = 'Koneksi ke server terputus. Periksa jaringan lalu coba lagi.';
        } finally {
            this.processing = false;
        }
    },

    /** Menyegarkan stok dari server dan menyesuaikan isi keranjang. */
    async refreshProducts() {
        try {
            const response = await fetch(config.productsUrl, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;

            this.products = await response.json();
            const byId = new Map(this.products.map((p) => [p.id, p]));

            this.cart = this.cart
                .filter((item) => byId.has(item.id))
                .map((item) => {
                    const p = byId.get(item.id);
                    return { ...item, harga: p.harga, stok: p.stok, qty: Math.min(item.qty, Math.max(p.stok, 0)) };
                })
                .filter((item) => item.qty > 0);
        } catch {
            // Abaikan; data lama tetap dipakai.
        }
    },

    // ---- Lain-lain ----------------------------------------------------------

    notify(message, type = 'info') {
        this.toast = { show: true, message, type };
        clearTimeout(this._toastTimer);
        this._toastTimer = setTimeout(() => (this.toast.show = false), 2500);
    },

    restoreCart() {
        try {
            const saved = JSON.parse(sessionStorage.getItem(CART_KEY) ?? 'null');
            if (!saved?.cart?.length) return;

            const byId = new Map(this.products.map((p) => [p.id, p]));
            this.cart = saved.cart
                .filter((i) => byId.has(i.id))
                .map((i) => ({ ...i, harga: byId.get(i.id).harga, stok: byId.get(i.id).stok, qty: Math.min(i.qty, byId.get(i.id).stok) }))
                .filter((i) => i.qty > 0);
            this.diskonInput = saved.diskonInput ?? '';
            this.diskonMode = saved.diskonMode ?? 'nominal';
        } catch {
            // sessionStorage tidak tersedia
        }
    },

    saveCart() {
        try {
            sessionStorage.setItem(CART_KEY, JSON.stringify({ cart: this.cart, diskonInput: this.diskonInput, diskonMode: this.diskonMode }));
        } catch {
            // sessionStorage tidak tersedia
        }
    },

    clearSavedCart() {
        try {
            sessionStorage.removeItem(CART_KEY);
        } catch {
            // sessionStorage tidak tersedia
        }
    },
});
