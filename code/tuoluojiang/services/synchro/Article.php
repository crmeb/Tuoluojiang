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

namespace Tuoluojiang\Open\services\synchro;

use App\Http\Service\Admin\AdminService;
use Tuoluojiang\Open\traits\VersionHeaderTrait;
use Tuoluojiang\Open\exceptions\ApiException;
use Tuoluojiang\Open\exceptions\HttpServiceExceptions;
use Tuoluojiang\Open\services\HttpService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class Article.
 */
class Article extends HttpService
{
    use VersionHeaderTrait;

    protected string $apiHost = '';

    protected string $account = '';

    protected string $secret = '';

    protected string $accessArticleToken = '';

    protected string $salt = '';

    private string $cacheArticleTokenPrefix = '_crm_oa_article';

    private string $classifyApi = '/api/v2/article/category';

    private string $listApi = '/api/v2/article/list';

    private string $recListApi = '/api/v2/article/rec_list';

    private string $infoApi = '/api/v2/article/info';

    private string $saveApi = '/api/v2/article/save';

    private string $collectApi = '/api/v2/article/collect';

    private string $supportApi = '/api/v2/article/support';

    private string $delApi = '/api/v2/article/delete';

    private string $saveLabelApi = '/api/v2/article/save_label';

    private string $userLabelApi = '/api/v2/article/user_label';

    private string $countApi = '/api/v2/article/count';

    private string $uploadApi = '/api/v2/article/upload';

    private string $userCreateApi = '/api/v2/article/create_user';

    private string $userSaveApi = '/api/v2/article/save_user';

    private string $userLogoutApi = '/api/v2/article/logout';

    private string $userLoginCaptchaApi = '/api/v2/article/login_captcha';

    private string $articleSuspensionApi = '/api/v2/article/suspension';

    private string $articleAuthorAchievementApi = '/api/v2/article/author_achievement';

    public function __construct(protected Cache $cache, array $config = [])
    {
        parent::__construct($config);
    }

    public function setFromType($uid = ''): static
    {
        if ($info = app()->get(AdminService::class)->get($uid, ['phone', 'password as only_pwd'])) {
            $this->account = $info['phone'];
            $this->secret  = $info['only_pwd'];
        }

        $this->apiHost = env('API_HOST', 'https://manage.tuoluojiang.com');
        return $this;
    }

    /**
     * 获取文章分类.
     * @param array $menus
     * @param mixed $types
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function articleCate($types, $menus = [])
    {
        $data = [
            'types' => $types,
            'menus' => $menus,
        ];
        return $this->httpRequest($this->classifyApi, $data);
    }

    /**
     * 保存.
     * @param array $data
     * @return array|mixed
     */
    public function articleLabelSave($data = [])
    {
        return $this->httpRequest($this->saveLabelApi, $data);
    }

    /**
     * 获取用户标签.
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function articleLabelGet()
    {
        return $this->httpRequest($this->userLabelApi);
    }

    /**
     * 获取文章列表.
     * @param mixed $data
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function articleList($data)
    {
        return $this->httpRequest($this->listApi, $data);
    }

    /**
     * 获取文章详情.
     * @param mixed $id
     * @param mixed $field
     * @return array|mixed
     */
    public function articleInfo($id, $field = ['*'])
    {
        $data = [
            'id'    => $id,
            'field' => $field,
        ];
        return $this->httpRequest($this->infoApi, $data);
    }

    /**
     * 文章收藏/取消收藏.
     * @param mixed $id
     * @param mixed $status
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function articleCollect($id, $status)
    {
        $data = [
            'id'     => $id,
            'status' => $status,
        ];
        return $this->httpRequest($this->collectApi, $data);
    }

    /**
     * 文章点赞/取消点赞.
     * @param mixed $id
     * @param mixed $status
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function articleSupport($id, $status)
    {
        $data = [
            'id'     => $id,
            'status' => $status,
        ];
        return $this->httpRequest($this->supportApi, $data);
    }

    /**
     * 保存文章内容.
     * @param mixed $data
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function articleSave($data)
    {
        return $this->httpRequest($this->saveApi, $data);
    }

    /**
     * 删除文章内容.
     * @param mixed $id
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function articleDelete($id)
    {
        $data = [
            'id' => $id,
        ];
        return $this->httpRequest($this->delApi, $data);
    }

    /**
     * 上传文章图片.
     * @param mixed $image
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function articleImage($image)
    {
        return $this->httpRequest($this->uploadApi, ['image' => base64_encode($image)]);
    }

    /**
     * 用户文章数量.
     * @return array|mixed
     */
    public function articleCount()
    {
        return $this->httpRequest($this->countApi);
    }

