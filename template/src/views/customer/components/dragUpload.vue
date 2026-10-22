<template>
    <div class="oa-dialog">
        <el-dialog title="导入数据" :visible.sync="show" width="789px" :before-close="handleClose"
            :close-on-click-modal="false">
            <div class="tips-box">
                按照模板中的格式编辑内容，完成后在下方上传<img src="@/assets/images/excel.png" alt="" class="img" />
                <span class="download" @click="download">下载{{ title }}</span>
            </div>
            <div class="upload-box">
                <el-upload class="upload-demo" drag action="##" :show-file-list="false" :headers="myHeaders"
                    :http-request="uploadServerLog" :before-upload="changeUploadFn">
                    <!-- 导入 -->
                    <template v-if="loading === 1">
                        <img src="@/assets/images/upload.png" alt="" class="img" />
                        <div class="el-upload__text">可直接将文件拖拽至此处上传，或 <em>点击添加</em></div>
                        <div class="el-upload__type">支持格式.xls、.xlsx格式，单次最多可导入 1 万条</div>
                    </template>
                    <!-- 导入中 -->
                    <template v-if="loading == 2">
                        <img src="@/assets/images/loading.gif" alt="" class="img-gif" />
                        <div class="el-upload__text">{{ file.name }}（{{ toSizeFile(file.size) }}）</div>
                        <div class="el-upload__type">正在导入...</div>
                    </template>
                    <!-- 导入成功 -->
                    <template v-if="loading == 3">
                        <img src="@/assets/images/uploadOk.png" alt="" class="img-ok" />
                        <div class="text-ok">导入完成！</div>
                        <div class="el-upload__type">

                           {{ response_data }}
                        </div>
                    </template>
                    <!-- 导入失败 -->
                    <template v-if="loading == 4">
                        <i class="iconfont icontishi2"></i>
                        <div class="text-ok">导入失败！</div>
                        <div class="el-upload__text mb8 mt16">
                            {{ file.name || '--' }}<span style="color: #909399">（{{ toSizeFile(file.size || 0)
                                }}）</span>
                        </div>
                        <div class="el-upload__text"><em>重新选择</em></div>
                    </template>
                </el-upload>
            </div>
            <div class="el-upload__tip">
                <!-- 支持将导出的列表数据文件，批量修改后直接上传 -->

                <!-- <div>被标记为唯一的字段必须确保每个值都是唯一的，否则重要数据不进行导入</div> -->
            </div>
        </el-dialog>

    </div>
</template>
<script>
import { uploader } from '@/utils/uploadCloud'
import { fileLinkDownLoad, formatBytes } from '@/utils/file'
import { clientImportTemplateApi, clientImportApi } from '@/api/client'
import exportExcel from '@/components/common/exportExcel'
export default {
    name: '',
    components: { exportExcel },
    props: {},
    data() {
        return {
            show: false,
            loading: 1,
            myHeaders: {
                authorization: 'Bearer ' + localStorage.getItem('token')
            },
            file: {},
            title:'客户模板',
            url:'',
            keyWord: '',
            response_data: '',
        }
    },

    methods: {
        openBox(keyWord) {
            this.keyWord = keyWord
            this.show = true
                clientImportTemplateApi(this.keyWord).then((res) => {
                    if (res.status === 200) {
                        this.url = res.data.url
                        this.title = res.data.url.split('/').pop().split('.')[0]
                    } 
                })

        },
        toSizeFile(size) {
            return formatBytes(size)
        },
        changeUploadFn(file, fileLis) {
            const fileTypeName = file.name.substr(file.name.lastIndexOf('.') + 1)
            let types = ['xlsx', 'xls']
            if (!types.includes(fileTypeName)) {
                this.$message.error('仅支持 ' + types.join(',') + ' 格式')
                return false
            }
            this.file = file
            this.loading = 2

        },

        // 下载模板
        download() {
          
                    fileLinkDownLoad(this.url, this.title+'.xlsx')
               
            
        },

        // 导入数据
        uploadServerLog(params) {
            const file = params.file
            let options = {
                way: 2,
                relation_type: '',
                relation_id: '',
                eid: ''
            }
      
            uploader(file, 0, options).then((res) => {
                if (res.status === 200) {
                    clientImportApi(this.keyWord, { file_id: res.data.id }).then((res) => {
                        if (res.status === 200) {
                            this.loading = 3
                            this.response_data = res.message
                            this.$emit('getTableData')
                        } else {
                            this.loading = 4
                            this.$message.error(res.msg || '导入失败')
                        }
                    })

                } else {
                    this.loading = 4
                    this.$message.error(res.msg || '导入失败')
                }
            })
        },





        handleClose() {
            this.show = false
            this.loading = 1
            this.file = {}
        }
    }
}
</script>
<style scoped lang="scss">
.tips-box {
    width: 100%;
    height: 38px;
    display: flex;
    align-items: center;
    background: #f4f6fa;
    border-radius: 4px;
    font-weight: 400;
    font-size: 14px;
    padding: 0 15px;
    color: #303133;

    .img {
        display: block;
        width: 15px;
        height: 18px;
        margin: 0 10px;
    }

    .download {
        cursor: pointer;
        color: #1890ff !important;
    }
}

.mt16 {
    margin-top: 16px;
}

.mb8 {
    margin-bottom: 8px;
}

.upload-box {
    margin: 20px 0;

    .upload-demo {
        width: 100%;
        height: 312px;

        ::v-deep .el-upload {
            width: 100%;
            height: 100%;
            border-color: #dddddd;
        }

        ::v-deep .el-upload .el-upload-dragger {
            width: 100%;
            height: 100%;
            border-color: #dddddd;
        }
    }

    .img {
        width: 64px;
        height: 73px;
        margin: 0 auto;
        margin-top: 90px;
        margin-bottom: 18px;
    }

    .img-ok {
        width: 39px;
        height: 39px;
        margin: 102px auto 14px auto;
    }

    .text-ok {
        font-weight: 600;
        font-size: 15px;
        color: #303133;
    }

    .img-gif {
        width: 75px;
        height: 75px;
        margin: 85px auto 21px auto;
    }

    .icontishi2 {
        display: inline-block;
        font-size: 39px;
        color: red;
        margin: 91px auto 14px auto;
    }
}

.el-upload__tip {
    font-family: PingFang SC, PingFang SC;
    font-weight: 400;
    font-size: 13px;
    color: #606266;
    line-height: 18px;
    margin-bottom: 10px;
}

.el-upload__text {
    font-weight: 400;
    font-size: 13px;
    color: #303133;
    line-height: 18px;
}

.el-upload__type {
    margin-top: 4px;
    font-weight: 400;
    font-size: 13px;
    color: #909399;
    line-height: 18px;
}
</style>
