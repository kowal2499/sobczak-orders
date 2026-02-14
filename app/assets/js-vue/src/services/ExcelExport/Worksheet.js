export default class Worksheet {
    constructor(excelWorksheet, fields) {
        this.worksheet = excelWorksheet
        this.fields = fields

        this.worksheet.addRow(fields.map(f => f.title))
    }

    buildRowValues(record) {
        return this.fields.map(f => {
            const val = f.getValue(record)
            if (val instanceof Date) {
                return val
            }
            return val
        })
    }

    addData(data) {
        const values = this.buildRowValues(data)
        const row = this.worksheet.addRow(values)

        // Dodaj komentarze do komórek, jeśli pole ma noteGetter
        this.fields.forEach((field, index) => {
            if (typeof field.meta?.excel?.noteGetter === 'function') {
                const note = field.meta.excel.noteGetter.call(field, data)
                if (note) {
                    const cell = row.getCell(index + 1)
                    cell.note = note
                }
            }
        })
    }
}