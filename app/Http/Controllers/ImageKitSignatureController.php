<?php

namespace App\Http\Controllers;

use ImageKit\ImageKit;
use Symfony\Component\HttpFoundation\Response;

class ImageKitSignatureController extends Controller
{
    public function system(): Response
    {
        $imageKit = new ImageKit(
            ...array_values(config('system-imagekit'))
        );
        $signature = $imageKit->getAuthenticationParameters();

        return response()->json($signature);
    }
}
