import LoginComponent from "../../components/frontend/auth/LoginComponent";
import SignupComponent from "../../components/frontend/auth/SignupComponent.vue";
import ForgotPasswordComponent from "../../components/frontend/auth/ForgotPasswordComponent";
import ForgotPasswordVerifyComponent from "../../components/frontend/auth/ForgotPasswordVerifyComponent";
import ResetPasswordComponent from "../../components/frontend/auth/ResetPasswordComponent";
import SignupVerifyComponent from "../../components/frontend/auth/SignupVerifyComponent";

export default [
    {
        path: '/login',
        component: LoginComponent,
        name: 'auth.login',
        meta: {
            isFrontend: true,
            auth: false
        }
    },
    {
        path: '/signup',
        component: SignupComponent,
        name: 'auth.signup',
        meta: {
            isFrontend: true,
            auth: false
        }
    },
    {
        path: '/signup/verify',
        component: SignupVerifyComponent,
        name: 'auth.signupVerify',
        meta: {
            isFrontend: true,
            auth: false
        }
    },
    {
        path: '/forgot-password',
        component: ForgotPasswordComponent,
        name: 'auth.forgotPassword',
        meta: {
            isFrontend: true,
            auth: false
        }
    },
    {
        path: '/forgot-password/verify',
        name: 'auth.forgotPasswordVerify',
        component: ForgotPasswordVerifyComponent,
        meta: {
            isFrontend: true,
            auth: false
        }
    },
    {
        path: '/forgot-password/reset-password',
        name: 'auth.resetPassword',
        component: ResetPasswordComponent,
        meta: {
            isFrontend: true,
            auth: false
        }
    },

];
