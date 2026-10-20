<template>
  <div class="base-container flex flex-column" v-bind="$attrs">
    <slot />
  </div>
</template>

<style scoped lang="scss">
.base-container {
  --container-bg: linear-gradient(154deg, rgba(255, 255, 255, 0.23) 0%, #fff 25%);

  --mb: 15px;
  margin: 0 14px var(--mb);
  height: calc(100vh - 64px - var(--mb));
  background: var(--container-bg);
  border-radius: 8px;
  padding: 20px;

  &.none-bg {
    background: none;
  }

  &.none-padding {
    padding: 0;
  }
}
</style>
