<!-- 自动拉群-渠道活码新增 / 编辑页面（独立路由，编辑态由 query.id 驱动） -->
<template>
  <BaseContainer class="channel-code-create none-padding">
    <!-- 顶部：返回 + 标题 -->
    <FormPageHeader :title="pageTitle" />

    <!-- 表单内容（可滚动区域）：左侧表单 + 右侧手机预览 -->
    <div v-loading="detailLoading" class="channel-code-create__body">
      <div class="channel-code-create__form">
        <el-form ref="formRef" :model="formData" :rules="rules" label-width="auto">
          <div class="section-title">基本信息</div>

          <el-form-item label="扫码名称：" prop="name">
            <el-input v-model="formData.name" size="small" placeholder="请输入渠道活码名称" />
            <div class="tips mt10">管理端管理此渠道活码的名称，例如：SVIP会员群</div>
          </el-form-item>

          <el-form-item label="选择分组：" prop="group_id">
            <el-select
              v-model="formData.group_id"
              size="small"
              filterable
              placeholder="请选择分组（单选）"
              class="w-full"
            >
              <el-option v-for="item in codeGroupList" :key="item.id" :label="item.name" :value="item.id" />
            </el-select>
            <div class="tips mt10">选择该活码对应的分组，支持根据分组名称模糊搜索</div>
          </el-form-item>

          <el-form-item label="添加验证：" prop="skip_verify">
            <el-radio-group v-model="formData.skip_verify">
              <el-radio :label="1">直接通过</el-radio>
              <el-radio :label="0">需验证</el-radio>
            </el-radio-group>
            <div class="tips mb10">
              需验证指的是客户扫码之后，需要客服审核同意才能添加成功；直接通过指的是客户扫码之后，直接添加成功
            </div>
          </el-form-item>

          <el-form-item label="使用员工：" prop="userids">
            <select-member :value="userList" :isqiWeiWork="true" @getSelectList="getSelectList" />
            <div class="tips mt10">用于设置客户通过渠道活码，添加这些员工时，会自动发送邀请加群消息</div>
          </el-form-item>

          <el-form-item label="客户标签：">
            <div class="select plan-footer-one mr10" @click="handleLabel">
              <span v-if="labelList && labelList.length == 0" class="placeholder">请选择标签</span>
              <div>
                <span
                  v-for="(item, labelIndex) in labelList"
                  :key="labelIndex"
                  class="el-tag el-tag--small el-tag--info el-tag--light mr10"
                >
                  {{ item.name }}
                  <i class="el-tag__close el-icon-close" @click.stop="delTag(labelIndex)" />
                </span>
              </div>
            </div>
            <div class="tips mt10">客户入群之后，系统会自动追加选中的客户标签</div>
          </el-form-item>

          <!-- 新客户欢迎语 -->
          <el-form-item label="新客户欢迎语：" prop="welcome.content" class="welcome-form-item">
            <div class="welcome-btn">
              <el-button type="text" size="small" @click="openLibrary">从素材库中选择</el-button>
            </div>

            <materialContent
              ref="materialContentRef"
              :rightCustomerName="true"
              @contentChange="handleContentChange"
              @getAttachData="getAttachData"
            />
            <div class="tips mt10">
              由管理员或者业务负责人统一配置，配置后，微信客户将在添加成员为联系人后收到该欢迎语
            </div>
          </el-form-item>
          <div class="dashed-line"></div>

          <div class="section-title">邀请加群设置</div>

          <el-form-item label="邀请加群：">
            <el-switch
              v-model="formData.invite_group"
              :active-value="1"
              :inactive-value="0"
              active-text="开启"
              inactive-text="关闭"
            />
            <div class="tips mt10">开启之后客户通过此渠道活码添加客服后，系统通过该客服自动邀请客户加入群聊</div>
          </el-form-item>

          <!-- 加群配置：群聊与自动建群规则已拆分为独立模块，此处只做选择 -->
          <el-form-item v-if="formData.invite_group == 1" label="加群配置：" prop="code_id">
            <div class="flex lh-center">
              <el-button size="small" @click="openGroupConfig">
                {{ groupConfig ? '重新选择' : '选择加群配置' }}
              </el-button>
              <!-- 展示名：该配置下所有群聊名称的顿号拼接（编辑态取详情的 group_names，选择弹窗取行内 group_chat） -->
              <span v-if="groupConfig" class="group-config-name ml10">{{ groupConfigName || '--' }}</span>
            </div>
            <div class="tips mt10">选择已维护好的加群配置，客户加好友后将按该配置邀请入群</div>
          </el-form-item>
        </el-form>
      </div>

      <!-- 右侧手机预览 -->
      <div class="channel-code-create__preview">
        <div class="image">
          <div class="right-box" v-if="formData.welcome.content">
            <div class="flex mb10">
              <img :src="userInfo.avatar" alt="" class="avatar" />
              <div class="msg">{{ formData.welcome.content }}</div>
            </div>
            <div class="flex mb10" v-for="(item, index) in attachList" :key="index">
              <img :src="userInfo.avatar" alt="" class="avatar" />
              <img v-if="item.types === 'image'" :src="item.file.url" alt="" class="img" />
              <div v-else-if="item.types === 'video'" class="img">
                <video style="width: 58px; height: 58px">
                  <track kind="captions" />
                  <source :src="item.file.url" type="video/mp4" />
                </video>
                <div class="mask">
                  <span class="iconfont iconbofang"></span>
                </div>
              </div>
              <div v-else class="msg">
                <div v-if="item.types === 'link'" class="line1">{{ item.link }}</div>
                <div v-if="item.types === 'mini_program'">
                  <div class="file-box line pb10">
                    <div>
                      <span class="over-text2">{{ item.title }} {{ item.title }}{{ item.title }}</span>
                    </div>
                    <img v-if="item.file?.url" :src="item.file.url" alt="" style="width: 44px; height: 44px; border-radius: 4px" />
                  </div>
                  <div class="size"><span class="iconfont iconxiaochengxu2" /> 小程序</div>
                </div>
                <div v-if="item.types === 'file'" class="file-box">
                  <div class="left">
                    <div class="over-text">{{ item.file.name }}</div>
                    <span class="size">{{ toSizeFile(item.file.size) }}</span>
                  </div>
                  <img src="../../../assets/images/word.png" alt="" class="file-img" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 底部操作栏：取消（返回） + 保存 -->
    <FormPageFooter :loading="submitting" @save="handleSave" />

    <!-- 加群配置选择组件 -->
    <groupConfigSelect ref="groupConfigSelectRef" @select="handleSelectGroupConfig" />
    <!-- 素材库组件 -->
    <materialLibrary ref="libraryRef" @selectMaterial="handleSelectMaterial" />
    <!-- 客户标签弹窗 -->
    <label-dialog ref="labelDialogRef" :config="labelData" @handleLabelConf="handleLabelConf" />
  </BaseContainer>
