/**
 * Pemindai barcode lewat kamera untuk halaman kasir.
 *
 * Memakai BarcodeDetector bawaan browser bila tersedia (Chrome Android,
 * ChromeOS, macOS). Browser lain memakai ZXing (WebAssembly) yang dimuat
 * dari server toko sendiri, sehingga tetap berfungsi tanpa internet.
 * Hanya area di dalam bingkai bidik yang dipindai, agar barcode produk lain
 * di sekitar meja kasir tidak ikut terbaca.
 */

const FORMATS = ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128', 'code_39', 'qr_code'];

/** Lebar maksimal potongan gambar yang dipindai (piksel). */
const MAX_SCAN_WIDTH = 1280;

let detectorPromise = null;
let audioContext = null;

async function createDetector() {
    if ('BarcodeDetector' in window) {
        try {
            const supported = await window.BarcodeDetector.getSupportedFormats();
            const formats = FORMATS.filter((f) => supported.includes(f));
            if (formats.length) {
                return new window.BarcodeDetector({ formats });
            }
        } catch {
            // Lanjut ke ZXing.
        }
    }

    const [{ BarcodeDetector, prepareZXingModule }, { default: wasmUrl }] = await Promise.all([
        import('barcode-detector/ponyfill'),
        import('zxing-wasm/reader/zxing_reader.wasm?url'),
    ]);

    await prepareZXingModule({
        overrides: { locateFile: (path, prefix) => (path.endsWith('.wasm') ? wasmUrl : prefix + path) },
        fireImmediately: true,
    });

    return new BarcodeDetector({ formats: FORMATS });
}

/** Kamera hanya dapat diakses dari HTTPS atau localhost. */
export function cameraSupported() {
    return window.isSecureContext && !!navigator.mediaDevices?.getUserMedia;
}

/** Pesan galat kamera yang mudah dipahami kasir. */
export function cameraErrorMessage(error) {
    switch (error?.name) {
        case 'NotAllowedError':
        case 'SecurityError':
            return 'Izin kamera ditolak. Izinkan akses kamera untuk situs ini di pengaturan browser.';
        case 'NotFoundError':
        case 'OverconstrainedError':
            return 'Kamera tidak ditemukan pada perangkat ini.';
        case 'NotReadableError':
        case 'AbortError':
            return 'Kamera sedang dipakai aplikasi lain. Tutup aplikasi tersebut lalu coba lagi.';
        case 'DetectorError':
            return 'Pemindai barcode gagal dimuat. Muat ulang halaman lalu coba lagi.';
        default:
            return 'Kamera tidak dapat dinyalakan. Muat ulang halaman lalu coba lagi.';
    }
}

/** Bunyi singkat: nada tinggi untuk berhasil, nada rendah untuk gagal. */
export function beep(ok = true) {
    try {
        audioContext ??= new (window.AudioContext || window.webkitAudioContext)();
        if (audioContext.state === 'suspended') {
            audioContext.resume();
        }

        const oscillator = audioContext.createOscillator();
        const gain = audioContext.createGain();
        const now = audioContext.currentTime;
        const duration = ok ? 0.08 : 0.25;

        oscillator.type = ok ? 'sine' : 'square';
        oscillator.frequency.value = ok ? 1800 : 220;
        gain.gain.setValueAtTime(0.15, now);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + duration);
        oscillator.connect(gain).connect(audioContext.destination);
        oscillator.start(now);
        oscillator.stop(now + duration);
    } catch {
        // Bunyi bersifat tambahan.
    }

    navigator.vibrate?.(ok ? 40 : [60, 60, 60]);
}

export default class BarcodeScanner {
    /**
     * @param {HTMLVideoElement} video
     * @param {{ onDetect: (code: string) => void, roi?: { width: number, height: number }, interval?: number }} options
     *        roi: ukuran bingkai bidik relatif terhadap elemen video (harus sama dengan tampilan).
     */
    constructor(video, { onDetect, roi = { width: 0.8, height: 0.5 }, interval = 100 }) {
        this.video = video;
        this.onDetect = onDetect;
        this.roi = roi;
        this.interval = interval;
        this.canvas = document.createElement('canvas');
        this.context = this.canvas.getContext('2d', { willReadFrequently: true });
        this.stream = null;
        this.running = false;
        this.timer = null;
        this.session = 0;
    }

