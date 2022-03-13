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
            $user = User::factory()->withPersonalTeam()->create()
        );

        $response = $this->post('/user/api-tokens', [
            'name' => 'Test Token',
            'permissions' => [
                'read',
                'update',
            ],
        ]);
        
        $this->assertCount(1, $user->fresh()->tokens);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . session()->get('flash.token'),
        ])->postJson('/api/logs/'.$user->currentTeam->id.'/store', [
            'level' => 'INFO',
            'timestamp' => time(),
            'label' => 'Laravel Test',
            'message' => 'Hello World!',
            'context' => '{"foo": "bar"}',
        ]);

        $response->assertCreated();
    }
}
