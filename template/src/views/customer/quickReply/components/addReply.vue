<template>
  <div>
    <el-dialog
      :title="this.id ? '编辑快捷回复' : '新增快捷回复'"
      :visible.sync="visible"
      width="650px"
      @close="handleClose"
    >
      <el-form :model="form" label-width="auto" ref="form" :rules="rules">
        <el-form-item label="内容分组：" prop="group_id">
          <el-select v-model="form.group_id" placeholder="请输入选择内容分组（单选）" size="small" style="width: 100%">
            <el-option v-for="item in leftList" :key="item.id" :label="item.name" :value="item.id"></el-option>
          </el-select>
        </el-form-item>

        <el-form-item>
          <template #label>
            <div><span class="required">*</span>回复类型：</div>
          </template>
          <div class="flex">
            <div
              v-for="(item, index) in type"
              :key="index"
              class="type-box"
              @click="typeFn(item)"
              :class="{ active: activeType == item.value }"
            >
              <div class="icon iconfont" :class="item.icon"></div>
              <span class="text">{{ item.label }}</span>
            </div>
          </div>
        </el-form-item>
        <div class="flex flex-between lh-center" v-if="activeType === 'text'">
          <!-- <span class="required">*</span>回复内容： -->
          <div class="title">群发内容</div>
          <el-button type="text" @click="openLibrary">从素材库中选择</el-button>
        </div>
        <el-form-item v-if="activeType === 'text'" label="" prop="content">
          <template #label>
            <div><span class="required">*</span>回复内容：</div>
          </template>
          <div>
            <el-input
              v-model="form.content"
              type="textarea"
              :rows="8"
              max="1000"
              placeholder="请输入快捷回复内容..."
              resize="none"
              autofocus="true"
            >
            </el-input>
          </div>
        </el-form-item>

        <!-- 图片/视频 -->
        <el-form-item v-if="activeType === 'image' || activeType === 'video'">
          <template #label>
            <div>
              <span class="required">*</span>{{ activeType === 'image' ? '上传图片：' : '上传视频：' }}
            </div>
          </template>
          <template v-if="file && file.url">
            <div class="pic" v-if="activeType === 'image'">
              <img class="img" :src="file.url" />
              <div class="pic-upload">
                <div class="pic-upload-content">
                  <i @click="handleAvatar(index)" class="el-icon-delete"></i>
                </div>
              </div>
            </div>
            <div v-if="activeType === 'video'" class="video-container">
              <video ref="{videoRef}" controls muted controlsList="nodownload" style="width: 250px; height: 150px">
                <track kind="captions" />
                <source :src="file.url" type="video/mp4" />
              </video>
              <div class="video-delete-btn" @click="handleAvatar">
                <i class="el-icon-delete"></i>
              </div>
            </div>
          </template>

          <el-upload
            v-else
            class="upload-demo mr10"
            action="##"
            :headers="myHeaders"
            :show-file-list="false"
            :http-request="uploadServerLog"
            :accept="activeType === 'image' ? 'image/*' : 'video/*'"
          >
            <div class="upload-btn">
              <span class="el-icon-plus" v-if="!isUploading"></span>
              <span class="el-icon-loading" v-else></span>
            </div>
          </el-upload>
          <div class="sort-tip">
            {{ activeType == 'image' ? '图片最大10MB，支持JPG,PNG格式' : '视频最大10MB，支持MP4格式' }}
          </div>
        </el-form-item>

        <!-- 文件上传 -->
        <el-form-item v-if="activeType === 'file'">
          <template #label>
            <div><span class="required">*</span>上传文件：</div>
          </template>
          <upload-list
            v-if="file && file.url"
            :file-list="[file]"
            :show-close="true"
            @fileDelete="handleAvatar"
          ></upload-list>
          <el-upload
            v-else
            class="upload-demo mr10"
            action="##"
            :headers="myHeaders"
            :show-file-list="false"
            :http-request="uploadServerLog"
          >
            <div class="upload-file" v-if="!isUploading">点击上传</div>

            <span class="el-icon-loading" v-else></span>
          </el-upload>
        </el-form-item>
        <!-- 网页 -->
        <template v-if="activeType === 'link'">
          <el-form-item label="网页链接：" prop="link">
            <el-input placeholder="请以http或者https开头" v-model="form.link" size="small" @change="handleLinkChange">
            </el-input>
          </el-form-item>
          <el-form-item label="链接标题：" prop="title">
            <el-input placeholder="请输入链接标题" v-model="form.title" size="small"> </el-input>
          </el-form-item>
          <el-form-item label="链接摘要：" prop="info">
            <el-input placeholder="请输入链接摘要" v-model="form.info" size="small"> </el-input>
          </el-form-item>
          <el-form-item>
            <template #label>
              <div><span class="required">*</span>链接封面：</div>
            </template>
            <div class="pic" v-if="file && file.url">
              <img class="img" :src="file.url" />
              <div class="pic-upload">
                <div class="pic-upload-content">
                  <i @click="handleAvatar(index)" class="el-icon-delete"></i>
                </div>
              </div>
            </div>
            <el-upload
              v-else
              class="upload-demo mr10"
              action="##"
              :headers="myHeaders"
              :show-file-list="false"
              :http-request="uploadServerLog"
              accept="image/*"
            >
              <div class="upload-btn">
                <span class="el-icon-plus" v-if="!isUploading"></span>
                <span class="el-icon-loading" v-else></span>
              </div>
            </el-upload>
            <div class="sort-tip">图片最大10MB，支持JPG,PNG格式</div>
          </el-form-item>
        </template>
        <!-- 小程序 -->
        <template v-if="activeType === 'mini_program'">
          <el-form-item label="小程序标题：" prop="title">
            <el-input v-model="form.title" size="small" placeholder="请输入小程序标题"> </el-input>
          </el-form-item>
          <el-form-item label="AppID：" prop="app_id">
            <el-input v-model="form.app_id" size="small" placeholder="请输入小程序AppID，必须是关联到企业的小程序应用">
            </el-input>
          </el-form-item>
          <el-form-item label="page路径：" prop="link">
            <el-input v-model="form.link" placeholder="请输入page路径，例如：page/index" size="small"> </el-input>
          </el-form-item>
          <el-form-item prop="replyType">
            <template #label>
              <div><span class="required">*</span>小程序封面：</div>
            </template>
            <div class="pic" v-if="file && file.url">
              <img class="img" :src="file.url" />
              <div class="pic-upload">
                <div class="pic-upload-content">
                  <i @click="handleAvatar(index)" class="el-icon-delete"></i>
                </div>
              </div>
            </div>

            <el-upload
              v-else
              class="upload-demo mr10"
              action="##"
              :headers="myHeaders"
              :show-file-list="false"
              accept="image/*"
              :http-request="uploadServerLog"
            >
              <div class="upload-btn">
                <span class="el-icon-plus" v-if="!isUploading"></span>
                <span class="el-icon-loading" v-else></span>
              </div>
            </el-upload>
            <div class="sort-tip">图片最大10MB，支持JPG,PNG格式</div>
          </el-form-item>
        </template>

        <el-form-item label="排序：" prop="sort">
          <el-input size="small" v-model.number="form.sort" placeholder="请输入排序（数字越大越靠前）" type="number" />
          <div class="sort-tip">数字越大越靠前</div>
        </el-form-item>
      </el-form>

      <template #footer>
        <div class="dialog-footer">
          <el-button @click="handleClose">取消</el-button>
          <el-button type="primary" :loading="isLoading" @click="handleConfirm">确定</el-button>
        </div>
      </template>
    </el-dialog>
    <!-- 素材库组件 -->
    <materialLibrary ref="libraryRef" @selectMaterial="handleSelectMaterial"></materialLibrary>
  </div>
