<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Console\Commands;

use App\Http\Service\Admin\AdminService;
use App\Http\Service\Company\CompanyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'company:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('You are welcome to use the Crmeb-OA application as a template to jump start your own Seam projects!');
        $this->newLine();
        if (! env('CONFIG_INIT')) {
            $connect  = false;
            $app_name = $this->ask('Please enter your application name');
            modify_env(['APP_NAME' => $app_name]);
            do {
                $DB_HOST     = $this->ask('Please enter your database host');
                $DB_DATABASE = $this->ask('Please enter your database name');
                $DB_USERNAME = $this->ask('Please enter your database user');
                $DB_PASSWORD = $this->ask('Please enter your database password');
                try {
                    $connect = mysqli_connect($DB_HOST, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE, 3306);
                    modify_env([
                        'DB_HOST'     => $DB_HOST,
                        'DB_DATABASE' => $DB_DATABASE,
                        'DB_USERNAME' => $DB_USERNAME,
                        'DB_PASSWORD' => $DB_PASSWORD,
                        'CONFIG_INIT' => true,
                    ]);
                } catch (\Exception $e) {
                    $this->error('Database connection error:' . $e->getMessage());
                }
            } while ((bool) $connect);
            $this->newLine();
        }
        if ($this->confirm('Load default data？', 'yes')) {
            $this->info('Loading initialization data...');
            $this->call('migrate');
            Event::dispatch('init.CreateMenus');
            $this->info('Loaded initialization data.');
            $this->newLine();
        }
        $userService = app()->get(AdminService::class);
        if (! $userService->count()) {
            $phone = $this->ask('Please enter your administrator phone');
            do {
                $password        = $this->secret('Please enter your administrator password');
                $passwordConfirm = $this->secret('Please confirm your password');
                if ($password != $passwordConfirm) {
                    $this->info('请确认密码是否一致');
                }
            } while ($password != $passwordConfirm);
            $userService->register($phone, $password);
            $this->newLine();
        }
        $entService = app()->get(CompanyService::class);
        if (! $entService->count()) {
            $enterprise_name  = $this->ask('Please enter your company name');
            $lead             = $this->ask('Please enter your company lead');
            $address          = $this->ask('Please enter your company address');
            $business_license = $this->ask('Please enter your company license');
            $scale            = $this->ask('Please enter your company scale');
            $phone            = $userService->value([], 'phone');
            $data             = compact('enterprise_name', 'lead', 'address', 'business_license', 'phone', 'scale');
            $entService->register($data);
            $this->line('company successfully created');
            $this->newLine();
        }
        $this->line('初始化成功,即将启动服务...');
        $this->call('open:http start');
    }
}
