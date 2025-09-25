@extends('layouts.app')

@section('content')
<style>
    .task-text .full-text {
        display: block;
        margin-top: 5px;
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: anywhere;
    }
    
    .task-item.task-done .p-3 {
        background-color: #d4edda !important; /* Warna hijau muda */
        border: 1px solid #c3e6cb;
    }

    .task-item.task-done .task-text,
    .task-item.task-done .fw-bold,
    .task-item.task-done .text-muted {
        color: #6c757d !important; /* Ubah warna teks menjadi abu-abu */
    }

    .task-item.task-done .toggle-favorite,
    .task-item.task-done .move-to,
    .task-item.task-done .delete-task {
        opacity: 0.5;
        pointer-events: none; /* Nonaktifkan klik */
    }
</style>

<div class="modal fade" id="syncAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-dark">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Perubahan Tertunda
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-dark">
                Ada perubahan yang belum disinkronkan. Silakan sinkronkan sekarang untuk menyimpan data Anda.
                <br><small class="text-muted" id="pending-count-modal"></small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Nanti Saja</button>
                <button type="button" class="btn btn-warning btn-sm" id="syncBtnModal">Sinkronkan Sekarang</button>
            </div>
        </div>
    </div>
</div>

<div class="bg-dark p-3 rounded">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center">
            <button class="btn btn-outline-light btn-sm me-2">
                <i class="bi bi-grid-3x3-gap"></i>
            </button>
            <button id="prevDate" class="btn btn-outline-light btn-sm">&lt;</button>
        </div>
    
        <div class="text-center d-flex justify-content-center align-items-center">
            <span id="dateTitle" class="fw-semibold"></span>
        </div>
    
        <div class="d-flex align-items-center">
            <button id="nextDate" class="btn btn-outline-light btn-sm me-2">&gt;</button>
            <button id="calendarBtn" class="btn btn-outline-light btn-sm me-2">
                <i class="bi bi-calendar-event"></i>
            </button>
            <button id="moveAllBtn" class="btn btn-outline-light btn-sm me-2" 
                data-bs-toggle="modal" data-bs-target="#moveAllModal">
                <i class="bi bi-arrow-return-right"></i>
            </button>
            <input type="date" id="datePicker" class="d-none">
        </div>
    </div>

    <div id="taskContainer">
        </div>
</div>

