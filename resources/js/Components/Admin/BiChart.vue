<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import {
    Chart,
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    DoughnutController,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js'

Chart.register(
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    DoughnutController,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
)

const props = defineProps({
    type: { type: String, default: 'bar' },
    labels: { type: Array, default: () => [] },
    data: { type: Array, default: () => [] },
    label: { type: String, default: 'Serie' },
    datasets: { type: Array, default: null },
    horizontal: { type: Boolean, default: false },
    height: { type: Number, default: 280 },
})

const canvas = ref(null)
let chart = null

const palette = ['#D7194B', '#4e73df', '#1cc88a', '#f6c23e', '#36b9cc', '#858796', '#e74a3b', '#5a5c69']

const buildDatasets = () => {
    const isDonut = props.type === 'doughnut' || props.type === 'pie'

    if (props.datasets?.length) {
        return props.datasets.map((ds, i) => ({
            label: ds.label ?? `Serie ${i + 1}`,
            data: ds.data ?? [],
            backgroundColor: ds.backgroundColor ?? (isDonut
                ? props.labels.map((_, idx) => palette[idx % palette.length])
                : palette[i % palette.length]),
            borderColor: ds.borderColor ?? palette[i % palette.length],
            borderWidth: props.type === 'line' ? 2 : 1,
            fill: props.type === 'line' ? (ds.fill ?? false) : undefined,
            tension: 0.3,
        }))
    }

    return [
        {
            label: props.label,
            data: props.data,
            backgroundColor: isDonut || props.type === 'bar'
                ? props.labels.map((_, i) => palette[i % palette.length])
                : 'rgba(215, 25, 75, 0.25)',
            borderColor: '#D7194B',
            borderWidth: props.type === 'line' ? 2 : 1,
            fill: props.type === 'line',
            tension: 0.3,
        },
    ]
}

const render = () => {
    if (!canvas.value) {
        return
    }

    if (chart) {
        chart.destroy()
    }

    const isDonut = props.type === 'doughnut' || props.type === 'pie'
    const chartDatasets = buildDatasets()

    chart = new Chart(canvas.value, {
        type: props.type,
        data: {
            labels: props.labels,
            datasets: chartDatasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: props.horizontal ? 'y' : 'x',
            plugins: {
                legend: {
                    display: isDonut || chartDatasets.length > 1,
                    labels: { color: '#B8B8C2' },
                },
            },
            scales: isDonut
                ? {}
                : {
                    x: {
                        ticks: { color: '#B8B8C2' },
                        grid: { color: 'rgba(43,43,51,0.6)' },
                    },
                    y: {
                        ticks: { color: '#B8B8C2' },
                        grid: { color: 'rgba(43,43,51,0.6)' },
                    },
                },
        },
    })
}

onMounted(render)
watch(() => [props.labels, props.data, props.datasets, props.type], render, { deep: true })
onBeforeUnmount(() => {
    if (chart) {
        chart.destroy()
    }
})
</script>

<template>
    <div :style="{ height: `${height}px` }">
        <canvas ref="canvas"></canvas>
    </div>
</template>