    /**
     * 新增文章账号信息.
     * @param mixed $data
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function createUser($data)
    {
        return $this->httpRequest($this->userCreateApi, $data, 'POST', false);
    }

    /**
     * 修改文章账号信息.
     * @param mixed $data
     * @return array|mixed
     * @throws BindingResolutionException
     */
    public function saveUser($data)
    {
        $res = $this->httpRequest($this->userSaveApi, $data, 'POST', false);
        if (! empty($res['token'])) {
            $this->accessArticleToken = $res['token'];
            return app()->get(AdminService::class)->update(['phone' => $data['phone']], ['only_pwd' => $data['password']]);
        }
    }

    /**
     * 登录文章账号.
     * @param mixed $data
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function articleLogin($data)
    {
        $res = $this->httpRequest($this->userLoginCaptchaApi, $data, 'POST', false);
        if (! empty($res['token'])) {
            $accessArticleTokenKey = md5($this->account . $this->cacheArticleTokenPrefix);
            $this->cache::tags(['articles'])->set($accessArticleTokenKey, $res['token'], 7 * 86400);
            return '登录成功';
        }
        throw new ApiException($res->get('message', '发生异常，请稍后重试'));
    }

    /**
     * 登录文章账号.
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function articleLogout()
    {
        $res = $this->httpRequest($this->userLogoutApi);
        if (! empty($res['token'])) {
            $cache                 = app()->cache;
            $accessArticleTokenKey = md5($this->account . '_' . $this->secret . $this->cacheArticleTokenPrefix);
            $cache->delete($accessArticleTokenKey);
        }
    }

    /**
     * 获取缓存token.
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getToken()
    {
        $accessArticleTokenKey = md5($this->account . $this->cacheArticleTokenPrefix);
        $cacheToken            = $this->cache::tags(['articles'])->get($accessArticleTokenKey);
        if (! $cacheToken) {
            throw new HttpServiceExceptions('登录已过期，请重新登录', 40010);
        }
        $this->accessArticleToken = $cacheToken;
        return $cacheToken;
    }

    /**
     * 请求
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function httpRequest(string $url, array $data = [], string $method = 'POST', bool $isHeader = true)
    {
        $header = [];
        if ($isHeader) {
            $this->getToken();
            if (! $this->accessArticleToken) {
                throw new ApiException('配置已更改或token已失效');
            }
            $header = ['Authorization' => 'Bearer ' . $this->accessArticleToken];
        }
        $header   = $this->getHeader($header);
        $response = collect();
        switch ($method) {
            case 'POST':
                $response = $this->setHeader($header)->postJSON($this->get($url), $data);
                break;
            case 'GET':
                $response = $this->setHeader($header)->getJSON($this->get($url), $data);
                break;
        }
        if ($response->get('status') === 200) {
            if (! empty($response['token'])) {
                $this->cache::tags(['articles'])->set(md5($this->account . $this->cacheArticleTokenPrefix), $response['token'], 7 * 86400);
            }
            return $response->get('data', '') ?: $response->get('message', '');
        }
        if ($response->get('status') === 40010) {
            throw new HttpServiceExceptions($response->get('message', '平台错误：发生异常，请稍后重试'), $response->get('status'));
        }
        throw new ApiException($response->get('message', '平台错误：发生异常，请稍后重试'));
    }

    /**
     * @return string
     */
    public function get(string $apiUrl = '')
    {
        return $this->apiHost . $apiUrl;
    }

    /**
     * 获取个人统计
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function articleSuspension(): mixed
    {
        return $this->httpRequest($this->articleSuspensionApi);
    }

    /**
     * 获取作者统计
     * @param mixed $data
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function articleAuthorAchievement($data)
    {
        return $this->httpRequest($this->articleAuthorAchievementApi, $data);
    }

    /**
     * 文章推荐列表.
     * @param mixed $data
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function articleRecList($data)
    {
        return $this->httpRequest($this->recListApi, $data);
    }
}
