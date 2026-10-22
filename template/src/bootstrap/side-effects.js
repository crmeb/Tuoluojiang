import { setupPermissionGuard } from "@/permission";
import { registerDialogDirectives } from "@/utils/directive";
import { setupGlobalAvatarFallback } from "@/utils/avatar";
import { setupWatermark } from "@/utils/watermark.util";
import { getMenus } from "@/utils/menu";
import { setupMenuSync } from "@/utils/menu-sync";
import { setupAuthVisibilityCheck } from "@/utils/auth-visibility";

export function registerSideEffects(Vue, router, store) {
  setupPermissionGuard(router, store);
  setupMenuSync(getMenus);
  setupAuthVisibilityCheck(store);
  registerDialogDirectives(Vue);
  setupGlobalAvatarFallback();
  setupWatermark(router, store);
}
