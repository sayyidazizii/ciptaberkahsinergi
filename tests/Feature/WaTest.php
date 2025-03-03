<?php

namespace Tests\Feature;

use Tests\TestCase;
use Cst\WALaravel\WA;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WaTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_send_message_via_wa()
    {
        $wa = WA::test();
        fwrite(STDOUT,$wa);
        $this->assertTrue(true);
    }
}
