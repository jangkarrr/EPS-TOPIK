<?php
$pageTitle = 'Flashcards';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth-check.php';

$userId = getCurrentUserId();
$csrfToken = generateCSRFToken();
$csrfName = CSRF_TOKEN_NAME;

require_once __DIR__ . '/includes/header.php';
?>

<!-- SheetJS CDN for Excel import/export -->
<script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>

<style>
    .line-clamp-1 { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
    .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .export-dropdown { display: none; }
    .export-dropdown.show { display: block; }
</style>

<!-- Hidden CSRF reference -->
<input type="hidden" id="fc-csrf-name" value="<?= $csrfName ?>">

<!-- ═══════════════════════════════════════════════════════════
     STATS CARDS
     ═══════════════════════════════════════════════════════════ -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <div class="stat-card bg-white rounded-xl border border-gray-100 p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900" id="stat-total">0</p>
                <p class="text-xs text-gray-400">Total Cards</p>
            </div>
        </div>
    </div>
    <div class="stat-card bg-white rounded-xl border border-gray-100 p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900" id="stat-new">0</p>
                <p class="text-xs text-gray-400">New</p>
            </div>
        </div>
    </div>
    <div class="stat-card bg-white rounded-xl border border-gray-100 p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900" id="stat-known">0</p>
                <p class="text-xs text-gray-400">Known</p>
            </div>
        </div>
    </div>
    <div class="stat-card bg-white rounded-xl border border-gray-100 p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900" id="stat-review">0</p>
                <p class="text-xs text-gray-400">Review</p>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     TOOLBAR
     ═══════════════════════════════════════════════════════════ -->
<div class="bg-white rounded-xl border border-gray-100 p-4 mb-4">
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <!-- Search -->
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="fc-search" placeholder="Search flashcards..." class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>

        <!-- Filters & Controls -->
        <div class="flex items-center gap-2 flex-wrap">
            <!-- Folder Filter -->
            <select id="fc-folder-filter" class="px-3 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                <option value="">All Folders</option>
                <option value="null">Unsorted</option>
            </select>

            <!-- Folders Management Button -->
            <button onclick="FolderManager.openModal()" class="px-3 py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition flex items-center gap-1.5" title="Manage Folders">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                Folders
            </button>

            <!-- Status Filter -->
            <select id="fc-status-filter" class="px-3 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                <option value="">All Status</option>
                <option value="new">New</option>
                <option value="known">Known</option>
                <option value="review">Review</option>
            </select>

            <!-- Sort -->
            <select id="fc-sort" class="px-3 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                <option value="newest">Newest</option>
                <option value="oldest">Oldest</option>
                <option value="alpha_asc">A → Z</option>
                <option value="alpha_desc">Z → A</option>
                <option value="status">Status</option>
            </select>

            <!-- View Toggle -->
            <div class="flex items-center bg-gray-100 rounded-lg p-0.5">
                <button data-view="grid" class="p-2 rounded-md bg-blue-100 text-blue-600 transition" title="Grid View">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                </button>
                <button data-view="table" class="p-2 rounded-md text-gray-400 transition" title="Table View">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </button>
            </div>

            <!-- Divider -->
            <div class="hidden sm:block w-px h-8 bg-gray-200"></div>

            <!-- Import -->
            <button onclick="FlashcardManager.openImportModal()" class="px-3 py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition flex items-center gap-1.5" title="Import">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span class="hidden sm:inline">Import</span>
            </button>

            <!-- Export Dropdown -->
            <div class="relative" id="export-wrapper">
                <button onclick="document.getElementById('export-menu').classList.toggle('show')" class="px-3 py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition flex items-center gap-1.5" title="Export">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span class="hidden sm:inline">Export</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="export-menu" class="export-dropdown absolute right-0 mt-1 w-44 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-20">
                    <button onclick="FlashcardManager.exportCards('csv'); document.getElementById('export-menu').classList.remove('show');" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export as CSV
                    </button>
                    <button onclick="FlashcardManager.exportCards('xlsx'); document.getElementById('export-menu').classList.remove('show');" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export as Excel
                    </button>
                </div>
            </div>

            <!-- Study Button -->
            <a id="fc-study-btn" href="<?= APP_URL ?>/flashcard-study.php" class="px-4 py-2.5 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-medium hover:from-blue-700 hover:to-indigo-700 transition flex items-center gap-1.5 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="hidden sm:inline">Study</span>
            </a>

            <!-- Add Card -->
            <button onclick="FlashcardManager.openCreateModal()" class="px-4 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition flex items-center gap-1.5 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span class="hidden sm:inline">Add Card</span>
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     BULK ACTION BAR
     ═══════════════════════════════════════════════════════════ -->
<div id="fc-bulk-bar" class="hidden bg-blue-50 border border-blue-200 rounded-xl p-3 mb-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <span class="text-sm font-medium text-blue-700"><span id="fc-selected-count">0</span> selected</span>
        <button onclick="FlashcardManager.toggleSelectAll(false)" class="text-xs text-blue-600 hover:underline">Deselect all</button>
    </div>
    <div class="flex items-center gap-2">
        <button onclick="FlashcardManager.exportCards('csv', true)" class="px-3 py-1.5 rounded-lg border border-blue-200 text-blue-700 text-xs font-medium hover:bg-blue-100 transition">Export CSV</button>
        <button onclick="FlashcardManager.exportCards('xlsx', true)" class="px-3 py-1.5 rounded-lg border border-blue-200 text-blue-700 text-xs font-medium hover:bg-blue-100 transition">Export Excel</button>
        <button onclick="FlashcardManager.bulkDelete()" class="px-3 py-1.5 rounded-lg bg-red-500 text-white text-xs font-medium hover:bg-red-600 transition">Delete Selected</button>
    </div>
</div>

<!-- Card count -->
<div class="flex items-center justify-between mb-3">
    <p class="text-xs text-gray-400" id="fc-card-count">0 cards</p>
</div>

<!-- ═══════════════════════════════════════════════════════════
     CARDS CONTAINER
     ═══════════════════════════════════════════════════════════ -->
<div id="fc-cards-container"></div>

<!-- Pagination -->
<div id="fc-pagination"></div>

<!-- ═══════════════════════════════════════════════════════════
     CREATE / EDIT MODAL
     ═══════════════════════════════════════════════════════════ -->
<div id="fc-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeModal('fc-modal')"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 rounded-t-2xl flex items-center justify-between">
            <h3 id="fc-modal-title" class="text-lg font-semibold text-gray-900">Add Flashcard</h3>
            <button onclick="closeModal('fc-modal')" class="p-1 rounded-lg hover:bg-gray-100 transition">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="fc-form" class="p-6 space-y-5">
            <input type="hidden" id="fc-card-id" value="">
            <input type="hidden" id="fc-remove-image" value="0">

            <!-- Term -->
            <div>
                <label for="fc-term" class="block text-sm font-medium text-gray-700 mb-1.5">Term <span class="text-red-400">*</span></label>
                <input type="text" id="fc-term" name="term" required placeholder="e.g., 안녕하세요" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent korean-text" autocomplete="off">
            </div>

            <!-- Definition -->
            <div>
                <label for="fc-definition" class="block text-sm font-medium text-gray-700 mb-1.5">Definition <span class="text-red-400">*</span></label>
                <textarea id="fc-definition" name="definition" required rows="3" placeholder="e.g., Hello (formal greeting)" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"></textarea>
            </div>

            <!-- Folder Selection -->
            <div>
                <label for="fc-folder" class="block text-sm font-medium text-gray-700 mb-1.5">Folder</label>
                <select id="fc-folder" name="deck_id" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                    <option value="">-- No Folder (Unsorted) --</option>
                </select>
            </div>

            <!-- Image Upload -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Image <span class="text-gray-400 font-normal">(optional)</span></label>
                <!-- Drop Zone -->
                <div id="fc-image-drop" class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center cursor-pointer hover:border-blue-300 hover:bg-blue-50/30 transition" onclick="document.getElementById('fc-image-input').click()">
                    <svg class="w-8 h-8 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <p class="text-sm text-gray-400">Drag & drop or <span class="text-blue-500 font-medium">browse</span></p>
                    <p class="text-xs text-gray-300 mt-1">JPG, PNG, GIF, WebP • Max 10MB</p>
                </div>
                <input type="file" id="fc-image-input" accept="image/*" class="hidden">

                <!-- Preview -->
                <div id="fc-image-preview" class="hidden relative">
                    <img id="fc-preview-img" src="" class="w-full h-40 object-contain rounded-xl border border-gray-200 bg-gray-50" alt="Preview">
                    <button type="button" onclick="FlashcardManager.removeImage()" class="absolute top-2 right-2 p-1.5 rounded-lg bg-white/90 shadow-sm border border-gray-200 hover:bg-red-50 transition">
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>

            <!-- Save Button -->
            <button type="submit" id="fc-save-btn" class="w-full px-4 py-3 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition flex items-center justify-center gap-2 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Save
            </button>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     FOLDERS (DECKS) MODAL
     ═══════════════════════════════════════════════════════════ -->
<div id="fc-folders-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeModal('fc-folders-modal')"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 rounded-t-2xl flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Manage Folders</h3>
            <button onclick="closeModal('fc-folders-modal')" class="p-1 rounded-lg hover:bg-gray-100 transition">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="p-6 space-y-6">
            <!-- Folders List -->
            <div>
                <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Your Folders</h4>
                <div id="folders-list" class="space-y-2 max-h-60 overflow-y-auto pr-1">
                    <!-- Loaded dynamically -->
                </div>
            </div>

            <!-- Create/Edit Folder Form -->
            <div class="border-t border-gray-100 pt-4">
                <h4 id="folder-form-title" class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Create New Folder</h4>
                <form id="folder-form" class="space-y-3" onsubmit="event.preventDefault(); FolderManager.saveFolder();">
                    <input type="hidden" id="folder-id" value="">
                    <div>
                        <label for="folder-name" class="block text-xs font-medium text-gray-500 mb-1">Folder Name <span class="text-red-400">*</span></label>
                        <input type="text" id="folder-name" required placeholder="e.g. Vocabulary Set 1" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="folder-color" class="block text-xs font-medium text-gray-500 mb-1">Theme Color</label>
                        <div class="flex items-center gap-2">
                            <input type="color" id="folder-color" value="#3B82F6" class="w-8 h-8 rounded border border-gray-200 cursor-pointer p-0">
                            <span class="text-xs text-gray-400">Choose a color highlight</span>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" id="folder-cancel-btn" onclick="FolderManager.resetForm()" class="hidden px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-medium text-gray-600 hover:bg-gray-50 transition">Cancel Edit</button>
                        <button type="submit" id="folder-save-btn" class="px-4 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium transition">Save Folder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     IMPORT MODAL
     ═══════════════════════════════════════════════════════════ -->
<div id="fc-import-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeModal('fc-import-modal')"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 rounded-t-2xl flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Import Flashcards</h3>
            <button onclick="closeModal('fc-import-modal')" class="p-1 rounded-lg hover:bg-gray-100 transition">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="p-6">
            <!-- Step 1: Upload File -->
            <div id="import-step-1">
                <div class="mb-4">
                    <p class="text-sm text-gray-600 mb-1">Upload an Excel (.xlsx) or CSV (.csv) file with your flashcards.</p>
                    <p class="text-xs text-gray-400">Expected columns: <strong>Term</strong>, <strong>Definition</strong>, <strong>Image</strong> (optional)</p>
                </div>
                <div id="fc-import-drop" class="border-2 border-dashed border-gray-200 rounded-xl p-10 text-center cursor-pointer hover:border-blue-300 hover:bg-blue-50/30 transition" onclick="document.getElementById('fc-import-file').click()">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p class="text-sm text-gray-500">Drag & drop your file here or <span class="text-blue-500 font-medium">browse</span></p>
                    <p class="text-xs text-gray-300 mt-1">Supports .xlsx, .csv</p>
                </div>
                <input type="file" id="fc-import-file" accept=".xlsx,.xls,.csv" class="hidden">
            </div>

            <!-- Step 2: Column Mapping -->
            <div id="import-step-2" class="hidden">
                <p class="text-sm text-gray-600 mb-4">Map your file columns to flashcard fields:</p>
                <div class="space-y-3 mb-6">
                    <div class="flex items-center gap-3">
                        <label class="text-sm font-medium text-gray-700 w-24">Term <span class="text-red-400">*</span></label>
                        <select id="import-map-term" class="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></select>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="text-sm font-medium text-gray-700 w-24">Definition <span class="text-red-400">*</span></label>
                        <select id="import-map-definition" class="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></select>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="text-sm font-medium text-gray-700 w-24">Image</label>
                        <select id="import-map-image" class="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></select>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="document.getElementById('import-step-2').classList.add('hidden'); document.getElementById('import-step-1').classList.remove('hidden');" class="px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 transition">Back</button>
                    <button onclick="FlashcardManager.previewImport()" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition">Preview Import</button>
                </div>
            </div>

            <!-- Step 3: Preview & Confirm -->
            <div id="import-step-3" class="hidden">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                        <p class="text-lg font-bold text-gray-900" id="import-total">0</p>
                        <p class="text-xs text-gray-400">Total Rows</p>
                    </div>
                    <div class="bg-emerald-50 rounded-lg p-3 text-center">
                        <p class="text-lg font-bold text-emerald-600" id="import-valid">0</p>
                        <p class="text-xs text-gray-400">Valid</p>
                    </div>
                    <div class="bg-red-50 rounded-lg p-3 text-center">
                        <p class="text-lg font-bold text-red-500" id="import-invalid">0</p>
                        <p class="text-xs text-gray-400">Invalid</p>
                    </div>
                    <div class="bg-amber-50 rounded-lg p-3 text-center">
                        <p class="text-lg font-bold text-amber-500" id="import-duplicates">0</p>
                        <p class="text-xs text-gray-400">Duplicates</p>
                    </div>
                </div>

                <!-- Preview Table -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-3">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100">
                                <th class="px-3 py-2 text-xs font-semibold text-gray-500 w-10">#</th>
                                <th class="px-3 py-2 text-xs font-semibold text-gray-500">Term</th>
                                <th class="px-3 py-2 text-xs font-semibold text-gray-500">Definition</th>
                                <th class="px-3 py-2 text-xs font-semibold text-gray-500 w-24">Image</th>
                            </tr>
                        </thead>
                        <tbody id="import-preview-body"></tbody>
                    </table>
                </div>

                <div id="import-invalid-details"></div>

                <div class="flex items-center gap-3 mt-4">
                    <button onclick="document.getElementById('import-step-3').classList.add('hidden'); document.getElementById('import-step-2').classList.remove('hidden');" class="px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 transition">Back</button>
                    <button id="import-confirm-btn" onclick="FlashcardManager.confirmImport()" class="px-6 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Import
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════════════════════════════ -->
<script src="<?= APP_URL ?>/assets/js/flashcard-export.js"></script>
<script src="<?= APP_URL ?>/assets/js/flashcards.js"></script>
<script>
    // Close export dropdown on outside click
    document.addEventListener('click', (e) => {
        const wrapper = document.getElementById('export-wrapper');
        if (wrapper && !wrapper.contains(e.target)) {
            document.getElementById('export-menu').classList.remove('show');
        }
    });

    // Init
    FlashcardManager.init('<?= APP_URL ?>/api/flashcards.php', '<?= $csrfToken ?>');
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
