/**
 * Hangul Writing Canvas
 * Whiteboard-style drawing tool for practicing Hangul handwriting
 */
const HangulCanvas = (() => {
    let canvas, ctx;
    let isDrawing = false;
    let lastX = 0, lastY = 0;
    let tool = 'pen'; // 'pen' | 'eraser'
    let penColor = '#1e293b';
    let penSize = 4;
    let eraserSize = 28;
    let history = [];
    let historyIndex = -1;
    let guideChar = '';
    let guideVisible = true;

    function init(canvasId) {
        canvas = document.getElementById(canvasId);
        if (!canvas) return;
        ctx = canvas.getContext('2d');

        resizeCanvas();
        window.addEventListener('resize', debounce(resizeCanvas, 200));

        // Mouse events
        canvas.addEventListener('mousedown', startDraw);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', endDraw);
        canvas.addEventListener('mouseleave', endDraw);

        // Touch events
        canvas.addEventListener('touchstart', handleTouch(startDraw), { passive: false });
        canvas.addEventListener('touchmove', handleTouch(draw), { passive: false });
        canvas.addEventListener('touchend', endDraw);
        canvas.addEventListener('touchcancel', endDraw);

        // Prevent scrolling on touch
        canvas.style.touchAction = 'none';

        saveState();
        updateCursor();
    }

    function resizeCanvas() {
        const container = canvas.parentElement;
        const rect = container.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;

        // Save current drawing
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = canvas.width;
        tempCanvas.height = canvas.height;
        tempCanvas.getContext('2d').drawImage(canvas, 0, 0);

        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        canvas.style.width = rect.width + 'px';
        canvas.style.height = rect.height + 'px';
        ctx.scale(dpr, dpr);

        // Restore drawing
        ctx.drawImage(tempCanvas, 0, 0, tempCanvas.width, tempCanvas.height, 0, 0, rect.width, rect.height);

        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        if (guideChar && guideVisible) drawGuide();
    }

    function handleTouch(fn) {
        return (e) => {
            e.preventDefault();
            const touch = e.touches[0];
            if (!touch) return;
            const rect = canvas.getBoundingClientRect();
            const mouseEvent = {
                offsetX: touch.clientX - rect.left,
                offsetY: touch.clientY - rect.top
            };
            fn(mouseEvent);
        };
    }

    function startDraw(e) {
        isDrawing = true;
        [lastX, lastY] = [e.offsetX, e.offsetY];

        // Draw a dot for single clicks
        ctx.beginPath();
        if (tool === 'eraser') {
            ctx.globalCompositeOperation = 'destination-out';
            ctx.arc(lastX, lastY, eraserSize / 2, 0, Math.PI * 2);
            ctx.fill();
        } else {
            ctx.globalCompositeOperation = 'source-over';
            ctx.fillStyle = penColor;
            ctx.arc(lastX, lastY, penSize / 2, 0, Math.PI * 2);
            ctx.fill();
        }
    }

    function draw(e) {
        if (!isDrawing) return;

        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(e.offsetX, e.offsetY);

        if (tool === 'eraser') {
            ctx.globalCompositeOperation = 'destination-out';
            ctx.lineWidth = eraserSize;
            ctx.strokeStyle = 'rgba(0,0,0,1)';
        } else {
            ctx.globalCompositeOperation = 'source-over';
            ctx.lineWidth = penSize;
            ctx.strokeStyle = penColor;
        }

        ctx.stroke();
        [lastX, lastY] = [e.offsetX, e.offsetY];
    }

    function endDraw() {
        if (!isDrawing) return;
        isDrawing = false;
        ctx.globalCompositeOperation = 'source-over';
        saveState();
    }

    function saveState() {
        historyIndex++;
        history = history.slice(0, historyIndex);
        history.push(canvas.toDataURL());
        // Limit history to 30 steps
        if (history.length > 30) {
            history.shift();
            historyIndex--;
        }
        updateUndoRedoUI();
    }

    function undo() {
        if (historyIndex <= 0) return;
        historyIndex--;
        restoreState();
    }

    function redo() {
        if (historyIndex >= history.length - 1) return;
        historyIndex++;
        restoreState();
    }

    function restoreState() {
        const img = new Image();
        img.onload = () => {
            const dpr = window.devicePixelRatio || 1;
            ctx.clearRect(0, 0, canvas.width / dpr, canvas.height / dpr);
            ctx.drawImage(img, 0, 0, canvas.width / dpr, canvas.height / dpr);
            updateUndoRedoUI();
        };
        img.src = history[historyIndex];
    }

    function clearCanvas() {
        const dpr = window.devicePixelRatio || 1;
        ctx.clearRect(0, 0, canvas.width / dpr, canvas.height / dpr);
        if (guideChar && guideVisible) drawGuide();
        saveState();
    }

    function setTool(t) {
        tool = t;
        updateCursor();
        updateToolUI();
    }

    function setPenColor(color) {
        penColor = color;
        updateColorUI();
    }

    function setPenSize(size) {
        penSize = parseInt(size);
        updateSizeUI();
    }

    function setEraserSize(size) {
        eraserSize = parseInt(size);
    }

    function setGuide(char) {
        guideChar = char;
        clearCanvas();
    }

    function toggleGuide() {
        guideVisible = !guideVisible;
        // Redraw from last state without guide, then overlay if needed
        if (historyIndex >= 0) restoreState();
        if (guideVisible && guideChar) drawGuide();
        const btn = document.getElementById('toggleGuideBtn');
        if (btn) {
            btn.classList.toggle('bg-blue-100', guideVisible);
            btn.classList.toggle('text-blue-700', guideVisible);
            btn.classList.toggle('bg-gray-100', !guideVisible);
            btn.classList.toggle('text-gray-500', !guideVisible);
        }
    }

    function drawGuide() {
        if (!guideChar || !guideVisible) return;
        const dpr = window.devicePixelRatio || 1;
        const w = canvas.width / dpr;
        const h = canvas.height / dpr;

        ctx.save();
        ctx.globalAlpha = 0.08;
        ctx.fillStyle = '#3B82F6';
        ctx.font = `bold ${Math.min(w, h) * 0.7}px 'Noto Sans KR', sans-serif`;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(guideChar, w / 2, h / 2);

        // Draw crosshair guides
        ctx.globalAlpha = 0.06;
        ctx.strokeStyle = '#3B82F6';
        ctx.lineWidth = 1;
        ctx.setLineDash([8, 8]);

        // Vertical center
        ctx.beginPath();
        ctx.moveTo(w / 2, 0);
        ctx.lineTo(w / 2, h);
        ctx.stroke();

        // Horizontal center
        ctx.beginPath();
        ctx.moveTo(0, h / 2);
        ctx.lineTo(w, h / 2);
        ctx.stroke();

        ctx.setLineDash([]);
        ctx.restore();
    }

    function updateCursor() {
        if (!canvas) return;
        if (tool === 'eraser') {
            canvas.style.cursor = `url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28"><circle cx="14" cy="14" r="12" fill="none" stroke="%23999" stroke-width="2"/></svg>') 14 14, crosshair`;
        } else {
            canvas.style.cursor = 'crosshair';
        }
    }

    function updateToolUI() {
        document.querySelectorAll('[data-tool]').forEach(btn => {
            const isActive = btn.dataset.tool === tool;
            btn.classList.toggle('ring-2', isActive);
            btn.classList.toggle('ring-blue-500', isActive);
            btn.classList.toggle('bg-blue-50', isActive);
        });
    }

    function updateColorUI() {
        document.querySelectorAll('[data-color]').forEach(btn => {
            const isActive = btn.dataset.color === penColor;
            btn.classList.toggle('ring-2', isActive);
            btn.classList.toggle('ring-offset-2', isActive);
            btn.classList.toggle('ring-blue-500', isActive);
        });
    }

    function updateSizeUI() {
        const el = document.getElementById('penSizeLabel');
        if (el) el.textContent = penSize + 'px';
    }

    function updateUndoRedoUI() {
        const undoBtn = document.getElementById('undoBtn');
        const redoBtn = document.getElementById('redoBtn');
        if (undoBtn) undoBtn.disabled = historyIndex <= 0;
        if (redoBtn) redoBtn.disabled = historyIndex >= history.length - 1;
    }

    function downloadImage() {
        const link = document.createElement('a');
        link.download = 'hangul-practice-' + Date.now() + '.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    }

    function debounce(fn, ms) {
        let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        if (e.ctrlKey && e.key === 'z') { e.preventDefault(); undo(); }
        if (e.ctrlKey && e.key === 'y') { e.preventDefault(); redo(); }
        if (e.key === 'p') setTool('pen');
        if (e.key === 'e') setTool('eraser');
        if (e.key === 'Delete' || (e.ctrlKey && e.key === 'Backspace')) clearCanvas();
    });

    return {
        init, setTool, setPenColor, setPenSize, setEraserSize,
        setGuide, toggleGuide, clearCanvas, undo, redo, downloadImage
    };
})();
