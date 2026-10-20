<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace Tuoluojiang\Open\utils;

class ForMac
{
    public $return_array = []; // 返回带有MAC地址的字串数组

    public $mac_addr; // mac地址

    public function getMacAddress($os_type)
    {
        switch (strtolower($os_type)) {
            case 'linux':
            case 'darwin':
                $this->forLinux();
                break;
            default:
                $this->forWindows();
                break;
        }
        $temp_array = [];
        foreach ($this->return_array as $value) {
            if (preg_match('/[0-9a-f][0-9a-f][:-][0-9a-f][0-9a-f][:-][0-9a-f][0-9a-f][:-][0-9a-f][0-9a-f][:-][0-9a-f][0-9a-f][:-][0-9a-f][0-9a-f]/i', $value, $temp_array)) {
                $this->mac_addr = $temp_array[0];
                break;
            }
        }
        unset($temp_array);
        return $this->mac_addr;
    }

    /**
     * @return array
     *               window
     */
    public function forWindows()
    {
        @shell_exec('ipconfig /all', $this->return_array);
        if ($this->return_array) {
            return $this->return_array;
        }
        $ipconfig = $_SERVER['WINDIR'] . '\\system32\\ipconfig.exe';
        if (is_file($ipconfig)) {
            @shell_exec($ipconfig . ' /all', $this->return_array);
        } else {
            @shell_exec($_SERVER['WINDIR'] . '\\system\\ipconfig.exe /all', $this->return_array);
        }
        return $this->return_array;
    }

    /**
     * @return array
     *               linux
     */
    public function forLinux()
    {
        @shell_exec('ifconfig -a', $this->return_array);
        return $this->return_array;
    }
}
