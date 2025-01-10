<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:delete-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deleta os Tokens de reset de senha expirados na tabela de forgot_passwords';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deleted = DB::table('forgot_passwords')
            ->where('expires_in', '<', now())
            ->delete();
        Log::info("Deletando Tokens expirados => {$deleted}" );
        $this->info("Deleted {$deleted} expired tokens.");
    }
}
