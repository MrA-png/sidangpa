<!DOCTYPE html>
@extends('main')

<body>
@include('header')
@include('sidebar')

<main id="main" class="main">
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
  {{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

    <div class="pagetitle">
        <h1>Tambah Data</h1>
        <nav>
          <ol class="breadcrumb">
            <li class="breadcrumb-item">Dashboard</li>
            <li class="breadcrumb-item">Proyek Akhir</li>
            <li class="breadcrumb-item">Data</li>
            <li class="breadcrumb-item active">Tambah</li>
          </ol>
        </nav>
      </div><!-- End Page Title -->

      <section class="section">
        <div class="row">
          <div class="col-lg-12">
                <div class="card">
                  <div class="card-body p-5">

                    <!-- Vertical Form -->
                    <form method="POST" action="{{ route('proyek-akhir.data.tambahDataProyek', ['id_master' => $id_master]) }}">
                      @csrf
                      <input type="hidden" name="id_master" value="{{ $id_master }}">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label for="nrp_mahasiswa" class="form-label">NRP Mahasiswa</label>
                                    <input type="text" class="form-control" id="nrp_mahasiswa" name="nrp_mahasiswa">
                                </div>
                                <!-- Tambah input untuk nama mahasiswa -->
                                <div class="mb-2">
                                    <label for="nama_mahasiswa" class="form-label">Nama Mahasiswa</label>
                                    <input type="text" class="form-control" id="nama_mahasiswa" name="nama_mahasiswa">
                                </div>
                                <div class="mb-2">
                                  <label for="jenis_kelamin" class="form-label">Jenis Kelamin Mahasiswa</label>
                                  <select class="form-control" id="jenis_kelamin" name="jenis_kelamin">
                                    <option value="" disabled selected>Pilih Jenis Kelamin</option>
                                    <option value="Laki-Laki">Laki-Laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                  </select>
                                </div>
                                <div class="mb-2">
                                    <label for="umur" class="form-label">Umur Mahasiswa</label>
                                    <input type="text" class="form-control" id="umur" name="umur">
                                </div>
                                <!-- Tambah input untuk judul pa-->
                                <div class="mb-2">
                                    <label for="judul_pa" class="form-label">Judul Proyek Akhir</label>
                                    <textarea class="form-control" id="judul_pa" name="judul_pa"></textarea>
                                </div>
                              </div>
                              <!-- Tambah dropdown untuk pilihan dosen pembimbing -->
                              <div class="col-md-6">
                                <div class="mb-3">
                                  <label for="dosen_pembimbing1">Dosen Pembimbing 1</label>
                                  <select name="dosen_pembimbing1" id="dosen_pembimbing1" class="form-control">
                                    <option value="">-- Pilih Dosen --</option>
                                    @foreach ($dosen as $dosenItem)
                                        <option value="{{ $dosenItem['id_dosen'] }}">{{ $dosenItem['nama_dosen'] }}</option>
                                    @endforeach
                                  </select>
                                </div>
                                <div class="mb-3">
                                  <label for="dosen_pembimbing2">Dosen Pembimbing 2</label>
                                  <select name="dosen_pembimbing2" id="dosen_pembimbing2" class="form-control">
                                    <option value="">-- Pilih Dosen --</option>
                                    @foreach ($dosen as $dosenItem)
                                        <option value="{{ $dosenItem['id_dosen'] }}">{{ $dosenItem['nama_dosen'] }}</option>
                                    @endforeach
                                  </select>
                                </div>
                                <div class="mb-3">
                                  <label for="dosen_pembimbing3">Dosen Pembimbing 3</label>
                                  <select name="dosen_pembimbing3" id="dosen_pembimbing3" class="form-control">
                                    <option value="">-- Pilih Dosen --</option>
                                    @foreach ($dosen as $dosenItem)
                                        <option value="{{ $dosenItem['id_dosen'] }}">{{ $dosenItem['nama_dosen'] }}</option>
                                    @endforeach
                                  </select>
                                </div>
                              </div>
                        </div>

                      <div class="col-md-12 d-flex justify-content-end align-items-end pt-5">
                        <button type="reset" class="btn btn-secondary" style="margin-right: 20px;">Reset</button>
                        <button type="submit" class=" btn btn-primary">Submit</button>
                      </div>
                    </form><!-- Vertical Form -->
      
                  </div>
                </div>

          </div>
        </div>
      </section>

      @include('footer')

<a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

</body>

</html>

</main>
