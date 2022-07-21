<?php

namespace App\Helper;

use Illuminate\Support\Str;

trait Tokenable
{
    public function generateAndSaveApiAuthToken()
    {
        $token = Str::random(60);

        // $this->mb_token = hash('sha256', $token);
        $this->mb_token = $token;
        $this->save();

        return $token;
    }
}
