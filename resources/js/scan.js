import { Html5Qrcode } from 'html5-qrcode';

/**
 * Utilitas scanner QR terpusat (html5-qrcode, 100% lokal, tanpa API eksternal).
 * Dipakai oleh modal pada form transaksi dan halaman Scan QR.
 *
 * Fitur:
 * - Normalisasi hasil decode (trim, ekstrak token CYL-XXXXXXXX dari URL/teks).
 * - Fallback berurutan: deviceId exact -> facingMode environment -> facingMode user -> daftar kamera satu per satu.
 * - Scan dari file gambar (fallback saat kamera tidak tersedia / insecure context).
 */

let activeScanner = null;

function friendlyError(err) {
    const name = err && err.name ? err.name : '';
    const msg = (err && err.message ? String(err.message) : '').toLowerCase();
    if (typeof window !== 'undefined' && window.isSecureContext === false) {
        return 'Browser memblokir kamera karena koneksi tidak aman (bukan HTTPS / localhost). Gunakan https:// atau http://localhost, atau gunakan "Upload Gambar QR" di bawah.';
    }
    if (name === 'NotAllowedError' || msg.includes('permission denied') || msg.includes('permission dismissed')) {
        return 'Akses kamera ditolak. Klik ikon kamera di address bar browser lalu pilih "Allow", kemudian tekan "Scan Lagi".';
    }
    if (name === 'NotFoundError' || msg.includes('no video input')) {
        return 'Kamera tidak ditemukan pada perangkat. Gunakan "Upload Gambar QR" atau input token manual.';
    }
    if (name === 'NotReadableError' || msg.includes('could not start video')) {
        return 'Kamera sedang digunakan aplikasi lain (Zoom/Meet/dll). Tutup aplikasi tersebut lalu tekan "Scan Lagi".';
    }
    if (name === 'OverconstrainedError') {
        return 'Kamera tidak memenuhi kriteria. Coba pilih kamera lain pada opsi "Pilih Kamera".';
    }
    if (msg.includes('secure')) {
        return 'Kamera membutuhkan koneksi aman (HTTPS atau localhost). Gunakan "Upload Gambar QR" sebagai alternatif.';
    }
    return 'Kamera tidak dapat digunakan. Izinkan akses kamera, atau gunakan "Upload Gambar QR" / input manual.';
}

function pickDefaultCamera(cameras) {
    if (!cameras || cameras.length === 0) return null;
    // Prioritaskan kamera belakang (smartphone), fallback kamera pertama (laptop).
    const back = cameras.find((c) => /back|rear|environment/i.test(c.label || ''));
    return back || cameras[0];
}

function fillCameraSelect(selectEl, cameras, currentId) {
    if (!selectEl) return;
    selectEl.innerHTML = '';
    cameras.forEach((camera, index) => {
        const option = document.createElement('option');
        option.value = camera.id;
        option.textContent = camera.label || `Kamera ${index + 1}`;
        if (camera.id === currentId) option.selected = true;
        selectEl.appendChild(option);
    });
    selectEl.style.display = cameras.length > 1 ? '' : 'none';
    const wrapper = selectEl.closest('[data-camera-wrap]');
    if (wrapper) wrapper.style.display = cameras.length > 1 ? '' : 'none';
}

/**
 * Normalisasi hasil decode QR menjadi token tabung.
 * - Trim whitespace / newline yang sering ikut dari scanner.
 * - Jika QR berisi URL (mis. https://.../scan/CYL-ABC), ambil segmen terakhir yang cocok.
 * - Cocokkan pola CYL-XXXXXXXX (8+ char alfanumerik), case-insensitive -> uppercase.
 * - Selain itu kembalikan teks mentah (untuk cylinder_code / serial).
 */
