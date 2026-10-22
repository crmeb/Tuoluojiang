import Vue from 'vue';
import Vuex from 'vuex';
import getters from './getters';

Vue.use(Vuex);

// 自动加载 ./modules 下的全部 vuex module，无需逐个 import
const modulesFiles = import.meta.glob('./modules/**/*.js', { eager: true });

const modules = Object.keys(modulesFiles).reduce((modules, modulePath) => {
  // set './modules/app.js' => 'app'
  const moduleName = modulePath.replace(/^\.\/modules\/(.*)\.\w+$/, '$1');
  const value = modulesFiles[modulePath];
  modules[moduleName] = value.default;
  return modules;
}, {});

const store = new Vuex.Store({
  modules,
  getters,
});

export default store;
