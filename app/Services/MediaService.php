<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Media;

class MediaService
{
    public function uploadAndStoreMedia($file, $transactionId)
    {
        $s3_url = $this->uploadFileToS3($file, $transactionId);
        return $this->storeMediaData($file, $s3_url);
    }

    private function uploadFileToS3($file, $transactionId)
    {
        try {
            $s3_name = uniqid() . '.' . $file->getClientOriginalExtension();
            $s3Path = Storage::disk('s3')->putFileAs('', $file, $s3_name);
            Log::info('|' . $transactionId . '| recuperando o nome do arquivo gerado pelo S3', ['s3Path' => $s3Path]);
            $s3_url = Storage::disk('s3')->url($s3Path);
            Log::info('|' . $transactionId . '| recuperando o caminho do arquivo completo', ['$s3_url' => $s3_url]);
            return $s3_url;
        } catch (Exception $e) {
            Log::error('Erro ao enviar arquivo para o S3', ['exception' => $e]);
            throw new Exception('Erro ao enviar arquivo para o S3', 0, $e);
        }

    }

    private function storeMediaData($file, $s3_url)
    {
        try {
            $user = Auth::user();
            $data = [
                'user_id' => $user->id,
                'original_name' => $file->getClientOriginalName(),
                'file_type' => $file->getClientMimeType(),
                's3_name' => basename($s3_url),
                's3_url' => $s3_url,
            ];
            $create_media = Media::create($data);
            $data['id'] = $create_media->id;
        } catch (Exception $e) {
            Log::error('Erro ao salvar dados da mídia no banco', ['exception' => $e]);
            throw new Exception('Erro ao salvar dados da mídia no banco', 0, $e);
        }
        return $data;
    }
}
