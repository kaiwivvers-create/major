<style>
    .crop-wrap {
        margin-top: 8px;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 10px;
        background: rgba(15, 23, 42, 0.55);
    }

    .crop-canvas {
        width: 100%;
        max-width: 420px;
        aspect-ratio: 4 / 3;
        border: 1px dashed rgba(56, 189, 248, 0.45);
        border-radius: 10px;
        background: rgba(15, 23, 42, 0.7);
        display: block;
        cursor: grab;
        touch-action: none;
        user-select: none;
    }

    .crop-canvas.dragging {
        cursor: grabbing;
    }

    .crop-tools {
        margin-top: 10px;
        display: grid;
        gap: 10px;
        max-width: 420px;
    }

    .crop-tool-row {
        display: grid;
        grid-template-columns: 58px 1fr auto;
        align-items: center;
        gap: 8px;
    }

    .crop-tool-row label {
        margin: 0;
        font-size: 0.85rem;
        color: var(--muted);
        font-weight: 600;
    }

    .crop-tool-row input[type="range"] {
        width: 100%;
    }

    .crop-tool-value {
        font-size: 0.82rem;
        color: var(--muted);
        min-width: 52px;
        text-align: right;
    }

    .crop-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .crop-btn {
        border: 1px solid var(--border);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.75);
        color: var(--text);
        padding: 6px 10px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: border-color 0.2s ease, color 0.2s ease;
    }

    .crop-btn:hover {
        border-color: var(--accent);
        color: var(--accent);
    }

    .crop-btn.is-active {
        border-color: var(--accent);
        background: rgba(14, 165, 233, 0.16);
        color: #f8fafc;
    }

    .error-text {
        color: #fecaca;
        font-size: 0.83rem;
        margin-top: 8px;
    }

    .profile-modal-actions.stack {
        justify-content: flex-start;
        margin-top: 10px;
    }

    .profile-modal-btn.logout {
        border-color: rgba(248, 113, 113, 0.45);
        background: rgba(127, 29, 29, 0.35);
        color: #fecaca;
    }

    .profile-modal-btn.logout:hover {
        border-color: rgba(248, 113, 113, 0.7);
        color: #fee2e2;
    }

    .muted {
        color: var(--muted);
    }
</style>

<div class="profile-modal-backdrop {{ $openProfileModal ? 'open' : '' }}" id="profile-modal-backdrop" aria-hidden="{{ $openProfileModal ? 'false' : 'true' }}">
    <div class="profile-modal-panel" role="dialog" aria-modal="true" aria-labelledby="profile-modal-title">
        <div class="profile-modal-head">
            <h3 id="profile-modal-title">Edit Profile</h3>
            <button type="button" class="profile-modal-close" id="close-profile-modal">Close</button>
        </div>

        @if ($errors->has('name') || $errors->has('nis') || $errors->has('password') || $errors->has('avatar_crop_data'))
            <div class="profile-modal-alert error">
                @foreach ($errors->only('name', 'nis', 'password', 'avatar_crop_data') as $messages)
                    @foreach ($messages as $message)
                        <div>{{ $message }}</div>
                    @endforeach
                @endforeach
            </div>
        @endif

        <form id="profile-form" method="POST" action="{{ $profileUpdateRoute ?? route('dashboard.student.profile') }}">
            @csrf

            <div class="profile-modal-field">
                <label for="profile_name">Name</label>
                <input id="profile_name" name="name" type="text" value="{{ old('name', $user->name) }}" required>
            </div>

            <div class="profile-modal-field">
                <label for="profile_nis">NIS</label>
                <input id="profile_nis" type="text" value="{{ old('nis', $user->nis) }}" readonly disabled>
                <input type="hidden" name="nis" value="{{ old('nis', $user->nis) }}">
            </div>

            <div class="profile-modal-field">
                <label for="profile_avatar_file">Profile Picture</label>
                <input id="profile_avatar_file" type="file" accept="image/*">
                <input id="avatar_crop_data" name="avatar_crop_data" type="hidden">
            </div>

            <div class="crop-wrap">
                <canvas id="avatar-crop-canvas" class="crop-canvas" width="420" height="315"></canvas>
                <div class="crop-tools">
                    <div class="crop-tool-row">
                        <label for="avatar_zoom">Zoom</label>
                        <input id="avatar_zoom" type="range" min="100" max="1600" step="10" value="100">
                        <span class="crop-tool-value" id="avatar_zoom_value">100%</span>
                    </div>
                    <div class="crop-tool-row">
                        <label for="avatar_rotate">Rotate</label>
                        <input id="avatar_rotate" type="range" min="-180" max="180" step="1" value="0">
                        <span class="crop-tool-value" id="avatar_rotate_value">0deg</span>
                    </div>
                    <div class="crop-actions">
                        <button type="button" class="crop-btn" id="avatar_rotate_left">Rotate -90</button>
                        <button type="button" class="crop-btn" id="avatar_rotate_right">Rotate +90</button>
                        <button type="button" class="crop-btn" id="avatar_flip_x">Flip H</button>
                        <button type="button" class="crop-btn" id="avatar_flip_y">Flip V</button>
                        <button type="button" class="crop-btn" id="avatar_reset">Reset</button>
                    </div>
                </div>
                <p class="muted" style="margin-top:8px;">Drag image to position. Scroll mouse wheel to zoom quickly.</p>
                @error('avatar_crop_data')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="profile-modal-field">
                <label for="profile_password">New Password (optional)</label>
                <input id="profile_password" name="password" type="password" autocomplete="new-password">
            </div>

            <div class="profile-modal-field">
                <label for="profile_password_confirmation">Confirm New Password</label>
                <input id="profile_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
            </div>

            <div class="profile-modal-actions">
                <button type="submit" class="profile-modal-btn primary">Save Profile</button>
            </div>
        </form>

        <div class="profile-modal-actions stack">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="profile-modal-btn logout">Log out</button>
            </form>
        </div>
    </div>
