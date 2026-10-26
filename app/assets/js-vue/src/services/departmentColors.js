import { DPT_GLUEING, DPT_CNC, DPT_GRINDING, DPT_LACQUERING, DPT_PACKING, DPT_INTOREX } from '@/helpers'

export const DEPARTMENT_COLORS = {
    [DPT_GLUEING]:     { bg: 'rgba(78, 121, 167, 0.7)',  border: '#4E79A7' },
    [DPT_CNC]:         { bg: 'rgba(242, 142, 43, 0.7)',  border: '#F28E2B' },
    [DPT_GRINDING]:    { bg: 'rgba(89, 161, 79, 0.7)',   border: '#59A14F' },
    [DPT_LACQUERING]:  { bg: 'rgba(225, 87, 89, 0.7)',   border: '#E15759' },
    [DPT_PACKING]:     { bg: 'rgba(118, 183, 178, 0.7)', border: '#76B7B2' },
    [DPT_INTOREX]:     { bg: 'rgba(176, 122, 161, 0.7)', border: '#B07AA1' },
    _fallback:         { bg: 'rgba(128, 128, 128, 0.5)', border: '#808080' },
}

export function departmentColor(slug) {
    return DEPARTMENT_COLORS[slug] || DEPARTMENT_COLORS._fallback
}
