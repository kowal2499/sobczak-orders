<script>
import { parseYMD } from '@/services/datesService'
import { STATE_WINDOW_DAYS } from '../../services/trends'

// SVG jest rozciągany na szerokość kafla, więc to tylko układ współrzędnych
const WIDTH = 300
const HEIGHT = 36
const PADDING = 2

export default {
    name: 'MetricTrend',
    props: {
        trend: {
            type: Object,
            required: true,
        },
    },
    computed: {
        targetId() {
            return `metric-trend-${this._uid}`
        },
        width: () => WIDTH,
        height: () => HEIGHT,
        fillId() {
            return `metric-trend-fill-${this._uid}`
        },
        // pole pod linią sięga zera, więc skala też musi od niego zaczynać
        bounds() {
            const all = this.trend.values.filter(value => value !== null)
            let min = Math.min(0, ...all)
            let max = Math.max(...all)
            if (min === max) {
                min -= 1
                max += 1
            }
            return { min, max }
        },
        mainPath() {
            return this.path(this.trend.values)
        },
        areaPath() {
            return this.segments(this.trend.values).map(segment => {
                const line = segment.map(({ x, y }) => `${x} ${y}`).join(' L')
                const baseline = this.point(0, 0, 1).y
                return `M${segment[0].x} ${baseline} L${line} L${segment[segment.length - 1].x} ${baseline} Z`
            }).join(' ')
        },
        // kursor wskazuje najbliższy dzień z zapisem, żeby nie trafiać w luki
        hovered() {
            if (this.hoverIndex === null) {
                return null
            }
            const values = this.trend.values
            let best = null
            values.forEach((value, index) => {
                if (value !== null && (best === null || Math.abs(index - this.hoverIndex) < Math.abs(best - this.hoverIndex))) {
                    best = index
                }
            })
            if (best === null) {
                return null
            }
            const { x, y } = this.point(best, values[best], values.length)
            return {
                label: `${this.formatDate(this.trend.dates[best])}: ${this.format(values[best])}`,
                left: `${x / WIDTH * 100}%`,
                top: `${y / HEIGHT * 100}%`,
            }
        },
        lines() {
            const t = this.trend
            if (t.kind === 'state') {
                return [
                    this.$t('dashboard.trend.state', { days: STATE_WINDOW_DAYS }),
                    this.$t('dashboard.trend.change', { value: this.format(t.current - t.start, true) }),
                ]
            }

            return [this.$t('dashboard.trend.flow')]
        },
    },
    methods: {
        onMove(event) {
            const rect = event.currentTarget.getBoundingClientRect()
            const x = (event.clientX - rect.left) / rect.width * WIDTH
            const count = this.trend.values.length
            const index = Math.round((x - PADDING) / (WIDTH - 2 * PADDING) * (count - 1))
            this.hoverIndex = Math.min(count - 1, Math.max(0, index))
        },
        formatDate(ymd) {
            return parseYMD(ymd).toLocaleDateString(this.$i18n.locale === 'en' ? 'en-GB' : 'pl-PL', {
                day: '2-digit',
                month: '2-digit',
            })
        },
        point(index, value, count) {
            const width = this.width
            const height = this.height
            const x = count > 1 ? index / (count - 1) * (width - 2 * PADDING) + PADDING : width / 2
            const { min, max } = this.bounds
            const y = height - PADDING - (value - min) / (max - min) * (height - 2 * PADDING)
            return { x: Math.round(x * 10) / 10, y: Math.round(y * 10) / 10 }
        },
        segments(values) {
            const segments = []
            let current = null
            values.forEach((value, index) => {
                if (value === null) {
                    current = null
                    return
                }
                if (!current) {
                    current = []
                    segments.push(current)
                }
                current.push(this.point(index, value, values.length))
            })
            return segments
        },
        path(values) {
            return this.segments(values)
                .map(segment => `M${segment.map(({ x, y }) => `${x} ${y}`).join(' L')}`)
                .join(' ')
        },
        format(value, signed = false) {
            if (value === null || value === undefined) {
                return '-'
            }
            return new Intl.NumberFormat(this.$i18n.locale === 'en' ? 'en-US' : 'pl-PL', {
                maximumFractionDigits: 2,
                signDisplay: signed ? 'exceptZero' : 'auto',
            }).format(value)
        },
    },
    data: () => ({
        hoverIndex: null,
    }),
}
</script>

<template>
    <span class="metric-trend" @mousemove="onMove" @mouseleave="hoverIndex = null">
        <svg
            :id="targetId"
            width="100%"
            :height="height"
            :viewBox="`0 0 ${width} ${height}`"
            preserveAspectRatio="none"
            class="metric-trend__chart"
            role="img"
            :aria-label="lines.join('. ')"
        >
            <defs>
                <linearGradient :id="fillId" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="0" :y2="point(0, 0, 1).y">
                    <stop offset="0" class="metric-trend__fill-top" />
                    <stop offset="1" stop-color="#fff" />
                </linearGradient>
            </defs>
            <path :d="areaPath" :fill="`url(#${fillId})`" />
            <path :d="mainPath" class="metric-trend__line" />
        </svg>
        <span v-if="hovered" class="metric-trend__dot" :style="{ left: hovered.left, top: hovered.top }" />
        <b-tooltip :target="targetId" triggers="hover" boundary="viewport">
            <div v-if="hovered" class="metric-trend__hovered">{{ hovered.label }}</div>
            <div class="font-weight-bold">{{ $t(`dashboard.trend.unit.${trend.unit}`) }}</div>
            <div v-for="line in lines" :key="line">{{ line }}</div>
            <div v-if="trend.note" class="metric-trend__note">{{ $t(`dashboard.trend.note.${trend.note}`) }}</div>
        </b-tooltip>
    </span>
</template>

<style scoped lang="scss">
.metric-trend {
    display: flex;
    position: relative;

    &__dot {
        position: absolute;
        width: 6px;
        height: 6px;
        margin: -3px 0 0 -3px;
        border-radius: 50%;
        background: var(--colorPrimary);
        pointer-events: none;
    }

    &__hovered {
        font-size: 1rem;
        font-weight: bold;
        margin-bottom: .25rem;
    }

    &__chart {
        display: block;
        overflow: visible;
        opacity: .8;
    }

    &__line {
        fill: none;
        stroke: var(--colorPrimary);
        stroke-width: 1.5;
        stroke-linejoin: round;
        stroke-linecap: round;
        vector-effect: non-scaling-stroke;
    }

    &__fill-top {
        stop-color: var(--colorPrimary);
        stop-opacity: .35;
    }

    &__note {
        margin-top: .25rem;
        opacity: .8;
        font-size: .75rem;
    }
}
</style>
