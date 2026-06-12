<template>
    <div class="activity-log-list" :class="{ compact }">
        <div v-if="loading" class="empty-message">
            {{ $t('agreement.activityLog.loading') }}
        </div>
        <div v-else-if="error" class="empty-message error">
            {{ $t('agreement.activityLog.loadFailed') }}
        </div>
        <div v-else-if="logs.length === 0" class="empty-message">
            {{ $t('agreement.activityLog.empty') }}
        </div>
        <ul v-else class="timeline">
            <li v-for="log in logs" :key="log.id" class="timeline-item">
                <ActivityLogEntry :log="log" :compact="compact" />
            </li>
        </ul>
    </div>
</template>

<script>
import ActivityLogEntry from './ActivityLogEntry.vue';

export default {
    name: 'ActivityLogList',

    components: { ActivityLogEntry },

    props: {
        fetcher: {
            type: Function,
            required: true,
        },
        loadOnMount: {
            type: Boolean,
            default: false,
        },
        compact: {
            type: Boolean,
            default: false,
        },
    },

    data() {
        return {
            logs: [],
            loading: false,
            error: false,
            hasLoaded: false,
        };
    },

    mounted() {
        if (this.loadOnMount) {
            this.load();
        }
    },

    methods: {
        async load() {
            this.loading = true;
            this.error = false;
            try {
                const { data } = await this.fetcher();
                this.logs = data.items ?? [];
                this.hasLoaded = true;
            } catch (e) {
                this.error = true;
                this.logs = [];
            } finally {
                this.loading = false;
            }
        },
        loadIfNeeded() {
            if (!this.hasLoaded) {
                this.load();
            }
        },
    },
};
</script>

<style lang="scss" scoped>
.empty-message {
    color: #6c757d;
    font-size: 0.9rem;
    padding: 1rem;
    text-align: center;
    background: #fff;
    border-radius: 6px;
    border: 1px dashed #dee2e6;

    &.error {
        color: #842029;
        border-color: #f5c2c7;
        background: #f8d7da;
    }
}

.timeline {
    list-style: none;
    margin: 0;
    padding: 0;
    position: relative;

    // Vertical axis line; left position = dates column width (130) + gap (12) + half of dot column (12) ≈ 154px
    &::before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        left: 154px;
        width: 1px;
        background: var(--colorPrimary);
    }
}

.timeline-item {
    position: relative;
}

.activity-log-list.compact {
    // Timeline axis stays visible in compact mode, but moves left
    // because the dates column is removed (dot column is the first column now).
    .timeline::before {
        left: 12px; // half of 24px dot column = dot center
    }
}

@media (max-width: 768px) {
    .timeline::before {
        display: none;
    }

    .timeline-item + .timeline-item {
        border-top: 1px solid var(--colorGrayLight80);
        margin-top: 0.5rem;
        padding-top: 0.5rem;
    }
}
</style>
