<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Responses;
use App\Http\Requests\Modules\CreateModulesRequest;
use App\Http\Requests\Modules\UpdateModulesRequest;
use App\Models\MembersArea;
use App\Models\Modules;
use App\Services\ModuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ModulesController extends Controller
{

    public function __construct(protected ModuleService $modules_service)
    {

    }
    public function index(Request $request)
    {
        $itemsPerPage = $request->query('items_per_page', 10);

        try {
            $get_members_modules = $this->modules_service->show($itemsPerPage);
            return Responses::SUCCESS('Módulos retornadas com sucesso', $get_members_modules);

        } catch (\Throwable $th) {
            Log::error('Não foi possível listar os Módulos', ['error' => $th->getMessage()]);
            return Responses::ERROR('Não foi possível recuperar a lista de Módulos. Erro genérico não mapeado',null,'-9999',400);
        }
    }

    public function store(CreateModulesRequest $request)
    {
        $modulo= $request->validated();
        $user = Auth::user();

        $getMembership= MembersArea::where('id', $request->members_area_id)
            ->where('user_id', $user->id)
            ->where('is_blocked', 0)
            ->where('is_deleted', 0)
            ->first();

        if (!$getMembership) {
            return Responses::ERROR('Area de Membros bloqueada ou não localizada', null, -1100, 400);
        }

        try {
            $create_module = $this->modules_service->createModule($modulo, $request);
            $create_module['members_area']['area_type'] =  $getMembership->area_type;
            return Responses::SUCCESS('Módulo criado com sucesso',$create_module, 201);

        } catch (\Throwable $th) {
            Log::error('Não foi possível criar o Módulo', ['error' => $th->getMessage()]);
            return Responses::ERROR('Ocorreu um erro ao tentar criar o Módulo', null, '-9999', 400);
        }
    }

    public function update(UpdateModulesRequest $request)
    {
        $modules_to_update = $request->validatedModule();
        $data_module_received = $request->validated();
        try {
            $formatted_data = $this->modules_service->Update($modules_to_update,$data_module_received, request()->header('x-transaction-id'));
            return Responses::SUCCESS('Módulo atualizado com sucesso', $formatted_data);
        }
        catch (\Throwable $th) {
            Log::error('Não foi possível atualizar o Módulo', ['error' => $th->getMessage()]);
            return Responses::ERROR('Não foi possível atualizar o Módulo. Erro genérico não mapeado', null, '-9999');
        }
    }

    public function destroy(UpdateModulesRequest $destroy)
    {
        $validated_module = $destroy->validatedModule();
        $updated_module= $validated_module->update([ 'is_deleted' => true]);

        return Responses::SUCCESS('Módulo excluido com sucesso', $updated_module);
    }

    public function getLessonsByModules($modules_id)
    {
        try {
            $get_members_modules = $this->modules_service->showByModules( $modules_id);
            return Responses::SUCCESS('Aulas retornadas com sucesso', $get_members_modules);

        } catch (\Throwable $th) {
            Log::error('Não foi possível listar as Aulas desse Módulo', ['error' => $th->getMessage()]);
            return Responses::ERROR('Não foi possível recuperar a lista de Aulas desse Módulo. Erro genérico não mapeado',null,'-9999',400);
        }
    }
}
