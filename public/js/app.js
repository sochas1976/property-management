// PropPulse Frontend Interactive Script

document.addEventListener('DOMContentLoaded', () => {
    // 1. Highlight Active Navigation Item
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-item').forEach(link => {
        const href = link.getAttribute('href');
        if (href && (currentPath.endsWith(href) || (href !== '/' && currentPath.includes(href)))) {
            link.classList.add('active');
        }
    });

    // 2. Unit Select Auto-Fill in Lease Creation Form
    const unitSelect = document.getElementById('unit_id');
    const rentInput = document.getElementById('rent_amount');
    const depositInput = document.getElementById('deposit_held');

    if (unitSelect && rentInput) {
        unitSelect.addEventListener('change', () => {
            const selectedOption = unitSelect.options[unitSelect.selectedIndex];
            if (selectedOption && selectedOption.dataset.rent) {
                rentInput.value = parseFloat(selectedOption.dataset.rent).toFixed(2);
                if (depositInput && selectedOption.dataset.deposit) {
                    depositInput.value = parseFloat(selectedOption.dataset.deposit).toFixed(2);
                }
            }
        });
    }

    // 3. Close modal when clicking outside modal card
    document.querySelectorAll('.modal-backdrop').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    });

    // 4. Close modal on ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-backdrop.active').forEach(modal => {
                modal.classList.remove('active');
            });
        }
    });

    // 5. Auto dismiss success alerts after 6 seconds
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            successAlert.style.opacity = '0';
            successAlert.style.transform = 'translateY(-10px)';
            setTimeout(() => successAlert.remove(), 400);
        }, 6000);
    }

    // 6. Theme Toggle (Wealthsimple Clean Warm Light / Dark)
    document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
        if (!btn.getAttribute('onclick')) {
            btn.addEventListener('click', (e) => {
                if (typeof window.toggleAppTheme === 'function') {
                    window.toggleAppTheme(e);
                }
            });
        }
    });

    // 7. Auto-expand dynamic textareas (grow/shrink automatically to fit content)
    const initAutoExpand = (textarea) => {
        const resize = () => {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        };
        textarea.addEventListener('input', resize);
        // Initial sizing once DOM is settled
        requestAnimationFrame(resize);
        window.addEventListener('resize', resize);
    };

    document.querySelectorAll('textarea.auto-expand').forEach(initAutoExpand);

    // 8. Auto-format Phone Numbers to XXX-XXX-XXXX format
    function formatPhoneNumber(value) {
        if (!value) return '';
        // Strip everything except digits
        let digits = value.replace(/\D/g, '');
        // If leading with 1 (country code) and 11 digits, strip the 1
        if (digits.length === 11 && digits.startsWith('1')) {
            digits = digits.substring(1);
        }
        // Limit to 10 digits
        digits = digits.substring(0, 10);

        if (digits.length <= 3) {
            return digits;
        } else if (digits.length <= 6) {
            return digits.slice(0, 3) + '-' + digits.slice(3);
        } else {
            return digits.slice(0, 3) + '-' + digits.slice(3, 6) + '-' + digits.slice(6);
        }
    }

    const attachPhoneFormatter = (input) => {
        // Initial format on load if already populated
        if (input.value) {
            input.value = formatPhoneNumber(input.value);
        }

        input.addEventListener('input', (e) => {
            // Keep cursor position stable where possible
            const start = input.selectionStart;
            const prevLength = input.value.length;
            const formatted = formatPhoneNumber(input.value);
            input.value = formatted;

            // Adjust cursor if characters were added/removed
            const diff = formatted.length - prevLength;
            if (start !== null) {
                const newPos = Math.max(0, start + diff);
                input.setSelectionRange(newPos, newPos);
            }
        });

        input.addEventListener('blur', () => {
            input.value = formatPhoneNumber(input.value);
        });
    };

    // Attach to any input with type="tel" or whose name/id contains "phone"
    document.querySelectorAll('input[type="tel"], input[name*="phone"], input[id*="phone"]').forEach(attachPhoneFormatter);

    // 9. Style Select Dropdowns when Placeholder Option (value="") is Selected
    const updateSelectTone = (select) => {
        if (!select.value) {
            select.classList.add('placeholder-selected');
        } else {
            select.classList.remove('placeholder-selected');
        }
    };

    document.querySelectorAll('select.form-control').forEach(select => {
        updateSelectTone(select);
        select.addEventListener('change', () => updateSelectTone(select));
    });

    // 10. Interactive e-Signature Pad (Mouse, Stylus, Touch & Type-to-Sign)
    initESignaturePad();
});

