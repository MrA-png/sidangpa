<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProyekAkhirExportMahasiswa;

class DataProyekAkhirController extends Controller
{
    public function getDataProyek($id_master)
    {
        $response = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
        ])->get($this->supabaseUrl . '/rest/v1/master_pa', [
            'id_master' => 'eq.' . $id_master,
            'select' => '*,proyek_akhir(*,dosen_pembimbing1(*),dosen_pembimbing2(*),dosen_pembimbing3(*))'
        ]);

        $data_pa = $response->json();

        // dd($data_pa);

        $dosenResponse = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
        ])->get($this->supabaseUrl . '/rest/v1/dosen?select=*');

        $dosen = $dosenResponse->json();

        return view('proyek_akhir/public_pa', compact('data_pa', 'id_master', 'dosen'));
    }

    public function tambahDataMasterPa(Request $request)
    {
        $request->validate([
            'start_year' => 'required|integer',
            'end_year' => 'required|integer|gte:start_year',
            'jurusan' => 'required',
        ]);

        $tahun_ajaran = $request->start_year . ' / ' . $request->end_year;

        DB::table('public.master_pa')->insert([
            'tahun_ajaran' => $tahun_ajaran,
            'jurusan' => $request->jurusan,
            'created_at' => now(),
        ]);

        return redirect()->route('proyek-akhir.data.filter')->with('success', 'Data Master PA berhasil ditambahkan.');
    }

    public function filterByDosen(Request $request, $id_master)
    {
        $id_dosen = $request->query('id_dosen');

        // Get filtered projects
        $response = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
        ])->get($this->supabaseUrl . '/rest/v1/proyek_akhir', [
            'id_master' => 'eq.' . $id_master,
            'select' => '*,dosen_pembimbing1(*),dosen_pembimbing2(*),dosen_pembimbing3(*)',
            'or' => sprintf('(dosen_pembimbing1.eq.%s,dosen_pembimbing2.eq.%s,dosen_pembimbing3.eq.%s)', $id_dosen, $id_dosen, $id_dosen)
        ]);

        $filtered_projects = $response->json();

        // Get master project data
        $masterResponse = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
        ])->get($this->supabaseUrl . '/rest/v1/master_pa', [
            'id_master' => 'eq.' . $id_master,
            'select' => 'id_master, created_at, tahun_ajaran, jurusan'
        ]);

        $masterData = $masterResponse->json();

        // Assuming $masterData contains only one entry as it is queried by id_master
        if (!empty($masterData) && isset($masterData[0])) {
            $masterData = $masterData[0];
            $masterData['proyek_akhir'] = $filtered_projects; // Attach filtered projects
        } else {
            $masterData = [
                'id_master' => $id_master,
                'created_at' => null,
                'tahun_ajaran' => null,
                'jurusan' => null,
                'proyek_akhir' => $filtered_projects
            ];
        }

        // Get dosen data
        $dosenResponse = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
        ])->get($this->supabaseUrl . '/rest/v1/dosen?select=*');

        $dosen = $dosenResponse->json();

        return view('proyek_akhir/public_pa', ['data_pa' => [$masterData], 'id_master' => $id_master, 'dosen' => $dosen]);
    }

    public function tambahDataProyek(Request $request, $id_master)
    {
        $request->validate([
            'nrp_mahasiswa' => 'required',
            'nama_mahasiswa' => 'required',
            'judul_pa' => 'required',
            'umur' => 'required',
            'jenis_kelmain' => 'required',
            'dosen_pembimbing1' => 'required',
            'dosen_pembimbing2' => 'required',
            'dosen_pembimbing3' => 'required',
        ]);

        DB::table('public.proyek_akhir')->insert([
            'nrp_mahasiswa' => $request->nrp_mahasiswa,
            'nama_mahasiswa' => $request->nama_mahasiswa,
            'judul_pa' => $request->judul_pa,
            'umur' => $request->umur,
            'jenis_kelamin' => $request->jenis_kelamin,
            'dosen_pembimbing1' => $request->dosen_pembimbing1,
            'dosen_pembimbing2' => $request->dosen_pembimbing2,
            'dosen_pembimbing3' => $request->dosen_pembimbing3,
            'id_master' => $id_master,
        ]);

        return redirect()->route('proyek-akhir.data', ['id_master' => $id_master])->with('success', 'Data berhasil ditambahkan.');
    }

    public function showEditForm($id)
    {
        $response = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
        ])->get($this->supabaseUrl . '/rest/v1/proyek_akhir', [
            'id_mhs' => 'eq.' . $id,
            'select' => '*,dosen_pembimbing1(*),dosen_pembimbing2(*),dosen_pembimbing3(*)'
        ]);

        $data_pa = $response->json();

        $dosenResponse = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
        ])->get($this->supabaseUrl . '/rest/v1/dosen?select=*');

        $dosen = $dosenResponse->json();

        return view('proyek_akhir/edit_pa', compact('data_pa', 'dosen'));
    }

    public function updateDataProyek(Request $request, $id)
    {
        $request->validate([
            'nrp_mahasiswa' => 'required',
            'nama_mahasiswa' => 'required',
            'judul_pa' => 'required',
            'umur' => 'required',
            'jenis_kelamin' => 'required',
            'dosen_pembimbing1' => 'required',
            'dosen_pembimbing2' => 'required',
            'dosen_pembimbing3' => 'required',
        ]);

        DB::table('public.proyek_akhir')
            ->where('id_mhs', $id)
            ->update([
                'nrp_mahasiswa' => $request->nrp_mahasiswa,
                'nama_mahasiswa' => $request->nama_mahasiswa,
                'judul_pa' => $request->judul_pa, 
                'umur' => $request->umur,
                'jenis_kelamin' => $request->jenis_kelamin,
                'dosen_pembimbing1' => $request->dosen_pembimbing1,
                'dosen_pembimbing2' => $request->dosen_pembimbing2,
                'dosen_pembimbing3' => $request->dosen_pembimbing3,
            ]);

        return redirect()->route('proyek-akhir.data', ['id_master' => $request->id_master])->with('success', 'Data berhasil diupdate.');
    }


    public function deleteDataProyek($id)
    {
        DB::table('public.proyek_akhir')->where('id_mhs', $id)->delete();
        return redirect()->back()->with('success', 'Data berhasil dihapus.');
    }

    public function dosenDropdown($id_master)
    {
        $response = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
        ])->get($this->supabaseUrl . '/rest/v1/dosen?select=*');

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to retrieve data from API'], 500);
        }

        $dosen = $response->json();

        return view('proyek_akhir/tambah_pa', compact('dosen', 'id_master'));
    }

    public function getDataMasterPA(Request $request)
    {
        $program_studi = $request->query('program_studi');
        $tahun_ajaran = $request->query('tahun_ajaran');

        // Build query parameters
        $queryParams = [
            'select' => '*,proyek_akhir(*)'
        ];

        if ($program_studi) {
            $queryParams['jurusan'] = 'eq.' . $program_studi;
        }

        if ($tahun_ajaran) {
            $queryParams['tahun_ajaran'] = 'eq.' . $tahun_ajaran;
        }

        $response = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
        ])->get($this->supabaseUrl . '/rest/v1/master_pa', $queryParams);

        $master_pa = $response->json();

        // Get the list of program studi and tahun ajaran for the filters
        $program_studi_list = DB::table('public.master_pa')->select('jurusan')->distinct()->pluck('jurusan');
        $tahun_ajaran_list = DB::table('public.master_pa')->select('tahun_ajaran')->distinct()->pluck('tahun_ajaran');

        return view('proyek_akhir/card_pa', compact('master_pa', 'program_studi_list', 'tahun_ajaran_list'));
    }

    public function exportDataProyek(Request $request, $id_master)
    {
        $response = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
        ])->get($this->supabaseUrl . '/rest/v1/master_pa', [
            'id_master' => 'eq.' . $id_master,
            'select' => '*,proyek_akhir(*,dosen_pembimbing1(*),dosen_pembimbing2(*),dosen_pembimbing3(*))'
        ]);

        $data_pa = $response->json();

        $format = $request->query('format', 'xlsx');

        if ($format == 'csv') {
            return Excel::download(new ProyekAkhirExportMahasiswa($data_pa), 'data_proyek_akhir.csv', \Maatwebsite\Excel\Excel::CSV);
        } else {
            return Excel::download(new ProyekAkhirExportMahasiswa($data_pa), 'data_proyek_akhir.xlsx');
        }
    }
      
    public function filterByUmur(Request $request, $id_master)
    {
        $jenis_kelamin = $request->input('jenis_kelamin');
        $operator = $request->input('operator');
        $value1 = $request->input('value1');
        $value2 = $request->input('value2');

        // Ensure correct case sensitivity for jenis_kelamin
        $jenis_kelamin = ucfirst($jenis_kelamin);

        // Start building the base query parameters
        $query = [
            'id_master' => 'eq.' . $id_master,
            'select' => '*,proyek_akhir(*,dosen_pembimbing1(*),dosen_pembimbing2(*),dosen_pembimbing3(*))',
            'proyek_akhir.jenis_kelamin' => 'eq.' . $jenis_kelamin,
        ];

        // Handle 'between' case separately
        if ($operator === 'between') {
            // Separate the two conditions into two distinct query parameters
            $query['proyek_akhir.umur'] = 'gte.' . $value1;
            $query['proyek_akhir.umur2'] = 'lte.' . $value2;
        } else {
            $operatorMap = [
                '<' => 'lt',
                '>' => 'gt',
                '<=' => 'lte',
                '>=' => 'gte',
                '=' => 'eq',
            ];
            $query['proyek_akhir.umur'] = $operatorMap[$operator] . '.' . $value1;
        }

        // Manually build the query string to ensure no duplicate keys
        $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        // Replace the manually added 'umur2' with the correct key
        $queryString = str_replace('proyek_akhir.umur2', 'proyek_akhir.umur', $queryString);

        // Send the request to Supabase
        $response = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
        ])->get($this->supabaseUrl . '/rest/v1/master_pa?' . $queryString);

        $data_pa = $response->json();

        // Fetching the dosen data
        $dosenResponse = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
        ])->get($this->supabaseUrl . '/rest/v1/dosen?select=*');

        $dosen = $dosenResponse->json();

        // Return the view with filtered data
        return view('proyek_akhir/public_pa', ['data_pa' => $data_pa, 'id_master' => $id_master, 'dosen' => $dosen]);
    }

}
