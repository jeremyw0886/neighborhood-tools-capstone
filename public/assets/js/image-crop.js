'use strict';

class ImageCrop {
  static #instance = null;

  /** @type {HTMLDialogElement} */
  #dialog;
  /** @type {HTMLImageElement} */
  #preview;
  /** @type {HTMLElement} */
  #viewport;
  /** @type {HTMLButtonElement} */
  #confirmBtn;
  /** @type {HTMLButtonElement|null} */
  #cancelBtn;
  /** @type {HTMLElement|null} */
  #labelEl;
  /** @type {HTMLElement|null} */
  #confirmIcon;
  /** @type {HTMLElement|null} */
  #descEl;

  #mode = null;
  #file = null;
  #objectUrl = null;
  #focalX = 50;
  #focalY = 50;
  #aspectRatio = 1.5;
  #repositionImageId = null;
  #layout = null;
  #frameLeft = 0;
  #frameTop = 0;
  #savedScrollY = 0;
  #onConfirmCallback = null;

  #confirmed = false;
  #dragging = false;
  #startX = 0;
  #startY = 0;
  #startFrameLeft = 0;
  #startFrameTop = 0;

  #abortController = new AbortController();

  /**
   * Cache references to dialog sub-elements and bind pointer/keyboard/dialog listeners.
   *
   * @param {HTMLDialogElement} dialog - The crop dialog element
   */
  constructor(dialog) {
    this.#dialog = dialog;
    this.#preview = document.getElementById('crop-preview');
    this.#viewport = document.getElementById('crop-viewport');
    this.#confirmBtn = dialog.querySelector('[data-crop-confirm]');
    this.#cancelBtn = dialog.querySelector('[data-crop-cancel]');
    this.#labelEl = dialog.querySelector('[data-crop-label]');
    this.#confirmIcon = this.#confirmBtn?.querySelector('i');
    this.#descEl = dialog.querySelector('header p');

    this.#bind();
  }

  /**
   * Initialize the singleton ImageCrop dialog and publish the NT.crop API.
   *
   * @returns {ImageCrop|null}
   */
  static init() {
    if (ImageCrop.#instance) return ImageCrop.#instance;
    const dialog = document.getElementById('crop-dialog');
    const preview = document.getElementById('crop-preview');
    const viewport = document.getElementById('crop-viewport');
    const frame = document.getElementById('crop-frame');
    if (!dialog || !preview || !viewport || !frame) return null;

    const instance = new ImageCrop(dialog);
    ImageCrop.#instance = instance;

    /**
     * Public crop-dialog API exposed on `NT.crop`.
     *
     * @typedef {object} CropOpts
     * @property {number} [focalX]      Initial X focal percent (0-100).
     * @property {number} [focalY]      Initial Y focal percent (0-100).
     * @property {number} [aspectRatio] Frame W/H ratio (default 1.5; pass 1 for square).
     * @property {string} [icon]        Font Awesome class for the confirm-button icon.
     * @property {string} [label]       Confirm-button text (e.g. "Upload", "Set Photo").
     *
     * @typedef {object} CropConfirmData
     * @property {?File}        file       Original file (upload mode only).
     * @property {number}       focalX     Chosen X focal percent (0-100).
     * @property {number}       focalY     Chosen Y focal percent (0-100).
     * @property {?string}      imageId    Image ID being repositioned (reposition mode only).
     *
     * @typedef {(mode: 'upload'|'reposition', data: CropConfirmData) => void} CropConfirmCallback
     */
    NT.crop = {
      /**
       * Open the dialog in upload mode for a freshly-selected file.
       *
       * @param {File} file
       * @param {CropOpts} [opts]
       */
      openUpload: (file, opts) => instance.#openUpload(file, opts),

      /**
       * Open the dialog in reposition mode for an existing image.
       *
       * @param {string} src      Image source URL.
       * @param {number} fx       Current X focal percent (0-100).
       * @param {number} fy       Current Y focal percent (0-100).
       * @param {string} id       Image identifier passed back on confirm.
       * @param {CropOpts} [opts]
       */
      openReposition: (src, fx, fy, id, opts) => instance.#openReposition(src, fx, fy, id, opts),

      /** Close the dialog without firing the confirm callback. */
      close: () => instance.#closeCropDialog(),

      /**
       * Register the single confirm callback. Each call replaces any
       * previous registration — callbacks do not stack.
       *
       * @param {CropConfirmCallback} cb
       */
      onConfirm: (cb) => { instance.#onConfirmCallback = cb; },

      /**
       * Toggle the confirm button between idle / disabled / loading
       * states. Callers should set `(true, true)` while their async
       * handler runs and call `close()` on success.
       *
       * @param {boolean} disabled
       * @param {boolean} loading
       */
      setConfirmState: (disabled, loading) => instance.#setConfirmState(disabled, loading),
    };

    return instance;
  }

  /**
   * Release any object URL, drop dynamic rules, detach listeners, remove the public NT.crop API, and reset the singleton.
   */
  destroy() {
    this.#cleanupCropState();
    this.#abortController.abort();
    delete NT.crop;
    ImageCrop.#instance = null;
  }

  /**
   * Tear down the singleton and re-init for a fresh DOM.
   */
  static reinit() {
    ImageCrop.#instance?.destroy();
    ImageCrop.init();
  }

  #bind() {
    const { signal } = this.#abortController;
    this.#viewport.addEventListener('pointerdown', this.#handlePointerDown, { signal });
    this.#viewport.addEventListener('pointermove', this.#handlePointerMove, { signal });
    this.#viewport.addEventListener('pointerup', this.#handlePointerUp, { signal });
    this.#viewport.addEventListener('pointercancel', this.#handlePointerUp, { signal });
    this.#viewport.addEventListener('keydown', this.#handleKeydown, { signal });
    window.addEventListener('resize', this.#handleResize, { signal });
    this.#confirmBtn.addEventListener('click', this.#handleConfirm, { signal });
    this.#cancelBtn?.addEventListener('click', () => this.#closeCropDialog(), { signal });
    this.#dialog.addEventListener('close', this.#handleDialogClose, { signal });
  }

  static #clamp(val, min, max) {
    return Math.max(min, Math.min(max, val));
  }

  #cleanupCropState() {
    if (this.#objectUrl) {
      URL.revokeObjectURL(this.#objectUrl);
      this.#objectUrl = null;
    }
    const wasConfirmed = this.#confirmed;
    this.#file = null;
    this.#mode = null;
    this.#layout = null;
    this.#repositionImageId = null;
    this.#aspectRatio = 1.5;
    this.#confirmed = false;
    NT.style.removeRule('crop-preview');
    NT.style.removeRule('crop-frame-size');
    NT.style.removeRule('crop-frame-pos');
    if (!wasConfirmed) {
      const fi = document.getElementById('add-photo')
        ?? document.getElementById('tool-photos')
        ?? document.getElementById('avatar');
      if (fi) fi.value = '';
    }
  }

  #closeCropDialog() {
    this.#dialog.close();
  }

