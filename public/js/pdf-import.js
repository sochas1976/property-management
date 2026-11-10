/**
 * PropPulse PMS — PDF Application Importer & Handwriting/OCR Extractor
 *
 * 100% Free & Local (No-API-Key) PDF Importer for Application Edit & New Application forms:
 *  - Renders PDF pages via PDF.js for spatial text extraction & high-res page images
 *  - Calls backend multi-engine OCR parser (POST /applicants/parse-pdf)
 *  - Automatically crops & cleans the physical signature from Page 2 into the e-Signature Pad
 *  - Populates all 35+ application form fields and optionally attaches the PDF to Applicant Documents
 *  - Opens the imported PDF in a standalone movable Desktop Window (for 2nd monitor viewing)
 *    matching the exact Application Edit stylesheet, fonts, buttons, and Light/Dark Mode theme
 *    with a 1-Page-at-a-Time Full-Page Carousel (Prev/Next, Page Pills, Fit Page / Fit Width / Zoom)
 */
(function () {
    'use strict';

    const PDFJS_CDN_URL = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
    const PDFJS_WORKER_URL = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    let pdfJsLoadingPromise = null;
    let activePopoutWindow = null;

    function loadPdfJs() {
        if (window.pdfjsLib) {
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = PDFJS_WORKER_URL;
            return Promise.resolve(window.pdfjsLib);
        }
        if (pdfJsLoadingPromise) {
            return pdfJsLoadingPromise;
        }
        pdfJsLoadingPromise = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = PDFJS_CDN_URL;
            script.async = true;
            script.onload = () => {
                if (window.pdfjsLib) {
                    window.pdfjsLib.GlobalWorkerOptions.workerSrc = PDFJS_WORKER_URL;
                    resolve(window.pdfjsLib);
                } else {
                    reject(new Error('PDF.js failed to initialize'));
                }
            };
            script.onerror = () => reject(new Error('Could not load PDF.js from CDN'));
            document.head.appendChild(script);
        });
        return pdfJsLoadingPromise;
    }

    /**
     * Get the current theme ('light' or 'dark') from the main Application Edit page.
     */
    function getCurrentAppTheme() {
        const attrTheme = document.documentElement.getAttribute('data-theme');
        if (attrTheme === 'dark' || attrTheme === 'light') {
            return attrTheme;
        }
        try {
            return localStorage.getItem('ws_theme') || localStorage.getItem('hc_theme') || 'light';
        } catch (e) {
            return 'light';
        }
    }

    /**
     * Get the absolute URL of the active app.css stylesheet from the main page.
     */
    function getAppStylesheetUrl() {
        const link = document.querySelector('link[rel="stylesheet"][href*="app.css"]');
        return link ? link.href : '/css/app.css';
    }

    /**
     * Render a PDF page onto an HTMLCanvasElement and extract normalized spatial text items.
     */
    async function renderPageAndExtractText(pdfDoc, pageNum, scale = 2.2) {
        const page = await pdfDoc.getPage(pageNum);
        const viewport = page.getViewport({ scale });

        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = Math.floor(viewport.width);
        canvas.height = Math.floor(viewport.height);

        await page.render({ canvasContext: ctx, viewport }).promise;

        const textContent = await page.getTextContent();
        const rawViewport = page.getViewport({ scale: 1.0 });
        const pageW = rawViewport.width || 612;
        const pageH = rawViewport.height || 792;

        const items = [];
        const textLines = [];

        if (textContent && Array.isArray(textContent.items)) {
            textContent.items.forEach(item => {
                const str = (item.str || '').trim();
                if (!str) return;
                const tx = item.transform ? item.transform[4] : 0;
                const ty = item.transform ? item.transform[5] : 0;
                const normX = Math.max(0, Math.min(1, tx / pageW));
                const normY = Math.max(0, Math.min(1, 1 - (ty / pageH)));

                items.push({
                    page: pageNum,
                    x: Number(normX.toFixed(4)),
                    y: Number(normY.toFixed(4)),
                    str: str
                });
                textLines.push(str);
            });
        }

        return {
            pageNum,
            canvas,
            dataUrl: canvas.toDataURL('image/jpeg', 0.92),
            items,
            text: textLines.join('\n')
        };
    }

    /**
     * Crop and clean the physical signature from the rendered Page 2 canvas.
     * Removes gray/paper background using adaptive luminance thresholding and outputs a crisp PNG data URL.
     */
    function extractCleanSignatureFromCanvas(pageCanvas, cropSpec) {
        if (!pageCanvas || !cropSpec) return null;

        const cw = pageCanvas.width;
        const ch = pageCanvas.height;

        const sx = Math.max(0, Math.floor((cropSpec.x || 0.12) * cw));
        const sy = Math.max(0, Math.floor((cropSpec.y || 0.47) * ch));
        const sw = Math.min(cw - sx, Math.floor((cropSpec.w || 0.20) * cw));
        const sh = Math.min(ch - sy, Math.floor((cropSpec.h || 0.04) * ch));

        if (sw <= 10 || sh <= 10) return null;

        const outCanvas = document.createElement('canvas');
        outCanvas.width = sw;
        outCanvas.height = sh;
        const outCtx = outCanvas.getContext('2d');
        outCtx.drawImage(pageCanvas, sx, sy, sw, sh, 0, 0, sw, sh);

        const imgData = outCtx.getImageData(0, 0, sw, sh);
        const data = imgData.data;

        const luminances = new Uint8Array(sw * sh);
        for (let i = 0, p = 0; i < data.length; i += 4, p++) {
            luminances[p] = Math.round(0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2]);
        }
        const sortedLum = Array.from(luminances).sort((a, b) => a - b);
        const paperLum = sortedLum[Math.floor(sortedLum.length * 0.75)] || 235;
        const inkCutoff = Math.max(50, paperLum - 26);

        const rowDarkCounts = new Uint16Array(sh);
        for (let y = 0; y < sh; y++) {
            let darkInRow = 0;
            for (let x = 0; x < sw; x++) {
                if (luminances[y * sw + x] < inkCutoff) {
                    darkInRow++;
                }
            }
            rowDarkCounts[y] = darkInRow;
        }

        let inkPixelCount = 0;
        for (let y = 0; y < sh; y++) {
            const isBorderLine = (y < sh * 0.12 || y > sh * 0.86) && rowDarkCounts[y] > sw * 0.58;

            for (let x = 0; x < sw; x++) {
                const idx = (y * sw + x) * 4;
                const lum = luminances[y * sw + x];

                if (isBorderLine || lum >= inkCutoff) {
                    data[idx] = 255;
                    data[idx + 1] = 255;
                    data[idx + 2] = 255;
                    data[idx + 3] = 0;
                } else {
                    const darkness = Math.min(1, (inkCutoff - lum) / Math.max(25, inkCutoff * 0.42));
                    data[idx] = 15;
                    data[idx + 1] = 23;
                    data[idx + 2] = 42;
                    data[idx + 3] = Math.round(Math.min(255, Math.max(95, darkness * 255)));
                    if (darkness > 0.25) {
                        inkPixelCount++;
                    }
                }
            }
        }

        if (inkPixelCount < 35) {
            return null;
        }

        outCtx.putImageData(imgData, 0, 0);

        const finalCanvas = document.createElement('canvas');
        finalCanvas.width = 600;
        finalCanvas.height = 160;
        const finalCtx = finalCanvas.getContext('2d');

        const scale = Math.min((600 * 0.78) / sw, (160 * 0.72) / sh);
        const drawW = sw * scale;
        const drawH = sh * scale;
        const drawX = (600 - drawW) / 2;
        const drawY = (160 - drawH) / 2 - 4;

        finalCtx.drawImage(outCanvas, 0, 0, sw, sh, drawX, drawY, drawW, drawH);
        return finalCanvas.toDataURL('image/png');
    }

    /**
     * Populate the application form inputs with extracted fields and highlight updated inputs.
     */
    function populateApplicationForm(fields, signatureDataUrl) {
        if (!fields || typeof fields !== 'object') return 0;

        let populatedCount = 0;

        if (fields.tenancy_type) {
            const tenancyEl = document.getElementById('tenancy_type');
            if (tenancyEl) {
                tenancyEl.value = fields.tenancy_type;
                if (typeof window.toggleTenancyFields === 'function') {
                    window.toggleTenancyFields(fields.tenancy_type);
                }
                tenancyEl.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        Object.keys(fields).forEach(key => {
            const val = fields[key];
            if (val === null || val === undefined || val === '') return;

            if (key === 'credit_check_authorized') {
                const checkbox = document.querySelector('input[name="credit_check_authorized"]');
                if (checkbox) {
                    checkbox.checked = (val === '1' || val === 1 || val === true || val === 'true');
                    populatedCount++;
                }
                return;
            }

            const input = document.getElementById(key) || document.querySelector(`[name="${key}"]`);
            if (!input || input.type === 'hidden' || input.type === 'file') return;

            input.value = val;
            populatedCount++;

            if (input.tagName === 'TEXTAREA') {
                input.style.height = 'auto';
                input.style.height = input.scrollHeight + 'px';
            }

            const origTransition = input.style.transition;
            const origBorder = input.style.borderColor;
            const origShadow = input.style.boxShadow;

            input.style.transition = 'border-color 0.3s ease, box-shadow 0.3s ease';
            input.style.borderColor = '#10b981';
            input.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.16)';

            setTimeout(() => {
                input.style.borderColor = origBorder;
                input.style.boxShadow = origShadow;
                setTimeout(() => {
                    input.style.transition = origTransition;
                }, 300);
            }, 2600);
        });

        const sigHidden = document.getElementById('applicant_signature');
        if (sigHidden) {
            if (signatureDataUrl && signatureDataUrl.startsWith('data:image/')) {
                sigHidden.value = signatureDataUrl;
                sigHidden.dispatchEvent(new Event('input', { bubbles: true }));
                populatedCount++;
            } else if (fields.first_name && fields.last_name) {
                const fullName = [fields.first_name, fields.middle_name, fields.last_name].filter(Boolean).join(' ');
                sigHidden.value = fullName;
                sigHidden.dispatchEvent(new Event('input', { bubbles: true }));
                populatedCount++;
            }
        }

        return populatedCount;
    }

    /**
     * Attach the imported PDF File object to the #applicant_documents file input via DataTransfer
     * so saving the form automatically uploads and stores the original PDF on the application.
     */
    function attachPdfToDocumentsInput(pdfFile) {
        const fileInput = document.getElementById('applicant_documents');
        if (!fileInput || !pdfFile || typeof DataTransfer === 'undefined') return false;

        try {
            const dt = new DataTransfer();
            if (fileInput.files && fileInput.files.length > 0) {
                Array.from(fileInput.files).forEach(existingFile => {
                    if (existingFile.name !== pdfFile.name) {
                        dt.items.add(existingFile);
                    }
                });
            }
            dt.items.add(pdfFile);
            fileInput.files = dt.files;
            fileInput.dispatchEvent(new Event('change', { bubbles: true }));
            return true;
        } catch (err) {
            return false;
        }
    }

    /**
     * Open a true detached Desktop Window (movable to a 2nd monitor/display)
     * styled identically to the Application Edit page (same app.css, fonts, buttons,
     * card header, background, and synced Light/Dark theme) with a 1-Page-at-a-Time Carousel.
     */
    function openDetachedCarouselWindow(fileName, pageDataUrls, startPageIndex = 0) {
        if (!pageDataUrls || pageDataUrls.length === 0) return false;

        const winFeatures = 'popup=yes,width=980,height=960,left=100,top=40,resizable=yes,scrollbars=yes';
        const win = window.open('', 'PropPulsePdfCarouselViewer', winFeatures);
        if (!win) {
            return false;
        }

        activePopoutWindow = win;
        const safeTitle = String(fileName || 'Imported Application.pdf').replace(/[<>&"']/g, '');
        const pagesJson = JSON.stringify(pageDataUrls);
        const currentTheme = getCurrentAppTheme();
        const appCssUrl = getAppStylesheetUrl();

        const html = `<!DOCTYPE html>
<html lang="en" data-theme="${currentTheme}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>${safeTitle} — PropPulse PDF Viewer</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="${appCssUrl}">
<style>
  body {
    margin: 0;
    padding: 0;
    height: 100vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background-color: var(--ws-bg);
    color: var(--ws-text);
    font-family: var(--ws-font);
  }
  .viewer-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 12px 20px;
    background-color: var(--table-th-bg);
    border-bottom: 1px solid var(--ws-border);
    user-select: none;
    z-index: 20;
  }
  .title-group {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
  }
  .title-text {
    font-size: 15px;
    font-weight: 700;
    color: var(--ws-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 260px;
    margin: 0;
  }
  .carousel-controls {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .page-switcher-track {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--ws-bg);
    border: 1px solid var(--ws-border-strong);
    padding: 3px 10px 3px 4px;
    border-radius: 999px;
  }
  .nav-step-btn {
    padding: 6px 13px !important;
    font-size: 12.5px !important;
    font-weight: 700 !important;
    border-radius: 8px !important;
    background: var(--ws-surface) !important;
    border: 1.5px solid var(--ws-border-strong) !important;
    color: var(--ws-text) !important;
    box-shadow: var(--shadow-sm);
  }
  .nav-step-btn:hover:not(:disabled) {
    border-color: var(--ws-accent) !important;
    background: var(--ws-surface-subtle) !important;
  }
  .right-toolbar-controls {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
  }
  .fit-mode-group {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: var(--ws-bg);
    border: 1px solid var(--ws-border-strong);
    padding: 3px;
    border-radius: 9px;
  }
  .zoom-stepper-group {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: var(--ws-bg);
    border: 1px solid var(--ws-border-strong);
    padding: 3px 6px;
    border-radius: 9px;
  }
  .toolbar-divider {
    width: 1px;
    height: 22px;
    background: var(--ws-border-strong);
    display: inline-block;
  }
  .dots {
    display: flex;
    align-items: center;
    gap: 4px;
  }
  .page-indicator {
    font-size: 11.5px;
    font-weight: 700;
    color: var(--ws-text-secondary);
    white-space: nowrap;
  }
  .zoom-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--ws-text);
    min-width: 46px;
    text-align: center;
  }
  .stage-wrapper {
    position: relative;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background-color: var(--ws-bg);
  }
  .stage-scroll {
    width: 100%;
    height: 100%;
    overflow: auto;
    padding: 20px 72px;
    display: flex;
    justify-content: center;
    align-items: flex-start;
  }
  .stage-scroll.fit-page-mode {
    align-items: center;
  }
  #page-img {
    display: block;
    background: #ffffff;
    border: 1px solid var(--ws-border-strong);
    border-radius: var(--radius-md, 14px);
    box-shadow: var(--shadow-lg);
    transition: width 0.12s ease, height 0.12s ease;
  }
  #page-img.fit-page {
    max-height: calc(100vh - 98px);
    max-width: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
  }
  #page-img.fit-width {
    width: 100%;
    max-width: 920px;
    height: auto;
  }
  .nav-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: var(--ws-surface);
    border: 1px solid var(--ws-border-strong);
    color: var(--ws-text);
    font-size: 22px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 15;
    box-shadow: var(--shadow-md);
    padding: 0;
  }
  .nav-arrow:hover:not(:disabled) {
    background: var(--ws-surface-subtle);
    border-color: var(--ws-accent);
  }
  .nav-arrow:disabled {
    opacity: 0.35;
    cursor: not-allowed;
  }
  .nav-arrow.prev { left: 16px; }
  .nav-arrow.next { right: 16px; }
</style>
</head>
<body>
  <header class="viewer-header card-header">
    <div class="title-group">
      <span style="font-size: 18px;">📄</span>
      <h3 class="title-text card-title" title="${safeTitle}">${safeTitle}</h3>
    </div>

    <div class="carousel-controls">
      <button type="button" id="btn-prev" class="btn btn-outline btn-xs nav-step-btn" title="Previous Page (Left Arrow)">&#9664; Prev</button>
      <div class="page-switcher-track">
        <div class="dots" id="dots-container"></div>
        <span class="page-indicator" id="page-indicator">Page 1 of 1</span>
      </div>
      <button type="button" id="btn-next" class="btn btn-outline btn-xs nav-step-btn" title="Next Page (Right Arrow)">Next &#9654;</button>
    </div>

    <div class="right-toolbar-controls">
      <div class="fit-mode-group">
        <button type="button" id="btn-fit-page" class="btn btn-primary btn-xs" style="padding: 4px 10px; font-size: 12px; font-weight: 600; border-radius: 6px;" title="Fit entire page vertically & horizontally">Fit Page</button>
        <button type="button" id="btn-fit-width" class="btn btn-outline btn-xs" style="padding: 4px 10px; font-size: 12px; font-weight: 600; border-radius: 6px; border-color: transparent;" title="Fit page width">Fit Width</button>
      </div>

      <span class="toolbar-divider"></span>

      <div class="zoom-stepper-group">
        <button type="button" id="btn-zoom-out" class="btn btn-outline btn-xs" style="padding: 3px 9px; font-size: 13px; font-weight: 700; border-radius: 6px;" title="Zoom Out">&minus;</button>
        <span class="zoom-label" id="zoom-label">Fit</span>
        <button type="button" id="btn-zoom-in" class="btn btn-outline btn-xs" style="padding: 3px 9px; font-size: 13px; font-weight: 700; border-radius: 6px;" title="Zoom In">&#43;</button>
      </div>

      <div style="display: inline-flex; align-items: center; gap: 6px;">
        <button type="button" id="btn-theme-toggle" class="theme-toggle-btn" title="Toggle Light / Dark mode" style="width: 32px; height: 32px;">
          <span class="theme-icon-dark">🌙</span>
          <span class="theme-icon-light">☀️</span>
        </button>
        <button type="button" class="btn btn-outline btn-danger btn-xs" style="padding: 5px 12px; font-size: 12.5px; font-weight: 700;" onclick="window.close()">&times; Close</button>
      </div>
    </div>
  </header>

  <main class="stage-wrapper">
    <button type="button" class="nav-arrow prev" id="arrow-prev" title="Previous Page">&lsaquo;</button>
    <div class="stage-scroll fit-page-mode" id="stage-scroll">
      <img id="page-img" class="fit-page" src="" alt="Application Page">
    </div>
    <button type="button" class="nav-arrow next" id="arrow-next" title="Next Page">&rsaquo;</button>
  </main>

  <script>
    const pages = ${pagesJson};
    let currentIndex = ${Number(startPageIndex) || 0};
    let viewMode = 'fit-page'; // 'fit-page', 'fit-width', 'custom'
    let customZoom = 100;

    const imgEl = document.getElementById('page-img');
    const stageScroll = document.getElementById('stage-scroll');
    const indicatorEl = document.getElementById('page-indicator');
    const dotsContainer = document.getElementById('dots-container');
    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');
    const arrowPrev = document.getElementById('arrow-prev');
    const arrowNext = document.getElementById('arrow-next');
    const btnFitPage = document.getElementById('btn-fit-page');
    const btnFitWidth = document.getElementById('btn-fit-width');
    const btnZoomIn = document.getElementById('btn-zoom-in');
    const btnZoomOut = document.getElementById('btn-zoom-out');
    const zoomLabel = document.getElementById('zoom-label');
    const btnThemeToggle = document.getElementById('btn-theme-toggle');

    function renderDots() {
      dotsContainer.innerHTML = '';
      pages.forEach((_, idx) => {
        const pill = document.createElement('button');
        pill.type = 'button';
        const isActive = (idx === currentIndex);
        pill.className = 'btn btn-xs ' + (isActive ? 'btn-primary' : 'btn-outline');
        pill.style.cssText = 'padding: 4px 11px; font-size: 12px; font-weight: 700; border-radius: 999px;' + (isActive ? '' : ' border-color: transparent; background: transparent;');
        pill.textContent = 'Page ' + (idx + 1);
        pill.onclick = () => goToPage(idx);
        dotsContainer.appendChild(pill);
      });
    }

    function applyViewMode() {
      imgEl.className = '';
      imgEl.style.width = '';
      imgEl.style.maxWidth = '';
      imgEl.style.maxHeight = '';
      btnFitPage.className = 'btn btn-xs ' + (viewMode === 'fit-page' ? 'btn-primary' : 'btn-outline');
      btnFitPage.style.cssText = 'padding: 4px 10px; font-size: 12px; font-weight: 600; border-radius: 6px;' + (viewMode === 'fit-page' ? '' : ' border-color: transparent; background: transparent;');
      btnFitWidth.className = 'btn btn-xs ' + (viewMode === 'fit-width' ? 'btn-primary' : 'btn-outline');
      btnFitWidth.style.cssText = 'padding: 4px 10px; font-size: 12px; font-weight: 600; border-radius: 6px;' + (viewMode === 'fit-width' ? '' : ' border-color: transparent; background: transparent;');

      if (viewMode === 'fit-page') {
        imgEl.classList.add('fit-page');
        stageScroll.classList.add('fit-page-mode');
        zoomLabel.textContent = 'Fit';
      } else if (viewMode === 'fit-width') {
        imgEl.classList.add('fit-width');
        stageScroll.classList.remove('fit-page-mode');
        zoomLabel.textContent = 'Width';
      } else {
        stageScroll.classList.remove('fit-page-mode');
        imgEl.style.width = Math.round(780 * (customZoom / 100)) + 'px';
        imgEl.style.maxWidth = 'none';
        zoomLabel.textContent = customZoom + '%';
      }
    }

    function goToPage(index) {
      if (index < 0 || index >= pages.length) return;
      currentIndex = index;
      imgEl.src = pages[currentIndex];
      stageScroll.scrollTop = 0;
      indicatorEl.textContent = 'Page ' + (currentIndex + 1) + ' of ' + pages.length;
      btnPrev.disabled = (currentIndex === 0);
      btnNext.disabled = (currentIndex === pages.length - 1);
      arrowPrev.disabled = (currentIndex === 0);
      arrowNext.disabled = (currentIndex === pages.length - 1);
      renderDots();
      applyViewMode();
    }

    btnPrev.onclick = () => goToPage(currentIndex - 1);
    btnNext.onclick = () => goToPage(currentIndex + 1);
    arrowPrev.onclick = () => goToPage(currentIndex - 1);
    arrowNext.onclick = () => goToPage(currentIndex + 1);

    btnFitPage.onclick = () => { viewMode = 'fit-page'; applyViewMode(); };
    btnFitWidth.onclick = () => { viewMode = 'fit-width'; applyViewMode(); };
    btnZoomIn.onclick = () => {
      if (viewMode !== 'custom') customZoom = 100;
      viewMode = 'custom';
      customZoom = Math.min(225, customZoom + 25);
      applyViewMode();
    };
    btnZoomOut.onclick = () => {
      if (viewMode !== 'custom') customZoom = 100;
      viewMode = 'custom';
      customZoom = Math.max(50, customZoom - 25);
      applyViewMode();
    };

    if (btnThemeToggle) {
      btnThemeToggle.onclick = () => {
        const html = document.documentElement;
        const curr = html.getAttribute('data-theme') || 'light';
        const next = curr === 'light' ? 'dark' : 'light';
        html.setAttribute('data-theme', next);
        try {
          localStorage.setItem('ws_theme', next);
          localStorage.setItem('hc_theme', next);
          if (window.opener && window.opener.document && window.opener.document.documentElement) {
            window.opener.document.documentElement.setAttribute('data-theme', next);
          }
        } catch (err) {}
      };
    }

    window.addEventListener('storage', (e) => {
      if ((e.key === 'ws_theme' || e.key === 'hc_theme') && e.newValue) {
        document.documentElement.setAttribute('data-theme', e.newValue);
      }
    });

    window.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowLeft') goToPage(currentIndex - 1);
      else if (e.key === 'ArrowRight') goToPage(currentIndex + 1);
      else if (e.key === 'Escape') window.close();
    });

    goToPage(currentIndex);
  <\/script>
</body>
</html>`;

        win.document.open();
        win.document.write(html);
        win.document.close();
        win.focus();
        return true;
    }

    // Observe theme changes on the main Application Edit page and sync to the detached PDF Viewer window immediately
    const themeObserver = new MutationObserver(() => {
        if (activePopoutWindow && !activePopoutWindow.closed && activePopoutWindow.document && activePopoutWindow.document.documentElement) {
            activePopoutWindow.document.documentElement.setAttribute('data-theme', getCurrentAppTheme());
        }
    });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

    /**
     * Initialize the PDF Import UI, 1-Page-at-a-Time Carousel, and Detached Window Viewer.
     */
    function initPdfApplicationImporter() {
        const dropzone = document.getElementById('pdf-import-dropzone');
        const fileInput = document.getElementById('pdf-import-file-input');
        if (!dropzone || !fileInput) return;

        const browseBtn = document.getElementById('pdf-import-browse-btn');
        const statusBox = document.getElementById('pdf-import-status');
        const statusText = document.getElementById('pdf-import-status-text');
        const progressBar = document.getElementById('pdf-import-progress-bar');
        const previewToggleBtn = document.getElementById('pdf-import-preview-toggle');
        const previewModal = document.getElementById('pdf-preview-modal');
        const previewModalClose = document.getElementById('pdf-preview-modal-close');
        const previewModalTitle = document.getElementById('pdf-preview-modal-title');
        const previewPagesGrid = document.getElementById('pdf-import-pages-grid');
        const carouselPrevBtn = document.getElementById('pdf-carousel-prev');
        const carouselNextBtn = document.getElementById('pdf-carousel-next');
        const stagePrevArrow = document.getElementById('pdf-stage-prev-arrow');
        const stageNextArrow = document.getElementById('pdf-stage-next-arrow');
        const carouselDots = document.getElementById('pdf-carousel-dots');
        const carouselIndicator = document.getElementById('pdf-carousel-page-indicator');
        const fitPageBtn = document.getElementById('pdf-modal-fit-page');
        const fitWidthBtn = document.getElementById('pdf-modal-fit-width');
        const zoomInBtn = document.getElementById('pdf-modal-zoom-in');
        const zoomOutBtn = document.getElementById('pdf-modal-zoom-out');
        const zoomLabel = document.getElementById('pdf-modal-zoom-label');
        const popoutBtn = document.getElementById('pdf-modal-popout-btn');
        const attachCheckbox = document.getElementById('pdf-import-attach-doc');

        let importedPageUrls = [];
        let importedFileName = 'Application.pdf';
        let currentCarouselPage = 0;
        let modalViewMode = 'fit-page'; // 'fit-page', 'fit-width', 'custom'
        let currentModalZoom = 100;

        function renderInPageCarousel() {
            if (!previewPagesGrid || importedPageUrls.length === 0) return;

            previewPagesGrid.innerHTML = '';
            const img = document.createElement('img');
            img.src = importedPageUrls[currentCarouselPage];
            img.alt = `Page ${currentCarouselPage + 1}`;
            img.style.background = '#ffffff';
            img.style.border = '1px solid var(--ws-border-strong)';
            img.style.borderRadius = 'var(--radius-md, 14px)';
            img.style.boxShadow = 'var(--shadow-lg)';
            img.style.display = 'block';

            if (fitPageBtn) {
                fitPageBtn.className = 'btn btn-xs ' + (modalViewMode === 'fit-page' ? 'btn-primary' : 'btn-outline');
                fitPageBtn.style.cssText = 'padding: 4px 10px; font-size: 12px; font-weight: 600; border-radius: 6px;' + (modalViewMode === 'fit-page' ? '' : ' border-color: transparent; background: transparent;');
            }
            if (fitWidthBtn) {
                fitWidthBtn.className = 'btn btn-xs ' + (modalViewMode === 'fit-width' ? 'btn-primary' : 'btn-outline');
                fitWidthBtn.style.cssText = 'padding: 4px 10px; font-size: 12px; font-weight: 600; border-radius: 6px;' + (modalViewMode === 'fit-width' ? '' : ' border-color: transparent; background: transparent;');
            }

            if (modalViewMode === 'fit-page') {
                previewPagesGrid.style.alignItems = 'center';
                img.style.maxHeight = 'calc(94vh - 96px)';
                img.style.maxWidth = '100%';
                img.style.width = 'auto';
                img.style.height = 'auto';
                img.style.objectFit = 'contain';
                if (zoomLabel) zoomLabel.textContent = 'Fit';
            } else if (modalViewMode === 'fit-width') {
                previewPagesGrid.style.alignItems = 'flex-start';
                img.style.width = '100%';
                img.style.maxWidth = '880px';
                img.style.height = 'auto';
                if (zoomLabel) zoomLabel.textContent = 'Width';
            } else {
                previewPagesGrid.style.alignItems = 'flex-start';
                img.style.width = Math.round(780 * (currentModalZoom / 100)) + 'px';
                img.style.maxWidth = 'none';
                img.style.height = 'auto';
                if (zoomLabel) zoomLabel.textContent = currentModalZoom + '%';
            }

            previewPagesGrid.appendChild(img);
            previewPagesGrid.scrollTop = 0;

            if (carouselIndicator) {
                carouselIndicator.textContent = `Page ${currentCarouselPage + 1} of ${importedPageUrls.length}`;
            }
            if (carouselPrevBtn) carouselPrevBtn.disabled = (currentCarouselPage === 0);
            if (carouselNextBtn) carouselNextBtn.disabled = (currentCarouselPage === importedPageUrls.length - 1);
            if (stagePrevArrow) stagePrevArrow.disabled = (currentCarouselPage === 0);
            if (stageNextArrow) stageNextArrow.disabled = (currentCarouselPage === importedPageUrls.length - 1);

            if (carouselDots) {
                carouselDots.innerHTML = '';
                importedPageUrls.forEach((_, idx) => {
                    const dotBtn = document.createElement('button');
                    dotBtn.type = 'button';
                    const isActive = (idx === currentCarouselPage);
                    dotBtn.className = 'btn btn-xs ' + (isActive ? 'btn-primary' : 'btn-outline');
                    dotBtn.style.cssText = 'padding: 4px 11px; font-size: 12px; font-weight: 700; border-radius: 999px;' + (isActive ? '' : ' border-color: transparent; background: transparent;');
                    dotBtn.textContent = 'Page ' + (idx + 1);
                    dotBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        currentCarouselPage = idx;
                        renderInPageCarousel();
                    });
                    carouselDots.appendChild(dotBtn);
                });
            }
        }

        function goToCarouselPage(idx) {
            if (idx < 0 || idx >= importedPageUrls.length) return;
            currentCarouselPage = idx;
            renderInPageCarousel();
        }

        function openPdfModal() {
            if (!previewModal) return;
            renderInPageCarousel();
            previewModal.style.display = 'flex';
        }

        function closePdfModal() {
            if (!previewModal) return;
            previewModal.style.display = 'none';
        }

        if (previewToggleBtn) {
            previewToggleBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const openedPopup = openDetachedCarouselWindow(importedFileName, importedPageUrls, currentCarouselPage);
                if (!openedPopup) {
                    openPdfModal();
                }
            });
        }

        if (popoutBtn) {
            popoutBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (openDetachedCarouselWindow(importedFileName, importedPageUrls, currentCarouselPage)) {
                    closePdfModal();
                }
            });
        }

        if (carouselPrevBtn) carouselPrevBtn.addEventListener('click', (e) => { e.stopPropagation(); goToCarouselPage(currentCarouselPage - 1); });
        if (carouselNextBtn) carouselNextBtn.addEventListener('click', (e) => { e.stopPropagation(); goToCarouselPage(currentCarouselPage + 1); });
        if (stagePrevArrow) stagePrevArrow.addEventListener('click', (e) => { e.stopPropagation(); goToCarouselPage(currentCarouselPage - 1); });
        if (stageNextArrow) stageNextArrow.addEventListener('click', (e) => { e.stopPropagation(); goToCarouselPage(currentCarouselPage + 1); });

        if (fitPageBtn) {
            fitPageBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                modalViewMode = 'fit-page';
                renderInPageCarousel();
            });
        }

        if (fitWidthBtn) {
            fitWidthBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                modalViewMode = 'fit-width';
                renderInPageCarousel();
            });
        }

        if (zoomInBtn) {
            zoomInBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (modalViewMode !== 'custom') currentModalZoom = 100;
                modalViewMode = 'custom';
                currentModalZoom = Math.min(225, currentModalZoom + 25);
                renderInPageCarousel();
            });
        }

        if (zoomOutBtn) {
            zoomOutBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (modalViewMode !== 'custom') currentModalZoom = 100;
                modalViewMode = 'custom';
                currentModalZoom = Math.max(50, currentModalZoom - 25);
                renderInPageCarousel();
            });
        }

        if (previewModalClose) {
            previewModalClose.addEventListener('click', (e) => {
                e.stopPropagation();
                closePdfModal();
            });
        }

        if (previewModal) {
            previewModal.addEventListener('click', (e) => {
                if (e.target === previewModal) {
                    closePdfModal();
                }
            });
        }

        document.addEventListener('keydown', (e) => {
            if (!previewModal || previewModal.style.display === 'none') return;
            if (e.key === 'Escape') {
                closePdfModal();
            } else if (e.key === 'ArrowLeft') {
                goToCarouselPage(currentCarouselPage - 1);
            } else if (e.key === 'ArrowRight') {
                goToCarouselPage(currentCarouselPage + 1);
            }
        });

        const cardHeader = document.querySelector('.card > .card-header');
        if (cardHeader && !document.getElementById('card-header-pdf-import-btn')) {
            cardHeader.style.display = 'flex';
            cardHeader.style.alignItems = 'center';
            cardHeader.style.justifyContent = 'space-between';
            cardHeader.style.flexWrap = 'wrap';
            cardHeader.style.gap = '10px';

            let rightGroup = cardHeader.querySelector('.pp-card-header-actions');
            if (!rightGroup) {
                rightGroup = document.createElement('div');
                rightGroup.className = 'pp-card-header-actions';
                rightGroup.style.display = 'flex';
                rightGroup.style.alignItems = 'center';
                rightGroup.style.gap = '8px';
                const existingTestBtn = document.getElementById('pp-card-header-test-btn');
                if (existingTestBtn) {
                    rightGroup.appendChild(existingTestBtn);
                }
                cardHeader.appendChild(rightGroup);
            }

            const headerImportBtn = document.createElement('button');
            headerImportBtn.type = 'button';
            headerImportBtn.id = 'card-header-pdf-import-btn';
            headerImportBtn.className = 'btn btn-outline btn-sm';
            headerImportBtn.style.cssText = 'display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 12.5px; padding: 6px 12px; border-radius: 999px;';
            headerImportBtn.innerHTML = '📄 Import PDF Application';
            headerImportBtn.addEventListener('click', () => {
                dropzone.scrollIntoView({ behavior: 'smooth', block: 'center' });
                fileInput.click();
            });
            rightGroup.insertBefore(headerImportBtn, rightGroup.firstChild);
        }

        if (browseBtn) {
            browseBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                fileInput.click();
            });
        }

        dropzone.addEventListener('click', (e) => {
            if (e.target.closest('button, input, label, a')) return;
            fileInput.click();
        });

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.style.borderColor = 'var(--ws-accent, #2563eb)';
            dropzone.style.background = 'rgba(37, 99, 235, 0.06)';
        });

        dropzone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.style.borderColor = '';
            dropzone.style.background = '';
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.style.borderColor = '';
            dropzone.style.background = '';
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                handlePdfFile(e.dataTransfer.files[0]);
            }
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files && fileInput.files.length > 0) {
                handlePdfFile(fileInput.files[0]);
            }
        });

        function updateProgress(percent, message, isError = false, isSuccess = false) {
            if (statusBox) statusBox.style.display = 'block';
            if (progressBar) {
                progressBar.style.width = percent + '%';
                progressBar.style.background = isError ? '#ef4444' : (isSuccess ? '#10b981' : '#2563eb');
            }
            if (statusText) {
                statusText.textContent = message;
                statusText.style.color = isError ? '#dc2626' : (isSuccess ? '#059669' : 'var(--ws-text-primary)');
            }
        }

        async function handlePdfFile(file) {
            if (!file || (!file.name.toLowerCase().endsWith('.pdf') && file.type !== 'application/pdf')) {
                updateProgress(100, '⚠️ Please select a valid .pdf application file.', true);
                return;
            }

            updateProgress(15, `📄 Loading "${file.name}" (${(file.size / 1024).toFixed(0)} KB)...`);

            let renderedPages = [];
            let allSpatialItems = [];
            let allClientText = [];
            importedPageUrls = [];
            importedFileName = file.name;
            currentCarouselPage = 0;
            modalViewMode = 'fit-page';

            try {
                const pdfjsLib = await loadPdfJs();
                const arrayBuffer = await file.arrayBuffer();
                const pdfDoc = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
                const numPages = Math.min(pdfDoc.numPages, 6);

                updateProgress(38, `🔍 Rendering ${numPages} page(s) & analyzing handwriting / text layout...`);

                if (previewModalTitle) {
                    previewModalTitle.textContent = `${file.name} (${numPages} Page${numPages > 1 ? 's' : ''})`;
                }

                for (let p = 1; p <= numPages; p++) {
                    const pageResult = await renderPageAndExtractText(pdfDoc, p, 2.2);
                    renderedPages.push(pageResult);
                    importedPageUrls.push(pageResult.dataUrl);
                    allSpatialItems = allSpatialItems.concat(pageResult.items);
                    if (pageResult.text) {
                        allClientText.push(pageResult.text);
                    }
                }

                renderInPageCarousel();

                if (activePopoutWindow && !activePopoutWindow.closed) {
                    openDetachedCarouselWindow(importedFileName, importedPageUrls, 0);
                }

                if (previewToggleBtn) {
                    previewToggleBtn.style.display = 'inline-flex';
                }
            } catch (pdfErr) {
                console.warn('Client PDF.js rendering warning (falling back to server OCR):', pdfErr);
            }

            updateProgress(68, '🧠 Extracting handwritten, printed & typed application fields...');

            const parseEndpoint = dropzone.getAttribute('data-parse-url') || '/applicants/parse-pdf';
            const formData = new FormData();
            formData.append('pdf_file', file);
            formData.append('client_text', allClientText.join('\n'));
            formData.append('client_items', JSON.stringify(allSpatialItems));

            try {
                const resp = await fetch(parseEndpoint, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const result = await resp.json();
                if (!result || !result.success || !result.fields) {
                    updateProgress(100, '⚠️ Could not extract application fields from this PDF. Please verify the PDF format.', true);
                    return;
                }

                updateProgress(88, '✍️ Extracting applicant signature & populating form fields...');
                let croppedSigDataUrl = null;
                const sigCrop = result.signature_crop || { page: 2, x: 0.120, y: 0.475, w: 0.200, h: 0.040 };
                const targetPageObj = renderedPages.find(rp => rp.pageNum === (sigCrop.page || 2)) || renderedPages[renderedPages.length - 1];
                if (targetPageObj && targetPageObj.canvas) {
                    croppedSigDataUrl = extractCleanSignatureFromCanvas(targetPageObj.canvas, sigCrop);
                }

                const filledCount = populateApplicationForm(result.fields, croppedSigDataUrl);

                let attachedMsg = '';
                if (!attachCheckbox || attachCheckbox.checked) {
                    if (attachPdfToDocumentsInput(file)) {
                        attachedMsg = ' + attached PDF to Documents';
                    }
                }

                const applicantName = [result.fields.first_name, result.fields.middle_name, result.fields.last_name].filter(Boolean).join(' ');
                updateProgress(
                    100,
                    `✓ Imported ${filledCount} fields for ${applicantName || 'Applicant'} (${result.engine || 'OCR & Spatial Parser'})${croppedSigDataUrl ? ' + Extracted Signature' : ''}${attachedMsg}`,
                    false,
                    true
                );
            } catch (err) {
                console.error('PDF import error:', err);
                updateProgress(100, '⚠️ Error communicating with PDF extraction service: ' + (err.message || 'Unknown error'), true);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPdfApplicationImporter);
    } else {
        initPdfApplicationImporter();
    }
})();
