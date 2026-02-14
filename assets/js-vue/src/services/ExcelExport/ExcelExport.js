import Excel from 'exceljs'
import { saveAs } from 'file-saver'
import Worksheet from './Worksheet'

export default class ExcelExport {
    constructor() {
        this.workbook = new Excel.Workbook()
        this.workbook.creator = 'SobczakApp'
        this.workbook.created = new Date()
        this.worksheets = new Map()
    }

    addWorksheet(name, fields, options = {}) {
        this.removeWorksheet(name)

        const worksheet = new Worksheet(
            this.workbook.addWorksheet(name, options),
            fields
        )
        this.worksheets.set(name, worksheet)
        return worksheet
    }

    removeWorksheet(name) {
        if (!this.getWorksheet(name)) {
            return
        }
        this.worksheets.delete(name)
    }

    getWorksheet(name) {
        return (this.worksheets.has(name) && this.worksheets.get(name)) || undefined
    }

    getDefaultFileName() {
        let [date, time] = new Date().toISOString().split('T')
        time = time.split('.')[0].replaceAll(':', '-')
        return `Export-${date} ${time}`
    }

    clear() {
        Array.from(this.worksheets.keys()).forEach(name => this.workbook.removeWorksheet(name))
        this.worksheets = new Map()
    }

    async save(fileName = null) {
        const blob = new Blob([
            await this.workbook.xlsx.writeBuffer()
        ], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
        saveAs(blob, fileName || this.getDefaultFileName() + '.xlsx')
    }
}