  #updateHintText() {
    if (!this.#descEl) return;
    const shape = this.#aspectRatio === 1 ? 'square' : '3:2';
    this.#descEl.textContent = `Drag to choose which part is visible in the ${shape} frame.`;
  }

  #layoutCrop() {
    const stageW = this.#viewport.clientWidth;
    const stageH = this.#viewport.clientHeight;
    const natW = this.#preview.naturalWidth;
    const natH = this.#preview.naturalHeight;
    if (!natW || !natH) return;

    const scale = Math.min(stageW / natW, stageH / natH);
    const dispW = natW * scale;
    const dispH = natH * scale;

    const imgLeft = (stageW - dispW) / 2;
    const imgTop = (stageH - dispH) / 2;

    NT.style.setRule('crop-preview', '#crop-preview',
      `width:${dispW}px;height:${dispH}px;left:${imgLeft}px;top:${imgTop}px`);

    let frameW, frameH;
    if (dispW / dispH > this.#aspectRatio) {
      frameH = dispH;
      frameW = dispH * this.#aspectRatio;
    } else {
      frameW = dispW;
      frameH = dispW / this.#aspectRatio;
    }

    this.#layout = { imgLeft, imgTop, dispW, dispH, frameW, frameH };

    NT.style.setRule('crop-frame-size', '#crop-frame',
      `width:${frameW}px;height:${frameH}px`);

