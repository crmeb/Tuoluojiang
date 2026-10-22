<template>
  <div class="image-attach-upload">
    <!-- 已上传图片：缩略图 + 右上角删除角标（对齐原型与商品规格图上传样式） -->
    <div v-for="(file, index) of fileList" :key="file.id || file.url || index" class="image-attach-upload__item">
      <el-image :src="file.url" :preview-src-list="previewList" class="image-attach-upload__img" />
      <i class="el-icon-error image-attach-upload__del" @click.stop="handleRemove(index)" />
    </div>

    <!-- 上传按钮：相机图标虚线框，达到上限后隐藏 -->
    <el-upload v-if="fileList.length < maxLength" :action="fileUrl" :headers="headers" accept="image/*"
      :show-file-list="false" :before-upload="handleBeforeUpload" :on-success="handleSuccess" :on-error="handleError"
      :multiple="maxLength > 1" class="image-attach-upload__upload">
      <div class="image-attach-upload__trigger">
        <img v-if="uploadingCount > 0" src="@/assets/images/loading.gif" alt="" class="image-attach-upload__loading" />
        <i v-else class="el-icon-camera cameraIconfont" />
      </div>
    </el-upload>
  </div>
</template>

<script>
import { ref, computed, watch, getCurrentInstance } from "vue";
import SettingMer from "@/libs/settingMer";
import { getToken } from "@/utils/auth";
import compressImg from "@/utils/compressImg";

// 超过该体积先压缩再上传（对齐图片库 uploadPicture 组件）
const COMPRESS_THRESHOLD = 2 * 1024 * 1024;

export default {
  name: "ImageAttachUpload",
  props: {
    // 已上传图片列表，每项含 url / id（与 oa-upload 的产出字段一致）
    value: {
      type: Array,
      default: () => []
    },
    maxLength: {
      type: Number,
      default: 1
    }
  },
  setup(props, { emit }) {
    const { proxy } = getCurrentInstance();

    // 走图片库同款上传：el-upload 直传 system/attach/upload，附件由后端落库
    const fileUrl = `${SettingMer.https}/system/attach/upload`;
    const headers = computed(() => ({ Authorization: "Bearer " + getToken() }));

    // 并发上传计数：multiple 选择时会按文件逐个触发
    const uploadingCount = ref(0);

    // props 要等父组件重渲染才更新（异步），multiple 并发上传时后一个成功会覆盖前一个，
    // 故用本地镜像持有最新列表，emit 后立即同步
    const fileList = ref([...props.value]);
    watch(
      () => props.value,
      (val) => {
        fileList.value = [...(val || [])];
      }
    );

    const previewList = computed(() => fileList.value.map((file) => file.url));

    const emitChange = (files) => {
      fileList.value = files;
      emit("input", files);
      emit("getVal", files);
    };

    const handleRemove = (index) => {
      const next = [...fileList.value];
      next.splice(index, 1);
      emitChange(next);
    };

    const handleBeforeUpload = async (file) => {
      // 上传中的文件也占用名额，避免 multiple 并发时超出上限
      if (fileList.value.length + uploadingCount.value >= props.maxLength) {
        proxy.$message.warning(`最多上传${props.maxLength}张图片`);
        throw new Error("exceed max length");
      }

      if (!/image\/\w+/.test(file.type)) {
        proxy.$message.error("请上传以jpg、jpeg、png等结尾的图片文件");
        throw new Error("invalid image type");
      }

      uploadingCount.value += 1;
      // 大图压缩后返回新 File，压缩失败则退回原文件继续上传
      if (file.size >= COMPRESS_THRESHOLD) {
        try {
          return await compressImg(file);
        } catch (e) {
          return file;
        }
      }
      return file;
    };

    // 接口恒返回 HTTP 200，业务失败（如 webp 被拒）体现在 body.status
    const handleSuccess = (res) => {
      uploadingCount.value -= 1;
      if (Number(res?.status) !== 200) {
        proxy.$message.error(res?.message || res?.msg || "上传失败");
        return;
      }
      const data = res.data || {};
      const url = data.url || data.src;
      emitChange([
        ...fileList.value,
        {
          real_name: data.real_name || data.name,
          size: data.size,
          name: data.name,
          url,
          id: data.id || data.attach_id,
          file_url: url
        }
      ]);
    };

    // 网络异常 / 非 2xx：element 会把响应体塞进 error.message，尽量还原后端文案
    const handleError = (err) => {
      uploadingCount.value -= 1;
      let msg = "";
      try {
        const body = JSON.parse(err?.message);
        msg = body.message || body.msg || "";
      } catch (e) {
        // 非 JSON 响应，走兜底文案
      }
      proxy.$message.error(msg || "图片上传失败，请稍后重试");
    };

    return {
      fileUrl,
      headers,
      uploadingCount,
      fileList,
      previewList,
      handleRemove,
      handleBeforeUpload,
      handleSuccess,
      handleError
    };
  }
};
</script>

<style lang="scss" scoped>
.image-attach-upload {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;

  &__item {
    position: relative;
    width: 58px;
    height: 58px;

    // !important 反制全局 table .el-image 的 36px 固定尺寸
    .image-attach-upload__img {
      display: block !important;
      width: 100% !important;
      height: 100% !important;
      border: 1px dotted rgba(0, 0, 0, 0.1);
      border-radius: 4px;
      overflow: hidden;
    }

    .image-attach-upload__del {
      position: absolute;
      z-index: 1;
      top: -6px;
      right: -6px;
      font-size: 16px;
      color: rgba(0, 0, 0, 0.45);
      background: #fff;
      border-radius: 50%;
      cursor: pointer;

      &:hover {
        color: #f56c6c;
      }
    }
  }

  &__trigger {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 58px;
    height: 58px;
    border: 1px dotted rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    background: rgba(0, 0, 0, 0.02);
    cursor: pointer;
  }

  &__loading {
    width: 24px;
    height: 24px;
  }
}
</style>