function normalizeToken(raw) {
    if (raw === null || raw === undefined) return '';
    let text = String(raw).trim().replace(/^["']+|["']+$/g, '');
    if (!text) return '';
    // Ambil baris pertama yang tidak kosong (beberapa QR multiline).
    text = text.split(/[\r\n]+/).map((s) => s.trim()).find((s) => s.length > 0) || '';
    // Jika berupa URL, ambil path terakhir.
    const cylMatch = text.match(/CYL-[A-Z0-9]{4,}/i);
    if (cylMatch) return cylMatch[0].toUpperCase();
    // Jika URL tanpa pola CYL, ambil segmen terakhir sebagai token generik.
    if (/^https?:\/\//i.test(text)) {
        const parts = text.split('/').map((s) => s.trim()).filter(Boolean);
        const last = (parts[parts.length - 1] || '').split('?')[0].split('#')[0];
        if (last) return last.toUpperCase();
    }
    return text.toUpperCase();
}

async function stopActiveScanner() {
    if (!activeScanner) return;
    const scanner = activeScanner;
    activeScanner = null;
    try {
        // Html5Qrcode.state: STARTED / STOPPED — stop() hanya valid saat STARTED.
        if (scanner.getState && scanner.getState() === 2 /* Html5QrcodeScannerState.SCANNING */) {
            await scanner.stop();
        } else {
            try {
                await scanner.stop();
            } catch (e) {
                // Belum berjalan: abaikan.
            }
        }
    } catch (e) {
        // Abaikan.
    }
    try {
        scanner.clear();
    } catch (e) {
        // Abaikan.
    }
}

window.QrScanner = {
    friendlyError,
    normalizeToken,

    get running() {
        return activeScanner !== null;
    },

    /**
     * Scan QR dari file gambar (fallback tanpa kamera).
     * @returns {Promise<string>} decoded text mentah (belum dinormalisasi).
     */
    async scanFile(file, readerId) {
        const tmpId = readerId && document.getElementById(readerId)
            ? readerId
            : (() => {
                const el = document.createElement('div');
                el.id = `qr-tmp-${Date.now()}`;
                el.style.display = 'none';
                document.body.appendChild(el);
                return el.id;
            })();
        const tmp = new Html5Qrcode(tmpId, { verbose: false });
        try {
            return await tmp.scanFile(file, true);
        } finally {
            try {
                tmp.clear();
            } catch (e) {
                // abaikan
            }
            if (!readerId || tmpId !== readerId) {
                document.getElementById(tmpId)?.remove();
            }
        }
    },

    /**
     * Buka kamera dan mulai memindai.
     * opts: { readerId, selectId?, statusId?, errorId?, fileId?, onDecode, facingMode? }
     */
    async open(opts) {
        await stopActiveScanner();

        const readerEl = document.getElementById(opts.readerId);
        const statusEl = opts.statusId ? document.getElementById(opts.statusId) : null;
        const errorEl = opts.errorId ? document.getElementById(opts.errorId) : null;
        const selectEl = opts.selectId ? document.getElementById(opts.selectId) : null;
        if (!readerEl) return;

        // Bersihkan sisa render sebelumnya (penting saat modal dibuka ulang).
        readerEl.innerHTML = '';

        const showError = (message) => {
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.classList.remove('hidden');
            }
            if (statusEl) statusEl.textContent = '';
        };
        const hideError = () => {
            if (errorEl) {
                errorEl.textContent = '';
                errorEl.classList.add('hidden');
            }
        };
        const setStatus = (message) => {
            if (statusEl) statusEl.textContent = message;
        };

        const scanner = new Html5Qrcode(opts.readerId, { verbose: false });
        activeScanner = scanner;

        const startWith = async (cameraId) => {
            hideError();
            setStatus('Menyiapkan kamera...');
            try {
                try {
                    await scanner.stop();
                } catch (e) {
                    // Belum berjalan: abaikan.
                }
            } catch (e) {
                // abaikan
            }
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };
            const onSuccess = (decodedText) => {
                if (opts.onDecode) opts.onDecode(decodedText);
            };
            const attempts = [];
            if (cameraId) {
                attempts.push({ deviceId: { exact: cameraId } });
            } else if (opts.facingMode) {
                attempts.push({ facingMode: opts.facingMode });
            } else {
                attempts.push({ facingMode: 'environment' });
            }
            // Fallback penting untuk laptop tanpa kamera belakang.
            attempts.push({ facingMode: 'user' });
            if (cameraId) {
                attempts.push({ facingMode: 'environment' });
                attempts.push({ facingMode: 'user' });
            }

            let lastErr = null;
            for (const spec of attempts) {
                try {
                    await scanner.start(spec, config, onSuccess, () => {});
                    // Jika scanner diganti (modal ditutup saat start), hentikan.
                    if (activeScanner !== scanner) {
                        try {
                            await scanner.stop();
                        } catch (e) {
                            // abaikan
                        }
                        return;
                    }
                    setStatus('Arahkan kamera ke QR Code tabung.');
                    return;
                } catch (err) {
                    lastErr = err;
                }
            }
            showError(friendlyError(lastErr));
        };

        // Enumerasi kamera — kegagalan di sini bukan fatal:
        // langsung coba facingMode agar tetap jalan di browser yang
        // menyembunyikan daftar kamera sebelum izin diberikan.
        let cameras = [];
        try {
            cameras = await Html5Qrcode.getCameras();
        } catch (err) {
            // Tetap coba start tanpa daftar kamera; pesan error hanya jika start gagal.
            cameras = [];
        }

        // Jika modal ditutup selama getCameras, batalkan.
        if (activeScanner !== scanner) return;

        if (cameras && cameras.length > 0) {
            const preferred = pickDefaultCamera(cameras);
            fillCameraSelect(selectEl, cameras, preferred ? preferred.id : null);
            if (selectEl) {
                selectEl.onchange = () => {
                    if (selectEl.value) startWith(selectEl.value);
                };
            }
            await startWith(preferred ? preferred.id : null);
        } else {
            if (selectEl) {
                selectEl.innerHTML = '';
                selectEl.style.display = 'none';
                const wrapper = selectEl.closest('[data-camera-wrap]');
                if (wrapper) wrapper.style.display = 'none';
            }
            await startWith(null);
        }
    },

    async close() {
        await stopActiveScanner();
        // Bersihkan file input agar memilih file yang sama tetap memicu change.
        try {
            document.querySelectorAll('input[data-qr-file]').forEach((el) => {
                el.value = '';
            });
        } catch (e) {
            // abaikan
        }
    },
};

// Bersihkan stream kamera saat pindah halaman agar tidak menggantung.
window.addEventListener('pagehide', () => {
    stopActiveScanner();
});
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden') {
        stopActiveScanner();
    }
});
