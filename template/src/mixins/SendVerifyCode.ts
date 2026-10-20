import { defineComponent } from 'vue';

export default defineComponent({
  data() {
    return {
      disabled: false,
      text: '验证码',
      _codeTimer: null as ReturnType<typeof setInterval> | null,
    };
  },
  beforeUnmount() {
    clearInterval(this._codeTimer ?? undefined);
  },
  methods: {
    sendCode() {
      if (this.disabled) return;
      this.disabled = true;
      let n = 60;

      this.text = '剩余 ' + n + 's';
      clearInterval(this._codeTimer ?? undefined);
      this._codeTimer = setInterval(() => {
        n = n - 1;
        if (n < 0) {
          clearInterval(this._codeTimer ?? undefined);
        }
        this.text = '剩余 ' + n + 's';
        if (this.text < '剩余 ' + 0 + 's') {
          this.disabled = false;
          this.text = '重新获取';
        }
      }, 1000);
    },
  },
});
