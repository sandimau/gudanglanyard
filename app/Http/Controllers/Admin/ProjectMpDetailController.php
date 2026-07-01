<?php

namespace App\Http\Controllers\Admin;

use App\Models\Chat;
use App\Models\Produksi;
use App\Models\ProjectMp;
use App\Services\StokService;
use Illuminate\Http\Request;
use App\Models\ProjectMpDetail;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use App\Http\Controllers\Controller;

class ProjectMpDetailController extends Controller
{

    public function detail(Request $request, $projectMp)
    {
        $projectMp = ProjectMp::find($projectMp);
        $projectMpdetails = ProjectMpDetail::where("project_id",$projectMp->id)->get();
        $marketplace = $projectMp->marketplace;

        $produksi = Produksi::orderBy('urutan')->get();
        $chats = Chat::where('project_mp_id', $projectMp->id)->get();

        return view('admin.projectmps.detail', compact('projectMp', 'marketplace', 'projectMpdetails', 'produksi', 'chats'));
    }

    public function updateStatus(Request $request, ProjectMpDetail $projectMp)
    {
        DB::transaction(function () use ($projectMp, $request) {
            $produk = $projectMp->produk;

            if ($produk?->produkModel?->stok == 1) {
                $awalProduksi = Produksi::find($projectMp->produksi_id);
                $perubahanProduksi = Produksi::find($request->produksi_id);

                if ($awalProduksi && $perubahanProduksi) {
                    $awal = $awalProduksi->grup;
                    $perubahan = $perubahanProduksi->grup;

                    if ($projectMp->projectMp->konsumen) {
                        $username = '(' . $projectMp->projectMp->konsumen . ')';
                    } else {
                        $username = '';
                    }

                    $stokService = app(StokService::class);

                    if ($awal == 'awal' and $perubahan != 'awal' and $perubahan != 'batal') {
                        $stokService->kurang(
                            $produk->id,
                            $projectMp->jumlah,
                            'jual',
                            'barang dijual ke ' . $projectMp->projectMp->marketplace->nama . ' ' . $username,
                            $projectMp->projectMp->id,
                            [],
                            false
                        );
                    }
                    if ($awal == 'selesai' and $perubahan == 'batal') {
                        $stokService->tambah(
                            $produk->id,
                            $projectMp->jumlah,
                            'btl',
                            'barang dikembalikan dari ' . $projectMp->projectMp->kontak->nama . ' ' . $username,
                            $projectMp->projectMp->id
                        );
                    }
                }
            }

            $projectMp->update([
                'produksi_id' => $request->produksi_id,
                'hpp' => $produk?->hpp,
            ]);
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => __('Status updated successfully.')]);
        }

        return redirect('/admin/projectMpDetail/' . $projectMp->projectMp->id)->withSuccess(__('Status updated successfully.'));
    }

    public function gambar(ProjectMpDetail $detail)
    {
        return view('admin.projectmps.gambar', compact('detail'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'gambar' => 'required|mimes:jpeg,png,jpg',
        ]);

        $ProjectMpDetail = ProjectMpDetail::find($request->ProjectMp_detail_id);
        $gambar = null;
        if ($request->hasFile('gambar')) {
            $img = $request->file('gambar');
            $filename = time() . '.' . $request->gambar->extension();
            $img_resize = Image::make($img->getRealPath());
            $img_resize->resize(500, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $save_path = public_path('uploads/projectMp/');
            if (!file_exists($save_path)) {
                try {
                    mkdir($save_path, 0755, true);
                } catch (\Exception $e) {
                    throw new \Exception('Unable to create directory. Please check folder permissions.');
                }
            }
            $img_resize->save($save_path . $filename);
            $gambar = $filename;
        }

        $ProjectMpDetail->update([
            'gambar' => $gambar,
        ]);

        return redirect('/admin/projectMpDetail/' . $ProjectMpDetail->projectMp->id)->withSuccess(__('Gambar detail updated successfully.'));
    }
    public function editGambar(ProjectMpDetail $detail)
    {
        return view('admin.projectmps.editGambar', compact('detail'));
    }

    public function updateGambar(Request $request)
    {
        $request->validate([
            'gambar' => 'required|mimes:jpeg,png,jpg',
        ]);

        $ProjectMpDetail = ProjectMpDetail::find($request->ProjectMp_detail_id);
        $gambar = null;
        if ($request->hasFile('gambar')) {
            $img = $request->file('gambar');
            $filename = time() . '.' . $request->gambar->extension();
            $img_resize = Image::make($img->getRealPath());
            $img_resize->resize(500, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $save_path = public_path('uploads/projectMp/');
            if (!file_exists($save_path)) {
                try {
                    mkdir($save_path, 0755, true);
                } catch (\Exception $e) {
                    throw new \Exception('Unable to create directory. Please check folder permissions.');
                }
            }
            $img_resize->save($save_path . $filename);
            $gambar = $filename;
        }

        if ($ProjectMpDetail->gambar) {
            unlink("uploads/projectMp/" . $ProjectMpDetail->gambar);
        }

        $ProjectMpDetail->update([
            'gambar' => $gambar,
        ]);

        return redirect('/admin/projectMpDetail/' . $ProjectMpDetail->projectMp->id)->withSuccess(__('Gambar detail updated successfully.'));
    }

    public function edit(ProjectMpDetail $detail)
    {
        return view('admin.projectmps.editDetail', compact('detail'));
    }

    public function update(Request $request, ProjectMpDetail $detail)
    {
        $detail->update($request->all());
        $detail->projectMp->update([
            'deadline' => $request->deadline,
        ]);
        return redirect('/admin/projectMpDetail/' . $detail->projectMp->id)->withSuccess(__('Detail updated successfully.'));
    }
}
