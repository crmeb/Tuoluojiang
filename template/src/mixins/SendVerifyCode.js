export default {
  data() {
    return {
      disabled: false,
      text: this.$t('login.code'),
      _codeTimer: null,
    };
  },
  watch: {
    lang() {
      this.setOptions();
    },
  },
  computed: {
    lang: function () {
      return this.$store.getters.lang;
    },
  },
  beforeDestroy() {
    clearInterval(this._codeTimer);
  },
  methods: {
    setOptions() {
      this.text = this.$t('login.code');
    },
    sendCode() {
      if (this.disabled) return;
      this.disabled = true;
      let n = 60;

      this.text = '剩余 ' + n + 's';
      clearInterval(this._codeTimer);
      this._codeTimer = setInterval(() => {
        n = n - 1;
        if (n < 0) {
          clearInterval(this._codeTimer);
        }
        this.text = '剩余 ' + n + 's';
        if (this.text < '剩余 ' + 0 + 's') {
          this.disabled = false;
          this.text = '重新获取';
        }
      }, 1000);
    },
  },
};
