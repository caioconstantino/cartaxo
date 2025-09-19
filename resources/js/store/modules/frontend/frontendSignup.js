import axios from "axios";

export const frontendSignup = {
  namespaced: true,
  state: {
    phone: {},
    email: {},
    formData: {},
  },
  getters: {
    phone: function (state) {
      return state.phone;
    },
    email: function (state) {
      return state.phone;
    },
    formData: function (state) {
      return state.formData;
    },
  },
  actions: {
    otpPhone: function (context, payload) {
      return new Promise((resolve, reject) => {
        let url = "auth/signup/otp-phone";
        axios
          .post(url, payload)
          .then((res) => {
            context.commit("phone", payload);
            resolve(res);
          })
          .catch((err) => {
            reject(err);
          });
      });
    },
    otpEmail: function (context, payload) {
      return new Promise((resolve, reject) => {
        let url = "auth/signup/otp-email";
        axios
          .post(url, payload)
          .then((res) => {
            context.commit("email", payload);
            resolve(res);
          })
          .catch((err) => {
            reject(err);
          });
      });
    },
    signupValidation: function (context, payload) {
      // garante só números no cpfcnpj
      if (payload.cpfcnpj) {
        payload.cpfcnpj = payload.cpfcnpj.replace(/\D/g, "");
      }

      let url = "auth/signup/register-validation";
      return new Promise((resolve, reject) => {
        axios
          .post(url, payload)
          .then((res) => {
            context.commit("formData", payload);
            context.commit("phone", payload);
            context.commit("email", payload);
            resolve(res);
          })
          .catch(reject);
      });
    },
    signup: function (context, payload) {
      // garante só números no cpfcnpj
      if (payload.cpfcnpj) {
        payload.cpfcnpj = payload.cpfcnpj.replace(/\D/g, "");
      }

      let url = "auth/signup/register";
      return new Promise((resolve, reject) => {
        axios
          .post(url, payload)
          .then((res) => {
            context.commit("phone", payload);
            context.commit("email", payload);
            resolve(res);
          })
          .catch(reject);
      });
    },

    reset: function (context) {
      context.commit("reset");
    },
  },
  mutations: {
    phone: function (state, payload) {
      state.phone.otp = payload;
    },
    email: function (state, payload) {
      state.email.otp = payload;
    },
    formData: function (state, payload) {
      state.formData = payload;
    },
    verify: function (state, payload) {
      state.phone.status = payload;
    },
    reset: function (state) {
      state.phone = {};
      state.email = {};
      state.formData = {};
    },
  },
};
