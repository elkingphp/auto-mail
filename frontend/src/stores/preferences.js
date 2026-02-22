import { defineStore } from 'pinia';
import i18n from '../i18n';

export const usePreferencesStore = defineStore('preferences', {
    state: () => ({
        locale: localStorage.getItem('locale') || 'ar',
        theme: localStorage.getItem('theme') || 'dark', // dark, light, glass
    }),
    actions: {
        setLocale(locale) {
            this.locale = locale;
            localStorage.setItem('locale', locale);
            if (i18n.global?.locale) {
                if (i18n.global.locale.value !== undefined) {
                    i18n.global.locale.value = locale;
                } else {
                    i18n.global.locale = locale;
                }
            }
            document.documentElement.dir = locale === 'ar' ? 'rtl' : 'ltr';
            document.documentElement.lang = locale;
        },
        setTheme(theme) {
            this.theme = theme;
            localStorage.setItem('theme', theme);
            this.applyTheme();
        },
        applyTheme() {
            const html = document.documentElement;
            html.classList.remove('light', 'dark', 'glass-mode');

            if (this.theme === 'light') {
                html.classList.add('light');
                html.style.colorScheme = 'light';
            } else if (this.theme === 'glass') {
                html.classList.add('dark', 'glass-mode');
                html.style.colorScheme = 'dark';
            } else {
                html.classList.add('dark');
                html.style.colorScheme = 'dark';
            }
        },
        init() {
            // Set initial direction
            document.documentElement.dir = this.locale === 'ar' ? 'rtl' : 'ltr';
            document.documentElement.lang = this.locale;
            this.applyTheme();
        }
    },
});
