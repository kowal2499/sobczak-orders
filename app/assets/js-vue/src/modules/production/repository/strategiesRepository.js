import axios from "axios"

export function getDateStrategies() {
    return axios.get('/production/date-strategies')
}
