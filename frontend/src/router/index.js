import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/login',
            name: 'login',
            component: () => import('../modules/auth/LoginView.vue'),
            meta: { guest: true, title: 'Login | RBDB' }
        },
        {
            path: '/dl/:id',
            name: 'download.report',
            component: () => import('../modules/reports/ReportDownloadOtp.vue'),
            meta: { guest: true, title: 'Secure Assets | RBDB' }
        },
        {
            path: '/',
            component: () => import('../layouts/DashboardLayout.vue'),
            meta: { requiresAuth: true },
            children: [
                {
                    path: '',
                    redirect: '/dashboard'
                },
                {
                    path: 'dashboard',
                    name: 'dashboard',
                    component: () => import('../modules/dashboard/DashboardView.vue'),
                    meta: { title: 'Dashboard' }
                },
                {
                    path: 'services',
                    name: 'services',
                    component: () => import('../modules/services/ServicesList.vue'),
                    meta: { title: 'Services' }
                },
                {
                    path: 'data-sources',
                    name: 'data-sources',
                    component: () => import('../modules/data-sources/DataSourceList.vue'),
                    meta: { title: 'Data Sources' }
                },
                {
                    path: 'reports',
                    name: 'reports',
                    component: () => import('../modules/reports/ReportsList.vue'),
                    meta: { title: 'Reports' }
                },
                {
                    path: 'reports/create',
                    name: 'reports.create',
                    component: () => import('../modules/reports/ReportTypeSelector.vue'),
                    meta: { title: 'Choose Archetype' }
                },
                {
                    path: 'reports/create/sql-native',
                    name: 'reports.create.sql',
                    component: () => import('../modules/reports/CreateSqlNativeReport.vue'),
                    meta: { title: 'Initialize SQL Asset' }
                },
                {
                    path: 'reports/create/query-builder',
                    name: 'reports.create.visual',
                    component: () => import('../modules/reports/CreateVisualReport.vue'),
                    meta: { title: 'Initialize Visual Archetype' }
                },
                {
                    path: 'reports/:id/edit',
                    name: 'reports.edit',
                    component: () => import('../modules/reports/ReportEdit.vue'),
                    meta: { title: 'Asset Modification' }
                },
                {
                    path: 'executions',
                    name: 'executions',
                    component: () => import('../modules/executions/ExecutionsList.vue'),
                    meta: { title: 'Executions' }
                },
                {
                    path: 'schedules',
                    name: 'schedules',
                    component: () => import('../modules/schedules/SchedulesList.vue'),
                    meta: { title: 'Schedules' }
                },
                {
                    path: 'delivery-targets',
                    name: 'delivery-targets',
                    component: () => import('../modules/delivery-targets/DeliveryTargetsList.vue'),
                    meta: { title: 'Delivery Targets' }
                },
                {
                    path: 'email-servers',
                    name: 'email-servers',
                    component: () => import('../modules/email-servers/EmailServersList.vue'),
                    meta: { title: 'Email Gateways' }
                },
                {
                    path: 'email-servers/:id',
                    name: 'email-servers.details',
                    component: () => import('../modules/email-servers/EmailServerDetails.vue'),
                    meta: { title: 'Gateway Analytics' }
                },
                {
                    path: 'ftp-servers',
                    name: 'ftp-servers',
                    component: () => import('../modules/ftp-servers/FtpServersList.vue'),
                    meta: { title: 'FTP Nodes' }
                },
                {
                    path: 'ftp-servers/:id',
                    name: 'ftp-servers.details',
                    component: () => import('../modules/ftp-servers/FtpServerDetails.vue'),
                    meta: { title: 'FTP Node Analytics' }
                },
                {
                    path: 'email-templates',
                    name: 'email-templates',
                    component: () => import('../modules/email-templates/EmailTemplatesList.vue'),
                    meta: { title: 'Message Templates' }
                },
                {
                    path: 'email-templates/:id',
                    name: 'email-templates.details',
                    component: () => import('../modules/email-templates/EmailTemplateDetails.vue'),
                    meta: { title: 'Template Analytics' }
                },
                {
                    path: 'users',
                    name: 'users',
                    component: () => import('../modules/users/UsersList.vue'),
                    meta: { title: 'User Management', role: 'Admin' }
                }
            ]
        }
    ]
});

router.beforeEach(async (to, from, next) => {
    const auth = useAuthStore();

    // Set page title
    if (to.meta.title) {
        document.title = to.meta.title;
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        next('/login');
    } else if (to.meta.guest && auth.isAuthenticated && to.name === 'login') {
        next('/dashboard');
    } else {
        // If authenticated but no user info, fetch it
        if (auth.isAuthenticated && !auth.user) {
            await auth.fetchMe();
        }

        // Check roll permission
        if (to.meta.role && auth.user?.role?.name !== to.meta.role) {
            next('/dashboard');
            return;
        }

        next();
    }
});

export default router;
