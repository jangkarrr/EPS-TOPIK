    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-100 bg-white px-6 py-4">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-gray-400">
            <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
            <p>Prepare smarter for EPS-TOPIK 🇰🇷</p>
        </div>
    </footer>
</div>

<!-- Sidebar Toggle Script -->
<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}

// Close sidebar on escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar.classList.contains('-translate-x-full')) {
            toggleSidebar();
        }
    }
});

// Modal helper
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.body.style.overflow = '';
}

// Toast notification
function showToast(message, type = 'success') {
    const colors = {
        success: 'bg-green-50 border-green-200 text-green-800',
        error: 'bg-red-50 border-red-200 text-red-800',
        info: 'bg-blue-50 border-blue-200 text-blue-800',
        warning: 'bg-amber-50 border-amber-200 text-amber-800'
    };
    const toast = document.createElement('div');
    toast.className = `toast fixed top-4 right-4 z-50 max-w-sm px-4 py-3 rounded-xl shadow-lg border ${colors[type] || colors.info}`;
    toast.innerHTML = `<div class="flex items-center gap-2"><span class="text-sm font-medium">${message}</span><button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 5000);
}
</script>
</body>
</html>
