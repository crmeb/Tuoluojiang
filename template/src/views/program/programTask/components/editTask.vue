<!-- 编辑任务侧滑弹窗  -->
<template>
  <div class="station">
    <el-drawer
      class="program-task-edit-task-drawer"
      v-model="drawer"
      :modal="true"
      :show-close="false"
      :append-to-body="true"
      size="1120px"
      :before-close="handleClose"
      @click="changeMembers"
    >
      <template #header>
        <div class="flex between-header align-center">
          <div class="flex align-center">
            <span class="ident copy" @click="copy" title="点击复制工作项地址" :data-clipboard-text="identAddress()"
              >#{{ formData.ident }}</span
            >
            <el-select
              v-model="formData.status"
              filterable
              :disabled="!isOperable"
              collapse-tags
              placeholder="状态"
              style="width: 100px"
              @change="changeStatus"
            >
              <el-option v-for="item in statusOptions" :key="item.value" :label="item.label" :value="item.value">
              </el-option>
            </el-select>
          </div>
          <div class="flex">
            <!-- <el-popover v-if="formData.level !== 4" placement="bottom" trigger="hover" content="新建子任务">
              <i slot="reference" class="iconfont icontiaojian" @click="addSubtasks(formData)"></i>
            </el-popover> -->
            <el-dropdown trigger="hover">
              <span class="el-dropdown-link">
                <i class="iconfont icongengduo2 fz30 pointer mb14"></i>
              </span>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item @click="addSubtasks(formData)">新建子任务</el-dropdown-item>
                  <el-dropdown-item @click="copyTask">复制工作项</el-dropdown-item>
                  <el-dropdown-item v-if="formData.operate" @click="deleteTask">删除</el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </div>
        </div>
      </template>
      <!-- 固定 -->
      <div class="header header-item">
        <div class="title">
          <div v-if="!nameShow" ref="title" class="name line3">{{ formData.name }}</div>
          <el-input
            v-else
            class="input-item"
            v-model="formData.name"
            maxlength="100"
            show-word-limit
            placeholder="请输入任务名称"
            ref="name"
            @blur="handleName"
            @keyup.enter="nameShow = false"
          />
          <i v-if="!nameShow && isOperable" @click="nameShowFn" class="iconfont iconbianji pointer"></i>
        </div>
        <div class="creator">
          <span
            >{{ formData.creator?.[0]?.name || '' }} · {{ formData.created_at }}，最近更新于{{
              formData.updated_at
            }}</span
          >
        </div>
      </div>
      <el-form ref="formData" :model="formData" label-width="84px" :rules="rule">
        <div class="left-card">
          <el-row class="ml20">
            <el-col :span="12">
              <el-form-item label="关联项目：" prop="program_id">
                <div class="bg">
                  <span v-if="!programShow" @click.stop="handleToggle('programShow')" class="pointer p-l-12">{{
                    getProgramVal()
                  }}</span>
                </div>
                <el-select
                  v-if="programShow"
                  v-model="formData.program_id"
                  placeholder="请选择关联项目"
                  @change="handleContract"
                  filterable
                >
                  <el-option v-for="item in projectList" :key="item.value" :label="item.label" :value="item.value">
                    <div>
                      <span>{{ item.label }}</span>
                      <span
                        v-if="item.status == 1 || item.status == 2"
                        :class="item.status == 1 ? 'program-stop' : 'program-close'"
                        >{{ item.status == 1 ? '已暂停' : '已关闭' }}</span
                      >
                    </div>
                  </el-option>
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="负责人：" class="select-bar">
                <div v-if="!adminsShow" @click.stop="handleToggle('adminsShow')" class="admins pointer bg flex">
                  <div class="p-l-12" v-if="formData.admins && formData.admins.length == 0">未设置</div>
                  <div class="avatar-box p-l-12" v-for="(item, index) in formData.admins" :key="index">
                    <img v-default-avatar="item" class="mr4" :src="getAvatarSrc(item)" alt="" />
                    <span>{{ item.name }}</span>
                  </div>
                </div>
                <el-select
                  v-if="adminsShow"
                  v-model="formData.uid"
                  filterable
                  clearable
                  placeholder="请选择负责人"
                  @change="selectAdmins"
                >
                  <el-option v-for="item in programMemberOptions" :key="item.id" :label="item.name" :value="item.id">
                  </el-option>
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>
          <el-row class="ml20">
            <el-col :span="12">
              <el-form-item label="计划时间：" class="select-bar">
                <div v-if="!timeValShow" class="bg pointer" @click.stop="handleToggle('timeValShow')">
                  <span class="p-l-12" v-if="formData.plan_start"
                    >{{ formData.plan_start }} - {{ formData.plan_end }}</span
                  >
                  <span class="p-l-12" v-if="!formData.plan_start">未设置</span>
                </div>
                <el-date-picker
                  v-if="timeValShow"
                  v-model="timeVal"
                  type="daterange"
                  clearable
                  format="YYYY-MM-DD"
                  value-format="YYYY-MM-DD"
                  placeholder="请选择项目计划时间"
                  range-separator="至"
                  start-placeholder="开始时间"
                  end-placeholder="结束时间"
                  @blur="timeValShow = false"
                  @change="onchangeTime"
                ></el-date-picker>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="协作者：" class="select-bar">
                <div v-if="!membersShow" @click.stop="handleToggle('membersShow')" class="admins pointer flex bg">
                  <div class="p-l-12" v-if="formData.members && formData.members.length == 0">未设置</div>
                  <div class="avatar-box p-l-12" v-if="formData.members && formData.members.length <= 3">
                    <div class="members-box" v-for="(item, index) in formData.members.slice(0, 3)" :key="index">
                      <img v-default-avatar="item" class="mr4" :src="getAvatarSrc(item)" alt="" />
                      <span>{{ item.name }}</span>
                    </div>
                  </div>
                  <el-popover
                    v-if="formData.members && formData.members.length > 3"
                    placement="bottom-start"
                    title="其他协作者："
                    width="260"
                    trigger="hover"
                  >
                    <div class="members-box" v-if="formData.members && formData.members.length > 3">
                      <div class="members-box" v-for="(item, index) in formData.members.slice(3)" :key="index">
                        <img v-default-avatar="item" :src="getAvatarSrc(item)" alt="" />

                        <span class="members-box-content">{{ item.name }}</span>
                      </div>
                    </div>
                    <template #reference>
                      <div class="p-l-12">
                        <div class="members-box" v-for="(item, index) in formData.members.slice(0, 3)" :key="index">
                          <img v-default-avatar="item" :src="getAvatarSrc(item)" alt="" />
                        </div>
                        <span v-if="!formData.members">- -</span>
                        <span class="contents" v-if="formData.members && formData.members.length > 3"
                          >+ {{ formData.members.length - 3 }}</span
                        >
                      </div>
                    </template>
                  </el-popover>
                </div>
                <el-select
                  v-else
                  v-model="formData.membersId"
                  multiple
                  filterable
                  collapse-tags
                  placeholder="指派协作者"
                >
                  <el-option v-for="item in programMemberOptions" :key="item.id" :label="item.name" :value="item.id">
                  </el-option>
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>

          <div class="content ml20">
            <div v-if="!describeShow" class="flex describe between">
              <span>描述：</span>
              <i v-if="isOperable" class="iconfont iconbianji pointer" @click="describeShow = true"></i>
            </div>
            <div
              class="describe-box"
              v-if="!describeShow && formData.describe"
              v-html="formData.describe"
              @click="previewPicture($event)"
            ></div>

            <div v-else>
              <component
                is="ueditorFrom"
                :border="describeShow"
                ref="ueditorFrom"
                :height="`400px`"
                :headers="describeShow"
                :readOnly="!describeShow"
                type="notepad"
                :content="formData.describe"
                @input="ueditorEdit"
                :placeholder="formData.describe ? '' : '无描述内容'"
              />
            </div>

            <div v-if="describeShow" class="ueditorBtn">
              <el-button @click="cancelDescribe">取消</el-button>
              <el-button type="primary" @click="submitDescribe">保存</el-button>
            </div>
          </div>
          <!-- 评论 -->
          <div class="comment">
            <el-tabs v-model="activeName">
              <el-tab-pane :label="`评论(${totalCount})`" name="reply">
                <comment
                  ref="comment"
                  :pid="formData.pid"
                  :taskId="formData.id"
                  @gettotalCount="gettotalCount"
                ></comment>
              </el-tab-pane>
              <el-tab-pane :label="`操作日志(${dynamicTotalCount})`" name="dynamic">
                <oa-log :list="dynamicTaskList"></oa-log>
              </el-tab-pane>
            </el-tabs>
          </div>
        </div>
        <div class="right-card">
          <el-form-item label="父级任务：" prop="path">
            <div v-if="!pathShow" @click.stop="handleToggle('pathShow')" class="flex bg pointer pointer p-l-12">
              <span v-if="formData.parent">{{ formData.parent.name }}</span>
              <span class="" v-else>未设置</span>
            </div>

            <el-cascader
              v-if="pathShow"
              v-model="formData.path"
              :options="programOptions"
              :props="{ checkStrictly: true }"
              collapse-tags
              clearable
              @change="handleRemove"
              filterable
            >
            </el-cascader>
          </el-form-item>
          <el-form-item label="优先级：" class="select-bar pointer">
            <div v-if="!priorityShow" class="flex align-center bg p-l-12" @click.stop="handleToggle('priorityShow')">
              <span class="circle" :class="getClass()"></span>
              <span>{{ getVal() }}</span>
            </div>
            <el-select
              @change="changePriority"
              v-else
              v-model="formData.priority"
              placeholder="请选择优先级"
            >
              <el-option v-for="item in priorityOptions" :key="item.value" :label="item.label" :value="item.value">
              </el-option>
            </el-select>
          </el-form-item>
          <el-form-item v-if="formData.program_id" label="关联版本：" prop="version_id">
            <div v-if="!versionShow" class="bg pointer set-version" @click.stop="handleToggle('versionShow')">
              <span class="pointer p-l-12">{{ getVersionVal() }}</span>
              <span class="" v-if="programVersionList.length == 0 || !formData.version_id">未设置</span>
            </div>
            <el-select
              v-if="versionShow"
              v-model="formData.version_id"
              clearable
              placeholder="请选择关联版本"
              @change="handleVersion"
              style="width: 80%"
              filterable
            >
              <el-option v-for="item in programVersionList" :key="item.id" :label="item.name" :value="item.id" />
            </el-select>
            <span v-if="isOperable" class="pointer settings" @click="setVersion">设置</span>
          </el-form-item>
        </div>
      </el-form>
    </el-drawer>
    <!-- 设置关联版本 -->
    <el-dialog
      :append-to-body="true"
      top="13%"
      title="版本设置"
      v-model="dialogVisible"
      width="30%"
      :before-close="handleCloseVersion"
    >
      <draggable v-model="inputs" class="drag-area" @start="dragging = true" @end="dragging = false" :item-key="$draggableKey">
        <template #item="{ element: input, index }">
          <div class="input-row">
            <i class="iconfont icontuodong pointer"></i>
            <el-input v-model="input.name" placeholder="请输入版本名称"></el-input>
            <span class="pointer" @click="removeInput(index)">-</span>
          </div>
        </template>
      </draggable>
      <span class="add-input pointer" @click="addInput">
        <i class="el-icon-plus"></i>
        <span>添加版本</span>
      </span>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="handleCloseVersion">取 消</el-button>
          <el-button type="primary" @click="submitVersion">确 定</el-button>
        </div>
      </template>
    </el-dialog>

    <image-viewer ref="imageViewer" :src-list="srcList"></image-viewer>
  </div>
