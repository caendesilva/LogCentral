<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\TestCase;
 
class StoreLogTest extends TestCase
{
    public function test_logs_cannot_be_posted_as_unauthenticated_user()
    {
        $response = $this->postJson('/api/logs/99999/store');
         
        $response->assertUnauthorized();
    }

    public function test_logs_cannot_be_posted_as_authenticated_user_without_team()
    {
        Sanctum::actingAs(
            $user = User::factory()->create()
        );
        $response = $this->postJson('/api/logs/99999/store');
       
        $response->assertNotFound();
    }

    public function test_logs_can_be_posted_as_authenticated_user()
    {
        Sanctum::actingAs(
            $user = User::factory()->withPersonalTeam()->create(), ['log:create']
        );

        $response = $this->postJson('/api/logs/'.$user->currentTeam->id.'/store', [
            'level' => 'INFO',
            'timestamp' => time(),
            'label' => 'Laravel Test',
            'message' => 'Hello World!',
            'context' => '{"foo": "bar"}',
        ]);

        $this->assertCount(1, $user->fresh()->logs);
        $response->assertCreated();
    }

    public function test_logs_cannot_be_posted_without_proper_token_permission()
    {
        Sanctum::actingAs(
            $user = User::factory()->withPersonalTeam()->create(), ['log:read']
        );

        $response = $this->postJson('/api/logs/'.$user->currentTeam->id.'/store');

        $this->assertCount(0, $user->fresh()->logs);
        $response->assertForbidden();
    }

    public function test_logs_cannot_be_posted_without_proper_team_permission()
    {
        Sanctum::actingAs(
            $user = User::factory()->withPersonalTeam()->create()
        );

        $user->currentTeam->users()->attach(
            $otherUser = User::factory()->create(), ['role' => 'viewer']
        );

        Sanctum::actingAs(
            $otherUser
        );


        $response = $this->postJson('/api/logs/'.$user->currentTeam->id.'/store');

        $this->assertCount(0, $otherUser->fresh()->logs);
        $response->assertForbidden();
    }


}
