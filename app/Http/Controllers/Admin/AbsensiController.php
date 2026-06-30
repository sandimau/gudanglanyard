<?php

namespace App\Http\Controllers\Admin;

use App\Models\Cuti;
use App\Models\Member;
use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\FreelanceTagihan;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class AbsensiController extends Controller
{
    /**
     * Menyimpan data absensi dari API absensi.
     * Format request sama dengan response GET /api/absensi: { "attendances": [ { "user": { "email": "..." }, "attendance_date", ... } ] }
     * Member dicari dari User (projectpro) yang emailnya sama dengan user.email dari API.
     */
    public function syncFromApi()
    {
        $apiUrls = config('services.absensi.api_urls', []);
        if ($apiUrls === []) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada URL API absensi yang dikonfigurasi (ABSENSI_API_URLS)',
                'saved' => 0,
            ], 500);
        }

        $attendances = [];
        $errors = [];

        foreach ($apiUrls as $url) {
            $response = Http::timeout(30)->get($url);
            if (! $response->successful()) {
                $errors[] = "{$url}: HTTP {$response->status()}";
                continue;
            }

            $data = $response->json();
            $rows = $data['attendances'] ?? [];
            if (! is_array($rows)) {
                $errors[] = "{$url}: format response tidak valid";
                continue;
            }

            $attendances = array_merge($attendances, $rows);
        }

        if ($attendances === [] && $errors !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data dari semua API absensi',
                'errors' => $errors,
                'saved' => 0,
            ], 502);
        }

        $saved = 0;
        $allowedJenis = ['sakit', 'ijin', 'terlambat', 'cuti', 'alpha', 'hadir'];

        // Kelompokkan per user email + tanggal (satu absensi per member per hari)
        // Jika ada beberapa clock_in, prioritas: yang terlambat (minutes_late > 0), else ambil yang pertama
        $byMemberAndDate = [];
        foreach ($attendances as $row) {
            $userEmail = $row['user']['email'] ?? null;
            if (! $userEmail) {
                continue;
            }
            $tanggal = Carbon::parse($row['attendance_date'])->format('Y-m-d');
            $key = $userEmail.'|'.$tanggal;
            $minutesLate = (float) ($row['minutes_late'] ?? 0);
            if (! isset($byMemberAndDate[$key]) || $minutesLate > 0) {
                $byMemberAndDate[$key] = $row;
                $byMemberAndDate[$key]['_tanggal'] = $tanggal;
            }
        }

        foreach ($byMemberAndDate as $row) {
            $userEmail = $row['user']['email'] ?? null;
            if (! $userEmail) {
                continue;
            }
            $member = Member::whereHas('user', function ($q) use ($userEmail) {
                $q->where('email', $userEmail);
            })->first();
            if (! $member) {
                continue;
            }

            $tanggal = $row['_tanggal'] ?? Carbon::parse($row['attendance_date'])->format('Y-m-d');
            $jamMasuk = $row['attendance_time'] ?? null;
            $minutesLate = (float) ($row['minutes_late'] ?? 0);
            $status = $row['status'] ?? null;

            if ($minutesLate > 0) {
                $jenis = 'terlambat';
                $keterangan = "Terlambat {$minutesLate} menit";
            } elseif (($cutiIjin = $this->getCutiAtauIjinDariModel($member->id, $tanggal))) {
                $jenis = $cutiIjin['jenis'];
                $keterangan = $cutiIjin['keterangan'];
            } elseif (in_array($status, ['sakit', 'alpha'], true)) {
                $jenis = $status;
                $keterangan = $row['keterangan'] ?? null;
            } else {
                $jenis = 'hadir';
                $keterangan = $row['keterangan'] ?? null;
            }

            if (! in_array($jenis, $allowedJenis, true)) {
                $jenis = 'hadir';
            }

            $result = $this->simpanAbsensi([
                'member_id' => $member->id,
                'tanggal' => $tanggal,
                'jenis' => $jenis,
                'keterangan' => $keterangan,
                'sumber' => 'api',
                'minutes_late' => $minutesLate,
                'jam_masuk' => $jamMasuk,
            ]);
            if ($result) {
                $saved++;
            }
        }

        $message = "{$saved} absensi berhasil disimpan";
        if ($errors !== []) {
            $message .= ' ('.count($errors).' sumber gagal diambil)';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'saved' => $saved,
            'sources' => count($apiUrls),
            'errors' => $errors,
        ]);
    }

    /**
     * Cek model Cuti untuk member + tanggal: cuti=1 → cuti, cuti=0 → ijin.
     * Return ['jenis' => 'cuti'|'ijin', 'keterangan' => ...] atau null bila tidak ada.
     */
    protected function getCutiAtauIjinDariModel(int $memberId, string $tanggal): ?array
    {
        $cuti = Cuti::where('member_id', $memberId)
            ->whereDate('tanggal', $tanggal)
            ->first();

        if (! $cuti) {
            return null;
        }

        $jenis = (int) $cuti->cuti === 1 ? 'cuti' : 'ijin';

        return [
            'jenis' => $jenis,
            'keterangan' => $cuti->keterangan,
        ];
    }

    /**
     * Simpan absensi dan handle logic freelance/karyawan
     */
    protected function simpanAbsensi(array $data): bool
    {
        // Cek duplikat: sudah ada absensi untuk member + tanggal ini?
        $exists = Absensi::where('member_id', $data['member_id'])
            ->whereDate('tanggal', $data['tanggal'])
            ->exists();

        if ($exists) {
            return false;
        }

        return DB::transaction(function () use ($data) {
            $absensi = Absensi::create($data);
            $member = Member::find($data['member_id']);

            // Freelance: buat tagihan upah (dari member.upah)
            if ($member && $member->jenis === 'freelance' && $member->upah) {
                FreelanceTagihan::create([
                    'member_id' => $member->id,
                    'absensi_id' => $absensi->id,
                    'tanggal' => $data['tanggal'],
                    'nominal_upah' => (int) $member->upah,
                    'dibayar' => 'belum',
                    'keterangan' => 'Tagihan upah harian - absensi ' . $data['jenis'],
                ]);
            }

            return true;
        });
    }

    public function index(Request $request)
    {
        $query = Absensi::with('member')->orderBy('tanggal', 'desc')->orderBy('id', 'desc');

        if ($request->member_id) {
            $query->where('member_id', $request->member_id);
        }
        if ($request->bulan) {
            $query->whereMonth('tanggal', $request->bulan);
        }
        if ($request->tahun) {
            $query->whereYear('tanggal', $request->tahun);
        }

        $absensis = $query->paginate(20);
        $members = Member::where('status', 1)->orderBy('nama_lengkap')->get();

        return view('admin.absensi.index', compact('absensis', 'members'));
    }

    public function create()
    {
        $members = Member::where('status', 1)->orderBy('nama_lengkap')->get();
        return view('admin.absensi.create', compact('members'));
    }

    public function destroy(Absensi $absensi)
    {
        // Hapus freelance tagihan jika ada
        $absensi->freelanceTagihan?->delete();
        $absensi->delete();

        return back()->withSuccess(__('Absensi berhasil dihapus.'));
    }
}
