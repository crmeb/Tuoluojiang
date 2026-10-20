import type { App } from "vue";
import type { Router } from "vue-router";
import { setupPermissionGuard } from "@/permission";
import { setupGlobalAvatarFallback } from "@/utils/avatar";
import { setupWatermark } from "@/utils/watermark.util";
import { getMenus } from "@/utils/menu";
import { setupMenuSync } from "@/utils/menu-sync";
import { setupAuthSync } from "@/utils/auth-sync";
import { setupAuthVisibilityCheck } from "@/utils/auth-visibility";

export function registerSideEffects(app: App, router: Router) {
  setupPermissionGuard(router);
  setupMenuSync(getMenus);
  setupAuthSync();
  setupAuthVisibilityCheck();
  setupGlobalAvatarFallback();
  setupWatermark(router);
}
