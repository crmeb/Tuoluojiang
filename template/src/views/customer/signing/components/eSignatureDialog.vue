<template>
    <div>
        <oaDialog ref="oaDialog" :fromData="fromData" :isFooter="false" @submit="submit">
            <div class="mb14">
            
            <div v-for="(item,index) in signatory" :key="index">
            <div class="signer-info" v-if="item.types == 0">
                <span class="status" @click="signCodeFn(item)" v-if="item.sign_status == 0">去签约</span>
                <span class="status success" v-if="item.sign_status == 1">已签约</span>
                <span class="status gray" v-if="item.sign_status == 2">已拒绝</span>
                <div class="signer-name">{{ item.company_name||'--' }}
                    <span class="company-icon">本企业</span>
                </div>
                <div class="signer-name mt6">
                    <span class="mr25">经办人：{{ item.name||'--' }}</span>
                    <span>手机号：{{ item.phone||'--' }}</span>
                </div>
            </div>
            <div v-else class="signer-info mt10">
                <span class="status"  v-if="item.sign_status == 0" @click="signCodeFn(item)">邀请签约</span>
                <span class="status success" v-if="item.sign_status == 1">已签约</span>
                <span class="status gray" v-if="item.sign_status == 2">已拒绝</span>
                <div class="signer-name">{{ item.name||'--' }}
                    <span class="company-icon individual">个人</span>
                </div>
                <div class="signer-name mt6">
                    <span>手机号：{{ item.phone||'--' }}</span>
                </div>
            </div>
            <div>

            </div>
            </div>
            </div>
        </oaDialog>
        <!-- 签署文件 -->
         <signCode ref="signCode"></signCode>
    </div>
</template>
<script>
export default {
    name: 'eSignatureDialog',
    components: {
        oaDialog: () => import('@/components/form-common/dialog-form'),
        signCode: () => import('./signCode.vue'),
    },
    data() {
        return {
            fromData: {
                width: '500px',
                title: '电子签',
                btnText: '确定',
                labelWidth: '100px',
                type: 'slot'
            },
            signatory:[],
        }
    },
    methods: {
        openBox(row) {
this.signatory = row
            this.$refs.oaDialog.openBox()
        },
        // 去签约
        signCodeFn(item) {
            this.$refs.signCode.openBox(item)
        },

        submit() { },
    }
}
</script>
<style scoped lang="scss">
.signer-info {
    width: 100%;
    // height: 67px;
    background: #F7F7F7;
    border-radius: 8px;
    padding: 20px;
    font-family: PingFang SC, PingFang SC;
    font-weight: 400;
    position: relative;

    .status {
        cursor: pointer;
        display: flex;
        position: absolute;
        top: 27px;
        right: 20px;
        height: 19px;
        width: 76px;
        height: 32px;
        background: #1890FF;
        border-radius: 4px;
        justify-content: center;
        align-items: center;
        font-size: 13px;
        color: #FFFFFF;
    }
    .success {
        background-color: #CCCCCC;
    }


    .gray {
        cursor: not-allowed;
        background-color: red;
    }

    .signer-name {

        font-size: 13px;
        height: 18px;
        line-height: 18px;
        color: #303133;

    }

    .company-icon {
        display: inline-block;
        padding: 2px 4px;
        background: rgba(24, 144, 255, 0.08);
        border-radius: 4px;
        font-size: 11px;
        color: #1890FF;
        margin-left: 2px;
    }

    .individual {
        color: #FF9900;
        font-size: 11px;
        background-color: rgba(255, 153, 0, 0.08);
    }

    .mt6 {
        margin-top: 10px;
    }

    .mr25 {
        margin-right: 25px;
    }
}

</style>
