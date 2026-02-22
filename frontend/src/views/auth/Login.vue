<template>
    <div class="login-card card">
        <h2>Login</h2>
        <form @submit.prevent="handleLogin">
            <div class="form-group">
                <label>Email</label>
                <input type="email" v-model="email" required />
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" v-model="password" required />
            </div>
            <div v-if="authStore.error" class="error">{{ authStore.error }}</div>
            <button type="submit" class="btn btn-primary" :disabled="authStore.loading">
                {{ authStore.loading ? 'Logging in...' : 'Login' }}
            </button>
        </form>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useRouter } from 'vue-router';

const email = ref('');
const password = ref('');
const authStore = useAuthStore();
const router = useRouter();

const handleLogin = async () => {
    const success = await authStore.login(email.value, password.value);
    if (success) {
        router.push('/');
    }
};
</script>

<style scoped>
.login-card {
    width: 100%;
    max-width: 400px;
}

.form-group {
    margin-bottom: 1rem;
}

label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text-secondary);
}

.error {
    color: var(--danger);
    margin-bottom: 1rem;
}

button {
    width: 100%;
}
</style>