</div>

<script>
    (() => {
        const openBtn = document.getElementById('open-profile-modal');
        const closeBtn = document.getElementById('close-profile-modal');
        const backdrop = document.getElementById('profile-modal-backdrop');
        const fileInput = document.getElementById('profile_avatar_file');
        const cropInput = document.getElementById('avatar_crop_data');
        const canvas = document.getElementById('avatar-crop-canvas');
        const zoomInput = document.getElementById('avatar_zoom');
        const zoomValue = document.getElementById('avatar_zoom_value');
        const rotateInput = document.getElementById('avatar_rotate');
        const rotateValue = document.getElementById('avatar_rotate_value');
        const rotateLeftBtn = document.getElementById('avatar_rotate_left');
        const rotateRightBtn = document.getElementById('avatar_rotate_right');
        const flipXBtn = document.getElementById('avatar_flip_x');
        const flipYBtn = document.getElementById('avatar_flip_y');
        const resetBtn = document.getElementById('avatar_reset');
        const form = document.getElementById('profile-form');
        if (
            !openBtn || !closeBtn || !backdrop || !fileInput || !cropInput || !canvas || !form ||
            !zoomInput || !zoomValue || !rotateInput || !rotateValue || !rotateLeftBtn || !rotateRightBtn ||
            !flipXBtn || !flipYBtn || !resetBtn
        ) return;

        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        const state = {
            image: null,
            baseScale: 1,
            zoom: 1,
            rotation: 0,
            flipX: 1,
            flipY: 1,
            panX: 0,
            panY: 0,
            dragging: false,
            pointerId: null,
            startX: 0,
            startY: 0,
            startPanX: 0,
            startPanY: 0,
        };
        const frameSize = Math.round(Math.min(canvas.width, canvas.height) * 0.74);
        const frameX = (canvas.width - frameSize) / 2;
        const frameY = (canvas.height - frameSize) / 2;
        const frameCenterX = canvas.width / 2;
        const frameCenterY = canvas.height / 2;
        const zoomMin = 1;
        const zoomMax = 16;
        const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

        const updateFlipButtons = () => {
            flipXBtn.classList.toggle('is-active', state.flipX === -1);
            flipYBtn.classList.toggle('is-active', state.flipY === -1);
        };

        const updateControlLabels = () => {
            zoomValue.textContent = `${Math.round(state.zoom * 100)}%`;
            rotateValue.textContent = `${Math.round(state.rotation)}deg`;
            zoomInput.value = String(Math.round(state.zoom * 100));
            rotateInput.value = String(Math.round(state.rotation));
            updateFlipButtons();
        };

        const openModal = () => {
            backdrop.classList.add('open');
            backdrop.setAttribute('aria-hidden', 'false');
        };

        const closeModal = () => {
            backdrop.classList.remove('open');
            backdrop.setAttribute('aria-hidden', 'true');
        };

        const drawPlaceholder = () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = 'rgba(15, 23, 42, 0.75)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = 'rgba(148, 163, 184, 0.9)';
            ctx.font = '14px Instrument Sans';
            ctx.textAlign = 'center';
            ctx.fillText('Choose an image to crop', canvas.width / 2, canvas.height / 2);
        };

        const drawTransformedImage = (drawCtx) => {
            if (!state.image) return;

            const scale = state.baseScale * state.zoom;
            drawCtx.save();
            drawCtx.translate(frameCenterX + state.panX, frameCenterY + state.panY);
            drawCtx.rotate((state.rotation * Math.PI) / 180);
            drawCtx.scale(scale * state.flipX, scale * state.flipY);
            drawCtx.drawImage(state.image, -state.image.width / 2, -state.image.height / 2);
            drawCtx.restore();
        };

        const draw = () => {
            if (!state.image) {
                drawPlaceholder();
                return;
            }

            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = 'rgba(15, 23, 42, 0.92)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            drawTransformedImage(ctx);

            ctx.fillStyle = 'rgba(2, 6, 23, 0.45)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.save();
            ctx.beginPath();
            ctx.rect(frameX, frameY, frameSize, frameSize);
            ctx.clip();
            drawTransformedImage(ctx);
            ctx.restore();

            ctx.strokeStyle = 'rgba(56, 189, 248, 0.95)';
            ctx.lineWidth = 2;
            ctx.strokeRect(frameX, frameY, frameSize, frameSize);
        };

        const setImage = (img) => {
            state.image = img;
            state.baseScale = Math.max(frameSize / img.width, frameSize / img.height);
            state.zoom = zoomMin;
            state.rotation = 0;
            state.flipX = 1;
            state.flipY = 1;
            state.panX = 0;
            state.panY = 0;
            state.dragging = false;
            state.pointerId = null;
            canvas.classList.remove('dragging');
            updateControlLabels();
            draw();
        };

        const getPointer = (event) => {
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            return {
                x: (event.clientX - rect.left) * scaleX,
                y: (event.clientY - rect.top) * scaleY,
            };
        };

        fileInput.addEventListener('change', (event) => {
            const file = event.target.files && event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = () => {
                const img = new Image();
                img.onload = () => setImage(img);
                img.src = String(reader.result || '');
            };
            reader.readAsDataURL(file);
        });

        zoomInput.addEventListener('input', () => {
            state.zoom = clamp(Number(zoomInput.value) / 100, zoomMin, zoomMax);
            updateControlLabels();
            draw();
        });

        rotateInput.addEventListener('input', () => {
            state.rotation = clamp(Number(rotateInput.value), -180, 180);
            updateControlLabels();
            draw();
        });

        rotateLeftBtn.addEventListener('click', () => {
            state.rotation = clamp(state.rotation - 90, -180, 180);
            updateControlLabels();
            draw();
        });

        rotateRightBtn.addEventListener('click', () => {
            state.rotation = clamp(state.rotation + 90, -180, 180);
            updateControlLabels();
            draw();
        });

        flipXBtn.addEventListener('click', () => {
            state.flipX *= -1;
            updateControlLabels();
            draw();
        });

        flipYBtn.addEventListener('click', () => {
            state.flipY *= -1;
            updateControlLabels();
            draw();
        });

        resetBtn.addEventListener('click', () => {
            if (!state.image) return;
            state.zoom = zoomMin;
            state.rotation = 0;
            state.flipX = 1;
            state.flipY = 1;
            state.panX = 0;
            state.panY = 0;
            updateControlLabels();
            draw();
        });

        canvas.addEventListener('pointerdown', (event) => {
            if (!state.image || state.dragging) return;
            state.dragging = true;
            state.pointerId = event.pointerId;
            const point = getPointer(event);
            state.startX = point.x;
            state.startY = point.y;
            state.startPanX = state.panX;
            state.startPanY = state.panY;
            canvas.classList.add('dragging');
            if (canvas.setPointerCapture) canvas.setPointerCapture(event.pointerId);
        });

        canvas.addEventListener('pointermove', (event) => {
            if (!state.dragging || event.pointerId !== state.pointerId) return;
            const point = getPointer(event);
            state.panX = state.startPanX + (point.x - state.startX);
            state.panY = state.startPanY + (point.y - state.startY);
            draw();
        });

        const endDrag = (event) => {
            if (!state.dragging) return;
            if (typeof event.pointerId === 'number' && state.pointerId !== event.pointerId) return;
            state.dragging = false;
            state.pointerId = null;
            canvas.classList.remove('dragging');
            if (typeof event.pointerId === 'number' && canvas.releasePointerCapture) {
                try {
                    canvas.releasePointerCapture(event.pointerId);
                } catch (error) {
                    // Ignore capture-release mismatch.
                }
            }
        };

        canvas.addEventListener('pointerup', endDrag);
        canvas.addEventListener('pointercancel', endDrag);
        canvas.addEventListener('lostpointercapture', endDrag);

        canvas.addEventListener('wheel', (event) => {
            if (!state.image) return;
            event.preventDefault();
            const delta = event.deltaY > 0 ? -0.09 : 0.09;
            state.zoom = clamp(state.zoom + delta, zoomMin, zoomMax);
            updateControlLabels();
            draw();
        }, { passive: false });

        form.addEventListener('submit', (event) => {
            cropInput.value = '';
            if (!state.image || !(fileInput.files && fileInput.files.length)) {
                return;
            }

            const out = document.createElement('canvas');
            out.width = 640;
            out.height = 640;
            const outCtx = out.getContext('2d');
            if (!outCtx) {
                event.preventDefault();
                return;
            }

            outCtx.fillStyle = 'rgba(15, 23, 42, 1)';
            outCtx.fillRect(0, 0, out.width, out.height);

            const outputScale = out.width / frameSize;
            outCtx.save();
            outCtx.translate(out.width / 2, out.height / 2);
            outCtx.scale(outputScale, outputScale);
            outCtx.translate(-frameCenterX, -frameCenterY);
            drawTransformedImage(outCtx);
            outCtx.restore();

            cropInput.value = out.toDataURL('image/png');
        });

        openBtn.addEventListener('click', openModal);
        closeBtn.addEventListener('click', closeModal);
        backdrop.addEventListener('click', (event) => {
            if (event.target === backdrop) closeModal();
        });
        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && backdrop.classList.contains('open')) closeModal();
        });

        updateControlLabels();
        drawPlaceholder();
    })();
</script>
