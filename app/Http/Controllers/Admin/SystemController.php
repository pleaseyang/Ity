<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Response\ApiCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class SystemController extends Controller
{
    public function logo(Request $request): Response
    {
        $file = $request->file('logo');
        $exists = Storage::exists('public/config');
        if ($exists === false) {
            Storage::makeDirectory('public/config');
        }
        $path = Storage::putFileAs(
            'public/config', $file, 'logo.png'
        );
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData([
                'path' => $path
            ])
            ->withMessage(__('message.common.update.success'))
            ->build();
    }
}