</template>

<script>
import { ref, onMounted, getCurrentInstance } from 'vue'
import BaseContainer from '@/components/BaseContainer/index.vue'
import FormPageHeader from '@/components/FormPageHeader/index.vue'
import FormPageFooter from '@/components/FormPageFooter/index.vue'
import { workMassTempApi } from '@/api/weCom'
import { formatBytes } from '@/utils/file'
import { getStorageJson } from '@/utils/storage'
import { useChannelCodeForm } from './composables/useChannelCodeForm'

export default {
  name: 'CreateGroup',
  components: {
    BaseContainer,
    FormPageHeader,
    FormPageFooter,
    materialContent: () => import('./components/materialContent'),
    materialLibrary: () => import('./components/materialLibrary'),
    selectMember: () => import('@/components/form-common/select-member'),
    labelDialog: () => import('@/views/customer/list/components/labelDialog'),
    groupConfigSelect: () => import('./components/groupConfigSelect')
  },
  setup() {
    const { proxy } = getCurrentInstance()
    const form = useChannelCodeForm()

    const formRef = ref(null)
    const materialContentRef = ref(null)
    const groupConfigSelectRef = ref(null)
    const libraryRef = ref(null)
    const labelDialogRef = ref(null)

    // 手机预览的欢迎语附件列表（由 materialContent 事件同步）
    const attachList = ref([])
    const userInfo = getStorageJson('userInfo', {})
    const labelData = ref({})

    onMounted(async () => {
      form.fetchGroupList()
      const data = await form.loadDetail()
      // 编辑态回填欢迎语素材与预览附件
      if (data) {
        const welcome = data.welcome || { content: '', attach: [] }
        attachList.value = welcome.attach || []
        if (materialContentRef.value) {
          materialContentRef.value.getData(welcome)
        }
      }
    })

    const getAttachData = (data) => {
      attachList.value = data
    }

    // 监听素材内容手动修改
    const handleContentChange = (content) => {
      form.formData.welcome.content = content
      validateWelcome()
    }

    // 欢迎语由子组件维护，内容变化后手动触发对应表单项校验
    const validateWelcome = () => {
      proxy.$nextTick(() => {
        formRef.value && formRef.value.validateField('welcome.content')
      })
    }

    // 打开素材库
    const openLibrary = () => {
      if (libraryRef.value) {
        libraryRef.value.openBox()
      } else {
        proxy.$message.error('素材库组件加载失败')
      }
    }

    // 素材选择回调：回填欢迎语文本与附件
    const handleSelectMaterial = (val) => {
      form.formData.welcome.content = val.content
      validateWelcome()
      workMassTempApi(val.id).then((res) => {
        if (!materialContentRef.value) return
        if (res.data.attach.length > 0) {
          res.data.attach.forEach((item) => {
            if (item.file) {
              item.file_id = item.file.id
            }
          })
          attachList.value = res.data.attach
        }
        materialContentRef.value.getData({ attach: attachList.value || [], content: res.data.content })
      })
    }

    // 打开客户标签弹窗
    const handleLabel = () => {
      labelData.value = {
        title: '客户标签',
        width: '540px',
        label: form.labelList.value,
        edit: 1
      }
      labelDialogRef.value.handleOpen()
    }

    // 选中客户标签成功回调
    const handleLabelConf = (res) => {
      form.labelList.value = res.data
    }

    const delTag = (index) => {
      form.labelList.value.splice(index, 1)
    }

    // 打开加群配置选择弹窗，传入已选配置用于回显
    const openGroupConfig = () => {
      if (groupConfigSelectRef.value) {
        groupConfigSelectRef.value.openBox(form.groupConfig.value)
      } else {
        proxy.$message.error('加群配置组件加载失败')
      }
    }

    // 加群配置选择回调；配置由弹窗维护，变更后手动触发对应表单项校验
    const handleSelectGroupConfig = (config) => {
      form.setGroupConfig(config)
      proxy.$nextTick(() => {
        formRef.value && formRef.value.validateField('code_id')
      })
    }

    // 员工选择回调
    const getSelectList = (data) => {
      if (!Array.isArray(data)) return // 防止非数组数据导致报错
      form.userList.value = data
      form.formData.userids = data.map((item) => item.value).filter(Boolean) // 过滤无效值
      proxy.$nextTick(() => {
        formRef.value && formRef.value.validateField('userids')
      })
    }

    const handleSave = () => {
      const attach = materialContentRef.value ? materialContentRef.value.uploadFileList : attachList.value
      form.save(formRef.value, attach)
    }

    const toSizeFile = (size) => formatBytes(size)

    return {
      ...form,

      formRef,
      materialContentRef,
      groupConfigSelectRef,
      libraryRef,
      labelDialogRef,

      attachList,
      userInfo,
      labelData,

      getAttachData,
      handleContentChange,
      openLibrary,
      handleSelectMaterial,
      handleLabel,
      handleLabelConf,
      delTag,
      openGroupConfig,
      handleSelectGroupConfig,
      getSelectList,
      handleSave,
      toSizeFile
    }
  }
}
</script>

