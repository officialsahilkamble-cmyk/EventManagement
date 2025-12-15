/**
 * Image Uploader Component
 * Supports drag-and-drop, multiple images, and cover image selection
 */

class ImageUploader {
    constructor(options) {
        this.container = document.querySelector(options.container);
        this.type = options.type; // 'event' or 'venue'
        this.entityId = options.entityId;
        this.multiple = options.multiple !== false; // default true
        this.maxFiles = options.maxFiles || 10;
        this.maxSize = options.maxSize || 5 * 1024 * 1024; // 5MB
        this.uploadUrl = options.uploadUrl || '../admin/upload-image.php';
        this.onUploadSuccess = options.onUploadSuccess || (() => { });
        this.onUploadError = options.onUploadError || (() => { });

        this.images = [];
        this.init();
    }

    init() {
        this.render();
        this.attachEvents();
    }

    render() {
        this.container.innerHTML = `
            <div class="image-uploader">
                <div class="upload-zone" id="uploadZone">
                    <div class="upload-zone-content">
                        <i class="fas fa-cloud-upload-alt text-6xl text-purple-400 mb-4"></i>
                        <p class="text-lg font-semibold text-gray-700 mb-2">Drag & Drop Images Here</p>
                        <p class="text-sm text-gray-500 mb-4">or click to browse</p>
                        <button type="button" class="btn btn-primary" id="browseBtn">
                            <i class="fas fa-folder-open mr-2"></i>Browse Files
                        </button>
                        <p class="text-xs text-gray-400 mt-3">
                            ${this.multiple ? `Up to ${this.maxFiles} images` : '1 image'} • Max ${this.maxSize / (1024 * 1024)}MB each • JPG, PNG, WebP
                        </p>
                    </div>
                    <input type="file" id="fileInput" class="hidden" accept="image/jpeg,image/png,image/jpg,image/webp" ${this.multiple ? 'multiple' : ''}>
                </div>
                
                <div class="image-preview-grid" id="imagePreviewGrid"></div>
                
                <div class="upload-progress hidden" id="uploadProgress">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                    <p class="text-sm text-gray-600 mt-2" id="progressText">Uploading...</p>
                </div>
            </div>
        `;
    }

    attachEvents() {
        const uploadZone = this.container.querySelector('#uploadZone');
        const fileInput = this.container.querySelector('#fileInput');
        const browseBtn = this.container.querySelector('#browseBtn');

        // Click to browse
        browseBtn.addEventListener('click', () => fileInput.click());
        uploadZone.addEventListener('click', (e) => {
            if (e.target === uploadZone || e.target.closest('.upload-zone-content')) {
                fileInput.click();
            }
        });

        // File input change
        fileInput.addEventListener('change', (e) => {
            this.handleFiles(e.target.files);
        });

        // Drag and drop
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('drag-over');
        });

        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('drag-over');
        });

        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('drag-over');
            this.handleFiles(e.dataTransfer.files);
        });
    }

    handleFiles(files) {
        const fileArray = Array.from(files);

        // Check file count
        if (!this.multiple && fileArray.length > 1) {
            this.showError('Only one image is allowed');
            return;
        }

        if (this.images.length + fileArray.length > this.maxFiles) {
            this.showError(`Maximum ${this.maxFiles} images allowed`);
            return;
        }

        // Validate and upload each file
        fileArray.forEach(file => {
            if (!this.validateFile(file)) return;
            this.uploadFile(file);
        });
    }

    validateFile(file) {
        // Check file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            this.showError(`${file.name}: Invalid file type. Only JPG, PNG, and WebP are allowed.`);
            return false;
        }

        // Check file size
        if (file.size > this.maxSize) {
            this.showError(`${file.name}: File size exceeds ${this.maxSize / (1024 * 1024)}MB limit`);
            return false;
        }

        return true;
    }

    async uploadFile(file) {
        const formData = new FormData();
        formData.append('image', file);
        formData.append('type', this.type);
        formData.append('entity_id', this.entityId);
        formData.append('is_cover', this.images.length === 0 ? '1' : '0'); // First image is cover by default

        // Show progress
        this.showProgress();

        try {
            const response = await fetch(this.uploadUrl, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.addImagePreview(result.data);
                this.onUploadSuccess(result.data);
            } else {
                this.showError(result.message);
                this.onUploadError(result.message);
            }
        } catch (error) {
            this.showError('Upload failed: ' + error.message);
            this.onUploadError(error.message);
        } finally {
            this.hideProgress();
        }
    }

    addImagePreview(imageData) {
        this.images.push(imageData);

        const grid = this.container.querySelector('#imagePreviewGrid');
        const preview = document.createElement('div');
        preview.className = 'image-preview-item';
        preview.dataset.imageId = imageData.id;

        preview.innerHTML = `
            <img src="${imageData.url}" alt="Preview">
            <div class="image-preview-overlay">
                <button type="button" class="btn-icon btn-icon-danger" onclick="imageUploader.removeImage(${imageData.id})">
                    <i class="fas fa-trash"></i>
                </button>
                ${this.multiple ? `
                    <button type="button" class="btn-icon ${imageData.is_cover ? 'btn-icon-primary' : 'btn-icon-secondary'}" 
                            onclick="imageUploader.setCoverImage(${imageData.id})">
                        <i class="fas fa-star"></i>
                    </button>
                ` : ''}
            </div>
            ${imageData.is_cover ? '<div class="cover-badge">Cover</div>' : ''}
        `;

        grid.appendChild(preview);
    }

    async removeImage(imageId) {
        if (!confirm('Remove this image?')) return;

        try {
            const response = await fetch('../admin/delete-image.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    image_id: imageId,
                    type: this.type
                })
            });

            const result = await response.json();

            if (result.success) {
                // Remove from array
                this.images = this.images.filter(img => img.id !== imageId);

                // Remove from DOM
                const preview = this.container.querySelector(`[data-image-id="${imageId}"]`);
                if (preview) preview.remove();

                this.showSuccess('Image removed successfully');
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to remove image: ' + error.message);
        }
    }

    async setCoverImage(imageId) {
        try {
            const response = await fetch('../admin/set-cover-image.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    image_id: imageId,
                    type: this.type,
                    entity_id: this.entityId
                })
            });

            const result = await response.json();

            if (result.success) {
                // Update UI
                this.container.querySelectorAll('.image-preview-item').forEach(item => {
                    const badge = item.querySelector('.cover-badge');
                    const starBtn = item.querySelector('.btn-icon');

                    if (parseInt(item.dataset.imageId) === imageId) {
                        if (!badge) {
                            const newBadge = document.createElement('div');
                            newBadge.className = 'cover-badge';
                            newBadge.textContent = 'Cover';
                            item.appendChild(newBadge);
                        }
                        if (starBtn) starBtn.classList.add('btn-icon-primary');
                    } else {
                        if (badge) badge.remove();
                        if (starBtn) starBtn.classList.remove('btn-icon-primary');
                    }
                });

                this.showSuccess('Cover image updated');
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to set cover image: ' + error.message);
        }
    }

    showProgress() {
        const progress = this.container.querySelector('#uploadProgress');
        progress.classList.remove('hidden');
    }

    hideProgress() {
        const progress = this.container.querySelector('#uploadProgress');
        progress.classList.add('hidden');
    }

    showError(message) {
        // You can integrate with your notification system
        alert(message);
    }

    showSuccess(message) {
        // You can integrate with your notification system
        console.log(message);
    }

    loadExistingImages(images) {
        images.forEach(img => this.addImagePreview(img));
    }
}

// Make it globally accessible
let imageUploader;
