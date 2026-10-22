import request from '@/api/request'
import Vue from 'vue'
import { Message, MessageBox } from 'element-ui'

let unique = 1
const uniqueId = () => ++unique
const createElement = new Vue().$createElement

export default function modalForm(formRequestPromise, config = {}) {
  return new Promise((resolve, reject) => {
    let formApi = null
    formRequestPromise
      .then(({ data }) => {
        if (!data.config) data.config = {}
        data.config.submitBtn = false
        data.config.resetBtn = false
        if (!data.config.form) data.config.form = {}
        data.config.form.labelSuffix = '：'
        data.config.form.labelWidth = '90px'
        if (!data.config.formData) data.config.formData = {}
        data.config.formData = { ...data.config.formData, ...config.formData }
        data.config.global = {
          upload: {
            props: {
              onSuccess(rep, file) {
                if (rep.status === 200) {
                  file.url = rep.data.src
                }
              }
            }
          },
          frame: {
            props: {
              onLoad(e) {
                e.fApi = formApi
              }
            }
          },
          inputNumber: {
            props: {
              controls: false
            }
          }
        }
        data = Vue.observable(data)
        MessageBox({
          title: data.title,
          showCancelButton: true,
          customClass: config.class || 'modal-form',
          mask: false,
          message: createElement('div', { class: 'common-form-create', key: uniqueId() }, [
            createElement('formCreate', {
              props: {
                rule: data.rule,
                option: data.config
              },
              on: {
                mounted: ($f) => {
                  formApi = $f
                }
              }
            })
          ]),
          beforeClose: (action, instance, done) => {
            if (action === 'confirm') {
              if (!formApi) {
                instance.confirmButtonLoading = false
                return
              }
              instance.confirmButtonLoading = true
              formApi.submit(
                (formData) => {
                  request[data.method.toLowerCase()](data.action.slice(4), formData)
                    .then((res) => {
                      if (res.status === 200) {
                        instance.confirmButtonLoading = false
                        formApi = null
                        done()
                        resolve(res)
                      } else {
                        instance.confirmButtonLoading = false
                        reject(res)
                      }
                    })
                    .catch((err) => {
                      instance.confirmButtonLoading = false
                      reject(err)
                    })
                },
                () => {
                  instance.confirmButtonLoading = false
                }
              )
            } else {
              instance.confirmButtonLoading = false
              formApi = null
              done()
            }
          }
        })
      })
      .catch((e) => {
        Message.error(e.message || '获取表单配置失败')
      })
  })
}