<style scoped lang="scss">
.channel-code-create {
  &__body {
    flex: 1;
    min-height: 0;
    display: flex;
  }

  &__form {
    width: 55%;
    padding: 20px;
    overflow-y: auto;
    height: 100%;
    scrollbar-width: none;
  }

  &__preview {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
  }
}

.w-full {
  width: 100%;
}

.section-title {
  font-family: PingFang SC, sans-serif;
  font-weight: 600;
  font-size: 14px;
  color: #333;
  margin: 0 0 20px 9px;
  position: relative;

  &:before {
    content: '';
    background-color: #1890ff;
    width: 3px;
    height: 14px;
    position: absolute;
    left: -9px;
    top: 50%;
    transform: translateY(-50%);
  }
}

// 已选加群配置名称，展示在选择按钮之后
.group-config-name {
  font-size: 13px;
  color: #303133;
}

.tips {
  line-height: 14px;
  font-family: PingFang SC, sans-serif;
  font-size: 12px;
  color: #909399;
}

.iconxiaochengxu2 {
  font-size: 5px;
}

// 客户标签选择框（伪 select 外观）
.plan-footer-one {
  position: relative;
  cursor: pointer;
  -webkit-appearance: none;
  background-color: #fff;
  background-image: none;
  border-radius: 4px;
  border: 1px solid #dcdfe6;
  box-sizing: border-box;
  color: #c0c4cc;
  display: inline-block;
  min-height: 32px;
  line-height: 30px;
  outline: none;
  font-size: 13px;
  padding: 0 10px;
  transition: border-color 0.2s cubic-bezier(0.645, 0.045, 0.355, 1);
  width: 100%;

  .el-tag.el-tag--info {
    font-family: PingFang SC, PingFang SC;
    font-weight: 400;
    font-size: 13px;
    color: #303133;
  }
}

