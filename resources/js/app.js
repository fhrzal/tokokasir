import './bootstrap';
import Alpine from 'alpinejs';
import pos from './pos';
import purchaseForm from './purchase-form';
import rupiahChart from './charts';
import { parseNumber, rupiah } from './format';

window.Alpine = Alpine;

Alpine.data('pos', pos);
Alpine.data('purchaseForm', purchaseForm);
Alpine.data('rupiahChart', rupiahChart);

// Utilitas kecil untuk input nominal di form Blade.
Alpine.magic('rupiah', () => rupiah);
Alpine.magic('parseNumber', () => parseNumber);

Alpine.start();
