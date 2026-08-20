/**
 * SGR QR-Code Generator - Interactive JavaScript
 * Real ISO QR Encoder with Level H Error Correction
 * Dynamic Org Branding, Edit Mode, Eyedropper, Format/Size Download
 */

let currentQRColor = '#0284c7';
let currentQRStyle = 'blue';
let currentToken = 'sgr_demo_01';
let currentTargetUrl = 'https://drive.google.com/file/d/sample';
let currentTitle = 'แผนม.ต้น ภาคเรียนที่ 1 ปีการศึกษา 2569';
let currentCenterLogo = window.DEFAULT_ORG_LOGO || 'assets/images/logo-sgr.png';
let currentDownloadSize = 1000;
let currentDownloadFormat = 'png';

let isEditMode = false;
let editingId = 0;

function initApp() {
    if (window.DEFAULT_ORG_LOGO) {
        currentCenterLogo = window.DEFAULT_ORG_LOGO;
    }
    initFormListeners();
    initColorPicker();
    initLogoPicker();
    initDownloadSettings();
    updatePreviewCard();
    generateQRCode();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
} else {
    initApp();
}

// Setup Realtime Live Input Listeners
function initFormListeners() {
    const titleInput = document.getElementById('title');
    const urlInput = document.getElementById('target_url');
    const catInput = document.getElementById('category');

    if (titleInput) {
        ['input', 'keyup', 'change', 'paste'].forEach(evt => {
            titleInput.addEventListener(evt, () => {
                setTimeout(updatePreviewCard, 10);
            });
        });
    }

    if (urlInput) {
        ['input', 'keyup', 'change', 'paste'].forEach(evt => {
            urlInput.addEventListener(evt, () => {
                setTimeout(() => {
                    updatePreviewCard();
                    generateQRCode();
                }, 10);
            });
        });
    }

    if (catInput) {
        catInput.addEventListener('change', () => {
            updatePreviewCard();
            const val = catInput.value;
            if (val.includes('Google Drive') || val.includes('Drive')) {
                selectPresetLogo('assets/images/icons/icon-drive.svg');
            } else if (val.includes('Google Form') || val.includes('แบบฟอร์ม')) {
                selectPresetLogo('assets/images/icons/icon-form.svg');
            } else if (val.includes('LINE') || val.includes('Line')) {
                selectPresetLogo('assets/images/icons/icon-line.svg');
            } else if (val.includes('เว็บไซต์')) {
                selectPresetLogo(window.DEFAULT_ORG_LOGO || 'assets/images/logo-sgr.png');
            }
        });
    }
}

// Update Live Preview Card (Right Box)
function updatePreviewCard() {
    const titleInput = document.getElementById('title');
    const urlInput = document.getElementById('target_url');
    const catInput = document.getElementById('category');

    const pvTitle = document.getElementById('preview_title');
    const pvUrl = document.getElementById('preview_url');
    const pvCat = document.getElementById('preview_category');

    const titleVal = titleInput ? titleInput.value.trim() || 'ชื่อ QR-Code ตัวอย่าง' : 'ชื่อ QR-Code';
    let urlVal = urlInput ? urlInput.value.trim() || 'https://www.google.com' : 'https://www.google.com';
    const catVal = catInput ? catInput.value : 'ทั่วไป';

    currentTitle = titleVal;
    currentTargetUrl = urlVal;

    if (pvTitle) pvTitle.textContent = titleVal;
    if (pvUrl) {
        pvUrl.textContent = urlVal;
        pvUrl.href = urlVal.startsWith('http') ? urlVal : 'https://' + urlVal;
    }
    if (pvCat) pvCat.textContent = catVal;
}

