<template>
    <div v-if="isOpen" class="modal-overlay" @click.self="close">
        <div class="modal">
            <header class="modal-header">
                <h3>{{ title }}</h3>
                <button class="close-btn" @click="close">&times;</button>
            </header>
            <div class="modal-body">
                <slot></slot>
            </div>
            <footer class="modal-footer">
                <slot name="footer">
                    <button class="btn" @click="close">Cancel</button>
                </slot>
            </footer>
        </div>
    </div>
</template>

<script setup>
defineProps({
    isOpen: Boolean,
    title: String
});
const emit = defineEmits(['close']);
const close = () => emit('close');
</script>

<style scoped>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal {
    background: var(--bg-secondary);
    border-radius: 8px;
    width: 500px;
    max-width: 95%;
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.modal-header {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: var(--text-secondary);
    cursor: pointer;
}

.modal-body {
    padding: 1rem;
}

.modal-footer {
    padding: 1rem;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}
</style>
