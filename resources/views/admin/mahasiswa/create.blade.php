@extends('layouts.app')

@section('content')
    <div class="container-lg mb-5">
        <div class="row">
            <div class="col">
                <h3>Tambah Data Mahasiswa</h3>
            </div>
        </div>
        <div class="row justify-content-center mt-4">
            <div class="col-4">
                <form action="{{ route('admin.mahasiswa.store') }}" method="post" autocomplete="on">
                    @csrf
                    <div class="mb-3">
                        <label for="nim" class="form-label">NIM<span class="text-danger ms-1">*</span></label>
                        <input type="number" class="form-control @error('nim') is-invalid @enderror" id="nim"
                            name="nim" value="{{ old('nim') }}" autofocus>
                        @error('nim')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama<span class="text-danger ms-1">*</span></label>
                        <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama"
                            name="nama" value="{{ old('nama') }}">
                        @error('nama')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                            name="email" value="{{ old('email') }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                            name="password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input @error('status_aktif') is-invalid @enderror"
                            id="status_aktif" name="status_aktif" value="1">
                        <label class="form-check-label" for="status_aktif">Status Aktif</label>
                        @error('status_aktif')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.mahasiswa.index') }}" class="btn btn-secondary">Kembali</a>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
