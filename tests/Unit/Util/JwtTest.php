<?php

namespace Tests\Unit\Util;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Tests\TestCase;

class JwtTest extends TestCase
{
    public function testJwt()
    {
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsb2dpblR5cGUiOiJhZG1pbiIsImxvZ2luSWQiOjEsInJuU3RyIjoiTVdSdm14enNPMnU5UWVlempOYWtwT29TaGZlZzBtNm4ifQ.Blwyt-GjwpWDOzN3YqlR1alIcvwP_ah7d3l8anElO2k';
        $key = InMemory::plainText('密钥');
        $singer = new Sha256();
        $configuration = Configuration::forUnsecuredSigner();
        $token = $configuration->parser()->parse($token);
        $configuration->setValidationConstraints(new SignedWith($singer, $key));
        $constraints = $configuration->validationConstraints();
        $verify = $configuration->validator()->validate($token, ...$constraints);
        if ($verify) {
            echo "jwt is success \n";
        } else {
            echo "jwt is error \n";
        }
        $loginId = $token->claims()->get('loginId');
        $loginType = $token->claims()->get('loginType');
        echo "loginType:" . $loginType . " loginId:" . $loginId;
        $this->assertTrue($verify);
    }
}
