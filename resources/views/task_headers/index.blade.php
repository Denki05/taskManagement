@extends('layouts.app')

@section('content')
<div class="bg-dark p-3 rounded">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <button id="prevDate" class="btn btn-outline-light btn-sm">&lt;</button>
        <div class="text-center">
            <h5 id="dateTitle" class="mb-0">Today</h5>
            <small id="dateText" class="text-secondary">
                {{ \Carbon\Carbon::now()->format('d M Y') }}
            </small>
        </div>
        <div class="d-flex align-items-center">
            <button id="nextDate" class="btn btn-outline-light btn-sm me-2">&gt;</button>
            <button id="calendarBtn" class="btn btn-outline-light btn-sm me-2">
                <i class="bi bi-calendar-event"></i>
            </button>
            <button class="btn btn-outline-light btn-sm me-2" 
                data-bs-toggle="modal" data-bs-target="#createTaskModal">
                <i class="bi bi-plus-lg"></i>
            </button>
            <input type="date" id="datePicker" class="d-none">
        </div>
    </div>

    <div id="taskContainer">
        <ul id="taskList" class="list-unstyled">
            @foreach($taskHeaders as $header)
                @foreach ($header->tasks as $task)
                    <li class="mb-3" data-id="{{ $task->id }}">
                        <div class="p-3 bg-white rounded shadow-sm d-flex justify-content-between align-items-start text-dark"
                            data-id="{{ $header->id }}">
                            <div class="flex-grow-1">
                                <div class="fw-bold task-text">
                                    {{ $loop->iteration }}.
                                    <span class="short-text">
                                        {{ \Illuminate\Support\Str::limit($task->keterangan_task, 50, '') }}
                                    </span>
                                    @if(strlen($task->keterangan_task) > 50)
                                        <span class="full-text d-none">{{ $task->keterangan_task }}</span>
                                        <a href="#" class="read-more small text-primary">Lihat selengkapnya</a>
                                    @endif
                                </div>
                            </div>
                            <div class="dropdown ms-2">
                                <a href="#" class="text-dark" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots-vertical fs-5"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item toggle-favorite" href="#" data-id="{{ $task->id }}">
                                            <i class="bi bi-star{{ $task->is_favorite ? '-fill text-warning' : '' }}"></i>
                                            Favorite
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item move-to" href="#" data-id="{{ $task->id }}">
                                            <i class="bi bi-calendar-plus"></i> Move To
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item text-danger delete-task" href="#" data-id="{{ $task->id }}">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </li>
                @endforeach
            @endforeach
        </ul>
    </div>
</div>