// Logo & Icon Picker Setup
function initLogoPicker() {
    const logoBtns = document.querySelectorAll('.logo-preset-btn');
    logoBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const logoPath = btn.dataset.logo;
            selectPresetLogo(logoPath, btn);
        });
    });

    const customInput = document.getElementById('custom_logo_input');
    if (customInput) {
        customInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    showToast('ขนาดไฟล์รูปภาพต้องไม่เกิน 5MB', 'warning');
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    currentCenterLogo = e.target.result;
                    
                    const customBadge = document.getElementById('custom_logo_preview_wrap');
                    const customThumb = document.getElementById('custom_logo_thumb');
                    if (customBadge && customThumb) {
                        customThumb.src = e.target.result;
                        customBadge.classList.remove('hidden');
                    }

                    document.querySelectorAll('.logo-preset-btn').forEach(b => {
                        b.classList.remove('ring-2', 'ring-blue-600', 'border-blue-600', 'bg-blue-50');
                    });

                    generateQRCode();
                    showToast('เปลี่ยนรูปภาพตรงกลางแล้ว', 'success');
                };
                reader.readAsDataURL(file);
            }
        });
    }
}

function selectPresetLogo(logoPath, activeBtn) {
    currentCenterLogo = logoPath;

    document.querySelectorAll('.logo-preset-btn').forEach(b => {
        b.classList.remove('ring-2', 'ring-blue-600', 'border-blue-600', 'bg-blue-50');
        if (b.dataset.logo === logoPath) {
            b.classList.add('ring-2', 'ring-blue-600', 'border-blue-600', 'bg-blue-50');
        }
    });

    const customBadge = document.getElementById('custom_logo_preview_wrap');
    if (customBadge && logoPath !== 'custom') {
        customBadge.classList.add('hidden');
    }

    generateQRCode();
}

function removeCustomLogo() {
    const customInput = document.getElementById('custom_logo_input');
    if (customInput) customInput.value = '';
    selectPresetLogo(window.DEFAULT_ORG_LOGO || 'assets/images/logo-sgr.png');
}

// Color Picker Setup (Expanded Colors + Native Color Picker + Eyedropper)
function initColorPicker() {
    const colorButtons = document.querySelectorAll('.qr-color-btn');
    colorButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const color = btn.dataset.color || '#0284c7';
            setColor(color, btn);
        });
    });

    const customColorInput = document.getElementById('custom_color_picker');
    const hexInput = document.getElementById('hex_color_input');

    if (customColorInput) {
        customColorInput.addEventListener('input', (e) => {
            setColor(e.target.value);
        });
    }

    if (hexInput) {
        hexInput.addEventListener('change', (e) => {
            let val = e.target.value.trim();
            if (!val.startsWith('#')) val = '#' + val;
            if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                setColor(val);
            }
        });
    }
}

function setColor(color) {
    currentQRColor = color;
    
    const colorInput = document.getElementById('qr_color');
    if (colorInput) colorInput.value = color;

    const customColorInput = document.getElementById('custom_color_picker');
    if (customColorInput) customColorInput.value = color;

    const hexInput = document.getElementById('hex_color_input');
    if (hexInput) hexInput.value = color.toUpperCase();

    document.querySelectorAll('.qr-color-btn').forEach(b => {
        b.classList.remove('ring-2', 'ring-offset-2', 'ring-blue-600', 'scale-110');
        const check = b.querySelector('.check-icon');
        if (check) check.classList.add('hidden');

        if (b.dataset.color && b.dataset.color.toLowerCase() === color.toLowerCase()) {
            b.classList.add('ring-2', 'ring-offset-2', 'ring-blue-600', 'scale-110');
            if (check) check.classList.remove('hidden');
        }
    });

    generateQRCode();
}

function pickColorWithEyedropper() {
    if ('EyeDropper' in window) {
        const eyeDropper = new EyeDropper();
        eyeDropper.open().then(result => {
            if (result && result.sRGBHex) {
                setColor(result.sRGBHex);
                showToast(`ดูดสีสำเร็จ: ${result.sRGBHex.toUpperCase()}`, 'success');
            }
        }).catch(() => {});
    } else {
        const customColorInput = document.getElementById('custom_color_picker');
        if (customColorInput) customColorInput.click();
    }
}

// Download Settings (Size & Format Listeners)
function initDownloadSettings() {
    const sizeSelect = document.getElementById('download_size_select');
    if (sizeSelect) {
        sizeSelect.addEventListener('change', (e) => {
            currentDownloadSize = parseInt(e.target.value) || 1000;
        });
    }

    const formatSelect = document.getElementById('download_format_select');
    if (formatSelect) {
        formatSelect.addEventListener('change', (e) => {
            currentDownloadFormat = e.target.value || 'png';
        });
    }
}

