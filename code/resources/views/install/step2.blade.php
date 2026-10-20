<!doctype html>
<html>
<head>
    <meta charset="UTF-8"/>
    <title>{{$Title}} - {{$Powered}}</title>
    <link rel="stylesheet" href="/install/css/install.css" />
    <link rel="stylesheet" href="/install/css/step2.css" />
    <link rel="stylesheet" href="/install/css/theme-chalk.css" />
    <script src="/install/js/vue2.6.11.js"></script>
    <script src="/install/js/element-ui.js"></script>
</head>
<body>
<div class="wrap" id="step2">
    <!--    --><?php //require './templates/header.php'; ?>
    <div class="title">
        安装检测
    </div>
    <div class="content">
        <div class="menu">
            <div class="head">
                <h1>安装检测</h1>
                <a class="again" href="/install/index/2">重新检测
                    <img class="upload" src="/install/images/upload.png" alt="">
                </a>
            </div>
            <div class="p8">安装环境需满足系统运行要求</div>
            <div>
                <div class="tab" :class="{'on': index === 0}" @click="index = 0">
                    <div class="left-img">
                        <img class="env" src="/install/images/environment.png" alt="">
                        <img v-if="{{$passOne}}" class="warring" src="/install/images/sure.png" alt="">
                        <img v-else class="warring" src="/install/images/warring.png" alt="">

                    </div>
                    <div>
                        <div>环境及配置</div>
                        <div class="p8">基础的系统操作环境</div>
                    </div>
                </div>
                <div class="tab" :class="{'on': index === 1}" @click="index = 1">
                    <div class="left-img">
                        <img class="jur" src="/install/images/jurisdiction.png" alt="">
                        <img v-if="{{$passTwo}}" class="warring btn-warning" src="/install/images/sure.png" alt="">
                        <img v-else class="warring btn-warning" src="/install/images/warring.png" alt="">
                    </div>
                    <div>
                        <div>权限检测</div>
                        <div class="p8">目录及文件权限检测</div>
                    </div>
                </div>

            </div>
        </div>
        <section class="config-list">
            <div class="server">
                <table width="100%" v-if="index === 0">
                    <tr>
                        <td class="td1">环境检测</td>
                        <td class="td1" width="20%">推荐配置</td>
                        <td class="td1" width="20%">最低要求</td>
                        <td class="td1" width="20%">当前状态</td>
                    </tr>
                    @foreach($configData as $config)
                        <tr>
                            <td>{{$config['name']}}</td>
                            <td>{{$config['config']}}</td>
                            <td>{{$config['lowest']}}</td>
                            <td>
                                <div class="ls-td">
                                @if($config['types'])
                                    <img class="yes" src="/install/images/yes.png" alt="对">
                                @else
                                    <img class="no" src="/install/images/warring.png" alt="错">
                                @endif
                                    {{$config['status']}}
                                </div>
                            </td>
                        </tr>

                    @endforeach
                </table>


                <table width="100%" v-else>
                    <tr>
                        <td class="td1">权限检查</td>
                        <td class="td1" width="20%">推荐配置</td>
                        <td class="td1" width="20%">读取</td>
                        <td class="td1" width="20%">写入</td>
                    </tr>
                    @foreach($files as $file)
                        <tr>
                            <td>{{$file['path']}}</td>
                            <td>{{$file['readable'] && $file['writable'] ? '读写' : '读取'}}</td>
                            <td>
                                <div class="ls-td">
                                    @if(! $file['readable'])
                                        无需检测
                                    @elseif(is_readable(base_path($file['path'])))
                                        <img class="yes" src="/install/images/yes.png" alt="对">
                                    @else
                                        <img class="no" src="/install/images/warring.png" alt="错">
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="ls-td">
                                    @if(! $file['writable'])
                                        无需写入
                                    @elseif(is_writeable(base_path($file['path'])))
                                        <img class="yes" src="/install/images/yes.png" alt="对">
                                    @else
                                        <img class="no" src="/install/images/warring.png" alt="错">
                                    @endif
                                </div>
                            </td>
                        </tr>

                    @endforeach
                </table>
            </div>

        </section>
    </div>
{{--    <div class="trip mid">--}}
{{--        <img src="/install/images/trip-icon.png" alt="">--}}
{{--        温馨提示：程序运行需配置伪静态，否则安装后会存在无法使用的情况--}}
{{--    </div>--}}
    <div class="bottom-btn">
        <div class="bottom tac up-btn">
            <a href="/install/index/1" class="btn">上一步</a>
        </div>
        <div class="bottom tac">
            @if($passOne && $passTwo)
                <a href="/install/index/3" class="btn next">下一步</a>
            @else
                <span class="next" @click="next" class="btn">下一步</span>
            @endif
        </div>
    </div>

</div>
@include('install/footer')
</body>
<script>
    new Vue({
        el: '#step2',
        data() {
            return {index: 0}
        },
        methods: {
            next() {
                this.$message({
                    message: '安装环境检测未通过，请检查',
                    type: 'warning'
                });
            },
            handleConfirm() {
                window.location.href = '/install/index/3'
            },
            handleAgain() {
                window.location.href = '/install/index/2'
            },
        }
    })
</script>
</html>
