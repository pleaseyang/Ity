<?php

namespace Models;

use App\Models\Config;
use Tests\TestCase;

class ConfigTest extends TestCase
{

    public function testWechatPayConfig()
    {
        $key = "ba8b3d16c31a49e788d29a7770159a4c";
        $path = "D:\WXCertUtil\WXCertUtil\cert/1490503752_20221230_cert.zip";
        $res = Config::wechatPayConfig($key, $path);
        dd($res);
    }
}
