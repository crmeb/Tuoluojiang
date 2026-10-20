<template>
  <div>
    <el-dialog :before-close="handleClose" :title="fromData.title" v-model="dialogVisible" width="680px">
      <div class="body">
        <div class="content">
          <ul class="content-title">
            <li>
              <p class="text-left">空间成员</p>
              <p>
                <el-checkbox v-model="lookCheck" @change="handleChange(1)">查看</el-checkbox>
              </p>
              <p>
                <el-checkbox v-model="createCheck" @change="handleChange(2)">创建</el-checkbox>
              </p>
              <p>
                <el-checkbox v-model="editCheck" @change="handleChange(3)">编辑</el-checkbox>
              </p>
              <p>
                <el-checkbox v-model="downloadCheck" @change="handleChange(4)">下载</el-checkbox>
              </p>
              <p>
                <el-checkbox v-model="deleteCheck" @change="handleChange(5)">删除</el-checkbox>
              </p>
            </li>
          </ul>
          <ul class="content-body">
            <li v-for="(item, index) in userList" :key="index">
              <p class="username">
                <img :src="item.card.avatar" alt="" />
                <span>{{ item.card.name }}</span>
              </p>

              <p>
                <el-checkbox v-model="item.lookCheck" :label="item.lookCheck" :value="item.lookCheck" :disabled="item.auth.is_admin == 1" />
              </p>
              <p>
                <el-checkbox v-model="item.createCheck" :label="item.createCheck" :value="item.createCheck" :disabled="item.auth.is_admin == 1" />
              </p>
              <p>
                <el-checkbox v-model="item.editCheck" :label="item.editCheck" :value="item.editCheck" :disabled="item.auth.is_admin == 1" />
              </p>
              <p>
                <el-checkbox
                  v-model="item.downloadCheck"
                  :label="item.downloadCheck" :value="item.downloadCheck"
                  :disabled="item.auth.is_admin == 1"
                />
              </p>
              <p>
                <el-checkbox v-model="item.deleteCheck" :label="item.deleteCheck" :value="item.deleteCheck" :disabled="item.auth.is_admin == 1" />
              </p>
            </li>
          </ul>
        </div>
      </div>
      <template #footer>
        <div class="dialog-footer text-right">
          <el-button @click="handleClose">取消</el-button>
          <el-button :loading="loading" type="primary" @click="handleAdd">确定</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { folderSpaceSubRuleApi, folderSubRuleApi } from '@/api/cloud.ts'
export default {
  name: 'SpaceDialog',
  props: {
    fromData: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  data() {
    return {
      dialogVisible: false,
      lookCheck: true,
      downloadCheck: false,
      editCheck: false,
      createCheck: false,
      deleteCheck: false,
      dialogData: [],
      userList: [],
      loading: false
    }
  },
  watch: {
    fromData: {
      handler(nVal) {
        this.getFolderShareRule()
      },
      deep: true
    }
  },
  methods: {
    handleClose() {
      this.userList = []
      this.resetCheck()
      this.dialogVisible = false
    },
    handleOpen() {
      this.dialogVisible = true
    },
    handleAdd() {
      if (this.userList.length <= 0) {
        this.$message.error('请添加分享成员')
      } else {
        var list = []
        var data = {}
        this.userList.forEach((value) => {
          list.push({
            uid: value.card.uid,
            read: value.lookCheck ? 1 : 0,
            create: value.createCheck ? 1 : 0,
            download: value.downloadCheck ? 1 : 0,
            update: value.editCheck ? 1 : 0,
            delete: value.deleteCheck ? 1 : 0
          })
        })
        data = {
          rule: list
        }
        if (this.fromData.edit === 1) {
          this.getFolderShare(this.fromData.fid, this.fromData.id, data)
        }
      }
    },
    handleChange(type) {
      if (type === 1) {
        this.lookCheck ? this.checkAll('lookCheck') : this.checkNone('lookCheck')
      } else if (type === 2) {
        this.createCheck ? this.checkAll('createCheck') : this.checkNone('createCheck')
      } else if (type === 3) {
        this.editCheck ? this.checkAll('editCheck') : this.checkNone('editCheck')
      } else if (type === 4) {
        this.downloadCheck ? this.checkAll('downloadCheck') : this.checkNone('downloadCheck')
      } else {
        this.deleteCheck ? this.checkAll('deleteCheck') : this.checkNone('deleteCheck')
      }
    },
    // 全选
    checkAll(items) {
      this.userList.forEach((item) => {
        item[items] = true
      })
    },
    // 反选
    checkNone(items) {
      this.userList.forEach((item) => {
        item[items] = false
      })
    },
    resetCheck() {
      this.lookCheck = false
      this.downloadCheck = false
      this.editCheck = false
      this.createCheck = false
      this.deleteCheck = false
    },
    deleteShare(index) {
      this.userList.splice(index, 1)
      if (this.userList.length <= 0) {
        this.resetCheck()
      }
    },
    // 添加
    getFolderShare(fid, id, data) {
      this.loading = true
      folderSubRuleApi(fid, id, data)
        .then((res) => {
          this.loading = false
          this.handleClose()
        })
        .catch((error) => {
          this.loading = false
        })
    },
    // 权限列表
    getFolderShareRule() {
      folderSpaceSubRuleApi(this.fromData.fid, this.fromData.id).then((res) => {
        this.userList = []
        if (res.data.length > 0) {
          res.data.forEach((value) => {
            var data = {
              card: value.user,
              id: value.to_uid,
              auth: value.auth
            }
            if (value.auth) {
              data.lookCheck = value.auth.read === 1
              data.createCheck = value.auth.create === 1
              data.downloadCheck = value.auth.download === 1
              data.editCheck = value.auth.update === 1
              data.deleteCheck = value.auth.delete === 1
              this.lookCheck = value.auth.read === 1
              this.createCheck = value.auth.read === 1
              this.downloadCheck = value.auth.download === 1
              this.editCheck = value.auth.update === 1
              this.deleteCheck = value.auth.delete === 1
            } else {
              data.lookCheck = false
              data.createCheck = false
              data.downloadCheck = false
              data.editCheck = false
              data.deleteCheck = false
              this.lookCheck = false
              this.createCheck = false
              this.downloadCheck = false
              this.editCheck = false
              this.deleteCheck = false
            }
            this.userList.push(data)
          })
        }
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.body {
  .add-button {
    border-bottom: 1px solid #d8d8d8;
  }
  .content {
    ul,
    p {
      margin: 0;
      padding: 0;
    }
    ul {
      list-style: none;
      li {
        padding: 10px 0;
        display: flex;
        justify-items: center;
        align-items: center;
        text-align: center;
        p {
          width: calc(100% / 6);
        }
      }
    }
    .text-left {
      justify-content: left !important;
    }
    .content-title p {
      display: flex;
      text-align: right;
      flex-direction: row;
      justify-content: flex-end;
      margin-right: 8px;
    }
    .content-body {
      :deep(.el-checkbox__label) {
        display: none;
      }
      .username {
        text-align: left;
        display: flex;
        align-items: center;
        img {
          height: 24px;
          width: 24px;
          border-radius: 50%;
        }
        span {
          padding-left: 6px;
        }
      }
    }
  }
}
</style>
