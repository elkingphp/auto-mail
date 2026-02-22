<template>
    <div class="stat-card" :class="color">
        <div class="stat-icon">{{ icon }}</div>
        <div class="stat-info">
            <div class="stat-label">{{ label }}</div>
            <div class="stat-value">{{ value }}</div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    label: String,
    value: [String, Number],
    icon: String,
    color: {
        type: String,
        default: 'blue'
    }
});
</script>

<style scoped>
.stat-card {
    background-color: var(--bg-dark-soft);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 20px;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background-color: var(--bg-dark-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-label {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin-bottom: 4px;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
}

/* Colors */
.blue .stat-icon {
    color: var(--accent-blue);
    background-color: rgba(59, 130, 246, 0.1);
}

.green .stat-icon {
    color: var(--accent-green);
    background-color: rgba(34, 197, 94, 0.1);
}

.red .stat-icon {
    color: var(--accent-red);
    background-color: rgba(239, 68, 68, 0.1);
}

.yellow .stat-icon {
    color: var(--accent-yellow);
    background-color: rgba(234, 179, 8, 0.1);
}
</style>
