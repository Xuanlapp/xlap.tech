import fs from 'node:fs';
import path from 'node:path';
import { createCanvas, loadImage } from '@napi-rs/canvas';
import { initializeCanvas, readPsd } from 'ag-psd';

initializeCanvas((width = 1, height = 1) => createCanvas(Math.max(1, width), Math.max(1, height)));

const DESIGN_LAYER_NAMES = new Set(['design', 'desgin']);
const OFFOREST_REPLACED_DESIGN_LAYER = '__offorestReplacedDesignLayer';

function readStdin() {
    return new Promise((resolve, reject) => {
        let input = '';

        process.stdin.setEncoding('utf8');
        process.stdin.on('data', (chunk) => {
            input += chunk;
        });
        process.stdin.on('end', () => resolve(input));
        process.stdin.on('error', reject);
    });
}

function normalizeName(name) {
    return String(name || '').trim();
}

function isDesignLayer(layer) {
    return !layer.children && DESIGN_LAYER_NAMES.has(normalizeName(layer.name).toLowerCase());
}

function isMockupGroup(layer, folderPrefix) {
    if (!layer.children) {
        return false;
    }

    return new RegExp(`^${escapeRegExp(folderPrefix)}\\s*\\d+\\b`, 'i').test(normalizeName(layer.name));
}

