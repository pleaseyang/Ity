<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function testValidateSize()
    {
        $data = ['name' => '1.00'];

        $validator = Validator::make($data, ['name' => 'integer']);

        if ($validator->fails()) {
            dd($validator->errors()->toArray());
        }

        dd('pass');
    }
}