<div class="modal fade" id="createTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="createTaskForm">
                <div class="modal-header">
                    <h5 class="modal-title text-dark">Buat agenda baru!</h5>
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
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 🔥 Pindahkan inisialisasi SortableJS ke dalam fungsi
    function initSortable() {
        new Sortable(document.getElementById('taskList'), {
            animation: 150,
            onEnd: function () {
                // ambil urutan terbaru
                let order = [];
                document.querySelectorAll('#taskList li[data-id]').forEach((el, index) => {
                    order.push({
                        id: el.dataset.id,
                        rank: index + 1
                    });
                });

                // kirim ke backend
                fetch('/task-lists/reorder', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ order: order })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // update nomor urut di UI
                        document.querySelectorAll('#taskList li[data-id]').forEach((el, index) => {
                            let numberEl = el.querySelector('.fw-bold');
                            if (numberEl) {
                                let textNode = numberEl.childNodes[0];
                                if (textNode.nodeType === Node.TEXT_NODE) {
                                    textNode.nodeValue = (index + 1) + '. ';
                                }
                            }
                        });
                    } else {
                        console.error('Reorder failed', data);
                    }
                }).catch(err => console.error(err));
            }
        });
    }

    // 🔥 Pindahkan semua event listener (termasuk .toggle-favorite, .delete-task, dll.)
    // ke dalam fungsi untuk memastikan event-event ini terpasang kembali setiap kali 
    // konten di update.
    function reattachEventListeners() {
        // Toggle Favorite
        document.querySelectorAll('.toggle-favorite').forEach(btn => {
            btn.removeEventListener('click', toggleFavoriteHandler);
            btn.addEventListener('click', toggleFavoriteHandler);
        });

        // Move To
        document.querySelectorAll('.move-to').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const taskId = this.dataset.id;
                document.getElementById('moveTaskId').value = taskId;
                const moveToModal = new bootstrap.Modal(document.getElementById('moveToModal'));
                moveToModal.show();
            });
        });

        // Delete
        document.querySelectorAll('.delete-task').forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.preventDefault();
                if (!confirm("Apakah kamu yakin menghapus agenda ini?")) return;

                const taskId = this.dataset.id;
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                try {
                    await fetch(`/task-lists/${taskId}/hide`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });
                    updateDateDisplay();
                } catch (err) {
                    console.error('Delete error:', err);
                    alert('Terjadi kesalahan saat menghapus task.');
                }
            });
        });

        // Read More
        document.querySelectorAll('.read-more').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const container = this.closest('.task-text');
                if (!container) return;

                const shortText = container.querySelector('.short-text');
                const fullText = container.querySelector('.full-text');
                if (shortText && fullText) {
                    fullText.classList.toggle('d-none');
                    shortText.classList.toggle('d-none');
                    this.textContent = fullText.classList.contains('d-none') ? 'Lihat selengkapnya' : 'Tutup';
                }
            });
        });
    }

    async function toggleFavoriteHandler(e) {
        e.preventDefault();

        const taskId = this.dataset.id;
        const icon = this.querySelector('i');
        if (!taskId || !icon) return;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const res = await fetch(`/task-lists/${taskId}/favorite`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            const data = await res.json();

            if (data.success) {
                // 🔹 Update ikon favorit
                if (data.is_favorite) {
                    icon.classList.remove('bi-star');
                    icon.classList.add('bi-star-fill', 'text-warning');
                } else {
                    icon.classList.remove('bi-star-fill', 'text-warning');
                    icon.classList.add('bi-star');
                }

                // 🔹 Reorder task list sesuai favorite_rank
                const ul = document.getElementById('taskList');
                if (data.favorites && data.favorites.length) {
                    // letakkan favorit terbaru di atas
                    data.favorites.slice().reverse().forEach(fav => {
                        const li = ul.querySelector(`li[data-id="${fav.id}"]`);
                        if (li) ul.insertBefore(li, ul.firstChild);
                    });
                }

                // 🔹 Update nomor urut di UI
                ul.querySelectorAll('li[data-id]').forEach((el, index) => {
                    const numberEl = el.querySelector('.fw-bold');
                    if (numberEl && numberEl.childNodes[0].nodeType === Node.TEXT_NODE) {
                        numberEl.childNodes[0].nodeValue = (index + 1) + '. ';
                    }
                });

                // 🔹 Update dataset agar tetap sinkron
                this.dataset.isFavorite = data.is_favorite ? 1 : 0;

            } else {
                console.error('Toggle favorite failed:', data.message);
                alert('Gagal mengubah status favorit.');
            }

        } catch (err) {
            console.error(err);
            alert('Terjadi kesalahan saat menghubungi server.');
        }
    }
   
    // 🔥 Modifikasi fungsi updateDateDisplay()
    const today = new Date();
    let activeDate = new Date(today);
    const dateTitle = document.getElementById("dateTitle");
    const dateText = document.getElementById("dateText");
    const prevBtn = document.getElementById("prevDate");
    const nextBtn = document.getElementById("nextDate");
    const calendarBtn = document.getElementById("calendarBtn");
    const datePicker = document.getElementById("datePicker");
    const taskContainer = document.getElementById("taskContainer");
    const moveToModal = new bootstrap.Modal(document.getElementById('moveToModal'));

    function updateDateDisplay() {
        const options = { day: '2-digit', month: 'short', year: 'numeric' };
        dateText.textContent = activeDate.toLocaleDateString("en-US", options);

        if (
            activeDate.getFullYear() === today.getFullYear() &&
            activeDate.getMonth() === today.getMonth() &&
            activeDate.getDate() === today.getDate()
        ) {
            dateTitle.textContent = "Today";
        } else {
            dateTitle.textContent = activeDate.toLocaleDateString("en-US", { weekday: 'long' });
        }

        const dateStr = activeDate.toISOString().split('T')[0];

        fetch(`/task-headers?date=${dateStr}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById("taskContainer");
            if (!data.success || data.taskHeaders.length === 0) {
                container.innerHTML = `
                    <div class="card bg-light text-center p-4">
                        <div class="text-muted">Belum ada agenda</div>
                    </div>`;
            } else {
                let html = `<ul id="taskList" class="list-unstyled">`;
                data.taskHeaders.forEach(header => {
                    header.tasks.forEach((task, i) => {
                        // 🔥 Perbaikan di sini: Gunakan is_favorite
                        const favoriteClass = task.is_favorite ? 'bi-star-fill text-warning' : 'bi-star';
                        const showMore = task.keterangan_task.length > 50;
                        const shortText = showMore ? task.keterangan_task.substring(0, 50) : task.keterangan_task;
                        const fullTextHtml = showMore ? `<span class="full-text d-none">${task.keterangan_task}</span><a href="#" class="read-more small text-primary">Lihat selengkapnya</a>` : '';

                        html += `
                            <li class="mb-3" data-id="${task.id}">
                                <div class="p-3 bg-white rounded shadow-sm d-flex justify-content-between align-items-start text-dark">
                                    <div class="flex-grow-1">
                                        <div class="fw-bold task-text">
                                            ${i + 1}.
                                            <span class="short-text">${shortText}</span>
                                            ${fullTextHtml}
                                        </div>
                                    </div>
                                    <div class="dropdown ms-2">
                                        <a href="#" class="text-muted" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical fs-5"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item toggle-favorite" href="#" data-id="${task.id}">
                                                    <i class="bi ${favoriteClass}"></i> Favorite
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item move-to" href="#" data-id="${task.id}">
                                                    <i class="bi bi-calendar-plus"></i> Move To
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger delete-task" href="#" data-id="${task.id}">
                                                    <i class="bi bi-trash"></i> Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        `;
                    });
                });
                html += `</ul>`;
                container.innerHTML = html;

                // 🔥 Panggil kembali inisialisasi Sortable dan event listeners
                initSortable();
                reattachEventListeners();
            }
        })
        .catch(err => {
            console.error("Load tasks failed:", err);
        });
    }

    // Tambahkan event listener untuk form yang perlu diinisialisasi sekali saja
    document.getElementById('moveToForm').addEventListener('submit', function (e) {
        e.preventDefault();
        let id = document.getElementById('moveTaskId').value;
        let date = document.getElementById('moveDate').value;
        fetch(`/task-headers/${id}/move`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ move_date: date })
        }).then(() => {
            moveToModal.hide();
            updateDateDisplay();
        });
    });

    const createTaskForm = document.getElementById('createTaskForm');
    const createTaskModalEl = document.getElementById('createTaskModal');
    const createTaskModal = new bootstrap.Modal(createTaskModalEl);

    createTaskForm.addEventListener('submit', function (e) {
        e.preventDefault();
        let formData = {
            keterangan_task: document.getElementById('taskKeterangan').value,
        };
        fetch('/task-lists', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                createTaskModal.hide();
                createTaskForm.reset();
                updateDateDisplay();
            } else {
                alert('Gagal menambahkan task!');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Terjadi kesalahan server!');
        });
    });

    // Event listener untuk tombol navigasi dan kalender
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

    // Inisialisasi tampilan awal
    updateDateDisplay();
});
</script>
@endsection