function escapeRegExp(value) {
    return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function walkLayers(layers, callback) {
    for (const layer of layers || []) {
        callback(layer);

        if (layer.children) {
            walkLayers(layer.children, callback);
        }
    }
}

function findMockupGroups(layers, folderPrefix) {
    const groups = [];

    walkLayers(layers, (layer) => {
        if (isMockupGroup(layer, folderPrefix)) {
            groups.push(layer);
        }
    });

    return groups.sort((a, b) => mockupNumber(a.name) - mockupNumber(b.name));
}

function setOnlyMockupGroupVisible(groups, activeGroup) {
    const previous = groups.map((group) => ({
        group,
        hidden: group.hidden,
        visible: group.visible,
    }));

    for (const group of groups) {
        const active = group === activeGroup;

        group.hidden = !active;
        group.visible = active;
    }

    return () => {
        for (const item of previous) {
            item.group.hidden = item.hidden;
            item.group.visible = item.visible;
        }
    };
}

function mockupNumber(name) {
    const match = normalizeName(name).match(/(\d+)/);

    return match ? Number(match[1]) : Number.MAX_SAFE_INTEGER;
}

async function replaceDesignLayers(psd, masterImagePath) {
    const image = await prepareMasterImage(masterImagePath);
    let replaced = 0;

    walkLayers(psd.children || [], (layer) => {
        if (!isDesignLayer(layer)) {
            return;
        }

        const placedWidth = toPositiveInt(layer?.placedLayer?.width, 0);
        const placedHeight = toPositiveInt(layer?.placedLayer?.height, 0);
        const width = placedWidth || Math.max(1, Math.round((layer.right ?? 0) - (layer.left ?? 0)));
        const height = placedHeight || Math.max(1, Math.round((layer.bottom ?? 0) - (layer.top ?? 0)));
        const canvas = createCanvas(width, height);
        const ctx = canvas.getContext('2d');

        drawImageContain(ctx, image, width, height);

        layer.canvas = canvas;
        layer.imageData = undefined;
        layer.hidden = false;
        layer[OFFOREST_REPLACED_DESIGN_LAYER] = true;
        replaced++;
    });

    if (replaced === 0) {
        throw new Error('Khong tim thay layer Design hoac Desgin trong PSD.');
    }
}

async function prepareMasterImage(masterImagePath) {
    const image = await loadImage(masterImagePath);
    const canvas = createCanvas(image.width || 1, image.height || 1);
    const ctx = canvas.getContext('2d');

    ctx.drawImage(image, 0, 0);

    if (shouldRemoveEdgeWhiteBackground()) {
        removeEdgeWhiteBackground(ctx, canvas.width, canvas.height);
    }

    return shouldTrimMasterImage()
        ? trimTransparentBounds(ctx, canvas.width, canvas.height)
        : canvas;
}

function shouldTrimMasterImage() {
    return String(process.env.OFFOREST_TRIM_MOCKUP_DESIGN || '').toLowerCase() === 'true';
}

function shouldRemoveEdgeWhiteBackground() {
    return String(process.env.OFFOREST_REMOVE_EDGE_WHITE || 'true').toLowerCase() !== 'false';
}

function removeEdgeWhiteBackground(ctx, width, height) {
    const imageData = ctx.getImageData(0, 0, width, height);
    const data = imageData.data;
    const visited = new Uint8Array(width * height);
    const queue = new Int32Array(width * height);
    let head = 0;
    let tail = 0;

    const enqueue = (x, y) => {
        if (x < 0 || y < 0 || x >= width || y >= height) {
            return;
        }

        const index = y * width + x;

        if (visited[index]) {
            return;
        }

        visited[index] = 1;

        if (!isNearWhite(data, index * 4)) {
            return;
        }

        queue[tail++] = index;
    };

    for (let x = 0; x < width; x++) {
        enqueue(x, 0);
        enqueue(x, height - 1);
    }

    for (let y = 1; y < height - 1; y++) {
        enqueue(0, y);
        enqueue(width - 1, y);
    }

    while (head < tail) {
        const index = queue[head++];
        const offset = index * 4;
        const x = index % width;
        const y = Math.floor(index / width);

        data[offset + 3] = 0;

        enqueue(x + 1, y);
        enqueue(x - 1, y);
        enqueue(x, y + 1);
        enqueue(x, y - 1);
    }

    ctx.putImageData(imageData, 0, 0);
}

function isNearWhite(data, offset) {
    return data[offset + 3] > 0
        && data[offset] >= 245
        && data[offset + 1] >= 245
        && data[offset + 2] >= 245;
}

function trimTransparentBounds(ctx, width, height) {
    const imageData = ctx.getImageData(0, 0, width, height);
    const data = imageData.data;
    let left = width;
    let top = height;
    let right = -1;
    let bottom = -1;

    for (let y = 0; y < height; y++) {
        for (let x = 0; x < width; x++) {
            const alpha = data[((y * width + x) * 4) + 3];

            if (alpha <= 8) {
                continue;
            }

            left = Math.min(left, x);
            top = Math.min(top, y);
            right = Math.max(right, x);
            bottom = Math.max(bottom, y);
        }
    }

    if (right < left || bottom < top) {
        return ctx.canvas;
    }

    const trimmedWidth = right - left + 1;
    const trimmedHeight = bottom - top + 1;
    const trimmed = createCanvas(trimmedWidth, trimmedHeight);
    const trimmedCtx = trimmed.getContext('2d');

    trimmedCtx.putImageData(ctx.getImageData(left, top, trimmedWidth, trimmedHeight), 0, 0);

    return trimmed;
}

function drawImageContain(ctx, image, targetWidth, targetHeight) {
    const imageWidth = image.width || targetWidth;
    const imageHeight = image.height || targetHeight;
    const scale = Math.min(targetWidth / imageWidth, targetHeight / imageHeight);
    const width = imageWidth * scale;
    const height = imageHeight * scale;
    const left = (targetWidth - width) / 2;
    const top = (targetHeight - height) / 2;

    ctx.clearRect(0, 0, targetWidth, targetHeight);
    ctx.drawImage(image, left, top, width, height);
}

function toPositiveInt(value, fallback = 0) {
    const parsed = Math.round(Number(value));

    return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
}

function getLayerBounds(layer) {
    const left = toSafeInt(layer?.left, 0);
    const top = toSafeInt(layer?.top, 0);
    const right = toSafeInt(layer?.right, left);
    const bottom = toSafeInt(layer?.bottom, top);

    return {
        left,
        top,
        right,
        bottom,
        width: toPositiveInt(right - left, 1),
        height: toPositiveInt(bottom - top, 1),
    };
}

function toSafeInt(value, fallback = 0) {
    const parsed = Number(value);

    return Number.isFinite(parsed) ? Math.trunc(parsed) : fallback;
}

function isLayerVisible(layer) {
    return layer?.visible !== false && layer?.hidden !== true;
}

function renderPsdToCanvas(psd, layersToDraw = null) {
    const width = toPositiveInt(psd?.width, 1);
    const height = toPositiveInt(psd?.height, 1);
    const canvas = createCanvas(width, height);
    const context = canvas.getContext('2d');

    const toCanvasFromImageData = (imageDataLike) => {
        if (!imageDataLike?.data || !imageDataLike?.width || !imageDataLike?.height) {
            return null;
        }

        const imageWidth = toPositiveInt(imageDataLike.width, 0);
        const imageHeight = toPositiveInt(imageDataLike.height, 0);

        if (!imageWidth || !imageHeight) {
            return null;
        }

        const pixels = imageDataLike.data instanceof Uint8ClampedArray
            ? imageDataLike.data
            : new Uint8ClampedArray(imageDataLike.data);
        const layerCanvas = createCanvas(imageWidth, imageHeight);
        const layerContext = layerCanvas.getContext('2d');
        const targetImageData = layerContext.createImageData(imageWidth, imageHeight);

        targetImageData.data.set(pixels.subarray(0, targetImageData.data.length));
        layerContext.putImageData(targetImageData, 0, 0);

        return layerCanvas;
    };

    const resolveLayerSource = (layer) => {
        if (layer?.canvas && typeof layer.canvas.getContext === 'function') {
            return layer.canvas;
        }

        if (layer?.imageData) {
            return toCanvasFromImageData(layer.imageData);
        }

        return null;
    };

    const resolveMaskSource = (mask) => {
        if (!mask) {
            return null;
        }

        if (mask?.canvas && typeof mask.canvas.getContext === 'function') {
            return mask.canvas;
        }

        if (mask?.imageData) {
            return toCanvasFromImageData(mask.imageData);
        }

        return null;
    };

    const normalizeOpacity = (opacityValue) => {
        const parsed = Number(opacityValue);

        if (!Number.isFinite(parsed)) {
            return 1;
        }

        if (parsed <= 1) {
            return Math.max(0, Math.min(1, parsed));
        }

        return Math.max(0, Math.min(1, parsed / 255));
    };

    const placeCanvas = (sourceCanvas, left, top) => {
        const placedCanvas = createCanvas(width, height);
        const placedContext = placedCanvas.getContext('2d');

        placedContext.drawImage(
            sourceCanvas,
            toSafeInt(left, 0),
            toSafeInt(top, 0),
            toPositiveInt(sourceCanvas?.width, 1),
            toPositiveInt(sourceCanvas?.height, 1),
        );

        return placedCanvas;
    };

    const applyMaskCanvas = (sourceCanvas, maskCanvas) => {
        if (!maskCanvas) {
            return sourceCanvas;
        }

        const maskedCanvas = createCanvas(width, height);
        const maskedContext = maskedCanvas.getContext('2d');

        maskedContext.drawImage(sourceCanvas, 0, 0, width, height);
        maskedContext.globalCompositeOperation = 'destination-in';
        maskedContext.drawImage(maskCanvas, 0, 0, width, height);

        return maskedCanvas;
    };

    const buildOpacityCanvas = (sourceCanvas, opacityValue) => {
        const opacityCanvas = createCanvas(width, height);
        const opacityContext = opacityCanvas.getContext('2d');

        opacityContext.save();
        opacityContext.globalAlpha = normalizeOpacity(opacityValue);
        opacityContext.drawImage(sourceCanvas, 0, 0, width, height);
        opacityContext.restore();

        return opacityCanvas;
    };

    const getPlacedLayerQuad = (layer, sourceCanvas) => {
        const placedLayer = layer?.placedLayer;

        if (!placedLayer) {
            return null;
        }

        const quad = Array.isArray(placedLayer?.nonAffineTransform) && placedLayer.nonAffineTransform.length === 8
            ? placedLayer.nonAffineTransform
            : (Array.isArray(placedLayer?.transform) && placedLayer.transform.length === 8
                ? placedLayer.transform
                : null);

        if (!quad) {
            return null;
        }

        return {
            sourceWidth: toPositiveInt(placedLayer?.width, toPositiveInt(sourceCanvas?.width, 1)),
            sourceHeight: toPositiveInt(placedLayer?.height, toPositiveInt(sourceCanvas?.height, 1)),
            points: [
                { x: Number(quad[0]) || 0, y: Number(quad[1]) || 0 },
                { x: Number(quad[2]) || 0, y: Number(quad[3]) || 0 },
                { x: Number(quad[4]) || 0, y: Number(quad[5]) || 0 },
                { x: Number(quad[6]) || 0, y: Number(quad[7]) || 0 },
            ],
        };
    };

    const drawPlacedLayerQuad = (targetContext, sourceCanvas, placedQuad) => {
        if (!targetContext || !sourceCanvas || !placedQuad?.points?.length) {
            return;
        }

        const [p0, p1, p2, p3] = placedQuad.points;
        const sourceWidth = toPositiveInt(placedQuad.sourceWidth, toPositiveInt(sourceCanvas?.width, 1));
        const sourceHeight = toPositiveInt(placedQuad.sourceHeight, toPositiveInt(sourceCanvas?.height, 1));
        const isAffineQuad = Math.hypot((p0.x + p2.x) - (p1.x + p3.x), (p0.y + p2.y) - (p1.y + p3.y)) < 0.75;

        if (isAffineQuad) {
            targetContext.save();
            targetContext.setTransform(
                (p1.x - p0.x) / sourceWidth,
                (p1.y - p0.y) / sourceWidth,
                (p3.x - p0.x) / sourceHeight,
                (p3.y - p0.y) / sourceHeight,
                p0.x,
                p0.y,
            );
            targetContext.drawImage(
                sourceCanvas,
                0,
                0,
                toPositiveInt(sourceCanvas?.width, 1),
                toPositiveInt(sourceCanvas?.height, 1),
                0,
                0,
                sourceWidth,
                sourceHeight,
            );
            targetContext.restore();
            return;
        }

        const expandTriangle = (points, amount = 0.75) => {
            const center = points.reduce(
                (acc, point) => ({ x: acc.x + point.x / points.length, y: acc.y + point.y / points.length }),
                { x: 0, y: 0 },
            );

            return points.map((point) => {
                const dx = point.x - center.x;
                const dy = point.y - center.y;
                const length = Math.hypot(dx, dy) || 1;

                return {
                    x: point.x + (dx / length) * amount,
                    y: point.y + (dy / length) * amount,
                };
            });
        };

        const drawTriangle = (trianglePoints, matrix) => {
            const expandedPoints = expandTriangle(trianglePoints);

            targetContext.save();
            targetContext.beginPath();
            targetContext.moveTo(expandedPoints[0].x, expandedPoints[0].y);
            targetContext.lineTo(expandedPoints[1].x, expandedPoints[1].y);
            targetContext.lineTo(expandedPoints[2].x, expandedPoints[2].y);
            targetContext.closePath();
            targetContext.clip();
            targetContext.setTransform(matrix.a, matrix.b, matrix.c, matrix.d, matrix.e, matrix.f);
            targetContext.drawImage(
                sourceCanvas,
                0,
                0,
                toPositiveInt(sourceCanvas?.width, 1),
                toPositiveInt(sourceCanvas?.height, 1),
                0,
                0,
                sourceWidth,
                sourceHeight,
            );
            targetContext.restore();
        };

        drawTriangle([p0, p1, p2], {
            a: (p1.x - p0.x) / sourceWidth,
            b: (p1.y - p0.y) / sourceWidth,
            c: (p2.x - p1.x) / sourceHeight,
            d: (p2.y - p1.y) / sourceHeight,
            e: p0.x,
            f: p0.y,
        });

        drawTriangle([p0, p2, p3], {
            a: (p2.x - p3.x) / sourceWidth,
            b: (p2.y - p3.y) / sourceWidth,
            c: (p3.x - p0.x) / sourceHeight,
            d: (p3.y - p0.y) / sourceHeight,
            e: p0.x,
            f: p0.y,
        });
    };

    const renderLayerContent = (layer, parentVisible = true, inheritedOpacity = 1) => {
        const layerVisible = parentVisible && isLayerVisible(layer);

        if (!layerVisible) {
            return null;
        }

        if (Array.isArray(layer?.children) && layer.children.length) {
            const groupCanvas = createCanvas(width, height);
            const groupContext = groupCanvas.getContext('2d');

            drawLayerSequence(layer.children, groupContext, layerVisible, inheritedOpacity);

            const groupMask = resolveMaskSource(layer?.mask);

            if (!groupMask) {
                return groupCanvas;
            }

            const maskBounds = getLayerBounds(layer.mask);

            return applyMaskCanvas(groupCanvas, placeCanvas(groupMask, maskBounds.left, maskBounds.top));
        }

        const srcCanvas = resolveLayerSource(layer);

        if (!srcCanvas) {
            return null;
        }

        const bounds = getLayerBounds(layer);
        const layerCanvas = createCanvas(width, height);
        const layerContext = layerCanvas.getContext('2d');
        const placedQuad = getPlacedLayerQuad(layer, srcCanvas);

        layerContext.save();
        layerContext.globalAlpha = Math.max(0, Math.min(1, inheritedOpacity));

        if (placedQuad) {
            drawPlacedLayerQuad(layerContext, srcCanvas, placedQuad);
        } else {
            layerContext.drawImage(
                srcCanvas,
                toSafeInt(bounds.left, 0),
                toSafeInt(bounds.top, 0),
                toPositiveInt(bounds.width, 1),
                toPositiveInt(bounds.height, 1),
            );
        }

        layerContext.restore();

        const renderedWithEffects = shouldApplyLayerEffects(layer)
            ? applyLayerEffects(layer, layerCanvas)
            : layerCanvas;
        const layerMask = resolveMaskSource(layer?.mask);

        if (!layerMask) {
            return renderedWithEffects;
        }

        const maskBounds = getLayerBounds(layer.mask);

        return applyMaskCanvas(renderedWithEffects, placeCanvas(layerMask, maskBounds.left, maskBounds.top));
    };

    const compositeLayerCanvas = (targetContext, sourceCanvas, opacityValue, blendMode) => {
        if (!sourceCanvas) {
            return;
        }

        targetContext.save();
        targetContext.globalAlpha = Math.max(0, Math.min(1, normalizeOpacity(opacityValue)));
        targetContext.globalCompositeOperation = blendModeToComposite(blendMode);
        targetContext.drawImage(sourceCanvas, 0, 0, width, height);
        targetContext.restore();
    };

    const drawLayerSequence = (layers, targetContext, parentVisible = true, inheritedOpacity = 1) => {
        let lastBaseCanvas = null;

        for (const layer of layers || []) {
            const layerVisible = parentVisible && isLayerVisible(layer);

            if (!layerVisible) {
                if (!layer?.clipping) {
                    lastBaseCanvas = null;
                }
                continue;
            }

            const layerContent = renderLayerContent(layer, layerVisible, inheritedOpacity);

            if (!layerContent) {
                if (!layer?.clipping) {
                    lastBaseCanvas = null;
                }
                continue;
            }

            const layerOpacity = normalizeOpacity(layer?.opacity ?? 1);
            const fillOpacity = normalizeOpacity(layer?.fillOpacity ?? 1);
            const effectiveOpacity = Math.max(0, Math.min(1, layerOpacity * fillOpacity));

            if (layer?.clipping) {
                if (!lastBaseCanvas) {
                    continue;
                }

                compositeLayerCanvas(
                    targetContext,
                    applyMaskCanvas(layerContent, lastBaseCanvas),
                    effectiveOpacity,
                    layer?.blendMode,
                );
                continue;
            }

            compositeLayerCanvas(targetContext, layerContent, effectiveOpacity, layer?.blendMode);
            lastBaseCanvas = buildOpacityCanvas(layerContent, effectiveOpacity);
        }
    };

    drawLayerSequence(layersToDraw || psd?.children || [], context, true, 1);

    return canvas;
}

function shouldApplyLayerEffects(layer) {
    const customEffectPassEnabled = String(process.env.OFFOREST_ENABLE_CUSTOM_EFFECTS || '').toLowerCase() === 'true';

    return Boolean((customEffectPassEnabled || layer?.[OFFOREST_REPLACED_DESIGN_LAYER] === true)
        && layer?.effects
        && hasEnabledLayerEffects(layer));
}

function applyLayerEffects(layer, sourceCanvas) {
    const effects = layer.effects || {};
    const dropShadow = getFirstEnabledEffect(effects.dropShadow);
    const innerShadow = getFirstEnabledEffect(effects.innerShadow);
    const outerGlow = getFirstEnabledEffect(effects.outerGlow);
    const innerGlow = getFirstEnabledEffect(effects.innerGlow);
    const bevel = getFirstEnabledEffect(effects.bevel);
    const solidFill = getFirstEnabledEffect(effects.solidFill);
    const stroke = getFirstEnabledEffect(effects.stroke);

    const width = toPositiveInt(sourceCanvas?.width, 1);
    const height = toPositiveInt(sourceCanvas?.height, 1);
    const output = createCanvas(width, height);
    const ctx = output.getContext('2d');

    if (dropShadow) {
        drawEffectCanvas(ctx, renderGlowOrShadow(sourceCanvas, dropShadow, { inside: false }));
    }

    if (outerGlow) {
        ctx.save();
        ctx.globalCompositeOperation = blendModeToComposite(outerGlow.blendMode);
        drawEffectCanvas(ctx, renderGlowOrShadow(sourceCanvas, outerGlow, { inside: false, glow: true }));
        ctx.restore();
    }

    if (stroke) {
        drawStroke(ctx, sourceCanvas, stroke, 0);
    }

    ctx.drawImage(sourceCanvas, 0, 0, width, height);

    if (solidFill) {
        ctx.save();
        ctx.globalCompositeOperation = blendModeToComposite(solidFill.blendMode);
        ctx.drawImage(applySolidFill(sourceCanvas, solidFill), 0, 0, width, height);
        ctx.restore();
    }

    if (innerShadow) {
        ctx.save();
        ctx.globalCompositeOperation = blendModeToComposite(innerShadow.blendMode);
        drawEffectCanvas(ctx, renderGlowOrShadow(sourceCanvas, innerShadow, { inside: true }));
        ctx.restore();
    }

    if (innerGlow) {
        ctx.save();
        ctx.globalCompositeOperation = blendModeToComposite(innerGlow.blendMode);
        drawEffectCanvas(ctx, renderGlowOrShadow(sourceCanvas, innerGlow, { inside: true, glow: true }));
        ctx.restore();
    }

    if (bevel) {
        drawEffectCanvas(ctx, renderBevelApproximation(sourceCanvas, bevel));
    }

    return output;
}

function hasEnabledLayerEffects(layer) {
    return Object.values(layer.effects || {}).some((entry) => Boolean(getFirstEnabledEffect(entry)));
}

function getFirstEnabledEffect(entry) {
    if (Array.isArray(entry)) {
        return entry.find((effect) => effect?.enabled !== false && effect?.present !== false) || null;
    }

    if (entry && typeof entry === 'object' && entry.enabled !== false && entry.present !== false) {
        return entry;
    }

    return null;
}

function effectPx(value) {
    if (typeof value === 'number') {
        return value;
    }

    return Number(value?.value || 0);
}

function effectOpacity(value) {
    const parsed = Number(value);

    if (!Number.isFinite(parsed)) {
        return 1;
    }

    if (parsed <= 1) {
        return Math.max(0, Math.min(1, parsed));
    }

    return Math.max(0, Math.min(1, parsed / 255));
}

function colorToRgba(color, opacity = 1) {
    const r = colorChannel(color?.r);
    const g = colorChannel(color?.g);
    const b = colorChannel(color?.b);

    return `rgba(${r}, ${g}, ${b}, ${Math.max(0, Math.min(1, opacity))})`;
}

function colorChannel(value) {
    if (typeof value !== 'number') {
        return 0;
    }

    return Math.max(0, Math.min(255, Math.round(value <= 1 ? value * 255 : value)));
}

function drawEffectCanvas(ctx, effectCanvas) {
    ctx.drawImage(effectCanvas, 0, 0);
}

function renderGlowOrShadow(sourceCanvas, effect, options = {}) {
    const opacity = effectOpacity(effect.opacity);
    const blur = Math.max(0, effectPx(effect.size));
    const distance = options.glow ? 0 : effectPx(effect.distance);
    const angle = degreesToRadians(effect.angle ?? 90);
    const offsetX = Math.cos(angle) * distance;
    const offsetY = Math.sin(angle) * distance;
    const effectCanvas = createCanvas(sourceCanvas.width, sourceCanvas.height);
    const effectContext = effectCanvas.getContext('2d');

    effectContext.save();
    effectContext.globalCompositeOperation = blendModeToComposite(effect.blendMode);
    effectContext.shadowColor = colorToRgba(effect.color, opacity);
    effectContext.shadowBlur = blur;
    effectContext.shadowOffsetX = offsetX;
    effectContext.shadowOffsetY = offsetY;
    effectContext.drawImage(sourceCanvas, 0, 0);
    effectContext.restore();

    effectContext.save();
    effectContext.globalCompositeOperation = options.inside ? 'destination-in' : 'destination-out';
    effectContext.drawImage(sourceCanvas, 0, 0);
    effectContext.restore();

    return effectCanvas;
}

function degreesToRadians(degrees) {
    return (Number(degrees) || 0) * Math.PI / 180;
}

function drawStroke(ctx, sourceCanvas, effect, pad) {
    const size = Math.max(1, Math.round(effectPx(effect.size)));
    const opacity = effectOpacity(effect.opacity);
    const tinted = tintAlpha(sourceCanvas, effect.color, opacity);

    ctx.save();

    for (let y = -size; y <= size; y++) {
        for (let x = -size; x <= size; x++) {
            if (x === 0 && y === 0) {
                continue;
            }

            if ((x * x) + (y * y) > size * size) {
                continue;
            }

            ctx.drawImage(tinted, pad + x, pad + y);
        }
    }

    ctx.restore();
}

function applySolidFill(sourceCanvas, effect) {
    const output = createCanvas(sourceCanvas.width, sourceCanvas.height);
    const ctx = output.getContext('2d');

    ctx.fillStyle = colorToRgba(effect.color, effectOpacity(effect.opacity));
    ctx.fillRect(0, 0, output.width, output.height);
    ctx.globalCompositeOperation = 'destination-in';
    ctx.drawImage(sourceCanvas, 0, 0);
    ctx.globalCompositeOperation = blendModeToComposite(effect.blendMode);

    return output;
}

function renderBevelApproximation(sourceCanvas, effect) {
    const output = createCanvas(sourceCanvas.width, sourceCanvas.height);
    const ctx = output.getContext('2d');
    const highlight = tintAlpha(sourceCanvas, effect.highlightColor, effectOpacity(effect.highlightOpacity) * 0.35);
    const shadow = tintAlpha(sourceCanvas, effect.shadowColor, effectOpacity(effect.shadowOpacity) * 0.25);
    const angle = degreesToRadians(effect.angle ?? 90);
    const size = Math.max(1, effectPx(effect.size) || 2);
    const dx = Math.cos(angle) * size;
    const dy = Math.sin(angle) * size;

    ctx.globalCompositeOperation = blendModeToComposite(effect.highlightBlendMode);
    ctx.drawImage(highlight, -dx, -dy);
    ctx.globalCompositeOperation = blendModeToComposite(effect.shadowBlendMode);
    ctx.drawImage(shadow, dx, dy);
    ctx.globalCompositeOperation = 'destination-in';
    ctx.drawImage(sourceCanvas, 0, 0);

    return output;
}

function tintAlpha(sourceCanvas, color, opacity = 1) {
    const output = createCanvas(sourceCanvas.width, sourceCanvas.height);
    const ctx = output.getContext('2d');

    ctx.drawImage(sourceCanvas, 0, 0);
    ctx.globalCompositeOperation = 'source-in';
    ctx.fillStyle = colorToRgba(color, opacity);
    ctx.fillRect(0, 0, output.width, output.height);
    ctx.globalCompositeOperation = 'source-over';

    return output;
}

function blendModeToComposite(blendMode) {
    return {
        normal: 'source-over',
        'pass through': 'source-over',
        passthrough: 'source-over',
        multiply: 'multiply',
        screen: 'screen',
        overlay: 'overlay',
        darken: 'darken',
        lighten: 'lighten',
        'color dodge': 'color-dodge',
        colordodge: 'color-dodge',
        'color burn': 'color-burn',
        colorburn: 'color-burn',
        'hard light': 'hard-light',
        hardlight: 'hard-light',
        'soft light': 'soft-light',
        softlight: 'soft-light',
        difference: 'difference',
        exclusion: 'exclusion',
    }[String(blendMode || '').toLowerCase()] || 'source-over';
}

async function main() {
    const payload = JSON.parse(await readStdin());
    const psdPath = requiredPath(payload.psd_path, 'psd_path');
    const masterImage = requiredPath(payload.master_image, 'master_image');
    const outputDirectory = payload.output_directory;
    const folderPrefix = payload.folder_prefix || 'MOCKUP';

    if (!outputDirectory) {
        throw new Error('Missing output_directory.');
    }

    fs.mkdirSync(outputDirectory, { recursive: true });

    for (const file of fs.readdirSync(outputDirectory)) {
        if (file.toLowerCase().endsWith('.png')) {
            fs.unlinkSync(path.join(outputDirectory, file));
        }
    }

    const psd = readPsd(fs.readFileSync(psdPath), {
        useImageData: true,
        useRawThumbnail: false,
        skipLayerImageData: false,
        skipCompositeImageData: false,
    });

    await replaceDesignLayers(psd, masterImage);

    const groups = findMockupGroups(psd.children || [], folderPrefix);

    if (groups.length === 0) {
        throw new Error(`Khong tim thay folder ${folderPrefix} * trong PSD.`);
    }

    const outputs = [];

    for (const group of groups) {
        const outputName = `${normalizeName(group.name).replace(/[\\\\/:*?"<>|]+/g, '-')}.png`;
        const outputPath = path.join(outputDirectory, outputName);
        const restoreVisibility = setOnlyMockupGroupVisible(groups, group);

        try {
            const canvas = renderPsdToCanvas(psd, psd.children || []);

            fs.writeFileSync(outputPath, canvas.toBuffer('image/png'));
            outputs.push(outputPath);
        } catch (error) {
            throw new Error(`Render group "${normalizeName(group.name) || 'UNKNOWN'}" failed: ${error?.message || 'unknown error'}`);
        } finally {
            restoreVisibility();
        }
    }

    process.stdout.write(JSON.stringify({ outputs }));
}

function requiredPath(value, name) {
    if (!value || !fs.existsSync(value)) {
        throw new Error(`Invalid ${name}: ${value || '(empty)'}`);
    }

    return value;
}

main().catch((error) => {
    process.stderr.write(`${error.stack || error.message}\n`);
    process.exit(1);
});
