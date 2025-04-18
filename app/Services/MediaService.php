<?php

namespace App\Services;
use App\Http\Helpers\Responses;
use App\Models\lessonMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Media;
use Mockery\Exception;
use Aws\S3\S3Client;

class MediaService
{
    public $filename, $media ;
    public function uploadAndStoreMedia($file, $transactionId, $data)
    {
        $s3_url = $this->uploadFileToS3($file, $transactionId);
        return $this->storeMediaData($file, $s3_url, $data);
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
    private function storeMediaData($file, $s3_url, $request)
    {
        try {
            $user = Auth::user();
            $data = [
                'user_id' => $user->id,
                'original_name' => $file->getClientOriginalName(),
                'file_type' => $file->getClientMimeType(),
                's3_name' => basename($s3_url),
                's3_url' => $s3_url,
                'modules_id' => $request->modules_id ?? null,
                'members_area_id' => $request->members_area_id ?? null,
                'media_type' => $request->media_type  ?? null,
                'upload_status' => 'complete'
            ];
            $create_media = Media::create($data);
            $data['id'] = $create_media->id;
        } catch (Exception $e) {
            Log::error('Erro ao salvar dados da mídia no banco', ['exception' => $e]);
            throw new Exception('Erro ao salvar dados da mídia no banco', 0, $e);
        }
        return $data;
    }
    public function UpdateMediaById($media_id, $data)
    {
        // criar validação para verificar se a midia pertence ao usuario logado para atualizar
        $media = Media::where('id', $media_id)
            ->where('user_id', Auth::id())
            ->first();
        if (!$media) {
            throw new \Exception('Mídia não encontrada.');
        }
        $media->update($data);
        return $media;
    }

    public function RemoveOldMedia($entity, $transactionId = null, $fieldType = 'members_area_id')
    {
        Log::info("|" . $transactionId . "| RemoveOldMedia| Atualizando e removendo as mídias antigas e atualizando para as novas enviadas |", [
            'Dados da entidade que serão atualizados' => $entity,
        ]);

        // Atualizar mídias do tipo logo
        if (isset($entity->media_id_logo)) {
            $mediaQueryLogo = Media::where($fieldType, $entity->id)
                ->where('media_type', 'logo');
            $mediaQueryLogo->update([$fieldType => null]);
        }

        // Atualizar mídias do tipo Thumbnail
        if (isset($entity->media_id_thumb)) {
            $mediaQueryThumb = Media::where($fieldType, $entity->id)
                ->where('media_type', 'Thumbnail');
            $mediaQueryThumb->update([$fieldType => null]);
        }

        if (isset($entity->media_id_attachment)) {
            $mediaQueryThumb = Media::where($fieldType, $entity->id)
                ->where('media_type', 'Attachment');
            $mediaQueryThumb->update([$fieldType => null]);
        }

        if (isset($entity->media_id_banner)) {
            $mediaQueryThumb = Media::where($fieldType, $entity->id)
                ->where('media_type', 'Banner');
            $mediaQueryThumb->update([$fieldType => null]);
        }
    }


    public function initiateUpload(Request $request)
    {
        Log::info('Informações da AWS', ['region' => env('AWS_DEFAULT_REGION')]);

        $s3 = new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        $bucket =env('AWS_LESSON_BUCKET');
        $key = $request->input('key');

        // Inicia o upload multipart
        $result = $s3->createMultipartUpload([
            'Bucket' => $bucket,
            'Key'    => $key,
            'ACL'    => 'private',
        ]);

        $bucket_hls =env('AWS_HLS');
        $fileInfo = pathinfo($key);
// Caminho e nome do arquivo sem a extensão
        $fileNameWithoutExtension = $fileInfo['dirname'] . '/HLS/' . $fileInfo['filename'];

        $data = [
            'user_id' => Auth::id(),
            'original_name' => basename($key),
            'file_type' => pathinfo($key, PATHINFO_EXTENSION),
            's3_name' => basename($key),
            's3_url' => $fileNameWithoutExtension,
            'media_type' => 'Content',
            'upload_status' => 'starting'
        ];
        $this->media = Media::create($data);

        $response=[
            'uploadId' => $result['UploadId'],
            'key'      => $key,
            'media' =>  $this->media
        ];

        $video =[
            'lesson_id' => $request->lesson_id,
            'media_id' => $this->media->id
        ];
        lessonMedia::create($video);

        return Responses::SUCCESS('Chaves para iniciar Upload gerada com sucesso', $response, 201);
    }

    public function getPresignedUrl(Request $request)
    {
        $s3 = new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        $bucket = env('AWS_LESSON_BUCKET');
        $key = $request->input('key');
        $uploadId = $request->input('uploadId');
        $partNumber = $request->input('partNumber');

        // Gera URL pré-assinada para uma parte do upload
        $cmd = $s3->getCommand('UploadPart', [
            'Bucket'     => $bucket,
            'Key'        => $key,
            'UploadId'   => $uploadId,
            'PartNumber' => $partNumber,
        ]);

        $s3 = $s3->createPresignedRequest($cmd, '+20 minutes');

        $response = [
            'url' => (string) $s3->getUri(),
        ];
        Log::info("|" . $request->header('x-transaction-id') . "| URL Pre Assinada | Atualizando status para uploading |", [
            'UploadId' => $uploadId, 'PartNumber' => $partNumber
        ]);
        Media::where('id',  $request->input('media_id'))->update([
            'upload_status' => 'uploading'
        ]);

        return Responses::SUCCESS('Url Pré Assinada Gerada com sucesso', $response, 201);
    }

    public function completeUpload(Request $request)
    {
        $s3 = new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        $bucket =env('AWS_LESSON_BUCKET');
        $key = $request->input('key');
        $uploadId = $request->input('uploadId');
        $parts = $request->input('parts'); // Array de eTags e números de partes


        $result = $s3->completeMultipartUpload([
            'Bucket'   => $bucket,
            'Key'      => $key,
            'UploadId' => $uploadId,
            'MultipartUpload' => [
                'Parts' => $parts,
            ],
        ]);

        Media::where('id', $request->input('media_id'))->update([
            'upload_status' => 'complete'
        ]);

        Log::info("|" . $request->header('x-transaction-id') . "| Envio concluido com sucesso de um video | Atualizando status para Complete |", [
            'UploadId' => $uploadId, 'PartNumber' => $parts
        ]);
        return Responses::SUCCESS('Finalização do Upload marcada com sucesso', $result, 201);
    }

}
