<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RespondsToMemberModal;
use App\Http\Controllers\Controller;
use App\Models\AkunDetail;
use App\Models\BukuBesar;
use App\Models\Cuti;
use App\Models\FreelanceTagihan;
use App\Models\Gaji;
use App\Models\Kasbon;
use App\Models\Lembur;
use App\Models\Member;
use App\Models\Penggajian;
use App\Models\Tunjangan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    use RespondsToMemberModal;

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';

        $members = Member::with(['user'])
            ->when($tab === 'nonaktif', fn ($q) => $q->nonaktif()->orderBy('id', 'desc'))
            ->when($tab === 'aktif', fn ($q) => $q->aktif()->orderBy('id', 'asc'))
            ->get();

        return view('admin.members.index', compact('members', 'tab'));
    }

    public function nonaktif()
    {
        return redirect()->route('members.index', ['tab' => 'nonaktif']);
    }

    public function create()
    {
        $users = User::pluck('name', 'id');
        for ($i = 1; $i < 32; $i++) {
            $num = (string) $i;
            if (strlen($num) == 1) {
                $num = '0' . $num;
                $tglGaji[$num] = $num;
            }
            $tglGaji[(string) $num] = $num;
        }
        return view('admin.members.create', compact('users','tglGaji'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateMemberModal($request, [
            'nama_lengkap' => 'required|string',
            'no_telp' => 'required|string',
            'status' => 'required|in:0,1',
            'tgl_masuk' => 'nullable|date',
            'tgl_lahir' => 'nullable|date',
            'tempat_lahir' => 'nullable|string',
            'alamat' => 'nullable|string',
            'tgl_gajian' => 'nullable',
            'no_rek' => 'nullable|string',
        ]);

        $validated['jenis'] = 'karyawan';
        Member::create($validated);

        return $this->memberModalResponse(
            $request,
            __('Member berhasil ditambahkan.'),
            route('members.index')
        );
    }

    public function edit(Member $member)
    {
        $users = User::pluck('name', 'id');
        for ($i = 1; $i < 32; $i++) {
            $num = (string) $i;
            if (strlen($num) == 1) {
                $num = '0' . $num;
                $tglGaji[$num] = $num;
            }
            $tglGaji[(string) $num] = $num;
        }

        $member->load('user');

        return view('admin.members.edit', compact('member', 'users','tglGaji'));
    }

    public function update(Request $request, Member $member)
    {
        $validated = $this->validateMemberModal($request, [
            'nama_lengkap' => 'required|string',
            'no_telp' => 'required|string',
            'status' => 'required|in:0,1',
            'jenis' => 'required|in:karyawan,freelance',
            'tgl_masuk' => 'nullable|date',
            'tgl_keluar' => 'nullable|date',
            'tgl_lahir' => 'nullable|date',
            'tempat_lahir' => 'nullable|string',
            'alamat' => 'nullable|string',
            'tgl_gajian' => 'nullable',
            'no_rek' => 'nullable|string',
        ]);

        $member->update($validated);

        return $this->memberModalResponse(
            $request,
            __('Member berhasil diperbarui.'),
            route('members.show', $member->id)
        );
    }

    public function show(Member $member)
    {
        $member->load('user');

        $cutis = Cuti::where('member_id', $member->id)->orderBy('created_at', 'desc')->orderBy('id','desc')->paginate(10);
        $lemburs = Lembur::where('member_id', $member->id)->orderBy('created_at', 'desc')->orderBy('id','desc')->paginate(10);
        $kasbons = Kasbon::where('member_id', $member->id)->orderBy('created_at', 'desc')->orderBy('id','desc')->paginate(10);
        $tunjangans = Tunjangan::where('member_id', $member->id)->orderBy('created_at', 'desc')->orderBy('id','desc')->paginate(10);
        $gajis = Gaji::where('member_id', $member->id)->with(['member', 'bagian', 'level'])->orderBy('id','desc')->paginate(10);
        $penggajians = Penggajian::where('member_id', $member->id)->orderBy('id','desc')->paginate(10);
        $gajian = Penggajian::where('member_id', $member->id)->latest('id')->first();
        return view('admin.members.show', compact('member', 'cutis', 'lemburs', 'kasbons', 'tunjangans', 'gajis', 'penggajians', 'gajian'));
    }

    public function destroy(Member $member)
    {
        $member->delete();

        return back();
    }

    public function cuti(Member $member)
    {
        $cutis = Cuti::where('member_id', $member->id)->where('cuti', 1)->orderBy('created_at', 'desc')->orderBy('id','desc')->paginate(10);
        return view('admin.members.cuti', compact('cutis','member'));
    }

    public function ijin(Member $member)
    {
        $cutis = Cuti::where('member_id', $member->id)->where('cuti', 0)->orderBy('created_at', 'desc')->orderBy('id','desc')->paginate(10);
        return view('admin.members.ijin', compact('cutis','member'));
    }

    public function kasbon(Member $member)
    {
        $kasbons = Kasbon::where('member_id', $member->id)->orderBy('created_at', 'desc')->orderBy('id','desc')->paginate(10);
        return view('admin.members.kasbon', compact('kasbons','member'));
    }

    public function lembur(Member $member)
    {
        $lemburs = Lembur::where('member_id', $member->id)->orderBy('created_at', 'desc')->orderBy('id','desc')->paginate(10);
        return view('admin.members.lembur', compact('lemburs','member'));
    }

    public function tunjangan(Member $member)
    {
        $tunjangans = Tunjangan::with('akunDetail')->where('member_id', $member->id)->orderBy('created_at', 'desc')->orderBy('id','desc')->paginate(10);
        return view('admin.members.tunjangan', compact('tunjangans','member'));
    }

    public function penggajian(Member $member)
    {
        $gajis = Gaji::where('member_id', $member->id)->with(['member', 'bagian', 'level'])->orderBy('id','desc')->paginate(10);
        $penggajians = Penggajian::where('member_id', $member->id)->orderBy('id','desc')->paginate(10);
        $gajian = Penggajian::where('member_id', $member->id)->latest('id')->first();
        return view('admin.members.penggajian', compact('gajis','penggajians','gajian','member'));
    }

    public function penggajianFreelance(Member $member)
    {
        $penggajians = Penggajian::where('member_id', $member->id)->orderBy('id','desc')->paginate(10);
        return view('admin.members.penggajianFreelance', compact('penggajians','member'));
    }

    public function gaji(Member $member)
    {
        $gajis = Gaji::where('member_id', $member->id)->with(['member', 'bagian', 'level'])->orderBy('id','desc')->paginate(10);
        return view('admin.members.gaji', compact('gajis','member'));
    }

    public function freelance(Request $request)
    {
        $tab = $request->get('tab', 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';

        $members = Member::with(['user'])
            ->when($tab === 'nonaktif', fn ($q) => $q->nonaktif('freelance')->orderBy('id', 'desc'))
            ->when($tab === 'aktif', function ($q) {
                $q->freelance()
                    ->withSum(['freelanceTagihans as total_upah_belum_dibayar' => function ($q) {
                        $q->where('dibayar', 'belum');
                    }], 'nominal_upah')
                    ->orderBy('id', 'asc');
            })
            ->get();

        return view('admin.members.freelance', compact('members', 'tab'));
    }

    public function freelanceTagihan(Member $member)
    {
        $member->load('user');
        $tagihans = FreelanceTagihan::where('member_id', $member->id)
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);
        $totalBelumDibayar = FreelanceTagihan::where('member_id', $member->id)->where('dibayar', 'belum')->sum('nominal_upah');
        return view('admin.members.freelance-tagihan', compact('member', 'tagihans', 'totalBelumDibayar'));
    }

    public function showFreelance(Member $member)
    {
        return view('admin.members.showFreelance', compact('member'));
    }

    public function freelanceCreate()
    {
        return view('admin.members.freelance-create');
    }

    public function freelanceStore(Request $request)
    {
        $validated = $this->validateMemberModal($request, [
            'nama_lengkap' => 'required|string',
            'no_telp' => 'required|string',
            'tgl_lahir' => 'nullable|date',
            'tempat_lahir' => 'nullable|string',
            'alamat' => 'nullable|string',
            'no_rek' => 'nullable|string',
            'upah' => 'nullable|numeric|min:0',
            'lembur' => 'nullable|numeric|min:0',
        ]);

        Member::create(array_merge($validated, [
            'status' => 1,
            'jenis' => 'freelance',
        ]));

        return $this->memberModalResponse(
            $request,
            __('Freelance berhasil ditambahkan.'),
            route('members.freelance')
        );
    }

    public function editFreelance(Member $member)
    {
        return view('admin.members.freelance-edit', compact('member'));
    }

    public function updateFreelance(Request $request, Member $member)
    {
        $validated = $this->validateMemberModal($request, [
            'nama_lengkap' => 'required|string',
            'no_telp' => 'required|string',
            'status' => 'required|in:0,1',
            'jenis' => 'required|in:karyawan,freelance',
            'tgl_masuk' => 'nullable|date',
            'tgl_lahir' => 'nullable|date',
            'tempat_lahir' => 'nullable|string',
            'alamat' => 'nullable|string',
            'no_rek' => 'nullable|string',
            'upah' => 'nullable|numeric|min:0',
            'lembur' => 'nullable|numeric|min:0',
        ]);

        $member->update($validated);

        return $this->memberModalResponse(
            $request,
            __('Freelance berhasil diperbarui.'),
            route('members.showFreelance', $member->id)
        );
    }

    /** Form bayar satu tagihan (halaman terpisah) */
    public function bayarTagihan(FreelanceTagihan $freelanceTagihan)
    {
        if ($freelanceTagihan->dibayar === 'sudah') {
            return redirect()->route('members.freelanceTagihan', $freelanceTagihan->member_id)
                ->withErrors(['message' => 'Tagihan ini sudah dibayar.']);
        }
        $freelanceTagihan->load('member');
        $kas = AkunDetail::pluck('nama', 'id')->prepend('Pilih kas', '')->toArray();
        return view('admin.members.freelance-tagihan-bayar', compact('freelanceTagihan', 'kas'));
    }

    /** Proses bayar satu tagihan → buat 1 penggajian */
    public function storeBayarTagihan(Request $request)
    {
        $this->validateMemberModal($request, [
            'freelance_tagihan_id' => 'required|exists:freelance_tagihans,id',
            'akun_detail_id' => 'required|exists:akun_details,id',
        ]);
        $tagihan = FreelanceTagihan::findOrFail($request->freelance_tagihan_id);
        if ($tagihan->dibayar === 'sudah') {
            return back()->withErrors(['message' => 'Tagihan ini sudah dibayar.'])->withInput();
        }
        $total = $tagihan->nominal_upah;
        $member = $tagihan->member;

        DB::transaction(function () use ($tagihan, $request, $total, $member) {
            $penggajian = Penggajian::create([
                'member_id' => $member->id,
                'jam_lembur' => 0,
                'lembur' => 0,
                'pokok' => 0,
                'total' => $total,
                'jumlah_lain' => 0,
                'lain_lain' => 'Bayar tagihan ' . ($tagihan->tanggal ? \Carbon\Carbon::parse($tagihan->tanggal)->format('d/m/Y') : ''),
                'akun_detail_id' => $request->akun_detail_id,
            ]);
            $tagihan->update(['dibayar' => 'sudah', 'penggajian_id' => $penggajian->id]);

            $akunDetail = AkunDetail::findOrFail($request->akun_detail_id);
            $update = $akunDetail->saldo - $total;
            $akunDetail->update(['saldo' => $update]);

            BukuBesar::insert([
                'akun_detail_id' => $request->akun_detail_id,
                'ket' => 'bayar tagihan upah ke ' . $member->nama_lengkap,
                'kredit' => $total,
                'kode' => 'gji',
                'debet' => 0,
                'saldo' => $update,
                'created_at' => Carbon::now(),
            ]);
        });

        return $this->memberModalResponse(
            $request,
            'Tagihan berhasil dibayar.',
            route('members.freelanceTagihan', $tagihan->member_id)
        );
    }

    /** Form bayar semua tagihan (halaman terpisah, bukan createFreelance) */
    public function bayarSemuaTagihan(Member $member)
    {
        $totalBelumDibayar = FreelanceTagihan::where('member_id', $member->id)->where('dibayar', 'belum')->sum('nominal_upah');
        $jmlLembur = Lembur::where([['member_id', $member->id], ['dibayar', 'belum']])->where('status', 'approved')->sum('jam');
        $totalLembur = ($member->lembur ?? 0) * $jmlLembur;
        $totalSemua = $totalBelumDibayar + $totalLembur;

        if ($totalSemua <= 0) {
            return redirect()->route('members.freelanceTagihan', $member->id)
                ->withErrors(['message' => 'Tidak ada tagihan atau lembur yang belum dibayar.']);
        }
        $kas = AkunDetail::pluck('nama', 'id')->prepend('Pilih kas', '')->toArray();
        return view('admin.members.freelance-tagihan-bayar-semua', compact('member', 'totalBelumDibayar', 'jmlLembur', 'totalLembur', 'totalSemua', 'kas'));
    }

    /** Proses bayar semua tagihan + lembur → 1 penggajian */
    public function storeBayarSemuaTagihan(Request $request)
    {
        $this->validateMemberModal($request, [
            'member_id' => 'required|exists:members,id',
            'akun_detail_id' => 'required|exists:akun_details,id',
        ]);
        $member = Member::findOrFail($request->member_id);
        $tagihans = FreelanceTagihan::where('member_id', $member->id)->where('dibayar', 'belum')->get();
        $jmlLembur = Lembur::where([['member_id', $member->id], ['dibayar', 'belum']])->sum('jam');
        $totalLembur = ($member->lembur ?? 0) * $jmlLembur;
        $totalTagihan = $tagihans->sum('nominal_upah');
        $total = $totalTagihan + $totalLembur;

        if ($total <= 0) {
            return back()->withErrors(['message' => 'Tidak ada tagihan atau lembur yang belum dibayar.'])->withInput();
        }

        DB::transaction(function () use ($tagihans, $request, $total, $totalLembur, $jmlLembur, $member) {
            $ket = collect([]);
            if ($tagihans->isNotEmpty()) {
                $ket->push('Tagihan upah (' . $tagihans->count() . ' item)');
            }
            if ($jmlLembur > 0) {
                $ket->push('Lembur ' . $jmlLembur . ' jam');
            }

            $penggajian = Penggajian::create([
                'member_id' => $member->id,
                'jam_lembur' => $jmlLembur,
                'lembur' => $totalLembur,
                'pokok' => $tagihans->count(),
                'total' => $total,
                'jumlah_lain' => 0,
                'lain_lain' => 'Bayar ' . $ket->implode(' + '),
                'akun_detail_id' => $request->akun_detail_id,
            ]);
            FreelanceTagihan::where('member_id', $member->id)->where('dibayar', 'belum')
                ->update(['dibayar' => 'sudah', 'penggajian_id' => $penggajian->id]);
            Lembur::where([['member_id', $member->id], ['dibayar', 'belum']])
                ->update(['dibayar' => 'sudah']);

            $akunDetail = AkunDetail::findOrFail($request->akun_detail_id);
            $update = $akunDetail->saldo - $total;
            $akunDetail->update(['saldo' => $update]);

            BukuBesar::insert([
                'akun_detail_id' => $request->akun_detail_id,
                'ket' => 'bayar tagihan + lembur ke ' . $member->nama_lengkap,
                'kredit' => $total,
                'kode' => 'gji',
                'debet' => 0,
                'saldo' => $update,
                'created_at' => Carbon::now(),
            ]);
        });

        return $this->memberModalResponse(
            $request,
            'Tagihan dan lembur berhasil dibayar.',
            route('members.freelanceTagihan', $member->id)
        );
    }
}
