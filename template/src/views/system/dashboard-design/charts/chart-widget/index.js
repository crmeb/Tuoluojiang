export const registerChartWidgets = (app) => {
  const requireComponent = import.meta.glob('./*.vue', { eager: true })
  /* 全局注册！！ */
  Object.keys(requireComponent).forEach((fileName) => {
    let comp = requireComponent[fileName].default
    app.component(comp.name, comp)
  })
}
