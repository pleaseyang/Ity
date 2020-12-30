<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use View;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * homePage
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function home()
    {
        View::addExtension('html', 'php');
        return view()->file(public_path('index.html'));
    }
}
