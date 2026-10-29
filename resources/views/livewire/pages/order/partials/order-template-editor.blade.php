<div x-data="orderTemplateEditor" x-on:order-template-open.window="open($event.detail)" x-on:order-template-close.window="visible = false; saving = false" x-on:order-template-saved.window="if ($event.detail.index === index) { visible = false; saving = false }" x-show="visible" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-[#080b11]/85 p-2 sm:p-4" role="dialog" aria-modal="true" aria-labelledby="order-template-editor-title" x-on:keydown.escape.window="visible = false; saving = false" x-on:keydown.window="handleKeydown($event)">
    <div class="relative flex max-h-[96vh] w-full max-w-[1500px] flex-col gap-3 overflow-y-auto rounded-xl border border-slate-700 bg-[#10141b] p-3 text-slate-100 shadow-2xl lg:flex-row lg:overflow-hidden lg:p-4">
        <button type="button" x-on:click="visible = false; saving = false" aria-label="Đóng" class="absolute right-3 top-3 z-20 flex h-10 w-10 items-center justify-center rounded-full border border-slate-600 bg-[#10141b] text-2xl text-slate-200 hover:bg-slate-800 disabled:opacity-50">&times;</button>
        <div class="flex min-h-[420px] flex-1 items-center justify-center rounded-xl border border-slate-700 bg-[#151a22] p-3 sm:min-h-[620px] lg:min-h-0">
            <div class="relative aspect-square w-full max-w-[760px] overflow-hidden bg-white" x-bind:style="`aspect-ratio: ${width} / ${height}`">
                <img x-bind:src="templates[type] || ''" alt="" aria-hidden="true" class="pointer-events-none absolute inset-0 h-full w-full object-fill" x-bind:style="`opacity: ${opacity / 100}; display: ${!templateOnTop ? 'block' : 'none'}`">
                <canvas x-ref="canvas" class="h-full w-full touch-none" aria-label="Ảnh Create Master để căn theo template" x-on:pointerdown="startDrag($event)" x-on:pointermove="drag($event)" x-on:pointerup="dragging = false" x-on:pointercancel="dragging = false" x-on:wheel="zoomWithWheel($event)"></canvas>
                <img x-bind:src="templates[type] || ''" alt="" aria-hidden="true" class="pointer-events-none absolute inset-0 h-full w-full object-fill" x-bind:style="`opacity: ${opacity / 100}; display: ${templateOnTop ? 'block' : 'none'}`">
                                <div x-show="saving" x-cloak class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-3 bg-[#151a22]/95 text-sm text-slate-200"><svg class="h-8 w-8 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg><span>Đang lưu ảnh bounds...</span></div><div x-show="!ready" class="absolute inset-0 flex items-center justify-center bg-[#151a22] text-sm text-slate-400" x-text="!type ? 'Chọn loại template' : (!templates[type] ? 'Chưa có template' : 'Đang tải ảnh...')" role="status"></div>
            </div>
        </div>
        <aside class="flex w-full flex-col rounded-xl border border-slate-700 bg-[#151a22] p-4 lg:max-w-[570px] lg:overflow-y-auto">
            <div class="flex items-start gap-3 border-b border-slate-700 pb-4 pr-12">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-600/20 text-blue-300" aria-hidden="true">▣</span>
                <div class="min-w-0">
                    <h2 id="order-template-editor-title" class="text-base font-semibold">Image Information</h2>
                    <p class="mt-1 text-xs text-slate-400">Create Master <span x-text="index === null ? '' : index + 1"></span></p>
                    <label for="order-template-type" class="mt-3 block text-xs font-semibold text-slate-300">Template</label>
                    <select id="order-template-type" x-model="type" x-on:change="loadTemplate()" x-bind:disabled="saving" class="mt-1 w-full rounded-lg border border-slate-600 bg-[#10141b] px-3 py-2 text-sm text-slate-100">
                        <option value="">Chọn template</option><option value="glass">Glass</option><option value="ceramic">Ceramic</option>
                    </select>
                    <p x-show="type && !templates[type]" role="alert" class="mt-2 text-xs text-red-300">Yêu cầu admin thêm template <span x-text="type"></span>!</p>
                </div>
            </div>
            <div class="mt-4 rounded-xl border border-blue-300/70 bg-[#1b2b4b] p-3">
                <div class="flex items-center justify-between text-xs font-semibold"><span>Bounds controls</span><span class="text-slate-300">Arrow keys: move</span></div>
                <div class="mt-3 flex gap-2">
                    <button type="button" x-on:click="templateOnTop = true; draw()" x-bind:class="templateOnTop ? 'bg-blue-600 text-white' : 'bg-[#151a22] text-slate-200'" class="flex-1 rounded-lg px-3 py-3 text-sm font-semibold">Trên ảnh</button>
                    <button type="button" x-on:click="templateOnTop = false; draw()" x-bind:class="!templateOnTop ? 'bg-blue-600 text-white' : 'bg-[#151a22] text-slate-200'" class="flex-1 rounded-lg px-3 py-3 text-sm font-semibold">Dưới ảnh</button>
                </div>
                <label for="order-template-zoom" class="mt-4 block text-xs font-semibold">Zoom <span class="float-right" x-text="Math.round(zoom * 100) + '%' "></span></label>
                <input id="order-template-zoom" type="range" min="0.5" max="2" step="0.01" x-model.number="zoom" x-on:input="draw()" class="mt-2 w-full">
                <label for="order-template-rotation" class="mt-4 block text-xs font-semibold">Xoay ảnh <span class="float-right" x-text="rotation + '°'"></span></label>
                <div class="mt-2 flex items-center gap-2"><button type="button" x-on:click="rotation = Math.max(-180, rotation - 1); draw()" class="rounded-lg border border-slate-600 px-3 py-2">-1°</button><input id="order-template-rotation" type="range" min="-180" max="180" step="1" x-model.number="rotation" x-on:input="draw()" class="w-full"><button type="button" x-on:click="rotation = Math.min(180, rotation + 1); draw()" class="rounded-lg border border-slate-600 px-3 py-2">+1°</button></div>
                <label for="order-template-opacity" class="mt-4 block text-xs font-semibold">Chỉnh độ mờ bounds <span class="float-right" x-text="opacity + '%' "></span></label>
                <input id="order-template-opacity" type="range" min="10" max="100" step="1" x-model.number="opacity" class="mt-2 w-full">
                <p class="mt-3 text-xs text-slate-300">Arrow keys 1px / keypress <span class="float-right">Wheel: 1%</span></p>
            </div>
            <div class="mt-4 flex-1">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Status</p>
                <p x-show="saved" class="mt-2 text-sm text-emerald-400">✓ Đã lưu ảnh bounds</p>
                <p x-show="error" x-text="error" role="alert" class="mt-2 text-sm text-red-300"></p>
                <p x-show="ready && !error && !saved" class="mt-2 inline-flex rounded-full border border-emerald-400/60 px-3 py-1 text-sm text-emerald-400">✓ Ready to save</p>
            </div>
            <div class="mt-4 flex justify-end gap-2 border-t border-slate-700 pt-4">
                <button type="button" x-on:click="visible = false; saving = false" class="rounded-lg border border-slate-600 px-5 py-3 text-sm font-semibold">Cancel</button>
                <button type="button" x-on:click="save()" x-bind:disabled="!ready || saving" class="rounded-lg bg-emerald-600 px-5 py-3 text-sm font-bold text-white disabled:opacity-50"><span x-show="!saving">✓ Save Bounds</span><span x-show="saving" class="inline-flex items-center gap-2" role="status"><svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>Đang lưu...</span></button>
            </div>
        </aside>
    </div>
</div>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('orderTemplateEditor', () => ({
            visible: false, ready: false, saving: false, saved: false, type: '', index: null, source: '', templates: {}, image: null, width: 1, height: 1, zoom: 1, rotation: 0, x: 0, y: 0, opacity: 60, templateOnTop: true, cropMode: 'circle', crop: { cx: 0, cy: 0, radius: 0, x: 0, y: 0, width: 0, height: 0 }, dragging: false, error: '', loadVersion: 0,
            open(detail) { this.index = detail.index; this.source = detail.image; this.type = detail.type || ''; this.templates = detail.templates || {}; this.visible = true; this.saving = false; this.saved = false; this.error = ''; this.loadTemplate(); },
            async loadTemplate() {
                const version = ++this.loadVersion; this.ready = false; this.error = '';
                if (!this.type || !this.templates[this.type]) return;
                try {
                    const load = src => new Promise((resolve, reject) => { const image = new Image(); image.onload = () => resolve(image); image.onerror = reject; image.src = src; });
                    const [guide, image] = await Promise.all([load(this.templates[this.type]), load(this.source)]);
                    if (version !== this.loadVersion) return;
                    this.width = guide.naturalWidth; this.height = guide.naturalHeight; this.guide = guide; this.detectCrop(); this.image = image; this.zoom = 1; this.rotation = 0; this.x = 0; this.y = 0; this.ready = true; await this.$nextTick(); this.draw();
                } catch { if (version === this.loadVersion) this.error = 'Không tải được ảnh hoặc template. Vui lòng thử lại.'; }
            },
            draw() {
                if (!this.ready) return;
                const canvas = this.$refs.canvas; canvas.width = this.width; canvas.height = this.height; const context = canvas.getContext('2d'); const scale = Math.max(this.width / this.image.naturalWidth, this.height / this.image.naturalHeight) * this.zoom;
                context.clearRect(0, 0, this.width, this.height); context.save(); context.translate(this.width / 2 + this.x, this.height / 2 + this.y); context.rotate(this.rotation * Math.PI / 180); context.drawImage(this.image, -this.image.naturalWidth * scale / 2, -this.image.naturalHeight * scale / 2, this.image.naturalWidth * scale, this.image.naturalHeight * scale); context.restore();
            },
            startDrag(event) { if (!this.ready) return; this.dragging = true; this.lastX = event.clientX; this.lastY = event.clientY; event.currentTarget.setPointerCapture(event.pointerId); },
            drag(event) { if (!this.dragging) return; const rect = this.$refs.canvas.getBoundingClientRect(); this.x += (event.clientX - this.lastX) * this.width / rect.width; this.y += (event.clientY - this.lastY) * this.height / rect.height; this.lastX = event.clientX; this.lastY = event.clientY; this.draw(); },
            detectCrop() {
                const probe = document.createElement('canvas'); probe.width = this.width; probe.height = this.height; const context = probe.getContext('2d'); context.drawImage(this.guide, 0, 0, this.width, this.height); const pixels = context.getImageData(0, 0, this.width, this.height).data; let minX = this.width, minY = this.height, maxX = -1, maxY = -1, count = 0;
                for (let y = 0; y < this.height; y += 2) for (let x = 0; x < this.width; x += 2) { const i = (y * this.width + x) * 4, red = pixels[i], green = pixels[i + 1], blue = pixels[i + 2], alpha = pixels[i + 3]; if (alpha > 20 && red > 150 && red > green * 1.35 && red > blue * 1.35) { count++; minX = Math.min(minX, x); minY = Math.min(minY, y); maxX = Math.max(maxX, x); maxY = Math.max(maxY, y); } }
                if (maxX < 0) { this.cropMode = 'circle'; this.crop = { cx: this.width / 2, cy: this.height / 2, radius: Math.min(this.width, this.height) * 0.445 }; return; }
                const boxWidth = maxX - minX, boxHeight = maxY - minY, sampled = Math.ceil(this.width / 2) * Math.ceil(this.height / 2), coverage = count / sampled;
                if (coverage > 0.15) { this.cropMode = 'rect'; this.crop = { x: minX, y: minY, width: boxWidth, height: boxHeight }; return; }
                this.cropMode = 'circle'; this.crop = { cx: minX + boxWidth / 2, cy: minY + boxHeight / 2, radius: Math.min(boxWidth, boxHeight) / 2 };
            },
            move(x, y) { this.x += x; this.y += y; this.draw(); },
            zoomWithWheel(event) { if (!this.ready) return; event.preventDefault(); this.zoom = Math.max(0.5, Math.min(2, this.zoom + (event.deltaY < 0 ? 0.01 : -0.01))); this.draw(); },
            handleKeydown(event) { if (!this.visible || !this.ready || ['INPUT','SELECT','TEXTAREA'].includes(event.target.tagName) || !['ArrowUp','ArrowDown','ArrowLeft','ArrowRight'].includes(event.key)) return; event.preventDefault(); const step = 1; if(event.key === 'ArrowUp') this.y -= step; if(event.key === 'ArrowDown') this.y += step; if(event.key === 'ArrowLeft') this.x -= step; if(event.key === 'ArrowRight') this.x += step; this.draw(); },
            async save() {
                if (!this.ready || this.saving) return; this.saving = true; this.error = '';
                try { const output = document.createElement('canvas'); output.width = this.width; output.height = this.height; const context = output.getContext('2d'); context.save(); context.beginPath(); if (this.cropMode === 'rect') context.rect(this.crop.x, this.crop.y, this.crop.width, this.crop.height); else context.arc(this.crop.cx, this.crop.cy, this.crop.radius, 0, Math.PI * 2); context.clip(); context.drawImage(this.$refs.canvas, 0, 0); context.restore(); await this.$wire.saveOrderTextTemplate(this.index, this.type, output.toDataURL('image/png')); this.saved = true; } catch { this.error = 'Không lưu được ảnh. Vui lòng thử lại.'; } finally { this.saving = false; }
            }
        }));
    });
</script>