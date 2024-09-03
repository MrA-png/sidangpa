@extends('main')

<body>
@include('header')
@include('sidebar')

<main id="main" class="main">

  <div class="row">
    <div class="col">
      <div class="pagetitle">
        <h1>Data Proyek Akhir</h1>
        <nav>
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.html">Home</a></li>
            <li class="breadcrumb-item">Tables</li>
            <li class="breadcrumb-item active">Data</li>
          </ol>
        </nav>
      </div><!-- End Page Title -->    
    </div>
    <div class="col" style="padding-left: 500px; padding-top: 10px;">
      <div class="row">
        <div class="col-auto">
          <button class="btn btn-light" type="button" data-bs-toggle="modal" data-bs-target="#filterUmurModal">
            Filter Umur
          </button>
        </div>
        <div class="col-5 dropdown">
          <button class="form-select btn btn-warning" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            Dosen
          </button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ route('proyek-akhir.data', ['id_master' => $id_master]) }}">Pilih Dosen</a></li>
            @foreach($dosen as $d)
                <li><a class="dropdown-item" href="{{ route('proyek-akhir.data.filterByDosen', ['id_master' => $id_master, 'id_dosen' => $d['id_dosen']]) }}">{{ $d['nama_dosen'] }}</a></li>
            @endforeach
          </ul>
        </div>
    </div>
  </div>

  <!-- Modal for Filter Umur -->
<div class="modal fade bd-example-modal-lg" id="filterUmurModal" tabindex="-1" aria-labelledby="filterUmurLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filterUmurLabel">Filter Umur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="GET" action="{{ route('proyek-akhir.data.filterByUmur', ['id_master' => $id_master]) }}">
                <div class="modal-body">
                    <div class="filter-group">
                        <div class="row">
                            <div class="col-3">
                                <select class="form-select" name="jenis_kelamin">
                                    <option value="Laki-Laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-3">
                                <select class="form-select" name="operator" id="operator-select" onchange="toggleInputFields()">
                                    <option value="<">&lt;</option>
                                    <option value=">">&gt;</option>
                                    <option value="<=">&lt;=</option>
                                    <option value=">=">&gt;=</option>
                                    <option value="=">=</option>
                                    <option value="between">Antara</option>
                                </select>
                            </div>
                            <div class="col-3">
                                <input type="number" class="form-control" name="value1" id="value-input-1" placeholder="Nilai Awal">
                            </div>
                            <div class="col-3">
                                <input type="number" class="form-control" name="value2" id="value-input-2" placeholder="Nilai Batas" style="display: none;">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                </div>
            </form>
        </div>
    </div>
</div>

  <section class="section">
    <div class="row justify-content-center">
      <div class="col-lg-12">

        <div class="card">
          <div class="card-body">
            <h5 class="card-title"></h5>

            @foreach($data_pa as $master)
              <p class="desc-generate text-center">
                <b>{{ $master['jurusan'] ?? 'Jurusan Tidak Ditemukan' }} | Tahun ajaran: {{ $master['tahun_ajaran'] ?? 'Tahun Ajaran Tidak Ditemukan' }}</b>
              </p>

              <div class="row">
                <div class="col-md-6"></div>
                <div class="col-md-6 pb-3" style="padding-left: 350px">
                  <a type="button" href="{{ route('proyek-akhir.data.tambah_pa', ['id_master' => $id_master]) }}" class="btn btn-success" style="background-color: #04BC00;">
                    + Tambah Proyek Akhir
                  </a>
                </div>
              </div>
              
              <table class="table table-bordered">
                <thead class="table-light">
                  <tr>
                  <th class="judul-tabel">Umur</th>
                  <th class="judul-tabel">Jenis Kelamin</th>
                    <th class="judul-tabel">Nama Mahasiswa</th>
                    <th class="judul-tabel">Judul Proyek Akhir</th>
                    <th class="judul-tabel">Pembimbing 1</th>
                    <th class="judul-tabel">Pembimbing 2</th>
                    <th class="judul-tabel">Pembimbing 3</th>
                    <th class="aksi">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($master['proyek_akhir'] ?? [] as $data)
                    <tr>
                      <td class="isi-tabel">{{ $data['umur'] }}</td>
                      <td class="isi-tabel">{{ $data['jenis_kelamin'] }}</td>
                      <td class="isi-tabel">{{ $data['nama_mahasiswa'] }}</td>
                      <td class="isi-tabel">{{ $data['judul_pa'] }}</td>
                      <td class="isi-tabel">{{ $data['dosen_pembimbing1']['nama_dosen'] ?? '-' }}</td>
                      <td class="isi-tabel">{{ $data['dosen_pembimbing2']['nama_dosen'] ?? '-' }}</td>
                      <td class="isi-tabel">{{ $data['dosen_pembimbing3']['nama_dosen'] ?? '-' }}</td>
                      <td>
                        <div class="d-flex">
                          <form>
                            <a href="{{ route('proyek-akhir.data.editDataProyek', $data['id_mhs']) }}" class="btn aksi-edit btn-primary btn-sm me-2">Edit</a>
                          </form>
                          <form action="{{ route('proyek-akhir.data.deleteDataProyek', $data['id_mhs']) }}" method="post">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn aksi-hapus btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">Hapus</button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  @endforeach
                @endforeach
                </tbody>
              </table>
          </div>
        </div>
      </div>
    </div>
  </section>

</main><!-- End #main -->

@include('footer')

<a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

<script>
function toggleInputFields() {
    const operator = document.getElementById("operator-select").value;
    const valueInput2 = document.getElementById("value-input-2");

    if (operator === "between") {
        valueInput2.style.display = "block";
    } else {
        valueInput2.style.display = "none";
    }
}
</script>


</body>

</html>
