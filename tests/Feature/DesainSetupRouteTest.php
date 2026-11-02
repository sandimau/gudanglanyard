<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Tests\TestCase;

class DesainSetupRouteTest extends TestCase
{
    public function test_setup_url_matches_setup_route_instead_of_desain_show(): void
    {
        $route = app('router')->getRoutes()->match(Request::create('/admin/desain/setup', 'GET'));

        $this->assertSame('desain.setup', $route->getName());
    }
}
