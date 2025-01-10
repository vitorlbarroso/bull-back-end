<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Responses;
use App\Http\Requests\MembersArea\CreateMembersAreaRequest;
use App\Http\Requests\MembersArea\DestroyMembersAreaRequest;
use App\Http\Requests\MembersArea\UpdateMembersAreaRequest;
use App\Services\MediaService;
use App\Services\MembersAreaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MembersAreaController extends Controller
{

    public function __construct(protected MembersAreaService $members_service, protected MediaService $mediaService)
    {

    }

    public function index(Request $request)
    {
        $itemsPerPage = $request->query('items_per_page', 10);

        try {
            $get_members = $this->members_service->show($itemsPerPage);
            return Responses::SUCCESS('Áreas de Membros retornadas com sucesso', $get_members);

        } catch (\Throwable $th) {
            Log::error('Não foi possível listar as áreas de membros', ['error' => $th->getMessage()]);
            return Responses::ERROR('Não foi possível recuperar a lista de área de membros. Erro genérico não mapeado',null,'-9999',400);
        }
    }

    public function store(CreateMembersAreaRequest $request)
    {
        $members= $request->validated();
        $members['user_id'] = Auth::user()->id;
        try{
            $response = $this->members_service->createMembersArea($members, $request);
            return Responses::SUCCESS('Áreas de Membros Criada com sucesso', $response,201);

        } catch (\Throwable $th) {
            Log::error('Não foi possível criar a área', ['error' => $th->getMessage()]);
            return Responses::ERROR('Ocorreu um erro ao tentar criar a area de membros', null, '-9999', 400);
        }
    }

    public function update(UpdateMembersAreaRequest $request)
    {
        $member_area_to_update = $request->validatedArea();
        $data_area_received = $request->validated();
        try {
            $formatted_data = $this->members_service->Update($member_area_to_update, $data_area_received, request()->header('x-transaction-id')) ;
            return Responses::SUCCESS('Área de membros atualizada com sucesso', $formatted_data);
        }
        catch (\Throwable $th) {
            Log::error('Não foi possível atualizar a Área de membros', ['error' => $th->getMessage()]);
            return Responses::ERROR('Não foi possível atualizar o a Área de Membros. Erro genérico não mapeado', null, '-9999');
        }
    }

    public function destroy(DestroyMembersAreaRequest $destroy)
    {

        $validatedArea = $destroy->validatedArea();
        $updatedArea = $validatedArea->update([ 'is_deleted' => true]);

        return Responses::SUCCESS('Área de membros removida com sucesso', $updatedArea);
    }

    public function getModulesByMembership(Request $request, $membership_id)
    {
        $itemsPerPage = $request->query('items_per_page', 10);
        try {
            $get_members_modules = $this->members_service->showByMembership($itemsPerPage, $membership_id);
            return Responses::SUCCESS('Módulos retornadas com sucesso', $get_members_modules);

        } catch (\Throwable $th) {
            Log::error('Não foi possível listar os Módulos', ['error' => $th->getMessage()]);
            return Responses::ERROR('Não foi possível recuperar a lista de Módulos. Erro genérico não mapeado',null,'-9999',400);
        }
    }

}