// Generate 100% Scannable QR Code on Canvas with Center Logo
function generateQRCode() {
    const container = document.getElementById('qrcode_container');
    if (!container) return;

    let target = currentTargetUrl || 'https://www.google.com';
    if (!target.startsWith('http://') && !target.startsWith('https://')) {
        target = 'https://' + target;
    }

    container.innerHTML = '';

    const size = 320;
    
    const qrResult = QRCode(container, {
        text: target,
        width: size,
        height: size,
        colorDark: currentQRColor,
        colorLight: "#ffffff"
    });

    const canvas = qrResult.canvas;
    canvas.id = 'qr_canvas';
    canvas.className = 'w-full h-full object-contain';

    if (currentCenterLogo && currentCenterLogo !== 'none') {
        const ctx = canvas.getContext('2d');
        drawCenterLogo(ctx, size, currentCenterLogo);
    }
}

// Draw Logo/Icon in the exact center (Safe Size within Level-H 30% tolerance)
function drawCenterLogo(ctx, size, logoSrc, callback) {
    const logoSize = Math.floor(size * 0.21);
    const logoX = (size - logoSize) / 2;
    const logoY = (size - logoSize) / 2;

    ctx.save();
    ctx.beginPath();
    ctx.arc(size / 2, size / 2, (logoSize / 2) + Math.max(2, Math.floor(size * 0.01)), 0, Math.PI * 2, true);
    ctx.fillStyle = '#ffffff';
    ctx.shadowColor = 'rgba(0, 0, 0, 0.15)';
    ctx.shadowBlur = 4;
    ctx.fill();
    ctx.lineWidth = Math.max(1.5, Math.floor(size * 0.006));
    ctx.strokeStyle = '#e2e8f0';
    ctx.stroke();
    ctx.restore();

    const logoImg = new Image();
    logoImg.crossOrigin = 'Anonymous';
    logoImg.onload = function() {
        ctx.save();
        ctx.beginPath();
        ctx.arc(size / 2, size / 2, logoSize / 2, 0, Math.PI * 2, true);
        ctx.closePath();
        ctx.clip();
        ctx.drawImage(logoImg, logoX, logoY, logoSize, logoSize);
        ctx.restore();
        if (callback) callback();
    };
    logoImg.onerror = function() {
        if (callback) callback();
    };
    logoImg.src = logoSrc;
}

// Main Download Function supporting Custom Format (PNG/JPG/SVG) and Size (500/1000/2000px)
function executeDownload() {
    const format = document.getElementById('download_format_select')?.value || currentDownloadFormat || 'png';
    const size = parseInt(document.getElementById('download_size_select')?.value) || currentDownloadSize || 1000;
    const name = document.getElementById('title')?.value || 'sgr_qrcode';
    const cleanName = name.replace(/[^a-zA-Z0-9ก-๙]/g, '_');

    let target = currentTargetUrl || 'https://www.google.com';
    if (!target.startsWith('http://') && !target.startsWith('https://')) {
        target = 'https://' + target;
    }

    if (format === 'svg') {
        downloadVectorSVG(cleanName, target, size);
        return;
    }

    const offscreenDiv = document.createElement('div');
    const qrResult = QRCode(offscreenDiv, {
        text: target,
        width: size,
        height: size,
        colorDark: currentQRColor,
        colorLight: '#ffffff'
    });

    const canvas = qrResult.canvas;
    const ctx = canvas.getContext('2d');

    function saveBlob() {
        const link = document.createElement('a');
        if (format === 'jpg' || format === 'jpeg') {
            link.download = `QR_${cleanName}_${size}px.jpg`;
            link.href = canvas.toDataURL('image/jpeg', 0.95);
        } else {
            link.download = `QR_${cleanName}_${size}px.png`;
            link.href = canvas.toDataURL('image/png', 1.0);
        }
        link.click();
        showToast(`ดาวน์โหลดไฟล์ ${format.toUpperCase()} (${size}x${size}px) เรียบร้อยแล้ว`, 'success');
    }

    if (currentCenterLogo && currentCenterLogo !== 'none') {
        drawCenterLogo(ctx, size, currentCenterLogo, saveBlob);
    } else {
        saveBlob();
    }
}

