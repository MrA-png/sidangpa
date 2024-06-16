<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str; 
use PDF;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProyekAkhirExport;

class ProyekAkhirController extends Controller
{
    public function dosenDropdownfilter()
    {
        $response = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
        ])->get($this->supabaseUrl . '/rest/v1/dosen?select=*');

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to retrieve data from API'], 500);
        }

        return $response->json();
    }
   
    public function getData(Request $request, $id_header)
    {
        // Fetch dosen data for dropdown
        $dosenList = $this->dosenDropdownfilter();
    
        // Existing code to fetch proyek_akhir data
        $dosen_pembimbing1 = $request->query('dosen_pembimbing1');
        $penguji_1 = $request->query('penguji_1');
        $penguji_2 = $request->query('penguji_2');
    
        $response = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
            'Authorization' => 'Bearer ' . $this->supabaseApiKey,
        ])->get($this->supabaseUrl . '/rest/v1/header', [
            'id_header' => 'eq.' . $id_header,
            'select' => '*,data_generate(*,id_mhs(*,dosen_pembimbing1(*),dosen_pembimbing2(*),dosen_pembimbing3(*)),id_ruang(*,riset_group(*)),penguji_1(*),penguji_2(*))'
        ]);
    
        $proyek_akhir = $response->json();
    
        $finalResult = [];
    
        foreach ($proyek_akhir as $header) {
            $headerEntry = [
                'created_at' => $header['created_at'],
                'judul' => $header['judul'],
                'prodi' => $header['prodi'],
                'tanggal' => $header['tanggal'],
                'waktu'=> $header['waktu'],
                'tahapan_sidang' => $header['tahapan_sidang'],
                'tahun_ajaran' => $header['tahun_ajaran'],
                'id_header' => $header['id_header'],
                'data_generate' => []
            ];
    
            $groupedData = [];
    
            if (isset($header['data_generate'])) {
                foreach ($header['data_generate'] as $data) {
                    $idRuang = $data['id_ruang']['id_ruang'];
                    $addData = true;
    
                    if ($dosen_pembimbing1 && $data['id_mhs']['dosen_pembimbing1']['nama_dosen'] != $dosen_pembimbing1) {
                        $addData = false;
                    }
    
                    if ($penguji_1 && (!isset($data['penguji_1']['nama_dosen']) || $data['penguji_1']['nama_dosen'] != $penguji_1)) {
                        $addData = false;
                    }
    
                    if ($penguji_2 && (!isset($data['penguji_2']['nama_dosen']) || $data['penguji_2']['nama_dosen'] != $penguji_2)) {
                        $addData = false;
                    }
    
                    if ($addData) {
                        if (!isset($groupedData[$idRuang])) {
                            $groupedData[$idRuang] = [
                                'id_ruang' => $idRuang,
                                'nama_ruang' => $data['id_ruang']['nama_ruang'],
                                'kode_ruang' => $data['id_ruang']['kode_ruang'],
                                'letak' => $data['id_ruang']['letak'],
                                'riset_group' => $data['id_ruang']['riset_group']['riset_group'],
                                'data_generate' => []
                            ];
                        }
                        $groupedData[$idRuang]['data_generate'][] = $data;
                    }
                }
            }
    
            $headerEntry['data_generate'] = array_values($groupedData);
            $finalResult[] = $headerEntry;
        }
    
        return view('generate', compact('finalResult', 'id_header', 'dosenList'));
    }


    public function import(Request $request)
    {
        $request->validate([
            'fileUpload' => 'required|mimes:csv,txt' // Allowing txt for better compatibility
        ]);
    
        $file = $request->file('fileUpload');
        $filePath = $file->getRealPath();
    
        $data = array_map('str_getcsv', file($filePath));
    
        $importData = [];
        foreach ($data as $key => $row) {
            if ($key === 0) {
                continue; // Skip header row
            }
            if (count($row) >= 6) {
                $importData[] = [
                    'id_mhs' => (string) Str::uuid(), // Generate UUID for id_mhs
                    'nrp_mahasiswa' => $row[0],
                    'nama_mahasiswa' => $row[1],
                    'judul_pa' => $row[2],
                    'dosen_pembimbing1' => $row[3],
                    'dosen_pembimbing2' => $row[4],
                    'dosen_pembimbing3' => $row[5],
                ];
            }
        }
    
        try {
            $response = Http::withHeaders([
                'apikey' => $this->supabaseApiKey,
                'Content-Type' => 'application/json',
            ])->post($this->supabaseUrl . '/rest/v1/proyek_akhir_mhs', $importData);
    
            if ($response->successful()) {
                $id_header = session('id_header');
    
                if ($id_header) {
                    return $this->generate($id_header, $importData)
                            ->with('success', 'Data header berhasil disimpan.');
                } else {
                    return redirect('/proyek-akhir/jadwal')->with('error', 'Gagal mengimpor data: id_header tidak ditemukan.');
                }
            } else {
                $error = $response->json();
                Log::error('Supabase import error', [
                    'status' => $response->status(),
                    'body' => $error,
                ]);
    
                return redirect('/proyek-akhir/jadwal')->with('error', 'Gagal mengimpor data: ' . json_encode($error));
            }
        } catch (\Exception $e) {
            Log::error('Exception during import', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
    
            return redirect('/proyek-akhir/jadwal')->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    
    public function generate($id_header, $importData)
    {
        // Fetch data from the provided URLs with API key in headers
        $risetGroupsResponse = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
        ])->get('https://ihpqktbogxquohofeevj.supabase.co/rest/v1/riset_group?select=*,dosen(*),ruang(*)');
    
        // Use importData instead of fetching mahasiswaResponse from API
        $mahasiswaData = $importData;
    
        $headerResponse = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
        ])->get("https://ihpqktbogxquohofeevj.supabase.co/rest/v1/header?id_header=eq.{$id_header}");
    
        // Check if the data retrieval was successful
        if ($risetGroupsResponse->failed() || $headerResponse->failed()) {
            return response()->json(['error' => 'Failed to retrieve data from API'], 500);
        }
    
        // Get the JSON response from the API calls
        $risetGroups = $risetGroupsResponse->json();
        $headerData = $headerResponse->json();
    
        // Initialize combinedData array grouped by riset_group
        $groupedData = [];
    
        // Create a mapping of dosen id to dosen name for quick lookup
        $dosenMap = [];
        foreach ($risetGroups as $risetGroup) {
            foreach ($risetGroup['dosen'] as $dosen) {
                $dosenMap[$dosen['id_dosen']] = $dosen['nama_dosen'];
            }
        }
    
        // Loop through each proyek_akhir_mhs entry
        foreach ($mahasiswaData as $mahasiswa) {
            // Find the corresponding riset_group based on dosen_pembimbing1
            foreach ($risetGroups as $risetGroup) {
                foreach ($risetGroup['dosen'] as $dosen) {
                    if ($dosen['id_dosen'] === $mahasiswa['dosen_pembimbing1']) {
                        // Check if the riset_group is already in groupedData
                        if (!isset($groupedData[$risetGroup['id_rg']])) {
                            // Initialize the riset_group entry with details and an empty mahasiswa array
                            $groupedData[$risetGroup['id_rg']] = [
                                'riset_group' => $risetGroup,
                                'mahasiswa' => [],
                            ];
                        }
    
                        // Add the mahasiswa to the corresponding riset_group entry
                        $groupedData[$risetGroup['id_rg']]['mahasiswa'][] = $mahasiswa;
                        break 2; // Break both inner loops
                    }
                }
            }
        }
    
        // Assign examiners to each mahasiswa
        foreach ($groupedData as &$group) {
            $dosenList = $group['riset_group']['dosen'];
    
            foreach ($group['mahasiswa'] as &$mahasiswa) {
                $dosenPembimbing1 = $mahasiswa['dosen_pembimbing1'];
                $dosenPembimbing2 = $mahasiswa['dosen_pembimbing2'] ?? null;
                $dosenPembimbing3 = $mahasiswa['dosen_pembimbing3'] ?? null;
    
                // Add names for pembimbing
                $mahasiswa['nama_dosen_pembimbing1'] = $dosenMap[$dosenPembimbing1] ?? null;
                $mahasiswa['nama_dosen_pembimbing2'] = $dosenMap[$dosenPembimbing2] ?? null;
                $mahasiswa['nama_dosen_pembimbing3'] = $dosenMap[$dosenPembimbing3] ?? null;
    
                $availableDosen = array_filter($dosenList, function($dosen) use ($dosenPembimbing1, $dosenPembimbing2, $dosenPembimbing3) {
                    return $dosen['id_dosen'] !== $dosenPembimbing1
                        && $dosen['id_dosen'] !== $dosenPembimbing2
                        && $dosen['id_dosen'] !== $dosenPembimbing3;
                });
    
                $availableDosen = array_values($availableDosen); // Re-index the array
    
                if (count($availableDosen) >= 2) {
                    $mahasiswa['penguji1'] = $availableDosen[0]['id_dosen'];
                    $mahasiswa['nama_penguji1'] = $availableDosen[0]['nama_dosen'];
                    $mahasiswa['penguji2'] = $availableDosen[1]['id_dosen'];
                    $mahasiswa['nama_penguji2'] = $availableDosen[1]['nama_dosen'];
                } elseif (count($availableDosen) === 1) {
                    $mahasiswa['penguji1'] = $availableDosen[0]['id_dosen'];
                    $mahasiswa['nama_penguji1'] = $availableDosen[0]['nama_dosen'];
                    $mahasiswa['penguji2'] = null;
                    $mahasiswa['nama_penguji2'] = null;
                } else {
                    $mahasiswa['penguji1'] = null;
                    $mahasiswa['nama_penguji1'] = null;
                    $mahasiswa['penguji2'] = null;
                    $mahasiswa['nama_penguji2'] = null;
                }
            }
        }
    
        // Group mahasiswa by their penguji (examiners)
        foreach ($groupedData as &$group) {
            $rooms = $group['riset_group']['ruang'];
            $roomCount = count($rooms);
    
            if ($roomCount > 0) {
                // Distribute mahasiswa to rooms ensuring each dosen_pembimbing1 is in one room only
                $mahasiswaPerDosen = [];
                foreach ($group['mahasiswa'] as $mahasiswa) {
                    $dosenPembimbing1 = $mahasiswa['dosen_pembimbing1'];
                    if (!isset($mahasiswaPerDosen[$dosenPembimbing1])) {
                        $mahasiswaPerDosen[$dosenPembimbing1] = [];
                    }
                    $mahasiswaPerDosen[$dosenPembimbing1][] = $mahasiswa;
                }
    
                $roomIndex = 0;
                foreach ($mahasiswaPerDosen as $mahasiswaGroup) {
                    if (!isset($rooms[$roomIndex]['mahasiswa'])) {
                        $rooms[$roomIndex]['mahasiswa'] = [];
                    }
                    $rooms[$roomIndex]['mahasiswa'] = array_merge($rooms[$roomIndex]['mahasiswa'], $mahasiswaGroup);
                    $roomIndex = ($roomIndex + 1) % $roomCount;
                }
            }
    
            // Assign updated rooms back to the riset_group
            $group['riset_group']['ruang'] = $rooms;
        }
        // Create the new response array with only the required fields
        $response = [];
        foreach ($groupedData as $group) {
            foreach ($group['riset_group']['ruang'] as $ruang) {
                foreach ($ruang['mahasiswa'] as $mahasiswa) {
                    $response[] = [
                        'penguji_1' => $mahasiswa['penguji1'],
                        'penguji_2' => $mahasiswa['penguji2'],
                        'id_mhs' => $mahasiswa['id_mhs'],
                        'id_header' => $id_header,
                        'id_ruang' => $ruang['id_ruang'],
                    ];
                }
            }
        }
    
        // Send the response data to the database
        $postResponse = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
        ])->post($this->supabaseUrl . '/rest/v1/data_generate', $response);
    
        // Check if the post request was successful
        if ($postResponse->failed()) {
            return response()->json(['error' => 'Failed to send data to the database'], 500);
        }
    
        session(['id_header' => $id_header]);
        return redirect('/proyek-akhir/generate-hasil/'.$id_header)->with('success', 'Data header berhasil disimpan.');
    }
    
    public function getDataGenerate(Request $request)
    {
        $tahunAjaran = $request->input('tahun_ajaran');

        // Base URL untuk API
        $url = $this->supabaseUrl . '/rest/v1/header?select=*&order=created_at.desc';

        // Jika ada tahun ajaran, tambahkan query parameter untuk filter
        if ($tahunAjaran) {
            $url .= '&tahun_ajaran=eq.' . $tahunAjaran;
        }

        // Ambil data dari API
        $response = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
        ])->get($url);
        
        $headers = $response->json();

        // Mengambil data tahun ajaran unik untuk dropdown
        $tahunAjaranList = collect($headers)->pluck('tahun_ajaran')->unique()->sort();

        return view('card', compact('headers', 'tahunAjaranList'));
    }



    public function edit($id_jadwal_generate)
    {
        $response = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
        ])->get($this->supabaseUrl . '/rest/v1/data_generate', [
            'id_jadwal_generate' => 'eq.' . $id_jadwal_generate,
            'select' => '*,penguji_1(*),penguji_2(*),id_mhs(*,dosen_pembimbing1(*), dosen_pembimbing2(*), dosen_pembimbing3(*))'
        ]);

        $data = $response->json();

        if (empty($data)) {
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        }
        
        $data_generate = $data[0];
        $id_header = $data_generate['id_header'];

        $dosenResponse = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
        ])->get($this->supabaseUrl . '/rest/v1/dosen?select=*');

        $dosen = $dosenResponse->json();

        $ruangResponse = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
        ])->get($this->supabaseUrl . '/rest/v1/ruang?select=*');

        $ruang = $ruangResponse->json();

        return view('edit', compact('data_generate', 'dosen', 'ruang',  'id_header'));
    }


    public function update(Request $request, $id_jadwal_generate)
    {
        $validatedData = $request->validate([
            'penguji_1' => 'required|uuid',
            'penguji_2' => 'required|uuid',
            'id_ruang' => 'required|uuid',
            'id_header' => 'required|uuid',
        ]);

        $response = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
            'Content-Type' => 'application/json',
        ])->patch($this->supabaseUrl . '/rest/v1/data_generate?id_jadwal_generate=eq.' . $id_jadwal_generate, $validatedData);

        if ($response->successful()) {
            return redirect()->route('proyek-akhir.getdata', ['id_header' => $validatedData['id_header']])->with('success', 'Data updated successfully');
        } else {
            return redirect()->route('proyek-akhir.getdata', ['id_header' => $validatedData['id_header']])->with('error', 'Failed to update data');
        }
    }

    public function destroy($id_jadwal_generate)
    {
        DB::table('public.data_generate')->where('id_jadwal_generate', $id_jadwal_generate)->delete();
        return redirect()->back()->with('success', 'Data berhasil dihapus.');
    }

    public function downloadPDF($id_header)
    {
        $response = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
        ])->get($this->supabaseUrl . '/rest/v1/header', [
            'id_header' => 'eq.' . $id_header,
            'select' => '*,data_generate(*,id_mhs(*,dosen_pembimbing1(*),dosen_pembimbing2(*),dosen_pembimbing3(*)),id_ruang(*,riset_group(*)),penguji_1(*),penguji_2(*))'
        ]);

        $proyek_akhir = $response->json();
        
        // Initialize array to store the final result
        $finalResult = [];

        foreach ($proyek_akhir as $header) {
            // Prepare the basic structure of each header entry
            $headerEntry = [
                'created_at' => $header['created_at'],
                'judul' => $header['judul'],
                'prodi' => $header['prodi'],
                'tanggal' => $header['tanggal'],
                'waktu'=> $header['waktu'],
                'tahapan_sidang' => $header['tahapan_sidang'],
                'id_header' => $header['id_header'],
                'tahun_ajaran' => $header['tahun_ajaran'],
                'data_generate' => []
            ];

            // Group the data_generate entries by id_ruang within each header
            $groupedData = [];

            if (isset($header['data_generate'])) {
                foreach ($header['data_generate'] as $data) {
                    $idRuang = $data['id_ruang']['id_ruang'];
                    if (!isset($groupedData[$idRuang])) {
                        $groupedData[$idRuang] = [
                            'id_ruang' => $idRuang,
                            'nama_ruang' => $data['id_ruang']['nama_ruang'],
                            'kode_ruang' => $data['id_ruang']['kode_ruang'],
                            'letak' => $data['id_ruang']['letak'],
                            'riset_group' => $data['id_ruang']['riset_group']['riset_group'],
                            'data_generate' => []
                        ];
                    }
                    $groupedData[$idRuang]['data_generate'][] = $data;
                }
            }

            // Convert groupedData to a numerically indexed array and add to headerEntry
            $headerEntry['data_generate'] = array_values($groupedData);

            // Add the prepared headerEntry to the final result
            $finalResult[] = $headerEntry;
        }

        $pdf = PDF::loadView('export-generate', compact('finalResult'));

        return $pdf->download('jadwal_sidang.pdf');
    }

    public function downloadExcel($id_header)
    {
        $response = Http::withHeaders([
            'apikey' => $this->supabaseApiKey,
        ])->get($this->supabaseUrl . '/rest/v1/header', [
            'id_header' => 'eq.' . $id_header,
            'select' => '*,data_generate(*,id_mhs(*,dosen_pembimbing1(*),dosen_pembimbing2(*),dosen_pembimbing3(*)),id_ruang(*,riset_group(*)),penguji_1(*),penguji_2(*))'
        ]);

        $proyek_akhir = $response->json();

        $finalResult = [];

        foreach ($proyek_akhir as $header) {
            $headerEntry = [
                'created_at' => $header['created_at'],
                'judul' => $header['judul'],
                'prodi' => $header['prodi'],
                'tanggal' => $header['tanggal'],
                'waktu'=> $header['waktu'],
                'tahapan_sidang' => $header['tahapan_sidang'],
                'id_header' => $header['id_header'],
                'tahun_ajaran' => $header['tahun_ajaran'],
                'data_generate' => []
            ];

            $groupedData = [];

            if (isset($header['data_generate'])) {
                foreach ($header['data_generate'] as $data) {
                    $idRuang = $data['id_ruang']['id_ruang'];
                    if (!isset($groupedData[$idRuang])) {
                        $groupedData[$idRuang] = [
                            'id_ruang' => $idRuang,
                            'nama_ruang' => $data['id_ruang']['nama_ruang'],
                            'kode_ruang' => $data['id_ruang']['kode_ruang'],
                            'letak' => $data['id_ruang']['letak'],
                            'riset_group' => $data['id_ruang']['riset_group']['riset_group'],
                            'data_generate' => []
                        ];
                    }
                    $groupedData[$idRuang]['data_generate'][] = $data;
                }
            }

            $headerEntry['data_generate'] = array_values($groupedData);
            $finalResult[] = $headerEntry;
        }

        return Excel::download(new ProyekAkhirExport($finalResult), 'jadwal_sidang.xlsx');
    }
}
