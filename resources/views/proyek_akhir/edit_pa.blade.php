<!DOCTYPE html>
@extends('main')

<body>
@include('header')
@include('sidebar')

<main id="main" class="main">

    <div class="pagetitle">
        <h1>Edit Data</h1>
        <nav>
          <ol class="breadcrumb">
            <li class="breadcrumb-item">Dashboard</li>
            <li class="breadcrumb-item">Proyek Akhir</li>
            <li class="breadcrumb-item">Data</li>
            <li class="breadcrumb-item active">Edit</li>
          </ol>
        </nav>
      </div><!-- End Page Title -->

      <section class="section">
        <div class="row">
          <div class="col-lg-12">
                <div class="card">
                  <div class="card-body p-5">

                    <!-- Vertical Form -->
                    {{-- <form method="POST" action="{{ route('pengumuman.tambahPengumuman') }}">
                      @csrf --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label for="nrp_mahasiswa" class="form-label">NRP Mahasiswa</label>
                                    <input type="text" class="form-control" id="nrp_mahasiswa" name="nrp_mahasiswa">
                                </div>
                                <!-- Tambah input untuk nama mnahasiswa -->
                                <div class="mb-2">
                                    <label for="nama_mahasiswa" class="form-label">Nama Mahasiswa</label>
                                    <input type="text" class="form-control" id="nama_mahasiswa" name="nama_mahasiswa">
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
                                    <label for="inputState" class="form-label">Dosen Pembimbing 1</label>
                                  <select name="prodi" id="inputState" class="form-select" placeholder="Pilihlah program studi">
                                      <option selected disabled>Dosen Pembimbing</option>
                                      <option>D3 Teknik Informatika</option>
                                  </select>
                                </div>
                                <div class="mb-3">
                                    <label for="inputState" class="form-label">Dosen Pembimbing 2</label>
                                  <select name="prodi" id="inputState" class="form-select" placeholder="Pilihlah program studi">
                                      <option selected disabled>Dosen Pembimbing</option>
                                      <option>D3 Teknik Informatika</option>
                                  </select>
                                </div>
                                <div class="mb-3">
                                    <label for="inputState" class="form-label">Dosen Pembimbing 3</label>
                                  <select name="prodi" id="inputState" class="form-select" placeholder="Pilihlah program studi">
                                      <option selected disabled>Dosen Pembimbing</option>
                                      <option>D3 Teknik Informatika</option>
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
