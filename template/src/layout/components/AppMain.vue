<template>
  <section class="app-main">
    <router-view v-slot="{ Component }">
      <transition name="el-fade-in-linear">
        <component :is="Component" :key="key" />
      </transition>
    </router-view>
    <div v-if="routeLoading" class="route-loading" role="status" aria-live="polite">
      <span class="route-loading__spinner" aria-hidden="true"></span>
      <span>页面加载中…</span>
    </div>
  </section>
</template>

<script>
import { routeLoading } from '@/router/loading.ts'

export default {
  name: 'AppMain',

  setup() {
    return { routeLoading }
  },

  computed: {
    key() {
      return this.$route.path
    }
  }
}
</script>

<style lang="scss" scoped>
.app-main {
  /* 50= navbar  50  */
  // min-height: calc(100vh - 66px);
  width: 100%;
  position: relative;
  flex: 1;
  min-height: 0;
}

.route-loading {
  position: absolute;
  inset: 0;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  min-height: 160px;
  background: rgba(255, 255, 255, 0.88);
  color: #606266;
  font-size: 14px;
}

.route-loading__spinner {
  width: 20px;
  height: 20px;
  border: 2px solid #dcdfe6;
  border-top-color: #409eff;
  border-radius: 50%;
  animation: route-loading-spin 0.8s linear infinite;
}

@keyframes route-loading-spin {
  to { transform: rotate(360deg); }
}

.fixed-header + .app-main {
  padding-top: 60px;
}
</style>

<style lang="scss" scoped>
// fix css style bug in open el-dialog
.el-popup-parent--hidden {
  .fixed-header {
    padding-right: 15px;
  }
}
</style>
