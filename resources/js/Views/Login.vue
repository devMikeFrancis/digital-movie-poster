<template>
    <div class="min-h-screen flex items-center justify-center px-4" style="background-color: #121212">
        <div class="w-full max-w-md">
            <h1 class="font-bold text-xl text-white flex items-center justify-center mb-8">
                <img src="/favicon-96x96.png" width="52" height="52" alt="DMP" class="mr-3" />
                Digital Movie Poster
            </h1>

            <div class="p-6 rounded-lg" style="background-color: #1c1c1c">
                <h2 class="text-lg font-semibold text-white mb-1">
                    {{ isSetup ? 'Create your admin account' : 'Sign in' }}
                </h2>
                <p class="text-sm text-gray-400 mb-6">
                    <template v-if="isSetup">
                        This device does not have an account yet. The first one you create
                        becomes the administrator.
                    </template>
                    <template v-else>Sign in to manage posters and settings.</template>
                </p>

                <form @submit.prevent="submit">
                    <div v-if="isSetup" class="mb-4">
                        <label class="block text-sm text-gray-300 mb-1" for="name">Name</label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            autocomplete="name"
                            class="w-full rounded"
                            required
                        />
                        <p v-if="errorFor('name')" class="text-red-400 text-sm mt-1">
                            {{ errorFor('name') }}
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm text-gray-300 mb-1" for="email">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="username"
                            class="w-full rounded"
                            required
                        />
                        <p v-if="errorFor('email')" class="text-red-400 text-sm mt-1">
                            {{ errorFor('email') }}
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm text-gray-300 mb-1" for="password">
                            Password
                        </label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            :autocomplete="isSetup ? 'new-password' : 'current-password'"
                            class="w-full rounded"
                            required
                        />
                        <p v-if="errorFor('password')" class="text-red-400 text-sm mt-1">
                            {{ errorFor('password') }}
                        </p>
                    </div>

                    <div v-if="isSetup" class="mb-4">
                        <label class="block text-sm text-gray-300 mb-1" for="password_confirmation">
                            Confirm password
                        </label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="w-full rounded"
                            required
                        />
                    </div>

                    <p v-if="generalError" class="text-red-400 text-sm mb-4">{{ generalError }}</p>

                    <button
                        type="submit"
                        :disabled="busy"
                        class="w-full py-2 px-4 text-white font-semibold disabled:opacity-60"
                        style="background-color: #2563eb"
                    >
                        {{ busy ? 'Please wait…' : isSetup ? 'Create account' : 'Sign in' }}
                    </button>
                </form>
            </div>

            <p class="text-center text-gray-500 text-sm mt-6">
                <a href="/" class="hover:text-gray-300">Back to the display</a>
            </p>
        </div>
    </div>
</template>

<script>
import { mapState } from 'pinia';
import { useAuthStore } from '@/store/auth';

export default {
    name: 'Login',
    data() {
        return {
            form: { name: '', email: '', password: '', password_confirmation: '' },
            errors: {},
            generalError: '',
            busy: false,
        };
    },
    computed: {
        ...mapState(useAuthStore, ['needsSetup']),
        isSetup() {
            return this.needsSetup;
        },
    },
    methods: {
        errorFor(field) {
            const messages = this.errors[field];
            return Array.isArray(messages) ? messages[0] : messages;
        },
        async submit() {
            this.busy = true;
            this.errors = {};
            this.generalError = '';

            const auth = useAuthStore();

            try {
                if (this.isSetup) {
                    await auth.setup(this.form);
                } else {
                    await auth.login({ email: this.form.email, password: this.form.password });
                }

                const redirect = this.$route.query.redirect || '/posters';
                this.$router.replace(redirect);
            } catch (e) {
                const response = e.response;

                if (response && response.status === 422) {
                    this.errors = response.data.errors || {};
                } else if (response && response.status === 429) {
                    this.generalError = 'Too many attempts. Wait a minute and try again.';
                } else if (response && response.data && response.data.message) {
                    this.generalError = response.data.message;
                } else {
                    this.generalError = 'Something went wrong. Please try again.';
                }
            } finally {
                this.busy = false;
            }
        },
    },
    async created() {
        await useAuthStore().loadStatus(true);
    },
};
</script>
