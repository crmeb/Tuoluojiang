const requireComponent = import.meta.glob('./**/*.vue', { eager: true })  //遍历当前目录和子目录

let comps = {}

Object.keys(requireComponent).forEach(fileName => {
  let comp = requireComponent[fileName].default;
  comps[comp.name] = comp
})

export default comps;
