<?php

namespace Tests\Fakes;

class FakeImageKit
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function uploadFile(array $options = []): object
    {
        return (object) ['error' => null, 'result' => (object) ['url' => asset('avatar.png')]];
    }
}