</template>

<script>
import {
  workReplySaveApi,
  workReplyDetailsApi,
  workReplyPutApi,
  getUrlMetadataApi,
  getMediaUploadByUrlApi
} from '@/api/weCom'
import { getUploadKeysApi } from '@/api/public'
import SettingMer from '@/libs/settingMer'
import { workMassTempApi } from '@/api/weCom'
import { uploader } from '@/utils/uploadCloud'
export default {
  name: 'QuickReplyModal',
  components: {
    uploadList: () => import('@/components/form-common/oa-uploadList'),
    materialLibrary: () => import('@/views/customer/weChatMass/components/materialLibrary')
  },
  props: {
    leftList: {
      type: Array,
      default: []
    },
    type: {
      type: Array,
      default: []
    },
    group_id: { type: [String, Number], default: '' }
  },
  data() {
    return {
      visible: false,
      activeType: 'text',
      myHeaders: {
        authorization: 'Bearer ' + localStorage.getItem('token')
      },
      isUploading: false,
      isLoading: false,
      metaLoading: false,
      id: '',
      maxSize: 20,
      file: {},
      form: {
        group_id: '', // 内容分组
        types: 'text', // 回复类型，默认文本
        title: '', // 标题
        info: '', // 摘要
        link: '', // 路径链接
        app_id: '',
        file_url: '',
        content: '',
        sort: 0,
        file_id: ''
      },

      rules: {
        group_id: [{ required: true, message: '请选择分组', trigger: 'blur' }],
        title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
        info: [{ required: true, message: '请输入摘要', trigger: 'blur' }],
        link: [{ required: true, message: '请输入路径链接', trigger: 'change' }],
        app_id: [{ required: true, message: '请输入应用id', trigger: 'change' }]
        // content: [{ required: true, message: '请输入内容', trigger: 'blur' }]
      }
    }
  },
  watch: {
    group_id(val) {
      this.form.group_id = val
    }
  },
  methods: {
    // 视频上传前的校验
    beforeVideoUpload(file) {
      const isVideo = file.type.startsWith('video/')
      if (!isVideo) {
        this.$message.error('请上传视频文件')
        return false
      }
      this.form.video = file
      return false // 这里返回false，实际项目中需改为后端接口地址，让Element UI的upload组件去真正上传
    },

    // 素材选择回调
    handleSelectMaterial(val) {
      workMassTempApi(val.id).then((res) => {
        this.form.content = res.data.content
      })
    },

    handleLinkChange(e) {
      if (!e || !this.activeType === 'link') return

      // 简单的 URL 格式校验
      const urlPattern = /^https?:\/\/.+/
      if (!urlPattern.test(e)) {
        return
      }

      this.fetchUrlMetadata(e)
    },

    async fetchUrlMetadata(url) {
      if (this.metaLoading) return

      this.metaLoading = true
      try {
        const res = await getUrlMetadataApi({ url })

        if (res.data) {
          const { title, description, cover_image } = res.data

          // 填充标题和摘要
          if (title) {
            this.form.title = title
          }
          if (description) {
            this.form.info = title
          }
          if (cover_image) {
            this.file = {
              url: cover_image
            }
            this.form.file_url = cover_image
            getMediaUploadByUrlApi({ url: cover_image }).then((res) => {
              this.form.file_id = res.data.id
              this.file.id = res.data.id
            })
          }
        }
      } catch (error) {
        console.error('获取网页元数据失败:', error)
      } finally {
        this.metaLoading = false
      }
    },

    async downloadAndUploadCover(imageUrl) {
      try {
        // 下载图片
        const response = await fetch(imageUrl)
        const blob = await response.blob()

        // 从 URL 中提取文件名
        const urlParts = imageUrl.split('/')
        const fileName = urlParts[urlParts.length - 1] || 'cover.jpg'
        const file = new File([blob], fileName, { type: blob.type || 'image/jpeg' })

        // 使用现有的上传方法
        await this.uploadServerLog({ file })
      } catch (error) {
        console.error('封面图片上传失败:', error)
      }
    },

    // 打开素材库
    openLibrary() {
      if (this.$refs.libraryRef) {
        this.$refs.libraryRef.openBox()
      } else {
        this.$message.error('素材库组件加载失败')
      }
    },
    openBox(id) {
      if (id) {
        this.id = id
        this.getInfo(id)
      }

      this.visible = true

      this.form.group_id = this.group_id || ''
    },
    getInfo(id) {
      workReplyDetailsApi(id).then((res) => {
        this.form = res.data
        this.activeType = res.data.types
        this.file = res.data.file
      })
    },

    handleAvatar() {
      this.file = {}
    },
    // 关闭弹窗
    handleClose() {
      this.isLoading = false
      this.visible = false
      this.id = ''
      this.file = {}
      this.activeType = 'text'

      this.form = {
        types: 'text',
        sort: 0,
        title: '',
        content: '',
        link: ''
      }
      if (this.$refs.form) {
        this.$refs.form.resetFields()
      }
    },
    typeFn(item) {
      let group_id = JSON.parse(JSON.stringify(this.form.group_id))
      this.activeType = item.value
      this.file = {}
      this.form.link = ''
      this.form.title = ''
      this.form.info = ''
      this.metaLoading = false

      if (this.$refs.form) {
        this.$refs.form.resetFields()
      }

      setTimeout(() => {
        this.form.group_id = group_id
      }, 100)
    },
    // 确认提交
    // 确认提交
    handleConfirm() {
      this.$refs.form.validate((valid) => {
        if (valid) {
          this.form.types = this.activeType
          if (this.file) {
            this.form.file_id = this.file.id
          }
          if (this.activeType == 'text' && !this.form.content) {
            return this.$message.error('请输入内容')
          }
          if (this.activeType == 'image' && !this.form.file_id) {
            return this.$message.error('请上传图片')
          }
          if (this.activeType == 'video' && !this.form.file_id) {
            return this.$message.error('请上传视频')
          }
          if (this.activeType == 'file' && !this.form.file_id) {
            return this.$message.error('请上传附件')
          }
          if ((this.activeType == 'mini_program' || this.activeType == 'link') && !this.form.file_id) {
            return this.$message.error('请上传封面图片')
          }
          if (this.id) {
            workReplyPutApi(this.id, this.form).then(() => {
              setTimeout(() => {
                this.$emit('getList')
              }, 300)
              this.handleClose()
            })
          } else {
            workReplySaveApi(this.form)
              .then(() => {
                setTimeout(() => {
                  this.$emit('getList')
                }, 300)
                this.handleClose()
              })
              .catch(() => {})
          }
        }
      })
    },
    // 统一提交方法
    saveSubmit() {
      if (this.id) {
        return workReplyPutApi(this.id, this.form).then(() => {
          setTimeout(() => {
            this.$emit('getList')
          }, 300)
          this.handleClose()
        })
      } else {
        return workReplySaveApi(this.form).then(() => {
          setTimeout(() => {
            this.$emit('getList')
          }, 300)
          this.handleClose()
        })
      }
    },

    // 上传文件方法
    async uploadServerLog(params) {
      this.isUploading = true
      try {
        // 参数校验：确保文件存在
        const file = params?.file
        if (!file) {
          this.isUploading = false
          this.$message.error('未获取到上传文件，请重新选择')
          return
        }

        if (['mini_program', 'image', 'link'].includes(this.activeType)) {
          if (file.size / 1024 / 1024 > 10) {
            this.$message.error('图片最大不能超过10M')
            this.isUploading = false
            return false
          }
          const allowedMimeTypes = ['image/png', 'image/jpeg', 'image/jpg']
          const allowedExtensions = ['.png', '.jpg', '.jpeg']
          const fileExtension = '.' + file.name.split('.').pop()?.toLowerCase()
          // 检查 MIME 类型或文件后缀是否符合要求
          const isAllowed =
            allowedMimeTypes.includes(file.type) || (fileExtension && allowedExtensions.includes(fileExtension))

          if (!isAllowed) {
            this.isUploading = false
            this.$message.error('请上传 PNG 或 JPG 格式的图片')
            return false
          }
        }
        if (this.activeType === 'video') {
          if (file.size / 1024 / 1024 > 10) {
            this.isUploading = false
            this.$message.error('视频最大不能超过10M')
            return false
          }
          const fileName = file.name.toLowerCase()
          const isMP4ByExtension = fileName.endsWith('.mp4') // 校验文件后缀
          const isMP4ByType = file.type === 'video/mp4' // 校验 MIME 类型

          // 双重校验：后缀或 MIME 类型任一不符合则拒绝
          if (!isMP4ByExtension || !isMP4ByType) {
            this.isUploading = false
            this.$message.error('请上传 MP4 格式的视频')
            return false
          }
        }

        if (this.activeType === 'file') {
          if (file.size / 1024 / 1024 > 200) {
            this.isUploading = false
            this.$message.error('文件最大不能超过200M')
            return false
          }
        }

        let obj = {
          key: file.name,
          contentType: file.type
        }

        const uploadKeysRes = await getUploadKeysApi(obj)

        if (!uploadKeysRes?.data) {
          this.isUploading = false
          this.$message.error('获取上传配置失败，返回格式异常')
        }

        const isLocalUpload = uploadKeysRes.data.type === 'local'
        const uploadUrl = isLocalUpload ? `work/media/upload` : `${SettingMer.https}/work/media/save`
        const uploadRes = await uploader(file, 0, {
          uploadRes: uploadKeysRes,
          url: uploadUrl
        })

        if (uploadRes?.data) {
          this.isUploading = false
          const { fileListname, name, size, url, id } = uploadRes.data

          this.file = {
            real_name: fileListname || name,
            size,
            name,
            url,
            id
          }
        } else {
          this.isUploading = false
          throw new Error('上传成功，但未返回文件信息')
        }
      } catch (error) {
        // 统一错误处理
        this.isUploading = false
        this.$message.closeAll()
        const errorMsg = error instanceof Error ? error.message : '文件上传失败，请重试'
        this.$message.error(errorMsg)
      }
    }
  }
}
</script>