// Download Vector SVG
function downloadVectorSVG(cleanName, target, size) {
    const offscreenDiv = document.createElement('div');
    const qrResult = QRCode(offscreenDiv, {
        text: target,
        width: size,
        height: size,
        colorDark: currentQRColor,
        colorLight: '#ffffff'
    });

    const dataUrl = qrResult.canvas.toDataURL('image/png');
    const svgContent = `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
  <rect width="100%" height="100%" fill="#ffffff"/>
  <image href="${dataUrl}" width="${size}" height="${size}" />
</svg>`;

    const blob = new Blob([svgContent], { type: 'image/svg+xml;charset=utf-8' });
    const link = document.createElement('a');
    link.download = `QR_${cleanName}.svg`;
    link.href = URL.createObjectURL(blob);
    link.click();
    showToast(`ดาวน์โหลดเวกเตอร์ SVG เรียบร้อยแล้ว`, 'success');
}

// Form Submit: Create or Update QR Code
function submitCreateForm() {
    const titleInput = document.getElementById('title');
    const urlInput = document.getElementById('target_url');
    const catInput = document.getElementById('category');

    const title = titleInput ? titleInput.value.trim() : '';
    let url = urlInput ? urlInput.value.trim() : '';
    const category = catInput ? catInput.value : 'ทั่วไป';

    if (!title || !url) {
        showToast('กรุณากรอก "ชื่อ QR-Code" และ "แนบลิงก์ URL"', 'warning');
        if (!title && titleInput) titleInput.focus();
        else if (!url && urlInput) urlInput.focus();
        return;
    }

    if (!url.startsWith('http://') && !url.startsWith('https://')) {
        url = 'https://' + url;
        if (urlInput) urlInput.value = url;
    }

    const formData = new FormData();
    formData.append('action', isEditMode ? 'update' : 'create');
    if (isEditMode) {
        formData.append('id', editingId);
    }
    formData.append('title', title);
    formData.append('target_url', url);
    formData.append('category', category);
    formData.append('qr_color', currentQRColor);
    formData.append('qr_style', currentQRStyle);

    const btn = document.getElementById('btn_submit_create');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> กำลังบันทึก...';
    }

    fetch('api/qrcode.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = isEditMode 
                ? '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> <span>บันทึกการแก้ไข</span>'
                : '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> <span>สร้าง & บันทึกลงระบบ</span>';
        }

        if (res.success) {
            showToast(isEditMode ? 'บันทึกการแก้ไขเรียบร้อยแล้ว!' : 'บันทึกและสร้าง QR-Code สำเร็จแล้ว!', 'success');
            if (res.token) currentToken = res.token;
            currentTargetUrl = res.target_url;
            updatePreviewCard();
            generateQRCode();
            refreshRecentTable();

            if (isEditMode) {
                cancelEditMode();
            }
        } else {
            showToast(res.message || 'เกิดข้อผิดพลาดในการบันทึก', 'error');
        }
    })
    .catch(err => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> <span>สร้าง & บันทึกลงระบบ</span>';
        }
        showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้: ' + err, 'error');
    });
}

// Edit QR Item (Load into form)
function editQRItem(id) {
    fetch(`api/qrcode.php?action=get&id=${id}`)
    .then(r => r.json())
    .then(res => {
        if (res.success && res.data) {
            const item = res.data;
            isEditMode = true;
            editingId = item.id;

            const titleInput = document.getElementById('title');
            const urlInput = document.getElementById('target_url');
            const catInput = document.getElementById('category');

            if (titleInput) titleInput.value = item.title;
            if (urlInput) urlInput.value = item.target_url;
            if (catInput) catInput.value = item.category;

            if (item.qr_color) setColor(item.qr_color);

            // Update button text & show cancel button
            const btn = document.getElementById('btn_submit_create');
            if (btn) {
                btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> <span>บันทึกการแก้ไข</span>';
            }

            const cancelBtn = document.getElementById('btn_cancel_edit');
            if (cancelBtn) cancelBtn.classList.remove('hidden');

            const editNotice = document.getElementById('edit_notice_banner');
            if (editNotice) {
                editNotice.classList.remove('hidden');
                document.getElementById('edit_item_title').textContent = item.title;
            }

            updatePreviewCard();
            generateQRCode();

            window.scrollTo({ top: 0, behavior: 'smooth' });
            showToast(`กำลังแก้ไข QR-Code: ${item.title}`, 'info');
        } else {
            showToast('ไม่สามารถดึงข้อมูลสำหรับแก้ไขได้', 'error');
        }
    });
}

