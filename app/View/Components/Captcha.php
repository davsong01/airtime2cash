<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Captcha extends Component
{
    public $groupClass;

    public function __construct($groupClass = '')
    {
        $this->groupClass = $groupClass;
    }

    public function render()
    {
        return view('components.captcha');
    }
}