    get active() {
        return this.running;
    }

    async start(deviceId = null) {
        this.stop();
        const session = ++this.session;

        detectorPromise ??= createDetector().catch((error) => {
            detectorPromise = null;
            throw error;
        });

        const size = { width: { ideal: 1280 }, height: { ideal: 720 } };
        const [detector, camera] = await Promise.allSettled([
            detectorPromise,
            navigator.mediaDevices.getUserMedia({
                audio: false,
                video: deviceId ? { deviceId: { exact: deviceId }, ...size } : { facingMode: { ideal: 'environment' }, ...size },
            }),
        ]);

        if (camera.status === 'rejected') {
            throw camera.reason;
        }

        // Pemindai gagal dimuat, atau stop() dipanggil saat kamera masih dinyalakan.
        if (detector.status === 'rejected' || session !== this.session) {
            camera.value.getTracks().forEach((track) => track.stop());
            if (detector.status === 'rejected') {
                throw Object.assign(new Error('Pemindai barcode gagal dimuat.'), { name: 'DetectorError', cause: detector.reason });
            }
            return;
        }

        this.detector = detector.value;
        this.stream = camera.value;
        this.video.srcObject = this.stream;

        try {
            await this.video.play();
        } catch (error) {
            if (session !== this.session) {
                return;
            }
            throw error;
        }

        this.track.applyConstraints?.({ advanced: [{ focusMode: 'continuous' }] }).catch(() => {});

        this.running = true;
        this.loop(session);
    }

    stop() {
        this.session++;
        this.running = false;
        clearTimeout(this.timer);
        this.stream?.getTracks().forEach((track) => track.stop());
        this.stream = null;
        this.video.srcObject = null;
    }

    get track() {
        return this.stream?.getVideoTracks()[0] ?? null;
    }

    get deviceId() {
        return this.track?.getSettings?.().deviceId ?? null;
    }

    get torchSupported() {
        return !!this.track?.getCapabilities?.().torch;
    }

    async setTorch(on) {
        await this.track?.applyConstraints({ advanced: [{ torch: on }] });
    }

    async cameras() {
        const devices = await navigator.mediaDevices.enumerateDevices();
        return devices.filter((device) => device.kind === 'videoinput');
    }

    async loop(session) {
        if (session !== this.session) {
            return;
        }

        if (this.video.readyState >= 2) {
            try {
                const codes = await this.detector.detect(this.frame());
                const code = codes.find((c) => c.rawValue)?.rawValue;
                if (code && session === this.session) {
                    this.onDetect(code.trim());
                }
            } catch {
                // Frame gagal dibaca; coba frame berikutnya.
            }
        }

        if (session === this.session) {
            this.timer = setTimeout(() => this.loop(session), this.interval);
        }
    }

    /**
     * Potongan frame kamera sesuai bingkai bidik. Video ditampilkan dengan
     * object-fit: cover, sehingga koordinat layar dikonversi ke piksel video.
     */
    frame() {
        const { videoWidth: vw, videoHeight: vh, clientWidth: w, clientHeight: h } = this.video;

        let sx = 0;
        let sy = 0;
        let sw = vw;
        let sh = vh;

        if (w && h) {
            const scale = Math.max(w / vw, h / vh);
            const offsetX = (w - vw * scale) / 2;
            const offsetY = (h - vh * scale) / 2;
            const rw = w * this.roi.width;
            const rh = h * this.roi.height;

            sx = Math.max(0, ((w - rw) / 2 - offsetX) / scale);
            sy = Math.max(0, ((h - rh) / 2 - offsetY) / scale);
            sw = Math.min(vw - sx, rw / scale);
            sh = Math.min(vh - sy, rh / scale);
        }

        const ratio = Math.min(1, MAX_SCAN_WIDTH / sw);
        this.canvas.width = Math.round(sw * ratio);
        this.canvas.height = Math.round(sh * ratio);
        this.context.drawImage(this.video, sx, sy, sw, sh, 0, 0, this.canvas.width, this.canvas.height);

        return this.canvas;
    }
}
