/**
 * FlashcardManager — Main flashcard management logic
 * Handles CRUD, search, pagination, import, bulk actions, view switching
 */
const FlashcardManager = {
    apiUrl: '',
    csrfToken: '',
    currentView: 'grid',
    currentPage: 1,
    perPage: 24,
    totalPages: 1,
    totalCards: 0,
    searchQuery: '',
    sortBy: 'newest',
    statusFilter: '',
    selectedIds: new Set(),
    allCards: [],
    searchTimer: null,

    init(apiUrl, csrfToken) {
        this.apiUrl = apiUrl;
        this.csrfToken = csrfToken;
        this.folderFilter = '';
        this.bindEvents();
        FolderManager.init(this);
        this.loadCards();
        this.loadStats();
    },

    // ─── EVENT BINDING ────────────────────────────────────
    bindEvents() {
        // Search
        const searchInput = document.getElementById('fc-search');
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                clearTimeout(this.searchTimer);
                this.searchTimer = setTimeout(() => {
                    this.searchQuery = searchInput.value.trim();
                    this.currentPage = 1;
                    this.loadCards();
                }, 300);
            });
        }

        // Sort
        const sortSelect = document.getElementById('fc-sort');
        if (sortSelect) {
            sortSelect.addEventListener('change', () => {
                this.sortBy = sortSelect.value;
                this.currentPage = 1;
                this.loadCards();
            });
        }

        // Status filter
        const statusSelect = document.getElementById('fc-status-filter');
        if (statusSelect) {
            statusSelect.addEventListener('change', () => {
                this.statusFilter = statusSelect.value;
                this.currentPage = 1;
                this.loadCards();
            });
        }

        // Folder filter
        const folderSelect = document.getElementById('fc-folder-filter');
        if (folderSelect) {
            folderSelect.addEventListener('change', () => {
                this.folderFilter = folderSelect.value;
                this.currentPage = 1;
                this.loadCards();
                this.loadStats();
                this.updateStudyButton();
            });
        }

        // View toggle
        document.querySelectorAll('[data-view]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.toggleView(btn.dataset.view);
            });
        });

        // Select all checkbox
        const selectAll = document.getElementById('fc-select-all');
        if (selectAll) {
            selectAll.addEventListener('change', () => {
                if (selectAll.checked) {
                    this.allCards.forEach(c => this.selectedIds.add(c.id));
                } else {
                    this.selectedIds.clear();
                }
                this.renderCurrentView();
                this.updateBulkBar();
            });
        }

        // Create modal form
        const form = document.getElementById('fc-form');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveCard();
            });
        }

        // Image upload preview
        const imageInput = document.getElementById('fc-image-input');
        if (imageInput) {
            imageInput.addEventListener('change', () => this.previewImage());
        }

        // Drag and drop on image area
        const dropZone = document.getElementById('fc-image-drop');
        if (dropZone) {
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('border-blue-400', 'bg-blue-50');
            });
            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('border-blue-400', 'bg-blue-50');
            });
            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('border-blue-400', 'bg-blue-50');
                if (e.dataTransfer.files.length) {
                    document.getElementById('fc-image-input').files = e.dataTransfer.files;
                    this.previewImage();
                }
            });
        }

        // Import file input
        const importInput = document.getElementById('fc-import-file');
        if (importInput) {
            importInput.addEventListener('change', () => this.handleImportFile());
        }

        // Import drop zone
        const importDrop = document.getElementById('fc-import-drop');
        if (importDrop) {
            importDrop.addEventListener('dragover', (e) => {
                e.preventDefault();
                importDrop.classList.add('border-blue-400', 'bg-blue-50');
            });
            importDrop.addEventListener('dragleave', () => {
                importDrop.classList.remove('border-blue-400', 'bg-blue-50');
            });
            importDrop.addEventListener('drop', (e) => {
                e.preventDefault();
                importDrop.classList.remove('border-blue-400', 'bg-blue-50');
                if (e.dataTransfer.files.length) {
                    document.getElementById('fc-import-file').files = e.dataTransfer.files;
                    this.handleImportFile();
                }
            });
        }
    },

    // ─── LOAD CARDS ───────────────────────────────────────
    async loadCards() {
        const container = document.getElementById('fc-cards-container');
        container.innerHTML = this.skeletonHTML();

        const params = new URLSearchParams({
            action: 'list',
            search: this.searchQuery,
            sort: this.sortBy,
            status: this.statusFilter,
            page: this.currentPage,
            per_page: this.perPage,
        });
        if (this.folderFilter !== '') {
            params.append('deck_id', this.folderFilter);
        }

        try {
            const res = await fetch(`${this.apiUrl}?${params}`);
            const data = await res.json();

            if (data.success) {
                this.allCards = data.cards;
                this.totalCards = data.total;
                this.totalPages = data.total_pages;
                this.renderCurrentView();
                this.renderPagination();
                this.updateCardCount();
            }
        } catch (err) {
            container.innerHTML = '<div class="text-center py-12 text-gray-500">Failed to load flashcards. Please try again.</div>';
        }
    },

    // ─── LOAD STATS ───────────────────────────────────────
    async loadStats() {
        try {
            const params = new URLSearchParams({ action: 'stats' });
            if (this.folderFilter !== '') {
                params.append('deck_id', this.folderFilter);
            }
            const res = await fetch(`${this.apiUrl}?${params}`);
            const data = await res.json();
            if (data.success) {
                const s = data.stats;
                document.getElementById('stat-total').textContent = s.total || 0;
                document.getElementById('stat-new').textContent = s.new_count || 0;
                document.getElementById('stat-known').textContent = s.known_count || 0;
                document.getElementById('stat-review').textContent = s.review_count || 0;
            }
        } catch (err) { /* silent */ }
    },

    // ─── RENDER ───────────────────────────────────────────
    renderCurrentView() {
        if (this.currentView === 'grid') {
            this.renderGrid();
        } else {
            this.renderTable();
        }
    },

    renderGrid() {
        const container = document.getElementById('fc-cards-container');
        if (this.allCards.length === 0) {
            container.innerHTML = this.emptyStateHTML();
            return;
        }

        let html = '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">';
        this.allCards.forEach(card => {
            const checked = this.selectedIds.has(card.id) ? 'checked' : '';
            const statusBadge = this.statusBadgeHTML(card.status);
            const folderBadge = card.deck_name
                ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold" style="background-color: ${this.escapeAttr(card.deck_color || '#3B82F6')}15; color: ${this.escapeAttr(card.deck_color || '#3B82F6')}">${this.escape(card.deck_name)}</span>`
                : '';
            const imgHTML = card.image_url
                ? `<div class="h-32 bg-gray-100 rounded-lg overflow-hidden mb-3"><img src="${this.escapeAttr(card.image_url)}" class="w-full h-full object-cover" alt="" loading="lazy"></div>`
                : '';

            html += `
            <div class="group bg-white rounded-xl border border-gray-100 p-4 hover:shadow-lg hover:border-blue-100 transition-all duration-200 relative" data-id="${card.id}">
                <div class="absolute top-3 left-3 z-10">
                    <input type="checkbox" class="fc-checkbox w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer" data-id="${card.id}" ${checked} onchange="FlashcardManager.toggleSelect(${card.id})">
                </div>
                <div class="absolute top-3 right-3 z-10 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button onclick="FlashcardManager.openEditModal(${card.id})" class="p-1.5 rounded-lg bg-white shadow-sm border border-gray-200 hover:bg-blue-50 hover:border-blue-200 transition" title="Edit">
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button onclick="FlashcardManager.confirmDelete(event, ${card.id})" class="p-1.5 rounded-lg bg-white shadow-sm border border-gray-200 hover:bg-red-50 hover:border-red-200 transition" title="Delete">
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
                <div class="pt-6">
                    ${imgHTML}
                    <div class="flex items-start justify-between gap-2 mb-1">
                        <h3 class="font-semibold text-gray-900 text-base korean-text leading-tight truncate flex-1">${this.escape(card.term)}</h3>
                        <div class="flex flex-col items-end gap-1 flex-shrink-0">
                            ${statusBadge}
                            ${folderBadge}
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 line-clamp-2">${this.escape(card.definition)}</p>
                    <p class="text-[11px] text-gray-300 mt-2">${this.formatDate(card.created_at)}</p>
                </div>
            </div>`;
        });
        html += '</div>';
        container.innerHTML = html;
    },

    renderTable() {
        const container = document.getElementById('fc-cards-container');
        if (this.allCards.length === 0) {
            container.innerHTML = this.emptyStateHTML();
            return;
        }

        let html = `
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/50">
                            <th class="px-4 py-3 text-left w-10"><input type="checkbox" id="fc-table-select-all" class="w-4 h-4 rounded border-gray-300 text-blue-600 cursor-pointer" onchange="FlashcardManager.toggleSelectAll(this.checked)"></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">Image</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Term</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Definition</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Folder</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Date</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">`;

        this.allCards.forEach(card => {
            const checked = this.selectedIds.has(card.id) ? 'checked' : '';
            const thumb = card.image_url
                ? `<img src="${this.escapeAttr(card.image_url)}" class="w-10 h-10 rounded-lg object-cover" loading="lazy">`
                : '<div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-300"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>';

            const folderBadge = card.deck_name
                ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold" style="background-color: ${this.escapeAttr(card.deck_color || '#3B82F6')}15; color: ${this.escapeAttr(card.deck_color || '#3B82F6')}">${this.escape(card.deck_name)}</span>`
                : '<span class="text-xs text-gray-400">-</span>';

            html += `
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-4 py-3"><input type="checkbox" class="fc-checkbox w-4 h-4 rounded border-gray-300 text-blue-600 cursor-pointer" data-id="${card.id}" ${checked} onchange="FlashcardManager.toggleSelect(${card.id})"></td>
                    <td class="px-4 py-3">${thumb}</td>
                    <td class="px-4 py-3"><span class="font-medium text-gray-900 korean-text">${this.escape(card.term)}</span></td>
                    <td class="px-4 py-3"><span class="text-sm text-gray-600 line-clamp-1">${this.escape(card.definition)}</span></td>
                    <td class="px-4 py-3">${folderBadge}</td>
                    <td class="px-4 py-3">${this.statusBadgeHTML(card.status)}</td>
                    <td class="px-4 py-3"><span class="text-xs text-gray-400">${this.formatDate(card.created_at)}</span></td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex justify-end gap-1">
                            <button onclick="FlashcardManager.openEditModal(${card.id})" class="p-1.5 rounded-lg hover:bg-blue-50 transition" title="Edit">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button onclick="FlashcardManager.confirmDelete(event, ${card.id})" class="p-1.5 rounded-lg hover:bg-red-50 transition" title="Delete">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>`;
        });

        html += '</tbody></table></div></div>';
        container.innerHTML = html;
    },

    // ─── PAGINATION ───────────────────────────────────────
    renderPagination() {
        const container = document.getElementById('fc-pagination');
        if (this.totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '<nav class="flex items-center justify-center space-x-1 mt-6">';

        if (this.currentPage > 1) {
            html += `<button onclick=\"FlashcardManager.goToPage(${this.currentPage - 1})\" class=\"px-3 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition\"><svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M15 19l-7-7 7-7\"/></svg></button>`;
        }

        const start = Math.max(1, this.currentPage - 2);
        const end = Math.min(this.totalPages, this.currentPage + 2);

        if (start > 1) {
            html += `<button onclick=\"FlashcardManager.goToPage(1)\" class=\"px-3 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition text-sm\">1</button>`;
            if (start > 2) html += '<span class=\"px-2 text-gray-400\">...</span>';
        }

        for (let i = start; i <= end; i++) {
            if (i === this.currentPage) {
                html += `<span class=\"px-3 py-2 rounded-lg bg-blue-600 text-white font-medium text-sm\">${i}</span>`;
            } else {
                html += `<button onclick=\"FlashcardManager.goToPage(${i})\" class=\"px-3 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition text-sm\">${i}</button>`;
            }
        }

        if (end < this.totalPages) {
            if (end < this.totalPages - 1) html += '<span class=\"px-2 text-gray-400\">...</span>';
            html += `<button onclick=\"FlashcardManager.goToPage(${this.totalPages})\" class=\"px-3 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition text-sm\">${this.totalPages}</button>`;
        }

        if (this.currentPage < this.totalPages) {
            html += `<button onclick=\"FlashcardManager.goToPage(${this.currentPage + 1})\" class=\"px-3 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition\"><svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 5l7 7-7 7\"/></svg></button>`;
        }

        html += '</nav>';
        container.innerHTML = html;
    },

    goToPage(page) {
        this.currentPage = page;
        this.loadCards();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    // ─── VIEW TOGGLE ──────────────────────────────────────
    toggleView(view) {
        this.currentView = view;
        document.querySelectorAll('[data-view]').forEach(btn => {
            btn.classList.toggle('bg-blue-100', btn.dataset.view === view);
            btn.classList.toggle('text-blue-600', btn.dataset.view === view);
            btn.classList.toggle('text-gray-400', btn.dataset.view !== view);
        });
        this.renderCurrentView();
    },

    // ─── SELECTION ────────────────────────────────────────
    toggleSelect(id) {
        if (this.selectedIds.has(id)) {
            this.selectedIds.delete(id);
        } else {
            this.selectedIds.add(id);
        }
        this.updateBulkBar();
    },

    toggleSelectAll(checked) {
        if (checked) {
            this.allCards.forEach(c => this.selectedIds.add(c.id));
        } else {
            this.selectedIds.clear();
        }
        document.querySelectorAll('.fc-checkbox').forEach(cb => {
            cb.checked = checked;
        });
        this.updateBulkBar();
    },

    updateBulkBar() {
        const bar = document.getElementById('fc-bulk-bar');
        const count = document.getElementById('fc-selected-count');
        if (this.selectedIds.size > 0) {
            bar.classList.remove('hidden');
            count.textContent = this.selectedIds.size;
        } else {
            bar.classList.add('hidden');
        }
    },

    // ─── CREATE / EDIT MODAL ──────────────────────────────
    openCreateModal() {
        document.getElementById('fc-modal-title').textContent = 'Add Flashcard';
        document.getElementById('fc-form').reset();
        document.getElementById('fc-card-id').value = '';
        document.getElementById('fc-image-preview').classList.add('hidden');
        document.getElementById('fc-image-drop').classList.remove('hidden');
        document.getElementById('fc-remove-image').value = '0';

        const folderSelect = document.getElementById('fc-folder');
        if (folderSelect) {
            folderSelect.value = (this.folderFilter && this.folderFilter !== 'null') ? this.folderFilter : '';
        }

        openModal('fc-modal');
        document.getElementById('fc-term').focus();
    },

    async openEditModal(id) {
        try {
            const res = await fetch(`${this.apiUrl}?action=get&id=${id}`);
            const data = await res.json();
            if (!data.success) {
                showToast(data.error || 'Card not found', 'error');
                return;
            }

            const card = data.card;
            document.getElementById('fc-modal-title').textContent = 'Edit Flashcard';
            document.getElementById('fc-card-id').value = card.id;
            document.getElementById('fc-term').value = card.term;
            document.getElementById('fc-definition').value = card.definition;
            document.getElementById('fc-remove-image').value = '0';

            const folderSelect = document.getElementById('fc-folder');
            if (folderSelect) {
                folderSelect.value = card.deck_id || '';
            }

            const preview = document.getElementById('fc-image-preview');
            const dropZone = document.getElementById('fc-image-drop');
            if (card.image_url) {
                document.getElementById('fc-preview-img').src = card.image_url;
                preview.classList.remove('hidden');
                dropZone.classList.add('hidden');
            } else {
                preview.classList.add('hidden');
                dropZone.classList.remove('hidden');
            }

            openModal('fc-modal');
            document.getElementById('fc-term').focus();
        } catch (e) {
            showToast('Failed to load flashcard', 'error');
        }
    },

    async saveCard() {
        const term = document.getElementById('fc-term').value.trim();
        const definition = document.getElementById('fc-definition').value.trim();
        const cardId = document.getElementById('fc-card-id').value;
        const imageInput = document.getElementById('fc-image-input');
        const removeImage = document.getElementById('fc-remove-image').value;
        const deckId = document.getElementById('fc-folder')?.value || '';

        if (!term) {
            showToast('Term is required', 'error');
            document.getElementById('fc-term').focus();
            return;
        }
        if (!definition) {
            showToast('Definition is required', 'error');
            document.getElementById('fc-definition').focus();
            return;
        }

        const formData = new FormData();
        formData.append('action', cardId ? 'update' : 'create');
        formData.append(document.querySelector('[name=\"csrf_token_name\"]')?.value || 'csrf_token', this.csrfToken);
        formData.append('term', term);
        formData.append('definition', definition);
        formData.append('remove_image', removeImage);

        if (cardId) formData.append('id', cardId);
        if (imageInput.files.length) formData.append('image', imageInput.files[0]);
        formData.append('deck_id', deckId);

        // Fix: use proper CSRF token name
        formData.set(document.getElementById('fc-csrf-name').value, this.csrfToken);

        const btn = document.getElementById('fc-save-btn');
        btn.disabled = true;
        btn.innerHTML = '<svg class=\"animate-spin w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\"><circle class=\"opacity-25\" cx=\"12\" cy=\"12\" r=\"10\" stroke=\"currentColor\" stroke-width=\"4\"></circle><path class=\"opacity-75\" fill=\"currentColor\" d=\"M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z\"></path></svg> Saving...';

        try {
            const res = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData,
            });
            const data = await res.json();

            if (data.success) {
                showToast(data.message, 'success');
                closeModal('fc-modal');
                this.loadCards();
                this.loadStats();
            } else {
                showToast(data.error || 'Failed to save', 'error');
            }
        } catch (e) {
            showToast('Network error', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M5 13l4 4L19 7\"/></svg> Save';
        }
    },

    // ─── DELETE ────────────────────────────────────────────
    confirmDelete(event, id) {
        event.stopPropagation();
        if (confirm('Are you sure you want to delete this flashcard?')) {
            this.deleteCard(id);
        }
    },

    async deleteCard(id) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        formData.append(document.getElementById('fc-csrf-name').value, this.csrfToken);

        try {
            const res = await fetch(this.apiUrl, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                showToast(data.message, 'success');
                this.selectedIds.delete(id);
                this.loadCards();
                this.loadStats();
                this.updateBulkBar();
            } else {
                showToast(data.error, 'error');
            }
        } catch (e) {
            showToast('Network error', 'error');
        }
    },

    async bulkDelete() {
        if (this.selectedIds.size === 0) return;
        if (!confirm(`Delete ${this.selectedIds.size} selected flashcard(s)?`)) return;

        const formData = new FormData();
        formData.append('action', 'bulk_delete');
        formData.append('ids', JSON.stringify([...this.selectedIds]));
        formData.append(document.getElementById('fc-csrf-name').value, this.csrfToken);

        try {
            const res = await fetch(this.apiUrl, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                showToast(data.message, 'success');
                this.selectedIds.clear();
                this.loadCards();
                this.loadStats();
                this.updateBulkBar();
            } else {
                showToast(data.error, 'error');
            }
        } catch (e) {
            showToast('Network error', 'error');
        }
    },

    // ─── IMAGE PREVIEW ────────────────────────────────────
    previewImage() {
        const input = document.getElementById('fc-image-input');
        const preview = document.getElementById('fc-image-preview');
        const previewImg = document.getElementById('fc-preview-img');
        const dropZone = document.getElementById('fc-image-drop');

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                preview.classList.remove('hidden');
                dropZone.classList.add('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        }
    },

    removeImage() {
        document.getElementById('fc-image-input').value = '';
        document.getElementById('fc-image-preview').classList.add('hidden');
        document.getElementById('fc-image-drop').classList.remove('hidden');
        document.getElementById('fc-remove-image').value = '1';
    },

    // ─── IMPORT ───────────────────────────────────────────
    importData: null,

    openImportModal() {
        this.importData = null;
        document.getElementById('fc-import-file').value = '';
        document.getElementById('import-step-1').classList.remove('hidden');
        document.getElementById('import-step-2').classList.add('hidden');
        document.getElementById('import-step-3').classList.add('hidden');
        openModal('fc-import-modal');
    },

    async handleImportFile() {
        const input = document.getElementById('fc-import-file');
        if (!input.files.length) return;

        const file = input.files[0];
        const ext = file.name.split('.').pop().toLowerCase();

        try {
            let parsed;
            if (ext === 'csv') {
                const text = await file.text();
                parsed = FlashcardExport.parseCSV(text);
            } else if (ext === 'xlsx' || ext === 'xls') {
                if (typeof XLSX === 'undefined') {
                    showToast('Excel library is loading, please try again in a moment', 'warning');
                    return;
                }
                const data = await file.arrayBuffer();
                const workbook = XLSX.read(data, { type: 'array' });
                const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                const json = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });

                if (json.length < 2) {
                    showToast('File appears to be empty', 'error');
                    return;
                }

                const headers = json[0].map(h => String(h).trim());
                const rows = [];
                for (let i = 1; i < json.length; i++) {
                    if (json[i].some(v => v !== undefined && v !== null && String(v).trim())) {
                        const row = {};
                        headers.forEach((h, idx) => {
                            row[h] = json[i][idx] !== undefined ? String(json[i][idx]).trim() : '';
                        });
                        rows.push(row);
                    }
                }
                parsed = { headers, rows };
            } else {
                showToast('Unsupported file type. Use .csv or .xlsx', 'error');
                return;
            }

            this.showImportMapping(parsed);
        } catch (err) {
            showToast('Error reading file: ' + err.message, 'error');
        }
    },

    showImportMapping(parsed) {
        const { headers, rows } = parsed;

        // Auto-map columns
        const termAliases = ['term', 'word', 'vocabulary', 'korean', 'front', '단어', '용어'];
        const defAliases = ['definition', 'meaning', 'translation', 'english', 'back', '뜻', '의미'];
        const imgAliases = ['image', 'picture', 'photo', 'img', '이미지', '사진'];
        const folderAliases = ['folder', 'deck', 'set', 'category', 'group', 'chapter', 'unit', 'section', '폴더', '카테고리', '묶음', '단원'];

        const findCol = (aliases) => {
            // Exact match first
            const exact = headers.find(h => aliases.includes(h.toLowerCase()));
            if (exact) return exact;
            // Partial/contains match
            return headers.find(h => aliases.some(a => h.toLowerCase().includes(a))) || '';
        };

        const termCol = findCol(termAliases) || headers[0] || '';
        const defCol = findCol(defAliases) || headers[1] || '';
        const imgCol = findCol(imgAliases) || '';
        const folderCol = findCol(folderAliases) || '';

        // Build mapping UI
        const optionsHTML = (selected) => {
            let html = '<option value="">-- Skip --</option>';
            headers.forEach(h => {
                html += `<option value="${this.escapeAttr(h)}" ${h === selected ? 'selected' : ''}>${this.escape(h)}</option>`;
            });
            return html;
        };

        document.getElementById('import-map-term').innerHTML = optionsHTML(termCol);
        document.getElementById('import-map-definition').innerHTML = optionsHTML(defCol);
        document.getElementById('import-map-image').innerHTML = optionsHTML(imgCol);
        document.getElementById('import-map-folder').innerHTML = optionsHTML(folderCol);

        this.importData = { headers, rows };

        document.getElementById('import-step-1').classList.add('hidden');
        document.getElementById('import-step-2').classList.remove('hidden');
    },

    previewImport() {
        if (!this.importData) return;

        const termCol = document.getElementById('import-map-term').value;
        const defCol = document.getElementById('import-map-definition').value;
        const imgCol = document.getElementById('import-map-image').value;
        const folderCol = document.getElementById('import-map-folder').value;

        if (!termCol || !defCol) {
            showToast('Please map both Term and Definition columns', 'error');
            return;
        }

        const valid = [];
        const invalid = [];
        const duplicateSet = new Set();
        const duplicates = [];

        this.importData.rows.forEach((row, idx) => {
            const term = (row[termCol] || '').trim();
            const definition = (row[defCol] || '').trim();
            const image = imgCol ? (row[imgCol] || '').trim() : '';
            const folder = folderCol ? (row[folderCol] || '').trim() : '';

            if (!term || !definition) {
                invalid.push({ row: idx + 2, reason: 'Missing term or definition', term, definition });
                return;
            }

            const key = term.toLowerCase() + '|' + definition.toLowerCase();
            if (duplicateSet.has(key)) {
                duplicates.push({ row: idx + 2, term, definition });
                return;
            }
            duplicateSet.add(key);
            valid.push({ term, definition, image, folder });
        });

        // Render preview
        document.getElementById('import-total').textContent = this.importData.rows.length;
        document.getElementById('import-valid').textContent = valid.length;
        document.getElementById('import-invalid').textContent = invalid.length;
        document.getElementById('import-duplicates').textContent = duplicates.length;

        // Preview table (first 10)
        let previewHTML = '';
        valid.slice(0, 10).forEach((c, i) => {
            const folderBadge = c.folder
                ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-600">${this.escape(c.folder)}</span>`
                : '<span class="text-gray-300">-</span>';
            previewHTML += `<tr class="border-b border-gray-50">
                <td class="px-3 py-2 text-xs text-gray-400">${i + 1}</td>
                <td class="px-3 py-2 text-sm font-medium korean-text">${this.escape(c.term)}</td>
                <td class="px-3 py-2 text-sm text-gray-600">${this.escape(c.definition)}</td>
                <td class="px-3 py-2 text-xs">${folderBadge}</td>
                <td class="px-3 py-2 text-xs text-gray-400">${this.escape(c.image || '-')}</td>
            </tr>`;
        });
        if (valid.length > 10) {
            previewHTML += `<tr><td colspan="5" class="px-3 py-2 text-xs text-gray-400 text-center">... and ${valid.length - 10} more</td></tr>`;
        }
        document.getElementById('import-preview-body').innerHTML = previewHTML;

        // Invalid rows
        let invalidHTML = '';
        if (invalid.length > 0) {
            invalidHTML = '<div class="mt-3"><p class="text-xs font-medium text-red-600 mb-1">Invalid rows:</p><div class="max-h-24 overflow-y-auto text-xs text-red-500">';
            invalid.forEach(r => {
                invalidHTML += `<p>Row ${r.row}: ${this.escape(r.reason)}</p>`;
            });
            invalidHTML += '</div></div>';
        }
        document.getElementById('import-invalid-details').innerHTML = invalidHTML;

        this.importData.validCards = valid;

        document.getElementById('import-step-2').classList.add('hidden');
        document.getElementById('import-step-3').classList.remove('hidden');
    },

    async confirmImport() {
        if (!this.importData?.validCards?.length) {
            showToast('No valid cards to import', 'error');
            return;
        }

        const btn = document.getElementById('import-confirm-btn');
        btn.disabled = true;
        btn.innerHTML = '<svg class="animate-spin w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Importing...';

        const formData = new FormData();
        formData.append('action', 'import');
        formData.append('cards', JSON.stringify(this.importData.validCards));
        formData.append(document.getElementById('fc-csrf-name').value, this.csrfToken);

        try {
            const res = await fetch(this.apiUrl, { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                showToast(data.message, 'success');
                closeModal('fc-import-modal');
                this.loadCards();
                this.loadStats();
                FolderManager.loadFolders(); // Reload folders in case new ones were created during import
            } else {
                showToast(data.error, 'error');
            }
        } catch (e) {
            showToast('Import failed', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Import';
        }
    },

    // ─── EXPORT ───────────────────────────────────────────
    async exportCards(format, selectedOnly = false) {
        let url = `${this.apiUrl}?action=export`;
        if (selectedOnly && this.selectedIds.size > 0) {
            url += `&ids=${[...this.selectedIds].join(',')}`;
        }

        try {
            const res = await fetch(url);
            const data = await res.json();

            if (data.success && data.cards.length) {
                if (format === 'csv') {
                    FlashcardExport.toCSV(data.cards);
                } else {
                    FlashcardExport.toXLSX(data.cards);
                }
                showToast(`Exported ${data.cards.length} flashcard(s)`, 'success');
            } else {
                showToast('No flashcards to export', 'warning');
            }
        } catch (e) {
            showToast('Export failed', 'error');
        }
    },

    // ─── HELPERS ──────────────────────────────────────────
    updateCardCount() {
        const el = document.getElementById('fc-card-count');
        if (el) el.textContent = `${this.totalCards} card${this.totalCards !== 1 ? 's' : ''}`;
    },

    statusBadgeHTML(status) {
        const styles = {
            'new': 'bg-gray-100 text-gray-600',
            'known': 'bg-emerald-50 text-emerald-600',
            'review': 'bg-amber-50 text-amber-600',
        };
        const labels = { 'new': 'New', 'known': 'Known', 'review': 'Review' };
        return `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold ${styles[status] || styles.new}">${labels[status] || 'New'}</span>`;
    },

    formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    },

    escape(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    },

    escapeAttr(str) {
        return (str || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    },

    skeletonHTML() {
        let html = '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">';
        for (let i = 0; i < 8; i++) {
            html += `<div class="bg-white rounded-xl border border-gray-100 p-4 animate-pulse">
                <div class="h-4 bg-gray-200 rounded w-3/4 mb-3"></div>
                <div class="h-3 bg-gray-100 rounded w-full mb-2"></div>
                <div class="h-3 bg-gray-100 rounded w-2/3"></div>
            </div>`;
        }
        html += '</div>';
        return html;
    },

    emptyStateHTML() {
        return `
        <div class="text-center py-16">
            <div class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center">
                <svg class="w-10 h-10 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-1">No flashcards yet</h3>
            <p class="text-sm text-gray-400 mb-4">Create your first flashcard or import from a file</p>
            <div class="flex items-center justify-center gap-3">
                <button onclick="FlashcardManager.openCreateModal()" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Flashcard
                </button>
                <button onclick="FlashcardManager.openImportModal()" class="px-4 py-2 bg-white border border-gray-200 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Import
                </button>
            </div>
        </div>`;
    },

    updateStudyButton() {
        const btn = document.getElementById('fc-study-btn');
        if (btn) {
            let href = btn.getAttribute('href').split('?')[0];
            if (this.folderFilter !== '') {
                href += `?deck_id=${this.folderFilter}`;
            }
            btn.setAttribute('href', href);
        }
    }
};

/**
 * FolderManager — Folder management logic
 */
const FolderManager = {
    manager: null,
    folders: [],

    init(manager) {
        this.manager = manager;
        this.loadFolders();
    },

    async loadFolders() {
        try {
            const res = await fetch(`${this.manager.apiUrl}?action=list_folders`);
            const data = await res.json();
            if (data.success) {
                this.folders = data.folders;
                this.populateDropdowns();
                this.renderFoldersList();
            }
        } catch (e) {
            console.error('Failed to load folders', e);
        }
    },

    populateDropdowns() {
        // 1. Toolbar Filter
        const filterSelect = document.getElementById('fc-folder-filter');
        if (filterSelect) {
            const currentFilter = filterSelect.value;
            let html = '<option value="">All Folders</option><option value="null">Unsorted</option>';
            this.folders.forEach(f => {
                html += `<option value="${f.id}" ${f.id == currentFilter ? 'selected' : ''}>${this.manager.escape(f.name)} (${f.card_count})</option>`;
            });
            filterSelect.innerHTML = html;
        }

        // 2. Add/Edit Form select
        const formSelect = document.getElementById('fc-folder');
        if (formSelect) {
            const currentValue = formSelect.value;
            let html = '<option value="">-- No Folder (Unsorted) --</option>';
            this.folders.forEach(f => {
                html += `<option value="${f.id}" ${f.id == currentValue ? 'selected' : ''}>${this.manager.escape(f.name)}</option>`;
            });
            formSelect.innerHTML = html;
        }
    },

    renderFoldersList() {
        const listContainer = document.getElementById('folders-list');
        if (!listContainer) return;

        if (this.folders.length === 0) {
            listContainer.innerHTML = '<p class="text-xs text-gray-400 py-3 text-center">No folders created yet.</p>';
            return;
        }

        let html = '';
        this.folders.forEach(f => {
            html += `
            <div class="flex items-center justify-between p-2.5 rounded-xl border border-gray-100 bg-white hover:border-blue-100 hover:shadow-sm transition duration-150">
                <div class="flex items-center gap-2.5 overflow-hidden">
                    <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: ${this.manager.escapeAttr(f.color || '#3B82F6')}"></span>
                    <div class="overflow-hidden">
                        <p class="text-sm font-semibold text-gray-800 truncate">${this.manager.escape(f.name)}</p>
                        <p class="text-[11px] text-gray-400">${f.card_count} card(s)</p>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <button onclick="FolderManager.editFolder(${f.id}, '${this.escapeQuote(f.name)}', '${this.escapeQuote(f.color)}')" class="p-1 rounded-md text-blue-600 hover:bg-blue-50 transition" title="Edit Folder">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button onclick="FolderManager.deleteFolder(${f.id})" class="p-1 rounded-md text-red-500 hover:bg-red-50 transition" title="Delete Folder">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>`;
        });
        listContainer.innerHTML = html;
    },

    openModal() {
        this.resetForm();
        this.loadFolders();
        openModal('fc-folders-modal');
    },

    editFolder(id, name, color) {
        document.getElementById('folder-id').value = id;
        document.getElementById('folder-name').value = name;
        document.getElementById('folder-color').value = color || '#3B82F6';
        document.getElementById('folder-form-title').textContent = 'Edit Folder';
        document.getElementById('folder-cancel-btn').classList.remove('hidden');
        document.getElementById('folder-name').focus();
    },

    resetForm() {
        document.getElementById('folder-id').value = '';
        document.getElementById('folder-form').reset();
        document.getElementById('folder-form-title').textContent = 'Create New Folder';
        document.getElementById('folder-cancel-btn').classList.add('hidden');
    },

    async saveFolder() {
        const id = document.getElementById('folder-id').value;
        const name = document.getElementById('folder-name').value.trim();
        const color = document.getElementById('folder-color').value;

        if (!name) {
            showToast('Folder name is required', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('action', id ? 'update_folder' : 'create_folder');
        formData.append('name', name);
        formData.append('color', color);
        formData.append(document.getElementById('fc-csrf-name').value, this.manager.csrfToken);
        if (id) formData.append('id', id);

        const btn = document.getElementById('folder-save-btn');
        btn.disabled = true;
        const origText = btn.textContent;
        btn.textContent = 'Saving...';

        try {
            const res = await fetch(this.manager.apiUrl, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                showToast(data.message, 'success');
                this.resetForm();
                this.loadFolders();
                this.manager.loadCards(); // Reload cards since counts or filters might be relevant
            } else {
                showToast(data.error || 'Failed to save folder', 'error');
            }
        } catch (e) {
            showToast('Network error', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = origText;
        }
    },

    async deleteFolder(id) {
        if (!confirm('Are you sure you want to delete this folder? The cards in it will not be deleted, they will just be unsorted.')) return;

        const formData = new FormData();
        formData.append('action', 'delete_folder');
        formData.append('id', id);
        formData.append(document.getElementById('fc-csrf-name').value, this.manager.csrfToken);

        try {
            const res = await fetch(this.manager.apiUrl, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                showToast(data.message, 'success');
                if (this.manager.folderFilter == id) {
                    this.manager.folderFilter = '';
                    const filterSelect = document.getElementById('fc-folder-filter');
                    if (filterSelect) filterSelect.value = '';
                    this.manager.updateStudyButton();
                }
                this.loadFolders();
                this.manager.loadCards();
            } else {
                showToast(data.error || 'Failed to delete folder', 'error');
            }
        } catch (e) {
            showToast('Network error', 'error');
        }
    },

    escapeQuote(str) {
        return (str || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }
};
