<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureRingkasanRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RingkasanRoleTest extends TestCase
{
    public function test_semua_halaman_dan_popup_ringkasan_memakai_pembatas_role(): void
    {
        foreach (['/admin/direktur-keuangan', '/admin/ringkasan-hutang', '/admin/ringkasan-keuangan', '/admin/ringkasan-detail/hutang/bayar', '/admin/ringkasan-detail/keuangan/omzet'] as $url) {
            $route = app('router')->getRoutes()->match(Request::create($url));
            $this->assertContains(EnsureRingkasanRole::class, $route->gatherMiddleware());
        }
    }

    public function test_hanya_super_dan_manager_diizinkan(): void
    {
        $middleware = new EnsureRingkasanRole();
        $request = Request::create('/admin/ringkasan-keuangan');

        foreach (['super', 'Manager'] as $role) {
            Auth::shouldReceive('user')->once()->andReturn((object) ['roles' => collect([(object) ['name' => $role]])]);
            $this->assertSame('ok', $middleware->handle($request, fn() => 'ok'));
        }

        Auth::shouldReceive('user')->once()->andReturn((object) ['roles' => collect([(object) ['name' => 'keuangan']])]);
        try {
            $middleware->handle($request, fn() => 'ok');
            $this->fail('Role selain super dan manager tidak boleh mengakses ringkasan.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }
}
