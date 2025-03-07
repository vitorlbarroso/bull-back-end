<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Responses;
use App\Http\Requests\Authentication\DefineForgotPasswordRequest;
use App\Http\Requests\Authentication\ForgotPasswordRequest;
use App\Http\Requests\Authentication\UserLoginRequest;
use App\Http\Requests\Authentication\UserRegisterRequest;
use App\Mail\Authentication\RecoverPasswordMail;
use App\Models\ForgotPassword;
use App\Services\UserPaymentsDataService;
use App\Services\UserService;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\PersonalAccessToken;
use App\Traits\Cachable;
class AuthController extends Controller
{
    use Cachable;
    public function login(UserLoginRequest $request)
    {
        $hasUser = User::where('email', $request->email)->first();

        if (!$hasUser || !Hash::check($request->password, $hasUser->password)) {
            return Responses::ERROR('E-mail ou senha incorretos', null, null, 401);
        }

        $token = $hasUser->createToken('auth_token')->plainTextToken;

        $hasUser->last_login = Carbon::now();
        $hasUser->save();

        return Responses::SUCCESS('Usuário autenticado com sucesso!', [
            'token' => $token,
            'token_type' => 'bearer',
        ]);
    }

    public function show(Request $request)
    {
        $user = Auth::user();

        $user->load('profile_media');

        $getPayConfigsPendences = UserService::getPayConfigsPendences();
        $user['pay_configs_pendences'] = $getPayConfigsPendences;

        $getUserTotalSales = UserPaymentsDataService::getUserTotalSalesInAllPeriod();
        $user['total_sales'] = $getUserTotalSales / 100;

        /*$this->getOrSetCache($request->header('x-transaction-id'),'user_' . Auth::id() . '_current_', function () {
            return Auth::user();
        }, 600);*/

        return Responses::SUCCESS("Usuário autenticado", $user);
    }

    public function store(UserRegisterRequest $request)
    {
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ];

        try {
            $createUser = User::create($data);

            $token = $createUser->createToken('auth_token')->plainTextToken;

            $data = [
                'user' => $createUser,
                'token' => $token,
                'token_type' => 'bearer',
            ];

            $createUser->last_login = Carbon::now();
            $createUser->save();

            return Responses::SUCCESS('Usuário criado com sucesso!', $data, 201);
        }
        catch (\Throwable $th) {
            Log::warning('Não foi possível criar a conta e inserir os dados do novo cadastro', ['error' => $th->getMessage()]);

            if(strpos($th->getMessage(), "SQLSTATE[23000]") !== false)
                return Responses::ERROR('E-mail já existente na base de dados', null, '-1000', 409);

            return Responses::ERROR('Erro genérico não mapeado', null, '-9999', 500);
        }
    }

    public function destroy(Request $request)
    {
        $accessToken = $request->bearerToken();

        $token = PersonalAccessToken::findToken($accessToken);

        $token->delete();

        return Responses::SUCCESS('Logout realizado com sucesso!');
    }

    public function forgot_password(ForgotPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return Responses::ERROR('Usuário inválido ou com token ainda em aberto!', null, '-1000');
        }

        $hasRecentlyToken = ForgotPassword::where('user_id', $user->id)
                                            ->where('expires_in', '>', Carbon::now())
                                            ->exists();

        if ($hasRecentlyToken) {
            return Responses::ERROR('Usuário inválido ou com token ainda em aberto', null, '-1100');
        }

        $randomToken = md5($user->id . time());

        try {
            \DB::transaction(function() use ($user, $randomToken) {
                ForgotPassword::create([
                    'user_id' => $user->id,
                    'token' => $randomToken,
                    'expires_in' => Carbon::now()->addMinutes(10)
                ]);

                Mail::to($user->email)->send(new RecoverPasswordMail($user->name, $randomToken, $user->email));
            });

            return Responses::SUCCESS('Recuperação de senha gerada com sucesso!', null, 200);
        }
        catch (\Throwable $th) {
            Log::warning('Não foi possível gerar um token de recuperação de senha', ['error' => $th->getMessage()]);

            return Responses::ERROR('Não foi possível possível gerar um token de recuperação de senha', null, '-9999');
        }
    }

    public function verify_token(Request $request)
    {
        $token = $request->query('token');
        $email = $request->query('email');

        if (!$token || !$email)
            return Responses::ERROR('Dados obrigatórios não preenchidos', null, '-1000');

        $tokenIsValid = ForgotPassword::where('token', $token)
                                        ->where('expires_in', '>', Carbon::now())
                                        ->whereHas('user', function ($query) use ($email) {
                                            $query->where('email', $email);
                                        })
                                        ->exists();

        if (!$tokenIsValid) {
            return Responses::ERROR('Token não existente ou expirado', null, '-1100');
        }

        return Responses::SUCCESS('Token existente e não expirado');
    }

    public function define_forgot_password(DefineForgotPasswordRequest $request)
    {
        $tokenIsValid = ForgotPassword::where('token', $request->token)
                                        ->where('expires_in', '>', Carbon::now())
                                        ->whereHas('user', function ($query) use ($request) {
                                            $query->where('email', $request->email);
                                        })
                                        ->first();

        if (!$tokenIsValid) {
            return Responses::ERROR('Token não existente ou expirado', null, '-1100');
        }

        try {
            $updateUserPassword = $tokenIsValid->user->update([
                'password' => Hash::make($request->new_password)
            ]);

            $token = $tokenIsValid->user->createToken('auth_token')->plainTextToken;

            $returnTokenData = [
                'user' => $tokenIsValid->user,
                'token' => $token,
                'token_type' => 'bearer'
            ];

            $tokenIsValid->delete();

            return Responses::SUCCESS('A nova senha foi definida com sucesso', $returnTokenData);
        }
        catch (\Throwable $th) {
            Log::warning('Não foi possível definir uma nova senha para recuperação de senha', ['error' => $th->getMessage()]);

            return Responses::ERROR('Não foi possível definir uma nova senha para recuperação de senha', null, '-9999');
        }
    }
}
