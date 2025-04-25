<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Responses;
use App\Http\Requests\Media\UpdateMediaRequest;
use App\Http\Requests\Media\UploadMediaRequest;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{

    public function __construct(protected MediaService $mediaService)
    {
    }

    public function __invoke(UploadMediaRequest $request)
    {
        try {
            $file = $request->file('file');
            $transactionId = $request->header('x-transaction-id');
            $data = $this->mediaService->uploadAndStoreMedia($file, $transactionId, $request);
            return Responses::SUCCESS('Media inserida no S3 com sucesso!', $data, 201);
        } catch (\Exception $e) {
            return Responses::ERROR('Não foi possível processar a solicitação', $e->getMessage(), '-9999', 500);
        }
    }
    public function update(UpdateMediaRequest $request)
    {
        try {
            $mediaToUpdate = $request->validatedMedia();
            $data = $this->mediaService->UpdateMediaById($mediaToUpdate->id, $request);
            return Responses::SUCCESS('Dados da Media Atualizada com sucesso!', $data, 201);
        } catch (\Exception $e) {
            return Responses::ERROR('Não foi possível processar a solicitação', $e->getMessage(), '-9999', 500);
        }
    }

}
