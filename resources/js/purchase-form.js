import { rupiah } from './format';

/**
 * Form barang masuk (UC-08): baris produk dinamis + input barcode.
 */
export default (config) => ({
    products: config.products,
    items: config.old?.length ? config.old : [],
    scan: '',

    init() {
        if (!this.items.length) {
            this.addRow();
        }
    },

    rupiah,

    product(id) {
        return this.products.find((p) => p.id === Number(id));
    },

    addRow(product = null) {
        this.items.push({
            key: Math.random().toString(36).slice(2),
            product_id: product?.id ?? '',
            qty: 1,
            harga_beli: product?.harga_beli ?? '',
        });
    },

    removeRow(index) {
        this.items.splice(index, 1);
        if (!this.items.length) {
            this.addRow();
        }
    },

    productChanged(row) {
        const p = this.product(row.product_id);
        if (p) {
            row.harga_beli = p.harga_beli;
        }
    },

    scanEnter() {
        const code = this.scan.trim().toLowerCase();
        if (!code) return;

        const p = this.products.find((x) => x.kode.toLowerCase() === code);
        this.scan = '';

        if (!p) {
            alert('Kode produk tidak ditemukan.');
            return;
        }

        const existing = this.items.find((r) => Number(r.product_id) === p.id);
        if (existing) {
            existing.qty = Number(existing.qty) + 1;
            return;
        }

        const empty = this.items.find((r) => !r.product_id);
        if (empty) {
            empty.product_id = p.id;
            empty.harga_beli = p.harga_beli;
        } else {
            this.addRow(p);
        }
    },

    subtotal(row) {
        return (Number(row.qty) || 0) * (Number(row.harga_beli) || 0);
    },

    get total() {
        return this.items.reduce((sum, row) => sum + this.subtotal(row), 0);
    },
});