// Cancel Edit Mode
function cancelEditMode() {
    isEditMode = false;
    editingId = 0;

    const btn = document.getElementById('btn_submit_create');
    if (btn) {
        btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> <span>สร้าง & บันทึกลงระบบ</span>';
    }

    const cancelBtn = document.getElementById('btn_cancel_edit');
    if (cancelBtn) cancelBtn.classList.add('hidden');

    const editNotice = document.getElementById('edit_notice_banner');
    if (editNotice) editNotice.classList.add('hidden');
}

// Print QR Sheet
function printCard() {
    const title = document.getElementById('title')?.value || 'QR Code สกร.';
    const url = currentTargetUrl;
    const canvas = document.getElementById('qr_canvas');
    const qrData = canvas ? canvas.toDataURL('image/png') : '';
    const orgShort = window.ORG_SHORT_NAME || 'สกร.';
    const orgName = window.ORG_NAME || 'สำนักงานส่งเสริมการเรียนรู้';
    const orgSub = window.ORG_SUB || 'ประจำจังหวัด';
    const orgLogo = window.DEFAULT_ORG_LOGO || 'assets/images/logo-sgr.png';

    const win = window.open('', '_blank', 'width=700,height=800');
    win.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>พิมพ์ QR-Code - ${title}</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
            <style>body { font-family: 'Sarabun', sans-serif; }</style>
        </head>
        <body class="p-8 text-center bg-slate-50 flex flex-col items-center justify-center min-h-screen">
            <div class="bg-white border-2 border-slate-300 rounded-3xl p-8 max-w-sm w-full shadow-lg">
                <div class="flex items-center justify-center space-x-2 mb-4">
                    <img src="${orgLogo}" class="w-10 h-10 object-contain">
                    <div class="text-left">
                        <div class="font-bold text-sm text-slate-800 leading-none">${orgName}</div>
                        <div class="text-[10px] text-slate-500">${orgSub}</div>
                    </div>
                </div>
                <h2 class="text-lg font-bold text-slate-800 mb-4">${title}</h2>
                <img src="${qrData}" class="w-56 h-56 mx-auto border p-2 rounded-2xl mb-4 shadow-sm">
                <p class="text-xs text-blue-600 font-mono break-all">${url}</p>
                <p class="text-[10px] text-slate-400 mt-4">สแกนเพื่อเข้าถึงลิงก์</p>
            </div>
            <button onclick="window.print()" class="mt-6 px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl shadow-md cursor-pointer">🖨️ สั่งพิมพ์เอกสารนี้</button>
        </body>
        </html>
    `);
}

// Share Link
function shareLink() {
    const url = currentTargetUrl;
    navigator.clipboard.writeText(url).then(() => {
        showToast('คัดลอกลิงก์เรียบร้อยแล้ว: ' + url, 'success');
    }).catch(() => {
        prompt('คัดลอกลิงก์:', url);
    });
}

// Refresh Recent QR Table
function refreshRecentTable() {
    fetch('api/qrcode.php?action=list&limit=5')
    .then(r => r.json())
    .then(res => {
        if (res.success && res.data) {
            renderRecentTable(res.data);
        }
    });
}

function renderRecentTable(list) {
    const tbody = document.getElementById('recent_tbody');
    if (!tbody) return;

    tbody.innerHTML = list.map(item => `
        <tr class="border-b border-slate-100 hover:bg-slate-50/80 transition text-sm">
            <td class="py-3.5 px-4">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 border border-blue-200 flex items-center justify-center text-blue-600 font-bold text-xs">
                        QR
                    </div>
                    <div>
                        <div class="font-medium text-slate-800">${item.title}</div>
                        <a href="${item.target_url}" target="_blank" class="text-xs text-blue-600 hover:underline truncate max-w-xs block font-mono">${item.target_url}</a>
                    </div>
                </div>
            </td>
            <td class="py-3.5 px-4 text-slate-600">
                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                    ${item.category || 'ทั่วไป'}
                </span>
            </td>
            <td class="py-3.5 px-4 text-slate-500">${item.created_at_thai || item.created_at}</td>
            <td class="py-3.5 px-4 text-slate-600">${item.created_by || 'ผู้ดูแลระบบ'}</td>
            <td class="py-3.5 px-4">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd"></path></svg>
                    ใช้งานถาวร
                </span>
            </td>
            <td class="py-3.5 px-4 text-center">
                <div class="flex items-center justify-center space-x-1.5 text-slate-400">
                    <button type="button" onclick="editQRItem(${item.id})" title="แก้ไขข้อมูล QR" class="p-1.5 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </button>
                    <a href="${item.target_url}" target="_blank" title="เปิดลิงก์" class="p-1.5 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    </a>
                    <button type="button" onclick="downloadDirectQR('${item.target_url}', '${item.title}', '${item.qr_color}')" title="ดาวน์โหลด QR" class="p-1.5 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    </button>
                    <button type="button" onclick="deleteQRItem(${item.id})" title="ลบข้อมูล" class="p-1.5 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Download direct QR with true ISO encoding
function downloadDirectQR(url, title, color) {
    const format = currentDownloadFormat || 'png';
    const size = currentDownloadSize || 1000;
    const offscreenDiv = document.createElement('div');
    const qrResult = QRCode(offscreenDiv, {
        text: url,
        width: size,
        height: size,
        colorDark: color || '#0284c7',
        colorLight: '#ffffff'
    });

    const ctx = qrResult.canvas.getContext('2d');
    
    function finish() {
        const link = document.createElement('a');
        if (format === 'jpg' || format === 'jpeg') {
            link.download = `QR_${title.replace(/\s+/g, '_')}.jpg`;
            link.href = qrResult.canvas.toDataURL('image/jpeg', 0.95);
        } else {
            link.download = `QR_${title.replace(/\s+/g, '_')}.png`;
            link.href = qrResult.canvas.toDataURL('image/png');
        }
        link.click();
    }

    if (currentCenterLogo && currentCenterLogo !== 'none') {
        drawCenterLogo(ctx, size, currentCenterLogo, finish);
    } else {
        finish();
    }
}

// Delete QR Item
function deleteQRItem(id) {
    if (!confirm('คุณต้องการลบ QR-Code นี้ใช่หรือไม่?')) return;
    fetch(`api/qrcode.php?action=delete&id=${id}`)
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast('ลบเรียบร้อยแล้ว', 'success');
            refreshRecentTable();
        } else {
            showToast(res.message || 'ไม่สามารถลบได้', 'error');
        }
    });
}

// Toast Notification
function showToast(message, type = 'info') {
    let container = document.getElementById('toast_container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast_container';
        container.className = 'fixed bottom-5 right-5 z-50 flex flex-col space-y-2';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    const colors = {
        success: 'bg-emerald-600 text-white shadow-emerald-500/20',
        error: 'bg-rose-600 text-white shadow-rose-500/20',
        warning: 'bg-amber-500 text-white shadow-amber-500/20',
        info: 'bg-slate-800 text-white shadow-slate-800/20'
    };

    toast.className = `flex items-center px-4 py-3 rounded-xl shadow-lg transform transition-all duration-300 translate-y-2 opacity-0 text-sm font-medium ${colors[type] || colors.info}`;
    toast.innerHTML = `<span class="mr-2">${type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ'}</span><span>${message}</span>`;

    container.appendChild(toast);
    setTimeout(() => toast.classList.remove('translate-y-2', 'opacity-0'), 10);
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}
