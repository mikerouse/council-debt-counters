<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_pages(): void
    {
        $response = $this->withHeaders(['Accept' => 'application/json'])->get('/admin/figure-submissions');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_view_submission_list(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/admin/figure-submissions');
        $response->assertStatus(200);
    }
}