function initESignaturePad() {
    const canvas = document.getElementById('esig-canvas');
    const hiddenInput = document.getElementById('applicant_signature');
    if (!canvas || !hiddenInput) return;

    const ctx = canvas.getContext('2d');
    const placeholder = document.getElementById('esig-placeholder');
    const undoBtn = document.getElementById('esig-undo-btn');
    const clearBtn = document.getElementById('esig-clear-btn');
    const modeDrawBtn = document.getElementById('esig-mode-draw-btn');
    const modeTypeBtn = document.getElementById('esig-mode-type-btn');
    const typePanel = document.getElementById('esig-type-panel');
    const typeInput = document.getElementById('esig-type-input');
    const statusBadge = document.getElementById('esig-status-badge');

    let isDrawing = false;
    let strokes = []; // Array of stroke arrays: [{x, y, width}, ...]
    let currentStroke = [];
    let baseImage = null; // Loaded existing signature image if editing
    let currentMode = 'draw';
    let lastCanvasWidth = 0;
    let isInternalUpdate = false;

    function updateStatus(signed, labelText) {
        if (placeholder) {
            placeholder.style.opacity = signed ? '0' : '1';
        }
        if (statusBadge) {
            if (signed) {
                statusBadge.textContent = labelText || '✓ e-Signature Captured';
                statusBadge.style.color = '#059669';
                statusBadge.style.background = 'rgba(16, 185, 129, 0.12)';
                statusBadge.style.borderColor = 'rgba(16, 185, 129, 0.3)';
            } else {
                statusBadge.textContent = 'Awaiting Signature';
                statusBadge.style.color = '#64748b';
                statusBadge.style.background = 'rgba(100, 116, 139, 0.1)';
                statusBadge.style.borderColor = 'rgba(100, 116, 139, 0.25)';
            }
        }
    }

    function setupCanvasDimensions(preserveContent = true) {
        const rect = canvas.getBoundingClientRect();
        if (rect.width === 0 || rect.width === lastCanvasWidth) return;
        lastCanvasWidth = rect.width;

        const dpr = window.devicePixelRatio || 1;
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.scale(dpr, dpr);

        if (preserveContent) {
            redrawAll();
        }
    }

    function drawStroke(points) {
        if (!points || points.length === 0) return;
        ctx.save();
        ctx.strokeStyle = '#0f172a';
        ctx.fillStyle = '#0f172a';
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        if (points.length === 1) {
            ctx.beginPath();
            ctx.arc(points[0].x, points[0].y, (points[0].w || 2.4) / 2, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
            return;
        }

        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);

        for (let i = 1; i < points.length - 1; i++) {
            const midX = (points[i].x + points[i + 1].x) / 2;
            const midY = (points[i].y + points[i + 1].y) / 2;
            ctx.lineWidth = points[i].w || 2.4;
            ctx.quadraticCurveTo(points[i].x, points[i].y, midX, midY);
        }

        const last = points[points.length - 1];
        ctx.lineWidth = last.w || 2.4;
        ctx.lineTo(last.x, last.y);
        ctx.stroke();
        ctx.restore();
    }

    function redrawAll() {
        const rect = canvas.getBoundingClientRect();
        ctx.clearRect(0, 0, rect.width, rect.height);

        if (baseImage) {
            // Draw existing saved image scaled nicely within canvas
            const ratio = Math.min((rect.width - 24) / baseImage.width, (rect.height - 20) / baseImage.height, 1);
            const drawW = baseImage.width * ratio;
            const drawH = baseImage.height * ratio;
            const drawX = (rect.width - drawW) / 2;
            const drawY = (rect.height - drawH) / 2;
            ctx.drawImage(baseImage, drawX, drawY, drawW, drawH);
        }

        strokes.forEach(s => drawStroke(s));
        if (currentStroke.length > 0) {
            drawStroke(currentStroke);
        }
    }

    function syncToHiddenInput(labelText) {
        const hasContent = Boolean(baseImage) || strokes.length > 0;
        isInternalUpdate = true;
        if (!hasContent) {
            hiddenInput.value = '';
            updateStatus(false);
        } else {
            hiddenInput.value = canvas.toDataURL('image/png');
            updateStatus(true, labelText || '✓ e-Signature Captured');
        }
        isInternalUpdate = false;
    }

    function getPoint(e) {
        const rect = canvas.getBoundingClientRect();
        let clientX, clientY, pressure = 0.5;

        if (e.touches && e.touches.length > 0) {
            clientX = e.touches[0].clientX;
            clientY = e.touches[0].clientY;
        } else {
            clientX = e.clientX;
            clientY = e.clientY;
            if (typeof e.pressure === 'number' && e.pressure > 0 && e.pointerType === 'pen') {
                pressure = e.pressure;
            }
        }

        // Stylus pressure or natural pen weight
        const weight = 1.6 + Math.min(Math.max(pressure, 0.2), 1.0) * 1.6;
        return {
            x: clientX - rect.left,
            y: clientY - rect.top,
            w: weight
        };
    }

    function startDrawing(e) {
        if (e.button !== undefined && e.button !== 0) return; // left click / touch / pen only
        e.preventDefault();
        setupCanvasDimensions(true);
        if (canvas.setPointerCapture && e.pointerId !== undefined) {
            try { canvas.setPointerCapture(e.pointerId); } catch (err) {}
        }
        isDrawing = true;
        currentStroke = [getPoint(e)];
        if (placeholder) placeholder.style.opacity = '0';
        redrawAll();
    }

    function moveDrawing(e) {
        if (!isDrawing) return;
        e.preventDefault();
        const pt = getPoint(e);
        const prev = currentStroke[currentStroke.length - 1];
        if (prev) {
            const dist = Math.hypot(pt.x - prev.x, pt.y - prev.y);
            if (dist < 1.5) return; // filter micro-jitter
            // Velocity-based tapering for mouse/stylus
            const dynamicW = Math.max(1.5, Math.min(3.2, 3.0 - dist * 0.03));
            pt.w = (pt.w + dynamicW) / 2;
        }
        currentStroke.push(pt);
        redrawAll();
    }

    function stopDrawing(e) {
        if (!isDrawing) return;
        if (e) e.preventDefault();
        isDrawing = false;
        if (currentStroke.length > 0) {
            strokes.push(currentStroke.slice());
            currentStroke = [];
        }
        redrawAll();
        syncToHiddenInput('✓ Hand-Signed (Mouse / Stylus)');
    }

    // Pointer Events (covers Mouse, Stylus/Pen, and Touch cleanly)
    if (window.PointerEvent) {
        canvas.addEventListener('pointerdown', startDrawing);
        canvas.addEventListener('pointermove', moveDrawing);
        canvas.addEventListener('pointerup', stopDrawing);
        canvas.addEventListener('pointercancel', stopDrawing);
    } else {
        canvas.addEventListener('mousedown', startDrawing);
        window.addEventListener('mousemove', moveDrawing);
        window.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('touchstart', startDrawing, { passive: false });
        canvas.addEventListener('touchmove', moveDrawing, { passive: false });
        canvas.addEventListener('touchend', stopDrawing);
    }

    // Render typed name onto canvas in cursive handwriting ink
    function renderTextSignatureToCanvas(textVal, statusLabel) {
        setupCanvasDimensions(false);
        const rect = canvas.getBoundingClientRect();
        strokes = [];
        currentStroke = [];
        baseImage = null;
        ctx.clearRect(0, 0, rect.width, rect.height);

        const cleanText = (textVal || '').trim();
        if (!cleanText) {
            syncToHiddenInput();
            return;
        }

        ctx.save();
        // Dynamically scale font size so long names always fit inside the signature pad
        let fontSize = 42;
        ctx.font = `700 ${fontSize}px 'Caveat', 'Segoe Script', 'Brush Script MT', cursive`;
        while (ctx.measureText(cleanText).width > rect.width - 48 && fontSize > 22) {
            fontSize -= 2;
            ctx.font = `700 ${fontSize}px 'Caveat', 'Segoe Script', 'Brush Script MT', cursive`;
        }

        ctx.fillStyle = '#0f172a';
        ctx.textBaseline = 'middle';
        ctx.textAlign = 'center';

        // Slight natural upward slant (-2.5 degrees) for authentic signature feel
        ctx.translate(rect.width / 2, rect.height * 0.52);
        ctx.rotate(-0.04);
        ctx.fillText(cleanText, 0, 0);
        ctx.restore();

        // Capture rendered text as baseImage so it persists alongside any additional pen strokes
        const dataUrl = canvas.toDataURL('image/png');
        const img = new Image();
        img.onload = () => {
            baseImage = img;
        };
        img.src = dataUrl;

        isInternalUpdate = true;
        hiddenInput.value = dataUrl;
        isInternalUpdate = false;
        updateStatus(true, statusLabel || '✓ e-Signature Generated');
    }

    // Undo Last Stroke
    if (undoBtn) {
        undoBtn.addEventListener('click', () => {
            if (strokes.length > 0) {
                strokes.pop();
                redrawAll();
                syncToHiddenInput();
            } else if (baseImage) {
                baseImage = null;
                if (typeInput) typeInput.value = '';
                redrawAll();
                syncToHiddenInput();
            }
        });
    }

    // Clear Pad
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            strokes = [];
            currentStroke = [];
            baseImage = null;
            if (typeInput) typeInput.value = '';
            const rect = canvas.getBoundingClientRect();
            ctx.clearRect(0, 0, rect.width, rect.height);
            syncToHiddenInput();
        });
    }

    // Mode Switching (Draw vs Type)
    function setSignatureMode(mode) {
        currentMode = mode;
        if (modeDrawBtn && modeTypeBtn) {
            modeDrawBtn.classList.toggle('active', mode === 'draw');
            modeTypeBtn.classList.toggle('active', mode === 'type');
        }
        if (typePanel) {
            typePanel.style.display = mode === 'type' ? 'block' : 'none';
            if (mode === 'type' && typeInput) {
                typeInput.focus();
            }
        }
    }

    if (modeDrawBtn) modeDrawBtn.addEventListener('click', () => setSignatureMode('draw'));
    if (modeTypeBtn) modeTypeBtn.addEventListener('click', () => setSignatureMode('type'));

    if (typeInput) {
        typeInput.addEventListener('input', () => {
            renderTextSignatureToCanvas(typeInput.value, '✓ Typed e-Signature');
        });
    }

    // Listen for external/programmatic updates to #applicant_signature (e.g. Test Mode Auto-Fill!)
    hiddenInput.addEventListener('input', () => {
        if (isInternalUpdate) return;
        const val = (hiddenInput.value || '').trim();
        if (!val) {
            strokes = [];
            currentStroke = [];
            baseImage = null;
            if (typeInput) typeInput.value = '';
            const rect = canvas.getBoundingClientRect();
            ctx.clearRect(0, 0, rect.width, rect.height);
            updateStatus(false);
        } else if (val.startsWith('data:image/')) {
            const img = new Image();
            img.onload = () => {
                baseImage = img;
                strokes = [];
                redrawAll();
                updateStatus(true, '✓ Saved e-Signature Loaded');
            };
            img.src = val;
        } else {
            if (typeInput) typeInput.value = val;
            renderTextSignatureToCanvas(val, '✓ Auto-Signed e-Signature');
        }
    });

    // Load initial signature value if editing an existing application
    requestAnimationFrame(() => {
        setupCanvasDimensions(false);
        const initialVal = (hiddenInput.value || '').trim();
        if (initialVal) {
            if (initialVal.startsWith('data:image/')) {
                const img = new Image();
                img.onload = () => {
                    baseImage = img;
                    redrawAll();
                    updateStatus(true, '✓ Saved e-Signature on File');
                };
                img.src = initialVal;
            } else {
                if (typeInput) typeInput.value = initialVal;
                renderTextSignatureToCanvas(initialVal, '✓ Signature on File');
            }
        } else {
            updateStatus(false);
        }
    });

    window.addEventListener('resize', () => {
        setupCanvasDimensions(true);
    });
}

// Helper: Open payment modal pre-selected for specific invoice
function openPaymentForInvoice(invoiceId, invoiceNumber, remainingBalance) {
    const modal = document.getElementById('modal-payment');
    const invoiceSelect = document.getElementById('pay_invoice_id');
    const amountInput = document.getElementById('amount_paid');

    if (modal && invoiceSelect) {
        invoiceSelect.value = invoiceId;
        if (amountInput) {
            amountInput.value = parseFloat(remainingBalance).toFixed(2);
        }
        modal.classList.add('active');
    }
}

// Helper: Update invoice default amount when lease is chosen
function updateInvoiceAmount(selectElem) {
    const selectedOption = selectElem.options[selectElem.selectedIndex];
    const amountInput = document.getElementById('inv_amount');
    if (selectedOption && selectedOption.dataset.amount && amountInput) {
        amountInput.value = parseFloat(selectedOption.dataset.amount).toFixed(2);
    }
}
