<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToMemberModal;
use App\Models\Member;
use App\Models\Sistem;
use App\Models\Whattodo;
use App\Jobs\DeleteOrders;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    use RespondsToMemberModal;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $gajiRecord = Whattodo::where('nama', 'gaji')->first();
        if ($gajiRecord) {
            $lastDay = (int) $gajiRecord->isi;
            $today = (int) date('j');

            if ($lastDay !== $today) {
                foreach ($this->getMissedGajianDays($lastDay, $today) as $day) {
                    foreach ($this->getMembersByGajianDay($day) as $row) {
                        $dayLabel = str_pad($day, 2, '0', STR_PAD_LEFT);
                        Whattodo::create([
                            'isi' => $row->nama_lengkap . ' gajian tanggal ' . $dayLabel,
                            'nama' => 'gajian',
                        ]);
                    }
                }

                $gajiRecord->update(['isi' => date('d')]);
            }
        }

        $member = Member::where('user_id', auth()->id())->first();
        if ($member) {
            $whatMember = Whattodo::where('member_id', $member->id)
                ->where('nama', 'tugas')
                ->get();
        }

        $whattodos = Whattodo::where('nama','!=','gaji')->get();
        $sistems = Sistem::get()->pluck('isi', 'nama');
        $whatMember = $whatMember ?? collect(); // Initialize if not set
        return view('admin.whattodos.home', compact('whattodos', 'whatMember'));
    }

    public function create()
    {
        $id = request()->get('member_id');
        $member = Member::find($id);
        return view('admin.whattodos.create', compact('member'));
    }

    public function store(Request $request)
    {
        $this->validateMemberModal($request, [
            'isi' => 'required|string',
            'member_id' => 'nullable|exists:members,id',
            'user_id' => 'nullable|exists:users,id',
        ]);

        Whattodo::create([
            'isi' => $request->isi,
            'nama' => 'tugas',
            'member_id' => $request->member_id,
            'user_id' => $request->user_id
        ]);

        return $this->memberModalResponse(
            $request,
            __('Whattodo created successfully.'),
            url('/whattodo')
        );
    }

    public function edit(Whattodo $what)
    {
        return view('admin.whattodos.edit', compact('what'));
    }

    public function update(Whattodo $what, Request $request)
    {
        $validated = $this->validateMemberModal($request, [
            'isi' => 'required|string',
        ]);

        $what->update([
            'isi' => $validated['isi'],
        ]);

        return $this->memberModalResponse(
            $request,
            __('Tugas berhasil diperbarui.'),
            url('/whattodo')
        );
    }

    public function destroy(Whattodo $what)
    {
        $what->delete();
        return back()->withDanger(__('Whattodo deleted successfully.'));
    }

    public function DeleteOrders()
    {
        $tafio = new DeleteOrders;
        $tafio->deleteOrders();
    }

    private function getMissedGajianDays(int $lastDay, int $today): array
    {
        if ($today > $lastDay) {
            return range($lastDay + 1, $today);
        }

        $daysInPrevMonth = (int) date('t', strtotime('last day of previous month'));
        $days = $lastDay < $daysInPrevMonth
            ? range($lastDay + 1, $daysInPrevMonth)
            : [];

        return array_merge($days, range(1, $today));
    }

    private function getMembersByGajianDay(int $day)
    {
        $dayPadded = str_pad($day, 2, '0', STR_PAD_LEFT);

        return Member::where('status', 1)
            ->where(function ($query) use ($day, $dayPadded) {
                $query->where('tgl_gajian', (string) $day)
                    ->orWhere('tgl_gajian', $dayPadded)
                    ->orWhereRaw('DAY(tgl_gajian) = ?', [$day]);
            })
            ->get();
    }
}
