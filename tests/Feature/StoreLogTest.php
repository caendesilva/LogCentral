<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class StoreLogTest extends TestCase
{
    public function test_logs_can_be_posted()
    {
        $response = $this->post('/api/logs', [
            'level' => 'INFO',
            'timestamp' => time(),
            'label' => 'Laravel Test',
            'message' => 'Hello World!',
            'context' => '{"foo": "bar"}',
        ]);

        $response->assertCreated();
    }
}
