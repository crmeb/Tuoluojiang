<!doctype html>
<html>
<head>
    <meta charset="UTF-8"/>
    <title>{{$Title}} - {{$Powered}}</title>
    <link rel="stylesheet" href="/install/css/install.css" />
    <link rel="stylesheet" href="/install/css/step4.css" />
    <link rel="stylesheet" href="/install/css/theme-chalk.css" />
    <script src="/install/js/vue2.6.11.js"></script>
    <script src="/install/js/element-ui.js"></script>
    <script src="/install/js/jquery.js"></script>
</head>
<body>
<div class="wrap" id="step4">
    <div class="title">
        安装进度
    </div>
    <section class="section">
        <div class="title">
            <h1 v-text="statusTitle"></h1>
        </div>
        <div class="progress">
            <el-progress :percentage="percentage" color="#37CA71" define-back-color="rgba(255,255,255,0.5)" :stroke-width="8" :status="progressStatus"></el-progress>
            <div class="progress-msg" v-if="!isShow">
                <div id="loginner_item" class="msg p8" v-text="currentMsg"></div>
                <!--                <div class="open" @click="openList">查看详情</div>-->
            </div>
        </div>
        <div class="install" ref="install" id="log" v-show="isShow">
            <div id="loginner" class="item" v-for="(item,index) in installList" :key="index">
                <span v-text="item.msg"></span>
                <span v-text="item.time"></span>
            </div>
        </div>
        <div class="bottom tac"><a href="javascript:;" class="btn_old mid" @click="handleAction">
            <img class="shuaxin" v-if="!failed && !finished" src="/install/images/shuaxin.png"/>&nbsp;<span v-text="buttonText"></span></a>
        </div>
    </section>
</div>
@include('install/footer')
</body>
<script type="text/javascript">
    $.ajaxSetup({cache: false});
    new Vue({
        el: '#step4',
        data() {
            return {
                percentage: 0,
                isShow: 0,
                installList: [],
                n:0,
                failed: false,
                finished: false,
                currentMsg: '正在准备安装...'
            }
        },
        computed: {
            statusTitle() {
                if (this.failed) {
                    return '安装失败，请根据提示处理后重试'
                }
                if (this.finished) {
                    return '安装完成，正在跳转...'
                }
                return '系统安装中，请稍等片刻...'
            },
            progressStatus() {
                return this.failed ? 'exception' : 'success'
            },
            buttonText() {
                if (this.failed) {
                    return '重新安装'
                }
                if (this.finished) {
                    return '安装完成'
                }
                return '正在安装...'
            }
        },
        mounted() {
            this.reloads(this.n);
        },
        methods: {
            reloads(num) {
                if (this.failed || this.finished) {
                    return;
                }
                var url = location.href+'?n='+num;
                $.ajax({
                    type: "POST",
                    url: url,
                    data: {n:num},
                    dataType: 'JSON',
                    cache: false,
                    beforeSend: () => {
                    },
                    success: (res) => {
                        if (!res || !res.data) {
                            this.handleFail('服务器返回异常，请刷新后重试')
                            return
                        }
                        const count = Number(res.data.count) > 0 ? Number(res.data.count) : 1
                        const n = Number(res.data.n) || 0
                        this.percentage = Math.round((n / count) * 100) > 100 ? 100 : Math.round((n / count) * 100)

                        if (res.data.msg){
                            this.currentMsg = res.data.msg
                            this.installList.push({
                                msg: res.data.msg,
                                time: res.data.time
                            })
                            this.$nextTick(e => {
                                this.$refs.install.scrollTop = this.$refs.install.scrollHeight;
                            })
                        }
                        if (res.data.error) {
                            this.handleFail(res.data.msg || '安装失败，请检查日志')
                            return false
                        }

                        if (n >= 0) {
                            if (n === 99999) {
                                this.finished = true
                                this.percentage = 100
                                setTimeout(e => {
                                    this.gonext()
                                }, 1000);
                                return false;
                            } else {
                                this.reloads(n);
                            }
                        } else {
                            this.handleFail(res.data.msg || '安装失败，请检查配置')
                        }

                    },
                    error: () => {
                        this.handleFail('请求安装接口失败，请检查服务状态后重试')
                    }
                });
            },
            openList() {
                this.isShow = true
                this.$nextTick(e => {
                    this.$refs.install.scrollTop = this.$refs.install.scrollHeight;
                })
            },
            handleFail(msg) {
                this.failed = true
                this.currentMsg = msg
                this.installList.push({
                    msg: msg,
                    time: new Date().toLocaleString()
                })
                this.$message.error(msg)
            },
            handleAction() {
                if (this.failed) {
                    window.location.href = '/install/index/4'
                }
            },
            gonext() {
                window.location.href = '/install/index/5';
            }
        }
    })
</script>
</html>
