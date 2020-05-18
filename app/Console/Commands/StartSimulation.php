<?php

namespace App\Console\Commands;

use App\Http\Controllers\SimulationController;
use App\Ticket;
use App\User;
use Facade\Ignition\Support\Packagist\Package;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class StartSimulation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'start {users_count} {bank}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start simulation';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $args = $this->arguments();
        $validator = Validator::make([
            'users_count' => $args['users_count'],
            'bank' => $args['bank'],
        ], [
            'users_count' => ['required', 'integer', 'min:10'],
            'bank' => ['required', 'integer', 'min:100'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        if ($args['bank'] < $args['users_count'] * 10) {
            $this->error('Bank must be at least 10 times bigger than users_count.');
            return 1;
        }

        $this->info('Simulation starting...');

        $controller = app()->make('App\Http\Controllers\SimulationController');
        $result = app()->call([$controller, 'start'], ['users_count' => (int) $args['users_count'], 'bank' => (int) $args['bank']]);

        $this->info('Simulation completed');

        $this->info('Номер розыгрыша ' . $result['lottery_number']);
        $this->info('Топ 10 участников:');

        foreach ($result['top_ten_users'] as $user) {
            $this->comment($user->first_name . ' ' . $user->last_name . ' ' . $user->amount);
        }
    }
}
