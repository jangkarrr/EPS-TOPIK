    </main>
    <footer class="border-t border-gray-100 bg-white px-6 py-4">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-gray-400">
            <p>&copy; <?= date('Y') ?> <?= APP_NAME ?> Admin Panel</p>
            <p>v<?= APP_VERSION ?></p>
        </div>
    </footer>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar.classList.contains('-translate-x-full')) toggleSidebar();
    }
});
function openModal(id) { document.getElementById(id).classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); document.body.style.overflow = ''; }
function showToast(message, type = 'success') {
    const colors = { success: 'bg-green-50 border-green-200 text-green-800', error: 'bg-red-50 border-red-200 text-red-800', info: 'bg-blue-50 border-blue-200 text-blue-800' };
    const toast = document.createElement('div');
    toast.className = `toast fixed top-4 right-4 z-50 max-w-sm px-4 py-3 rounded-xl shadow-lg border ${colors[type] || colors.info}`;
    toast.innerHTML = `<div class="flex items-center gap-2"><span class="text-sm font-medium">${message}</span></div>`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 5000);
}
function confirmDelete(url, name) {
    if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
        window.location.href = url;
    }
}
</script>
</body>
</html>