<div class="modal fade" id="createTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="createTaskForm">
                <div class="modal-header d-flex justify-content-between align-items-center">
                    <h5 class="modal-title text-dark">
                        Buat agenda baru!
                        <small class="text-muted d-block fs-6" id="modalTaskDate"></small>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @csrf
                    <div class="mb-3">
                        <textarea class="form-control" name="keterangan_task" id="taskKeterangan" rows="2" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="moveToModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="moveToForm">
                <div class="modal-header">
                    <h5 class="modal-title text-dark">Pindah Agenda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @csrf
                    <input type="hidden" id="moveTaskId" name="task_id">
                    <div class="mb-3">
                        <input type="date" class="form-control" id="moveDate" name="move_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Move</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="moveAllModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="moveAllForm">
                <div class="modal-header">
                    <h5 class="modal-title text-dark">Pindahkan Semua Agenda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @csrf
                    <p id="moveAllConfirmationText"></p>
                    <div class="mb-3">
                        <label for="moveAllDate" class="form-label">Pilih Tanggal Tujuan:</label>
                        <input type="date" class="form-control" id="moveAllDate" name="move_all_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Pindahkan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const SYNC_INTERVAL = 30000; // 30 detik
    let syncTimer = null;
    let syncDelayTimer = null;
    let activeDate = new Date();
    const today = new Date();
    const dateTitle = document.getElementById("dateTitle");
    const prevBtn = document.getElementById("prevDate");
    const nextBtn = document.getElementById("nextDate");
    const calendarBtn = document.getElementById("calendarBtn");
    const datePicker = document.getElementById("datePicker");
    const taskContainer = document.getElementById("taskContainer");
    
    // Inisialisasi modal secara global
    const syncAlertModalEl = document.getElementById('syncAlertModal');
    const syncAlertModal = new bootstrap.Modal(syncAlertModalEl);
    const createTaskModalEl = document.getElementById('createTaskModal');
    const createTaskModal = new bootstrap.Modal(createTaskModalEl);
    const moveToModalEl = document.getElementById('moveToModal');
    const moveToModal = new bootstrap.Modal(moveToModalEl);
    const moveAllModalEl = document.getElementById('moveAllModal');
    const moveAllModal = new bootstrap.Modal(moveAllModalEl);

    // --- Fungsi Bantuan untuk Logika Sinkronisasi ---

    function updateSyncNotification(showModalImmediately = false) {
        const pendingChanges = JSON.parse(localStorage.getItem('pending_task_changes') || '[]');
        const modalBody = syncAlertModalEl.querySelector('.modal-body');

        if (pendingChanges.length > 0) {
            modalBody.innerHTML = `Ada ${pendingChanges.length} perubahan yang belum disinkronkan. Silakan sinkronkan sekarang untuk menyimpan data Anda.`;
            
            // Tampilkan modal jika kondisi terpenuhi
            if (showModalImmediately) {
                syncAlertModal.show();
            }

            // Atur interval untuk menampilkan modal setiap 30 detik
            if (!syncTimer) {
                syncTimer = setInterval(() => {
                    if (pendingChanges.length > 0) {
                        syncAlertModal.show();
                    } else {
                        clearInterval(syncTimer);
                        syncTimer = null;
                    }
                }, SYNC_INTERVAL);
            }
        } else {
            // Sembunyikan modal dan hapus timer jika tidak ada perubahan
            syncAlertModal.hide();
            if (syncTimer) {
                clearInterval(syncTimer);
                syncTimer = null;
            }
        }
    }
    
    // Event listener untuk tombol 'Nanti Saja' pada modal
    syncAlertModalEl.addEventListener('hide.bs.modal', function () {
        // Ketika modal ditutup, timer tetap berjalan
    });

    function addChangeToQueue(change) {
        let pendingChanges = JSON.parse(localStorage.getItem('pending_task_changes') || '[]');
        pendingChanges.push(change);
        localStorage.setItem('pending_task_changes', JSON.stringify(pendingChanges));
        
        // Reset timer dan atur ulang untuk memunculkan notifikasi setelah 30 detik
        if (syncDelayTimer) {
            clearTimeout(syncDelayTimer);
        }
        syncDelayTimer = setTimeout(() => {
            updateSyncNotification(true);
        }, SYNC_INTERVAL);
        
        // Perbarui UI secara instan dengan perubahan
        updateDateDisplay();
    }

    async function syncChanges() {
        const pendingChanges = JSON.parse(localStorage.getItem('pending_task_changes') || '[]');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        if (pendingChanges.length === 0) {
            alert('Tidak ada perubahan untuk disinkronkan.');
            return;
        }

        try {
            const res = await fetch('{{ route('tasks.sync') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ changes: pendingChanges })
            });
            const data = await res.json();

            if (data.success) {
                alert(data.message);
                localStorage.removeItem('pending_task_changes');
                if (syncTimer) {
                    clearInterval(syncTimer);
                    syncTimer = null;
                }
                if (syncDelayTimer) {
                    clearTimeout(syncDelayTimer);
                    syncDelayTimer = null;
                }
                syncAlertModal.hide();
                // Muat ulang tampilan setelah sinkronisasi berhasil
                updateDateDisplay(); 
            } else {
                alert(data.message || 'Gagal menyinkronkan perubahan.');
            }
        } catch (err) {
            console.error(err);
            alert('Terjadi kesalahan jaringan. Silakan coba lagi.');
        }
    }

    // Event listener untuk tombol sinkronisasi di modal
    document.getElementById('syncBtnModal').addEventListener('click', syncChanges);

    // --- Fungsi Baru: Menerapkan perubahan lokal ke data yang diambil dari server ---
    function applyPendingChangesToUI(taskHeaders, dateStr) {
        const pendingChanges = JSON.parse(localStorage.getItem('pending_task_changes') || '[]');
        let modifiedTasks = taskHeaders.flatMap(header => header.tasks);

        // Filter dan hapus item yang di-hide atau di-move
        modifiedTasks = modifiedTasks.filter(task => {
            const hideChange = pendingChanges.find(c => c.action === 'hide' && c.id == task.id);
            const moveChange = pendingChanges.find(c => c.action === 'move' && c.id == task.id && c.source_date === dateStr);
            return !hideChange && !moveChange;
        });

        // Terapkan perubahan lain (done, favorite)
        modifiedTasks.forEach(task => {
            const doneChange = pendingChanges.find(c => c.action === 'done' && c.id == task.id);
            if (doneChange) {
                task.status = 2;
            }
            const favoriteChange = pendingChanges.find(c => c.action === 'favorite' && c.id == task.id);
            if (favoriteChange) {
                task.is_favorite = favoriteChange.is_favorite;
            }
        });
        
        // Terapkan reorder
        const reorderChange = pendingChanges.find(c => c.action === 'reorder');
        if (reorderChange) {
            // Urutkan ulang berdasarkan urutan lokal
            const newOrder = reorderChange.order;
            modifiedTasks.sort((a, b) => {
                const rankA = newOrder.find(item => item.id == a.id)?.rank || 9999;
                const rankB = newOrder.find(item => item.id == b.id)?.rank || 9999;
                return rankA - rankB;
            });
        }
        
        // Periksa moveAll
        const moveAllChange = pendingChanges.find(c => c.action === 'moveAll' && c.source_date === dateStr);
        if (moveAllChange) {
            return []; // Kosongkan tampilan jika moveAll dilakukan untuk tanggal ini
        }

        return modifiedTasks;
    }

    // --- Event Delegation pada taskContainer ---
    taskContainer.addEventListener('click', function (e) {
        // Toggle Favorite
        if (e.target.closest('.toggle-favorite')) {
            e.preventDefault();
            const btn = e.target.closest('.toggle-favorite');
            const taskId = btn.dataset.id;
            const isFavorite = btn.dataset.isFavorite === '1';
            const newIsFavorite = !isFavorite;

            // Tambahkan perubahan ke antrian
            addChangeToQueue({
                action: 'favorite',
                id: taskId,
                is_favorite: newIsFavorite
            });
        }

        // Mark Done
        if (e.target.closest('.complete-task')) {
            e.preventDefault();
            const btn = e.target.closest('.complete-task');
            const taskId = btn.dataset.id;
            
            // Tambahkan perubahan ke antrian
            addChangeToQueue({
                action: 'done',
                id: taskId,
                status: 2
            });
        }

        // Delete
        if (e.target.closest('.delete-task')) {
            e.preventDefault();
            const btn = e.target.closest('.delete-task');
            const taskId = btn.dataset.id;
            
            if (confirm("Apakah kamu yakin menghapus agenda ini?")) {
                // Tambahkan perubahan ke antrian
                addChangeToQueue({
                    action: 'hide',
                    id: taskId
                });
            }
        }
        
        // Move To
        if (e.target.closest('.move-to')) {
            e.preventDefault();
            const taskId = e.target.closest('.move-to').dataset.id;
            document.getElementById('moveTaskId').value = taskId;
            moveToModal.show();
        }

        // Read More
        if (e.target.closest('.read-more')) {
            e.preventDefault();
            const container = e.target.closest('.task-text');
            if (!container) return;

            const shortText = container.querySelector('.short-text');
            const fullText = container.querySelector('.full-text');
            const btn = e.target.closest('.read-more');

            fullText.classList.toggle('d-none');
            shortText.classList.toggle('d-none');
            btn.textContent = fullText.classList.contains('d-none') ? 'selengkapnya..' : 'Tutup';
        }

        // Handle Add Task button click
        if (e.target.closest('#addTaskBtn')) {
            updateModalTaskDate();
            document.getElementById("taskKeterangan").value = "";
            createTaskModal.show();
        }
    });

    // --- Fungsi Utilitas yang Diubah untuk Optimistic UI ---
    function initSortable() {
        const taskListEl = document.getElementById('taskList');
        if (!taskListEl) return;
        
        new Sortable(taskListEl, {
            animation: 150,
            handle: '.drag-handle',
            onEnd: function () {
                let order = [];
                taskListEl.querySelectorAll('li[data-id]').forEach((el, index) => {
                    const taskId = el.dataset.id;
                    const rank = index + 1;
                    
                    // Update UI nomor urut secara instan
                    const numberEl = el.querySelector('.fw-bold');
                    if (numberEl && numberEl.childNodes[0].nodeType === Node.TEXT_NODE) {
                        numberEl.childNodes[0].nodeValue = `${rank}. `;
                    }

                    order.push({ id: taskId, rank: rank });
                });
                
                // Tambahkan perubahan reorder ke antrian
                addChangeToQueue({
                    action: 'reorder',
                    order: order
                });
            }
        });
    }
    
    // --- Fungsi Utama untuk Mengelola UI dan Data ---
    function updateDateDisplay() {
        const optionsDate = { day: '2-digit', month: 'short' };
        const optionsWeekday = { weekday: 'long' };
        let titleText = "";
        
        if (activeDate.getFullYear() === today.getFullYear() && activeDate.getMonth() === today.getMonth() && activeDate.getDate() === today.getDate()) {
            titleText = "Today, " + activeDate.toLocaleDateString("en-US", optionsDate);
        } else {
            titleText = activeDate.toLocaleDateString("en-US", optionsWeekday) + ", " + activeDate.toLocaleDateString("en-US", optionsDate);
        }
        dateTitle.textContent = titleText;
        const dateStr = activeDate.toISOString().split("T")[0];

        // Fetch data dari server
        fetch(`/task-headers?date=${dateStr}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById("taskContainer");
            let html = "";
            
            // Panggil fungsi baru untuk memproses data
            const modifiedTasks = applyPendingChangesToUI(data.taskHeaders, dateStr);

            html += `<div class="mb-3">
                <button id="addTaskBtn" class="btn w-100 btn-light text-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                    TAMBAH AGENDA
                </button>
            </div>`;
            
            // Logic yang diperbarui untuk menampilkan pesan jika tidak ada agenda
            if (modifiedTasks.length > 0) {
                html += `<ul id="taskList" class="list-unstyled">`;
                let index = 1;
                modifiedTasks.forEach(task => {
                    const isLong = task.keterangan_task && task.keterangan_task.length > 50;
                    const shortText = isLong ? task.keterangan_task.substring(0, 50) + '...' : (task.keterangan_task || '');
                    const isDone = task.status == 2;

                    html += `
                        <li class="mb-3 task-item ${isDone ? 'task-done' : ''}" data-id="${task.id}" data-is-favorite="${task.is_favorite ? '1' : '0'}">
                            <div class="p-3 bg-white rounded shadow-sm d-flex justify-content-between align-items-center text-dark"
                                data-task-id="${task.id}">
                                
                                <div class="d-flex flex-column align-items-center me-3">
                                    <a href="#" class="toggle-favorite text-dark mb-2" data-id="${task.id}" data-is-favorite="${task.is_favorite ? '1' : '0'}">
                                        <i class="${task.is_favorite ? 'bi bi-star-fill text-warning' : 'bi bi-star'}"></i>
                                    </a>
                                    <span class="fw-bold">${index}. </span>
                                    <a href="#" class="delete-task text-dark mt-2" data-id="${task.id}">
                                        <i class="bi bi-x-lg"></i>
                                    </a>
                                </div>
                                
                                <div class="flex-grow-1 text-center px-2">
                                    <div class="fw-bold task-text">
                                        <span class="short-text ${isLong ? '' : 'd-none'}">${shortText}</span>
                                        <span class="full-text ${isLong ? 'd-none' : ''}">${task.keterangan_task}</span>
                                        ${ isLong ? `<a href="#" class="read-more small text-primary">selengkapnya..</a>` : '' }
                                    </div>
                                </div>
                                
                                <div class="d-flex flex-column align-items-center ms-3">
                                    ${!isDone ? `<a href="#" class="text-success mb-2 complete-task" data-id="${task.id}"><i class="bi bi-check-lg"></i></a>` : ''}
                                    <a href="#" class="move-to text-dark mb-2" data-id="${task.id}">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                    
                                    <div class="drag-handle" title="Drag untuk urutkan">
                                        <i class="bi bi-three-dots-vertical fs-5 text-muted"></i>
                                    </div>
                                </div>
                            </div>
                        </li>
                    `;
                    index++;
                });
                html += `</ul>`;
            } else {
                html += `<div class="card bg-light text-center p-4"><div class="text-muted">Belum ada agenda</div></div>`;
            }
            container.innerHTML = html;
            initSortable();
        })
        .catch(err => {
            console.error("Load tasks failed:", err);
        });
        updateModalTaskDate();
    }
    
    // --- Modifikasi Form dan Modal untuk Menggunakan Pending Update ---

    document.getElementById('createTaskForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const keterangan = document.getElementById('taskKeterangan').value;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const dateStr = activeDate.toISOString().split("T")[0];

        try {
            const res = await fetch('{{ route('task-lists.store') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    keterangan_task: keterangan,
                    date: dateStr 
                })
            });

            const data = await res.json();
            if (data.success) {
                // Berhasil, muat ulang tampilan
                updateDateDisplay();
            } else {
                alert(data.message || 'Gagal menyimpan agenda.');
            }
        } catch (err) {
            console.error('Save task failed:', err);
            alert('Terjadi kesalahan jaringan atau server. Silakan coba lagi.');
        }

        document.getElementById('taskKeterangan').value = '';
        createTaskModal.hide();
    });

    document.getElementById('moveToForm').addEventListener('submit', function (e) {
        e.preventDefault();
        let taskId = document.getElementById('moveTaskId').value;
        let moveDate = document.getElementById('moveDate').value;
        
        // Tambahkan aksi ke antrian
        addChangeToQueue({
            action: 'move',
            id: taskId,
            move_date: moveDate,
            source_date: activeDate.toISOString().split("T")[0]
        });
        
        moveToModal.hide();
    });

    document.getElementById('moveAllForm').addEventListener('submit', function (e) {
        e.preventDefault();
        
        let sourceDate = activeDate.toISOString().split("T")[0];
        let targetDate = document.getElementById('moveAllDate').value;
        
        if (!targetDate) {
            alert('Silakan pilih tanggal tujuan.');
            return;
        }
        if (sourceDate === targetDate) {
            alert('Tanggal tujuan tidak boleh sama dengan tanggal saat ini.');
            return;
        }
        
        // Tambahkan aksi ke antrian
        addChangeToQueue({
            action: 'moveAll',
            source_date: sourceDate,
            target_date: targetDate
        });

        moveAllModal.hide();
    });

    // --- Inisialisasi Event Listener Navigasi ---
    
    prevBtn.addEventListener("click", () => {
        activeDate.setDate(activeDate.getDate() - 1);
        updateDateDisplay();
    });

    nextBtn.addEventListener("click", () => {
        activeDate.setDate(activeDate.getDate() + 1);
        updateDateDisplay();
    });

    calendarBtn.addEventListener("click", () => {
        if (datePicker.showPicker) {
            datePicker.showPicker();
        } else {
            datePicker.click();
        }
    });

    datePicker.addEventListener("change", (e) => {
        if (!e.target.value) return;
        activeDate = new Date(e.target.value);
        updateDateDisplay();
    });

    // --- Fungsi Bantuan Modal ---

    function updateModalTaskDate() {
        const options = { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' };
        const formatted = activeDate.toLocaleDateString("id-ID", options);
        document.getElementById("modalTaskDate").textContent = formatted;
    }
    
    // --- Inisialisasi Awal ---
    updateDateDisplay();
    updateSyncNotification();
});
</script>
@endsection