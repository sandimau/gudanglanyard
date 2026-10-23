<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Konten;
use App\Models\KontenTab;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KontenController extends Controller
{
    public function jadwal(Request $request)
    {
        $request->validate(['bulan' => ['nullable', 'date_format:Y-m']]);
        $bulan = $request->filled('bulan')
            ? Carbon::createFromFormat('!Y-m', $request->query('bulan'))
            : now()->startOfMonth();
        $awal = $bulan->copy()->startOfMonth();
        $akhir = $bulan->copy()->endOfMonth();
        $kalenderAwal = $awal->copy()->startOfWeek(Carbon::MONDAY);
        $kalenderAkhir = $akhir->copy()->endOfWeek(Carbon::SUNDAY);
        $kontens = Konten::whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])
            ->orderBy('waktu_posting')->orderBy('id')->get()->groupBy(fn ($konten) => $konten->tanggal->toDateString());
        $bolehTambahKonten = KontenTab::exists();
        $canSetupKonten = user_has_role_insensitive('super', 'manager');

        return view('admin.konten.jadwal', compact('bulan', 'kalenderAwal', 'kalenderAkhir', 'kontens', 'bolehTambahKonten', 'canSetupKonten'));
    }

    public function proses()
    {
        $tabs = KontenTab::ordered()->groupBy('grup');
        $kontens = Konten::with('tab')->orderBy('tanggal')->orderBy('waktu_posting')->orderBy('id')->get();
        $perTab = $kontens->groupBy('konten_tab_id');
        $canSetupKonten = user_has_role_insensitive('super', 'manager');

        return view('admin.konten.proses', compact('tabs', 'perTab', 'canSetupKonten'));
    }

    public function semua(Request $request)
    {
        $request->validate([
            'bulan' => ['nullable', 'date_format:Y-m'],
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $pencarian = trim((string) $request->query('q', ''));
        $bulanKembali = $request->query('bulan', now()->format('Y-m'));
        $kontens = Konten::with('tab')
            ->when($pencarian !== '', function ($query) use ($pencarian) {
                $query->where(function ($query) use ($pencarian) {
                    $query->where('topik', 'like', '%'.$pencarian.'%')
                        ->orWhere('platform', 'like', '%'.$pencarian.'%')
                        ->orWhere('tujuan', 'like', '%'.$pencarian.'%');
                });
            })
            ->orderByDesc('tanggal')
            ->orderBy('waktu_posting')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.konten.semua', compact('kontens', 'pencarian', 'bulanKembali'));
    }

    public function create(Request $request)
    {
        $request->validate(['tanggal' => ['nullable', 'date_format:Y-m-d']]);
        $tabs = KontenTab::ordered();
        if ($tabs->isEmpty()) {
            return $this->setupBelumAda();
        }

        $konten = new Konten(['tanggal' => $request->query('tanggal', now()->toDateString())]);

        return view('admin.konten.form', compact('konten', 'tabs'));
    }

    public function store(Request $request)
    {
        $tabPertama = KontenTab::ordered()->first();
        if (!$tabPertama) {
            return $this->setupBelumAda();
        }

        $data = $this->validated($request);
        $data['konten_tab_id'] = $tabPertama->id;
        Konten::create($data);

        $route = $request->input('asal') === 'proses'
            ? route('konten.proses')
            : route('konten.jadwal', ['bulan' => Carbon::parse($data['tanggal'])->format('Y-m')]);

        return redirect($route)
            ->withSuccess('Jadwal konten berhasil ditambahkan.');
    }

    public function edit(Konten $konten)
    {
        $tabs = KontenTab::ordered();

        return view('admin.konten.form', compact('konten', 'tabs'));
    }

    public function update(Request $request, Konten $konten)
    {
        $data = $this->validated($request);
        $location = $this->returnLocation($request, Carbon::parse($data['tanggal'])->format('Y-m'));
        $konten->update($data);

        return redirect($location)
            ->withSuccess('Jadwal konten berhasil diperbarui.');
    }

    public function destroy(Request $request, Konten $konten)
    {
        $location = $this->returnLocation($request, $konten->tanggal->format('Y-m'));
        $konten->delete();

        return redirect($location)
            ->withSuccess('Jadwal konten berhasil dihapus.');
    }

    public function updateTab(Request $request, Konten $konten)
    {
        $data = $request->validate([
            'konten_tab_id' => ['required', Rule::exists('konten_tabs', 'id')],
        ]);
        $konten->update($data);

        return back()->withSuccess('Proses konten berhasil diperbarui.');
    }

    public function chats(Konten $konten)
    {
        $chats = $konten->chats()->with(['member', 'user'])->orderBy('id')->get()
            ->map(fn (Chat $chat) => $this->chatData($chat));

        return response()->json(['chats' => $chats]);
    }

    public function storeChat(Request $request, Konten $konten)
    {
        $data = $request->validate([
            'isi' => ['required', 'string', 'max:5000'],
        ]);
        $member = Member::withoutGlobalScope('cabang')->where('user_id', auth()->id())->first();
        $chat = $konten->chats()->create([
            'isi' => $data['isi'],
            'member_id' => $member?->id,
            'user_id' => $member ? null : auth()->id(),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['chat' => $this->chatData($chat->load(['member', 'user']))], 201);
        }

        return back()->withSuccess('Pesan konten berhasil dikirim.');
    }

    private function chatData(Chat $chat): array
    {
        return [
            'id' => $chat->id,
            'author' => $chat->author_name ?: 'Anonim',
            'date' => $chat->created_at?->format('d/m/Y H:i') ?? '',
            'text' => $chat->isi,
        ];
    }

    private function setupBelumAda()
    {
        $canSetupKonten = user_has_role_insensitive('super', 'manager');

        return redirect()->route($canSetupKonten ? 'konten.setup' : 'konten.jadwal')
            ->with('danger', $canSetupKonten
                ? 'Buat tab proses konten terlebih dahulu sebelum menambahkan jadwal.'
                : 'Setup konten belum tersedia. Hubungi super atau manager sebelum menambahkan jadwal.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'topik' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'string', 'max:255'],
            'waktu_posting' => ['required', 'date_format:H:i'],
            'tujuan' => ['required', 'string'],
        ]);
    }

    private function returnLocation(Request $request, string $bulan): string
    {
        if ($request->input('asal') === 'proses') {
            return route('konten.proses');
        }

        if ($request->input('asal') !== 'semua') {
            return route('konten.jadwal', ['bulan' => $bulan]);
        }

        $data = $request->validate([
            'daftar_bulan' => ['nullable', 'date_format:Y-m'],
            'daftar_q' => ['nullable', 'string', 'max:255'],
            'daftar_page' => ['nullable', 'integer', 'min:1'],
        ]);

        return route('konten.semua', array_filter([
            'bulan' => $data['daftar_bulan'] ?? null,
            'q' => $data['daftar_q'] ?? null,
            'page' => $data['daftar_page'] ?? null,
        ], fn ($value) => $value !== null && $value !== ''));
    }
}