</template>

<script>
import { mapStores } from 'pinia'
import { useAppStore } from '@/stores/app.ts'
import { defineAsyncComponent } from 'vue'
import { modalSure } from '@/libs/public.ts'
import { getAvatarSrc } from '@/utils/avatar.ts'
import {
  putProgramTaskApi,
  getProgramTaskInfoApi,
  getDynamicTaskApi,
  deleteProgramTaskApi,
  getProgramVersionApi,
  setProgramVersionApi
} from '@/api/program.ts'
import imageViewer from '@/components/common/imageViewer'
import draggable from 'vuedraggable'
import ClipboardJS from 'clipboard'
import SettingMer from '@/libs/settingMer.ts'
import ueditorFrom from '@/components/form-common/oa-wangeditor'
import oaLog from '@/components/form-common/oa-log'

export default {
  emits: ['getProgramVersionList', 'getMemberList', 'getProgramSelectList', 'openTask', 'getTableData'],
  name: 'addProgram',
  components: {
    comment: defineAsyncComponent(() => import('../components/comment')),
    ueditorFrom,
    draggable,
    imageViewer,
    oaLog
  },
  props: {
    projectList: {
      type: Array,
      default: () => []
    },
    programVersionList: {
      type: Array,
      default: () => []
    },
    programOptions: {
      type: Array,
      default: () => []
    },
    programMemberOptions: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      drawer: false,
      id: 0,
      principal: [],
      type: 0,
      memberShow: false,
      onlyOne: false,
      edit: false,
      dialogVisible: false,
      inputs: [
        {
          id: '',
          name: ''
        }
      ],
      dragging: false,
      timeVal: [],
      formData: {
        status: 1,
        parent: {
          name: ''
        },
        uid: ''
      },
      priorityOptions: [
        {
          value: 1,
          label: '紧急'
        },
        {
          value: 2,
          label: '高'
        },
        {
          value: 3,
          label: '中'
        },
        {
          value: 4,
          label: '低'
        },
        {
          value: 0,
          label: '无优先级'
        }
      ],
      statusOptions: [
        {
          value: 0,
          label: '未处理'
        },
        {
          value: 1,
          label: '进行中'
        },
        {
          value: 2,
          label: '已解决'
        },
        {
          value: 3,
          label: '已验收'
        },
        {
          value: 4,
          label: '已拒绝'
        },
        {
          value: 5,
          label: '已关闭'
        }
      ],
      rule: {
        name: [{ required: true, message: '请输入项目名称', trigger: 'blur' }],
        program_id: [{ required: true, message: '请选择关联项目', trigger: 'change' }]
      },
      nameShow: false,
      adminsShow: false,
      membersShow: false,
      priorityShow: false,
      timeValShow: false,
      pathShow: false,
      programShow: false,
      versionShow: false,
      describeShow: false,
      activeName: 'reply',
      dynamicTaskList: [],
      dynamicTotalCount: 0,
      totalCount: 0,
      srcList: []
    }
  },
  computed: {
    ...mapStores(useAppStore),
    // 是否有操作权限
    isOperable() {
      return this.formData.operate
    }
  },
  methods: {
    getAvatarSrc,
    handleToggle(showProperty) {
      if (!this.formData.operate) {
        return this.$message('没有操作权限')
      }
      this[showProperty] = true
    },
    //预览图片
    previewPicture(e) {
      if (e.target.tagName == 'IMG') {
        this.srcList = [e.target.src]
        this.$refs.imageViewer.openImageViewer(e.target.src)
      }
    },
    nameShowFn() {
      this.nameShow = true
      setTimeout(() => {
        this.$refs.name.focus()
      }, 300)
    },
    identAddress() {
      let type = 'type',
        relation_id = 'relation_id',
        program_id = 'program_id'
      return `${SettingMer.httpUrl}${window.location.pathname}${window.location.search}?&${type}=2&${relation_id}=${this.id}&${program_id}=${this.formData.ident}`
    },
    // 设置版本
    setVersion() {
      this.dialogVisible = true
      this.getVersion()
    },
    submitVersion() {
      setProgramVersionApi(this.formData.program_id, { data: this.inputs }).then(() => {
        this.dialogVisible = false
        this.inputs = []
        this.$emit('getProgramVersionList', this.formData.program_id)
      })
    },
    // 获取版本
    async getVersion() {
      const result = await getProgramVersionApi({ program_id: this.formData.program_id })
      this.inputs = result.data
    },
    handleCloseVersion() {
      this.dialogVisible = false
      this.inputs = []
    },
    addInput() {
      this.inputs.push({ id: '', name: '' })
    },
    removeInput(index) {
      this.inputs.splice(index, 1)
    },
    // 选择项目
    handleContract(eid) {
      this.$emit('getProgramVersionList', eid)
      this.$emit('getMemberList', eid)
      this.$emit('getProgramSelectList', eid)
      this.formData.version_id = ''
      this.formData.members = []
      this.formData.uid = ''
      this.versionShow = true
      this.programShow = false
      this.putProgramTask('program_id', this.formData.program_id)
    },
    // 修改版本
    handleVersion(e) {
      this.versionShow = false
      // if(!this.formData.version_id)
      this.putProgramTask('version_id', this.formData.version_id)
    },

    // 选择成员回调
    // getSelectList(data) {
    //   this.formData.uid = data[0].value
    //   this.principal = data
    //   this.formData.admins = data
    //   this.adminsShow = false
    //   this.putProgramTask('uid', this.formData.uid)
    // },
    // 获取任务详情
    async getProgramInfo(id) {
      const result = await getProgramTaskInfoApi(id)
      let membersId = []
      result.data.members.map((item) => {
        membersId.push(item.id)
      })
      result.data.membersId = membersId
      this.formData = result.data
      this.principal = result.data.admins
      this.formData.uid = result.data.uid !== 0 ? this.formData.uid : null
      this.timeVal = this.formData.plan_start ? [this.formData.plan_start, this.formData.plan_end] : []
      this.formData.priority = result.data.priority ? result.data.priority : 0
      this.formData.version_id = result.data.version_id ? result.data.version_id : null
      if (this.formData.describe == '') {
        this.formData.describe = '<p><br></p>'
      }
      this.$emit('getProgramVersionList', result.data.program_id)
    },
    // 删除标签
    cardTag(index) {
      this.principal.splice(index, 1)
      this.formData.uid = ''
    },
    onchangeTime(e) {
      if (e == null) {
        this.timeVal = ''
        this.formData.plan_start = ''
        this.formData.plan_end = ''
      } else {
        this.formData.plan_start = this.timeVal[0] || ''
        this.formData.plan_end = this.timeVal[1] || ''
      }
      this.timeValShow = false
      this.putProgramTask('plan_date', this.formData.plan_start, this.formData.plan_end)
    },
    handleRemove(e) {
      this.formData.path = e
      this.formData.pid = e[e.length - 1]
      this.putProgramTask('pid', this.formData.pid)
      this.pathShow = false
    },
    ueditorEdit(e) {
      this.formData.describe = e
    },
    openBox(id) {
      this.drawer = true
      this.edit = id ? true : false
      this.id = id ? id : 0
      if (this.edit) {
        this.getProgramInfo(id)

        this.getDynamicTask()
        // this.$refs.comment.getTaskComment()
      }
    },
    reset() {
      this.formData = {
        name: '',
        uid: '',
        pid: 0,
        path: [],
        program_id: null,
        version_id: null,
        members: [],
        plan_start: '',
        plan_end: '',
        status: null,
        priority: 0,
        describe: ''
      }
      this.timeVal = []
      this.edit = false
      this.principal = []
    },
    // 新建子任务
    addSubtasks(data) {
      this.formData = {}
      this.formData.path = data.pid ? [data.pid].concat([data.id]) : data.id
      this.formData.pid = data.id
      this.formData.program_id = data.program_id
      this.$emit('getProgramSelectList', data.program_id)
      this.$emit('openTask', this.formData, 1)
      this.drawer = false
    },
    // 复制工作项
    copyTask() {
      this.$emit('openTask', this.formData, 2)
    },
    // 评论总条数
    gettotalCount(total) {
      this.totalCount = total
    },
    // 删除
    deleteTask() {
      modalSure('删除任务，关联的子级任务均会被删除').then(() => {
        deleteProgramTaskApi(this.id).then(() => {
          this.drawer = false
          this.reset()
          this.$refs.formData.resetFields()
          this.$emit('getTableData')
        })
      })
    },
    handleName() {
      if (this.formData.name.trim()) {
        this.nameShow = false
        this.putProgramTask('name', this.formData.name)
      } else {
        this.$message('任务名称不能为空')
      }
    },

    changeMembers() {
      const conditions = [
        { show: this.membersShow, key: 'members', value: this.formData.membersId },
        { show: this.adminsShow, key: 'uid', value: this.formData.uid },
        { show: this.programShow, key: 'program_id', value: this.formData.program_id },
        { show: this.timeValShow, key: 'plan_date', value: [this.formData.plan_start, this.formData.plan_end] },
        { show: this.pathShow, key: 'pid', value: this.formData.pid },
        { show: this.priorityShow, key: 'priority', value: this.formData.priority },
        { show: this.versionShow, key: 'version_id', value: this.formData.version_id }
      ]

      const promises = conditions
        .filter((condition) => condition.show)
        .map((condition) => {
          return new Promise((resolve) => {
            setTimeout(() => {
              this.putProgramTask(condition.key, condition.value)
              resolve()
            }, 500)
          })
        })

      Promise.all(promises).then(() => {
        this.membersShow = false
        this.adminsShow = false
        this.programShow = false
        // this.timeValShow = false
        this.priorityShow = false
        this.versionShow = false
      })
    },
    selectAdmins() {
      this.putProgramTask('uid', this.formData.uid)
      this.adminsShow = false
    },
    changePriority() {
      this.priorityShow = false
      this.putProgramTask('priority', this.formData.priority)
    },
    getVal() {
      let data = this.priorityOptions.find((option) => option.value === this.formData.priority)
      if (data) return data.label
    },
    getProgramVal() {
      let data = this.projectList.find((option) => option.value === this.formData.program_id)
      if (data) return data.label
    },
    getVersionVal() {
      let data = this.programVersionList.find((option) => option.id === this.formData.version_id)
      if (data) return data.name
    },
    getClass() {
      let type = this.formData.priority
      switch (type) {
        case 0: {
          return 'info'
        }
        case 1: {
          return 'danger'
        }
        case 2: {
          return 'warning'
        }
        case 3: {
          return 'default'
        }
        case 4: {
          return 'success'
        }
        default:
          'info'
      }
    },
    // 保存富文本
    submitDescribe() {
      this.putProgramTask('describe', this.formData.describe)
    },
    // 修改任务
    putProgramTask(field, data, endTime) {
      let datas = {}
      datas['field'] = field
      if (field == 'plan_date') {
        datas['plan_start'] = data || ''
        datas['plan_end'] = endTime || ''
      } else {
        datas[field] = data
      }
      if (field == 'program_id') {
        if (!datas[field]) return
      }
      if (field == 'plan_date' && datas.plan_end == '') {
        datas.plan_start = ''
      }

      putProgramTaskApi(this.id, datas).then(() => {
        if (field == 'describe') {
          this.describeShow = false
        }

        this.getProgramInfo(this.id)
      })
    },
    // 修改状态
    changeStatus(e) {
      this.putProgramTask('status', e)
    },
    // 取消
    cancelDescribe() {
      this.describeShow = false
      this.getProgramInfo(this.id)
    },
    // 操作日志列表
    getDynamicTask() {
      getDynamicTaskApi({ relation_id: this.id }).then((res) => {
        this.dynamicTaskList = res.data.list
        this.dynamicTotalCount = res.data.total_count > 100 ? '99+' : res.data.total_count
      })
    },
    // 关闭抽屉
    handleClose() {
      this.drawer = false
      const properties = [
        'nameShow',
        'adminsShow',
        'membersShow',
        'priorityShow',
        'timeValShow',
        'pathShow',
        'programShow',
        'versionShow',
        'describeShow'
      ]

      properties.forEach((property) => {
        this[property] = false
      })

      this.$emit('getTableData')
      this.$refs.comment.cancelComment()
    },
    // iconHadler({ row }) {
    //   if (!row.describe || row.describe.length == 0) {
    //     return 'icon-no'
    //   }
    // },
    copy() {
      this.$nextTick(() => {
        const clipboard = new ClipboardJS('.copy')
        clipboard.on('success', () => {
          this.$message.success('复制成功')
          clipboard.destroy()
        })
        this.appStore.setClickTab(false)
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.header {
  background: #fff;
  z-index: 999;
}
.header-item {
  position: fixed;
}
.station :deep(.edui-editor-iframeholder) {
  height: 300px !important;
}
.dialog-footer {
  // padding-top: 20px;
  // border-top: 1px solid #e6ebf5;
  text-align: right;
}
:deep(.el-cascader),
:deep(.el-input-number),
:deep(.el-select),
:deep(.el-date-editor) {
  width: 100%;
}
.mr4 {
  margin-right: 4px;
}
.el-form {
  height: 100%;
  display: flex;
  justify-content: space-between;
  .left-card {
    width: 875px;
    padding-top: 125px;
    overflow-y: auto;
    .admins {
      .avatar-box {
        display: flex;
        align-items: center;
        margin-right: 6px;
      }
      img {
        width: 16px;
        height: 16px;
        border-radius: 50%;
      }
    }
    .flex-box {
      height: 32px;
      line-height: 32px;
    }
    .content {
      margin: 20px;
    }
    .el-row {
      margin-right: 20px;
    }
  }
  .line-short {
    width: 1px;
    background: rgba(204, 204, 204, 0.3);
  }
  .right-card {
    width: 245px;
    padding-top: 125px;
    margin-right: 20px;
    border-left: 1px solid #f2f6fc;
  }
}
.between {
  justify-content: space-between;
}

.between-header {
  justify-content: space-between;
  cursor: pointer;
  .iconfont {
    font-size: 20px;
  }
  .ident {
    font-size: 12px;
    color: #606266;
    background: #f5f5f5;
    margin-right: 20px;
    border-radius: 2px;
    padding: 3px 5px;
  }
  .icontiaojian {
    margin-right: 20px;
  }
}
.header {
  width: 100%;
  padding: 20px;
  margin-right: 20px;
  border-bottom: 1px solid #f2f6fc;
  .title {
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    :deep(.el-input__inner) {
      width: 1080px;
    }
    .name {
      font-size: 24px;
      font-weight: 600;
      color: rgba(0, 0, 0, 0.85);
    }
    i {
      font-size: 18px;
      margin-left: 12px;
    }
  }
  .creator {
    font-size: 14px;
    color: #909399;
  }
}
.align-center {
  align-items: center;
}
.circle {
  display: inline-block;
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #1890ff;
  margin-right: 5px;
}
.danger {
  background: #ed4014;
}
.warning {
  background: #ff9900;
}
.info {
  background: #c0c4cc;
}
.default {
  background: #1890ff;
}
.success {
  background: #19be6b;
}
.ueditorBtn {
  margin: 20px 0;
}
.describe {
  font-size: 14px;
  color: #909399;
}
.describe-box {
  margin: 10px 0 26px;
  :deep(table) {
    width: 100% !important;
    border: 1px solid #ccc;
  }

  :deep(table th) {
    border: 1px solid #ccc;
  }
  :deep(table td) {
    padding: 10px 5px;
    border: 1px solid #ccc;
  }

  :deep(p img) {
    max-width: 800px;
  }
}
:deep(.el-form-item__label) {
  color: #909399;
  padding: 0 !important;
}
.comment {
  :deep(.el-tabs__active-bar.is-top) {
    margin-left: 20px;
  }
  :deep(.el-tabs__item.is-top:nth-child(2)) {
    margin-left: 20px;
  }
}
:deep(.el-form-item) {
  margin-bottom: 0;
}
:deep(.el-table__header-wrapper) {
  display: none;
}
.expand-describe {
  background: #f9f9f9;
  margin: 0 20px;
  padding: 6px 20px 10px;
  font-size: 14px;
  border-radius: 4px;

  .expand-left {
    width: 50%;
    align-items: flex-start;
    :deep(table) {
      border: 1px solid #ccc;
    }

    :deep(table th) {
      border: 1px solid #ccc;
    }
    :deep(table td) {
      padding: 10px 5px;
      border: 1px solid #ccc;
    }
  }
  .expand-right {
    width: 50%;
    margin-left: 20px;
    align-items: flex-start;
    :deep(table) {
      border: 1px solid #ccc;
    }

    :deep(table th) {
      border: 1px solid #ccc;
    }
    :deep(table td) {
      padding: 10px 5px;
      border: 1px solid #ccc;
    }
  }
  span {
    color: #909399;
    min-width: 60px;
    display: inline-block;
  }
  p {
    color: #606266;
    line-height: 16px;
    margin-right: 36px;
    margin-bottom: 0;
    :deep(p) {
      font-size: 14px;
      margin: 0;
    }
  }
}
.p-l-12 {
  padding-left: 12px;
}
.bg {
  &:hover {
    background: #f7f7f7;
    border-radius: 4px;
  }
}
.set-version {
  width: 80%;
  display: inline-block;
}
.settings {
  // margin-left: 16px;
  color: #1890ff;
}
.input-row {
  display: flex;
  align-items: center;
  margin-bottom: 20px;
  .icontuodong {
    color: #dcdfe6;
    margin-right: 4px;
  }
  span {
    font-size: 16px;
    padding: 0px 5px;
    border-radius: 50%;
    background: #ed4014;
    color: #fff;
    line-height: 15px;
  }
}
.add-input {
  color: #1890ff;
}

.input-row .el-input {
  flex-grow: 1;
  margin-right: 10px;
}
:deep(.icon-no .el-table__expand-icon) {
  display: none;
}
:deep(.el-tabs__nav-wrap::after) {
  background-color: #f2f6fc;
}
:deep(.el-tabs__nav-wrap) {
  height: 54px;
  border-top: 1px solid #f2f6fc;
  line-height: 54px;
}
:deep(.w-e-content-preview),
.w-e-text {
  padding: 0;
}
:deep(.w-e-text p) {
  margin: 0;
}
.placeholder {
  padding: 0;
}
:deep(.w-e-text-container) {
  padding-top: 10px;
}
.created-time {
  color: #909399;
  font-weight: 400;
  margin-left: 20px;
}
.members-box {
  margin-right: 6px;
  display: flex;
  align-items: center;

  img {
    width: 16px;
    height: 16px;
    border-radius: 50%;
  }
}
.members-box-content {
  margin: 0 3px 3px 0;
}
.contents {
  height: 20px;
  line-height: 22px;
  color: #909399;
}
:deep(.el-popover__reference) {
  display: flex;
  align-items: center;
}
.program-stop {
  font-size: 12px;
  border: 1px solid #f90;
  color: #f90;
  padding: 0 2px;
}
.program-close {
  font-size: 12px;
  border: 1px solid #909399;
  color: #909399;
  padding: 0 2px;
}
.icongengduo2 {
  font-size: 30px !important;
  color: #909399;
  font-weight: 400;
}
</style>
<style>
.tooltip-width {
  max-width: 1000px;
}
.el-cascader-node__label {
  max-width: 200px;
}
</style>

<style lang="scss">
// 抽屉挂到 body 上，scoped 样式选不到，按 class 定位
.program-task-edit-task-drawer .el-drawer__body {
  padding: 0 !important;
}
</style>