<style scoped lang="scss">
.sort-tip {
  font-size: 12px;
  color: #909399;
  height: 14px;
  margin-top: 4px;
}
.uploaded-video {
  margin-top: 8px;
  font-size: 14px;
  color: #606266;
}
.required {
  color: #f56c6c;
  margin-right: 4px;
}
.video-container {
  position: relative;
  display: inline-block;
  background-color: #707070;
}
.el-icon-loading {
  font-size: 18px;
  color: #606266;
}
.video-delete-btn {
  position: absolute;
  width: 100%;
  top: 0;
  left: 0;
  height: 30px;
  line-height: 30px;
  top: 0;
  background-color: rgba(0, 0, 0, 0.5);
  text-align: center;
  color: #fff;
  cursor: pointer;
}

.type-box {
  cursor: pointer;
  width: 58px;
  height: 58px;
  padding: 10px 0;
  background: #f9f9f9;
  color: #606266;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  margin-right: 14px;
  border-radius: 6px;
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  .text {
    font-size: 12px;
    margin-top: 2px;
  }
  .icon {
    flex: 1;
    margin-top: 8px;
    font-size: 20px;
  }
}

.active {
  color: #1890ff;
  background: #f3f8fe;
}
.upload-file {
  height: 32px;
  line-height: 32px;
  border-radius: 6px;
  padding: 0 14px;
  border: 1px solid #dcdfe6;
}
.upload-btn {
  width: 58px;
  height: 58px;
  line-height: 58px;
  text-align: center;
  border: 1px dashed #dcdfe6;
  border-radius: 6px;
  .el-icon-plus {
    font-size: 18px;
  }
}
.title {
  font-family: PingFang SC, sans-serif;
  font-weight: 600;
  font-size: 14px;
  color: #333;
  margin-left: 10px;
  // margin: 0 0 20px 9px;
  position: relative;
  &:before {
    content: '';
    background-color: #1890ff;
    width: 3px;
    height: 14px;
    position: absolute;
    left: -9px;
    top: 50%;
    transform: translateY(-50%); // 垂直居中更可靠
  }
}
.pic {
  width: 58px;
  height: 58px;
  border-radius: 6px;
  position: relative;

  img {
    border-radius: 6px;
    width: 100%;
    height: 100%;
  }
  &:hover {
    .pic-upload {
      display: block;
    }
  }
  .pic-upload {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    right: 0;
    display: none;
    border-radius: 6px;
    background-color: rgba(0, 0, 0, 0.6);
    .pic-upload-content {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      i {
        color: #fff;
        font-size: 18px;
        cursor: pointer;
        margin-right: 10px;
        &:last-of-type {
          margin-right: 0;
        }
      }
    }
  }
}
.pic-uploader-icon {
  border: 1px dashed #d9d9d9;
  font-size: 28px;
  color: #8c939d;
  width: 80px;
  height: 80px;
  line-height: 80px;
  text-align: center;
  cursor: pointer;
}
</style>
