<script setup>
import { computed } from 'vue'
import { SideBar, HeaderBar, PageContainer } from '@/components/layout'
import ActionFeedback from '@/components/ui/ActionFeedback/index.vue'
import { useWorkspace } from '@/composables/useWorkspace'
import DashboardPage from '@/views/dashboard/DashboardPage.vue'
import OpportunityListPage from '@/views/opportunities/OpportunityListPage.vue'
import OpportunityShowPage from '@/views/opportunities/OpportunityShowPage.vue'
import CompanyDocumentListPage from '@/views/documents/CompanyDocumentListPage.vue'
import SearchProfileListPage from '@/views/profiles/SearchProfileListPage.vue'
const { user, dashboard, nav, title, view, busy, error, message, detail, go, signOut } =
    useWorkspace()
const pages = {
    dashboard: DashboardPage,
    opportunities: OpportunityListPage,
    documents: CompanyDocumentListPage,
    profiles: SearchProfileListPage,
    detail: OpportunityShowPage,
}
const page = computed(() => (view.value === 'detail' && !detail.value ? null : pages[view.value]))
</script>
<template>
    <div class="app-shell">
        <SideBar
            :user="user"
            :dashboard="dashboard"
            :nav="nav"
            :view="view"
            :busy="busy"
            @navigate="go"
            @logout="signOut"
        />
        <div class="main-shell">
            <HeaderBar :user="user" :title="title" /><PageContainer>
                <ActionFeedback
                    :error="error"
                    :message="message"
                    :busy="busy"
                    @clear-error="error = ''"
                    @clear-message="message = ''"
                />
                <component :is="page" v-if="page" />
            </PageContainer>
        </div>
    </div>
</template>