.dashed-line {
  width: 100%;
  height: 3px;
  border-bottom: 1px dashed #dcdfe6;
  margin-bottom: 30px;
  margin-top: 33px;
}

// 手机预览
.channel-code-create__preview {
  .image {
    width: 235px;
    height: 474px;
    background: url(../../../assets/images/mobilePhone.png) no-repeat;
    background-size: 100% 100%;
    object-fit: contain;
    position: relative;

    .right-box {
      position: absolute;
      left: 20px;
      top: 60px;
      width: 200px;
      height: 350px;
      overflow: auto;
      scrollbar-width: none; /* firefox */
      -ms-overflow-style: none; /* IE 10+ */
      overflow-x: hidden;

      .avatar {
        width: 20px;
        height: 21px;
        border-radius: 2px;
        margin-right: 6px;
      }

      .msg {
        width: 166px;
        min-height: 32px;
        background: #ffffff;
        border-radius: 2px;
        font-family: PingFang SC, PingFang SC;
        font-weight: 400;
        font-size: 10px;
        line-height: 11px;
        color: #333333;
        padding: 10px 12px;
      }

      .img {
        display: block;
        border-radius: 4px;
        width: 58px;
        height: 58px;
        position: relative;

        .mask {
          position: absolute;
          top: 0;
          left: 0;
          width: 58px;
          height: 58px;
          border-radius: 4px;
          background-color: rgba(0, 0, 0, 0.3);
          display: flex;
          justify-content: center;
          align-items: center;

          .iconbofang {
            color: #fff;
          }
        }
      }

      .line {
        border-bottom: 1px solid #eeeeee;
      }

      .file-box {
        display: flex;
        justify-content: space-between;

        .left {
          // min-width: 0 让超长文件名在 flex 容器内被裁剪，避免把右侧文件图标挤出预览区
          flex: 1;
          min-width: 0;
          display: flex;
          flex-direction: column;
          justify-content: space-between;

          .over-text {
            max-width: 100%;
          }
        }

        .file-img {
          flex-shrink: 0;
          display: block;
          width: 31px;
          height: 38px;
          margin-left: 6px;
        }
      }

      .size {
        font-family: PingFang SC, PingFang SC;
        font-weight: 400;
        font-size: 5px;
        color: #999999;
      }
    }
  }
}

::v-deep .el-radio:focus:not(.is-focus):not(:active):not(.is-disabled) .el-radio__inner {
  box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2); // 保留焦点样式，提升可访问性
}

// 补充 flex 布局样式
.flex {
  display: flex;
}

.flex-between {
  justify-content: space-between;
}

.lh-center {
  align-items: center;
}

.welcome-form-item {
  position: relative;
  margin-top: 35px;
}

.welcome-btn {
  position: absolute;
  right: 0;
  top: -36px;
}
</style>
