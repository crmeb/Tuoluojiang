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

namespace App\Http\Controller\AdminApi\Work;

use App\Constants\CustomEnum\ClueEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Model\Customer\Lead;
use App\Http\Requests\ApiRequest;
use App\Http\Service\Customer\LeadService;
use App\Http\Service\Customer\RecordService;
use App\Http\Service\Customer\CustomerService;
use App\Http\Service\Work\WorkClientService;
use Tuoluojiang\Open\services\wechat\Work;
use EasyWeChat\Kernel\Exceptions\BadRequestException;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\RuntimeException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Any;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

/**
 * 企业微信回调.
 */
#[Prefix('ent')]
class WorkCallbackController extends AuthController
{
    /**
     * 企业微信.
     * @return Response
     * @throws BadRequestException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws BindingResolutionException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    #[Any('work_serve', '企业微信')]
    public function serve()
    {
        return app()->get(Work::class)->serve();
    }

    /**
     * 线索同步.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post('customer_clue', '线索同步')]
    public function customer_clue(ApiRequest $request)
    {
        $name = $request->post('name', '');
        $phone = $request->post('phone', '');
        $source = $request->post('source', '');
        $unionid = $request->post('unionid', '');

        Log::error('线索同步customer_clue:' . json_encode(['name' => $name, 'phone' => $phone, 'source' => $source, 'unionid' => $unionid]));

        if (!$name) {
            return $this->fail('请填写姓名');
        }
        $sourc1 = 3;
        switch ($source) {
            case 'pro':
            case 'Pro':
                $sourc1 = 5;
                break;
            case 'JAVA外贸':
                $sourc1 = 12;
                break;
            case 'java单商户':
                $sourc1 = 11;
                break;
            case '知识付费':
                $sourc1 = 10;
                break;
            case '标准版':
                $sourc1 = 9;
                break;
            case '多门店':
                $sourc1 = 8;
                break;
            case 'JAVA多商户':
                $sourc1 = 7;
                break;
            case 'PHP多商户':
                $sourc1 = 6;
                break;
        }

        if ($unionid) {
            $client = app()->get(WorkClientService::class)->select(['unionid' => $unionid], ['external_userid', 'userid']);
            if ($client) {
                foreach ($client as $item) {
                    $customerClue = app()->get(LeadService::class)->get(['external_userid' => $item['external_userid'], 'userid' => $item['userid']]);
                    if ($customerClue) {
                        if (!$customerClue->phone) {
                            $customerClue->phone = $phone;
                            $customerClue->save();
                        }
                    }
                }
                app()->get(WorkClientService::class)->update(['unionid' => $unionid], ['phone' => $phone]);
                return $this->success('添加成功');
            }
        }

        $res = Lead::create([
            'name' => $name,
            'source' => $sourc1,
            'phone' => $phone,
            'created_at' => now()->toDateTimeString(),
            'createtime' => now()->toDateTimeString(),
        ]);
        app()->get(RecordService::class)->saveRecord(ViewSearchEnum::VIEW_CLUE, [
            'eid' => $res->id,
            'type' => ClueEnum::OPERATE_CREATE,
            'creator_uid' => 0,
            'record_version' => 0,
            'reason' => $source . '演示站点同步线索“' . $name . '”',
        ]);
        return $this->success('添加成功');
    }

    #[Get('index', 'index')]
    public function index()
    {
        $page = request()->get('page', 1);
        $limit = request()->get('limit', 20);
        $list = DB::table('crud_biaozhunbanyanshizhanyonghu')->where('weiyizhi', '<>', '')->forPage($page, $limit)->select(['shoujihao', 'weiyizhi', 'id'])->get()->toArray();
        $successNum = $customerNum = 0;
        foreach ($list as $item) {
            $item = (array)$item;
            if (!$item['shoujihao']) {
                continue;
            }
            $workInfo = app()->get(WorkClientService::class)->get(['unionid' => $item['weiyizhi']]);
            if (!$workInfo) {
                continue;
            }
            app()->get(WorkClientService::class)->update(['unionid' => $item['weiyizhi']], ['phone' => $item['shoujihao']]);
            $customerClue = app()->get(LeadService::class)->get(['external_userid' => $workInfo['external_userid'], 'userid' => $workInfo['userid']]);
            $is_edit = false;
            if ($customerClue) {
                if (!$customerClue->phone) {
                    $customerClue->phone = $item['shoujihao'];
                    $customerClue->save();
                    ++$successNum;
                    $is_edit = true;
                }
            } else {
                $customer = app()->get(CustomerService::class)->get(['external_userid' => $workInfo['external_userid'], 'userid' => $workInfo['userid']]);
                if ($customer) {
                    if (!$customer->customer_tel) {
                        $customer->customer_tel = $item['shoujihao'];
                        $customer->save();
                        ++$customerNum;
                        $is_edit = true;
                    }
                }
            }
            if ($is_edit) {
                DB::table('crud_biaozhunbanyanshizhanyonghu')->where('id', $item['id'])->update(['is_edit' => 1]);
            }
        }
        return $this->success(['count' => count($list), 'limit' => $limit, 'successNum' => $successNum, 'customerNum' => $customerNum]);
    }

    #[Get('run', 'run')]
    public function run()
    {
        $html = <<<'HTML'
<script src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
<script>
let page = 1
function run(){
  $.ajax({
    url: '/api/ent/index?page='+page,
    method: 'get',
    success: function(data){
      if(data.data.count >= data.data.limit){
        page++
        setTimeout(()=>{
          run()
        },1000)
      }
    }
  })
}
run()
</script>

HTML;
        echo $html;
    }
}
