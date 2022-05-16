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
        $data = ['name' => '2020-01-02 00:23:21'];

        $validator = Validator::make($data, ['name' => 'date']);

        if ($validator->fails()) {
            dd($validator->errors()->toArray());
        }

        dd('pass');
    }
}
