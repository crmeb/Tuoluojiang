<template>
  <div class="page-account-top">
    <div class="title-box">{{ modeConfig.title }}</div>

    <el-form
      ref="formRef"
      :model="form"
      :rules="rules"
      autocomplete="on"
      class="login-form"
      label-position="left"
      @keydown.enter="onFormEnter"
    >
      <el-form-item prop="phone">
        <el-input
          v-model="form.phone"
          placeholder="请输入手机号码"
          autocomplete="on"
          name="phone"
          prefix-icon="el-icon-mobile-phone"
          tabindex="1"
          type="text"
        />
      </el-form-item>
      <el-form-item prop="verification_code">
        <div class="item-box">
          <div>
            <el-input
              v-model="form.verification_code"
              placeholder="请输入验证码"
              autocomplete="off"
              prefix-icon="el-icon-lock"
              tabindex="2"
              type="text"
            />
          </div>
          <el-button :disabled="codeDisabled" class="button" type="primary" @click="sendSmsCode(form.phone)">
            {{ codeText }}
          </el-button>
        </div>
      </el-form-item>
      <el-form-item prop="password">
        <el-input
          v-model="form.password"
          placeholder="请输入密码"
          :type="passwordVisible ? 'text' : 'password'"
          autocomplete="off"
          name="password"
          prefix-icon="el-icon-lock"
          tabindex="3"
        />
        <span class="show-pwd" @click="togglePassword">
          <i class="iconfont" :class="passwordVisible ? 'iconchakan' : 'iconyincang'"></i>
        </span>
      </el-form-item>

      <el-button
        :loading="loading"
        style="width: 100%; margin-bottom: 30px"
        type="primary"
        @click.prevent="handleSubmit"
      >
        {{ modeConfig.submitText }}
      </el-button>
    </el-form>

    <div class="tips-btn">
      已有账号,返回
      <el-link
        underline="never"
        style="margin-top: -3px; margin-left: 5px"
        type="primary"
        @click="$emit('back-login')"
      >
        登录
      </el-link>
    </div>
  </div>
</template>

<script>
import { computed, reactive, ref } from 'vue'
import { registerUserApi, savePasswordApi } from '@/api/user.ts'
import helper from '@/libs/helper.ts'
import { useSmsCode } from '../composables/useSmsCode.ts'

/**
 * 手机号 + 短信验证码 + 密码 的通用面板
 * 账号注册与找回密码流程一致，仅文案与提交接口不同
 */
export default {
  emits: ['back-login'],
  name: 'PhonePasswordPanel',
  props: {
    mode: {
      type: String,
      default: 'register',
      validator: (value) => ['register', 'reset'].includes(value)
    }
  },
  setup(props, { emit }) {
    const { codeDisabled, codeText, sendSmsCode } = useSmsCode()

    const formRef = ref(null)
    const loading = ref(false)
    const passwordVisible = ref(false)

    const form = reactive({
      phone: '',
      verification_code: '',
      password: ''
    })

    const modeConfig = computed(() =>
      props.mode === 'register'
        ? { title: '账号注册', submitText: '注册', submitApi: registerUserApi }
        : { title: '找回密码', submitText: '完成', submitApi: savePasswordApi }
    )

    const validatePhone = (rule, value, callback) => {
      if (!value) {
        return callback(new Error('请输入手机号码'))
      }
      if (!helper.phoneReg.test(value)) {
        return callback(new Error('请输入有效的手机号码'))
      }
      callback()
    }

    const rules = {
      phone: [{ required: true, validator: validatePhone, trigger: 'change' }],
      verification_code: [{ required: true, message: '请输入短信验证码', trigger: 'blur' }],
      password: [{ required: true, message: '请输入密码', trigger: 'blur' }]
    }

    const togglePassword = () => {
      passwordVisible.value = !passwordVisible.value
    }

    /**
     * 表单内回车提交；IME 组字（拼音候选确认）期间的回车仅用于选字，
     * isComposing 为 true 或 keyCode 为 229 时必须忽略，避免验证码未输完就提交。
     * @param {KeyboardEvent} event
     */
    const onFormEnter = (event) => {
      if (event.isComposing || event.keyCode === 229) return
      handleSubmit()
    }

    const handleSubmit = async () => {
      if (loading.value) return

      const valid = await formRef.value.validate().catch(() => false)
      if (!valid) return

      loading.value = true
      try {
        await modeConfig.value.submitApi({ ...form, password_confirm: form.password })
        emit('back-login')
      } catch (error) {
        // 错误提示由 request 统一处理，这里静默
      } finally {
        loading.value = false
      }
    }

    return {
      formRef,
      form,
      rules,
      loading,
      passwordVisible,
      modeConfig,
      codeDisabled,
      codeText,

      sendSmsCode,
      togglePassword,
      onFormEnter,
      handleSubmit
    }
  }
}
</script>

<style lang="scss" scoped>
.page-account-top {
  padding: 20px 0 !important;
  box-sizing: border-box !important;

  .title-box {
    position: relative;
    text-align: left;
    color: #1890ff;
    font-weight: bold;
    height: 40px;
    line-height: 40px;
    margin-bottom: 30px;
    font-size: 18px;

    &:after {
      content: ' ';
      position: absolute;
      left: 0%;
      bottom: 0px;
      width: 60px;
      height: 2px;
      background: #1890ff;
    }
  }
}

.login-form {
  position: relative;
  max-width: 100%;
  margin: 0 auto;
  overflow: hidden;
}

// el-form-item__content 在 Element Plus 中是 flex 容器，子元素不再默认撑满整行
.item-box {
  display: flex;
  width: 100%;

  div {
    position: relative;
    flex: 1;
  }

  .button {
    margin-left: 8px;
  }
}

// 拉满输入框高度后用 flex 居中，替代原先 top:7px + vertical-align 的手调数值：
// 那套数值是为 svg-icon 的盒模型凑的，换成 iconfont 的 <i> 后基线对齐方式不同会偏下。
.show-pwd {
  position: absolute;
  right: 10px;
  top: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  font-size: 16px;
  color: #889aa4;
  cursor: pointer;
  user-select: none;
}

.tips-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #999;
  font-size: 13px;

  .el-link {
    font-size: 13px;
  }
}
</style>
