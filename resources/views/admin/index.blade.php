@extends('layouts.app')

@section('content')
<style>
    /* Mengubah lebar maksimum body agar konten admin bisa melebar */
    body > .container, body > .container-fluid {
        max-width: none !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    /* Mengubah lebar dan padding container utama hanya untuk admin */
    .admin-container {
        width: 100%;
        padding-top: 1.5rem;
        padding-bottom: 1.5rem;
        padding-left: 2rem;
        padding-right: 2rem;
    }

    /* Mengubah lebar elemen filter agar terlihat lebih rapi di layout lebar */
    .admin-filter-container {
        max-width: 80%;
        margin-left: auto;
        margin-right: auto;
    }

    @media (max-width: 768px) {
        .admin-filter-container {
            max-width: 100%;
        }
    }
</style>

<div class="admin-container">
    <div class="row justify-content-center mb-5 admin-filter-container">
        <div class="col-12">
            <form method="GET" action="{{ route('admin.index') }}">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-12 col-md-4">
                        <div class="input-group">
                            <label for="tanggal" class="input-group-text">Tanggal</label>
                            <input type="date" name="tanggal" id="tanggal" class="form-control" value="{{ $selectedDate }}">
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="input-group">
                            <label for="pic" class="input-group-text">PIC</label>
                            <select name="pic" id="pic" class="form-select">
                                <option value="">-- Semua PIC --</option>
                                <option value="erick">Erick</option>
                                <option value="lindy">Lindy</option>
                                <option value="mala">Mala</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary me-2 flex-grow-1 flex-md-grow-0">Filter</button>
                        <a href="{{ route('admin.index') }}" class="btn btn-danger flex-grow-1 flex-md-grow-0">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    @forelse($taskHeaders as $header)
        <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Tanggal</th>
                                <th scope="col">Agenda</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($header->tasks as $index => $task)
                                <tr class="{{ $task->status == 0 ? 'text-muted' : '' }}">
                                    <th scope="row">{{ $index + 1 }}</th>
                                    <th scope="row">{{ $task->created_at }}</th>
                                    <td>
                                        @if($task->is_favorite)
                                            <i class="bi bi-star-fill text-warning me-2"></i>
                                        @endif
                                        {{ $task->keterangan_task }}
                                    </td>
                                    <td>
                                        <span class="badge {{ $task->status == 1 ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $task->status == 1 ? 'Active' : 'Hide' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
    @empty
        <div class="alert alert-info text-center mt-5" role="alert">
            Belum ada agenda untuk PIC
        </div>
    @endforelse
</div>
@endsection