    this.#positionFrameFromFocal();
  }

  #positionFrameFromFocal() {
    if (!this.#layout) return;
    const { imgLeft, imgTop, dispW, dispH, frameW, frameH } = this.#layout;
    const maxOffsetX = dispW - frameW;
    const maxOffsetY = dispH - frameH;

    this.#frameLeft = imgLeft + (maxOffsetX > 0 ? (this.#focalX / 100) * maxOffsetX : 0);
    this.#frameTop = imgTop + (maxOffsetY > 0 ? (this.#focalY / 100) * maxOffsetY : 0);

    NT.style.setRule('crop-frame-pos', '#crop-frame',
      `left:${this.#frameLeft}px;top:${this.#frameTop}px`);
  }

  #setFramePosition(left, top) {
    this.#frameLeft = left;
    this.#frameTop = top;
    NT.style.setRule('crop-frame-pos', '#crop-frame',
      `left:${left}px;top:${top}px`);
  }

  #updateFocalFromFrame() {
    if (!this.#layout) return;
    const { imgLeft, imgTop, dispW, dispH, frameW, frameH } = this.#layout;
    const maxOffsetX = dispW - frameW;
    const maxOffsetY = dispH - frameH;

    const relLeft = this.#frameLeft - imgLeft;
    const relTop = this.#frameTop - imgTop;

    this.#focalX = maxOffsetX > 0 ? ImageCrop.#clamp(Math.round((relLeft / maxOffsetX) * 100), 0, 100) : 50;
    this.#focalY = maxOffsetY > 0 ? ImageCrop.#clamp(Math.round((relTop / maxOffsetY) * 100), 0, 100) : 50;
  }

  #openUpload(file, opts = {}) {
    this.#mode = 'upload';
    this.#file = file;
    this.#focalX = opts.focalX ?? 50;
    this.#focalY = opts.focalY ?? 50;
    this.#aspectRatio = opts.aspectRatio ?? 1.5;
    this.#repositionImageId = null;

    this.#objectUrl = URL.createObjectURL(file);
    this.#preview.onload = () => this.#layoutCrop();
    this.#preview.src = this.#objectUrl;

    if (this.#confirmIcon) this.#confirmIcon.className = opts.icon ?? 'fa-solid fa-cloud-arrow-up';
    if (this.#labelEl) this.#labelEl.textContent = opts.label ?? 'Upload';
    if (this.#confirmBtn) this.#confirmBtn.disabled = false;

    this.#updateHintText();
    this.#dialog.dataset.mode = 'upload';
    this.#savedScrollY = window.scrollY;
    this.#dialog.showModal();
    this.#viewport.focus();
  }

  #openReposition(imgSrc, focalX, focalY, imageId, opts = {}) {
    this.#mode = 'reposition';
    this.#file = null;
    this.#objectUrl = null;
    this.#focalX = focalX;
    this.#focalY = focalY;
    this.#aspectRatio = opts.aspectRatio ?? 1.5;
    this.#repositionImageId = imageId;

    this.#preview.onload = () => this.#layoutCrop();
    this.#preview.src = imgSrc;

    if (this.#preview.complete && this.#preview.naturalWidth) {
      this.#layoutCrop();
    }

    if (this.#confirmIcon) this.#confirmIcon.className = 'fa-solid fa-check';
    if (this.#labelEl) this.#labelEl.textContent = 'Save';
    if (this.#confirmBtn) this.#confirmBtn.disabled = false;

    this.#updateHintText();
    this.#dialog.dataset.mode = 'reposition';
    this.#savedScrollY = window.scrollY;
    this.#dialog.showModal();
    this.#viewport.focus();
  }

  #setConfirmState(disabled, loading) {
    if (this.#confirmBtn) {
      this.#confirmBtn.disabled = disabled;
      if (loading) this.#confirmBtn.dataset.loading = '';
      else delete this.#confirmBtn.dataset.loading;
    }
  }

  #handlePointerDown = (e) => {
    if (!this.#layout) return;
    e.preventDefault();
    this.#viewport.setPointerCapture(e.pointerId);
    this.#dragging = true;
    this.#startX = e.clientX;
    this.#startY = e.clientY;
    this.#startFrameLeft = this.#frameLeft;
    this.#startFrameTop = this.#frameTop;
  };

  #handlePointerMove = (e) => {
    if (!this.#dragging || !this.#layout) return;
    e.preventDefault();

    const { imgLeft, imgTop, dispW, dispH, frameW, frameH } = this.#layout;
    const dx = e.clientX - this.#startX;
    const dy = e.clientY - this.#startY;

    const newLeft = ImageCrop.#clamp(this.#startFrameLeft + dx, imgLeft, imgLeft + dispW - frameW);
    const newTop = ImageCrop.#clamp(this.#startFrameTop + dy, imgTop, imgTop + dispH - frameH);

    this.#setFramePosition(newLeft, newTop);
    this.#updateFocalFromFrame();
  };

  #handlePointerUp = () => { this.#dragging = false; };

  #handleKeydown = (e) => {
    const step = 1;
    let handled = false;

    if (e.key === 'ArrowLeft')  { this.#focalX = ImageCrop.#clamp(this.#focalX - step, 0, 100); handled = true; }
    if (e.key === 'ArrowRight') { this.#focalX = ImageCrop.#clamp(this.#focalX + step, 0, 100); handled = true; }
    if (e.key === 'ArrowUp')    { this.#focalY = ImageCrop.#clamp(this.#focalY - step, 0, 100); handled = true; }
    if (e.key === 'ArrowDown')  { this.#focalY = ImageCrop.#clamp(this.#focalY + step, 0, 100); handled = true; }

    if (handled) {
      e.preventDefault();
      this.#positionFrameFromFocal();
    }
  };

  #handleResize = () => {
    if (this.#dialog.open) this.#layoutCrop();
  };

  #handleConfirm = () => {
    this.#confirmed = true;
    if (this.#onConfirmCallback) {
      this.#onConfirmCallback(this.#mode, {
        file: this.#file,
        focalX: this.#focalX,
        focalY: this.#focalY,
        imageId: this.#repositionImageId,
      });
    }
  };

  #handleDialogClose = () => {
    this.#cleanupCropState();
    window.scrollTo({ top: this.#savedScrollY, behavior: 'instant' });
    requestAnimationFrame(() => {
      window.scrollTo({ top: this.#savedScrollY, behavior: 'instant' });
    });
  };
}

ImageCrop.init();
