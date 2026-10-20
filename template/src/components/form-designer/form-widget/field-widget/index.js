const requireComponent = import.meta.glob('./*.vue', { eager: true })

let comps = {}

Object.keys(requireComponent).forEach(fileName => {
  let comp = requireComponent[fileName].default;
  comps[comp.name] = comp
})

export default comps;
