<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Api\TinyMceController;
use Illuminate\Http\Request;

/**
 * Upload de imagens do editor (TinyMCE) no CMS.
 * Delega ao mesmo fluxo usado em {@see TinyMceController::uploadImage} (API).
 */
class UploadImageController extends RestrictedController
{
    public function editorUpload(Request $request, TinyMceController $tinyMce)
    {
        return $tinyMce->uploadImage($request);
    }
}
