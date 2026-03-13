export default {
    methods: {
        mapDetails(data) {
            const agreementLinesMap = data?.reduce((acc, item) => {
                if (!acc.has(item.agreementLine.id)) {
                    acc.set(item.agreementLine.id, {
                        ...item.agreementLine,
                        ...item.agreement,
                        factor: item.agreementLine.factor,
                        customerName: item.customer.name,
                        completedAt: item.completedAt,
                        involved_dpt01: {factor: null, factorsStack: [], production: null},
                        involved_dpt02: {factor: null, factorsStack: [], production: null},
                        involved_dpt03: {factor: null, factorsStack: [], production: null},
                        involved_dpt04: {factor: null, factorsStack: [], production: null},
                        involved_dpt05: {factor: null, factorsStack: [], production: null},
                        involved_dpt06: {factor: null, factorsStack: [], production: null},
                    })
                }

                const lineData = acc.get(item.agreementLine.id)
                lineData[`involved_${item.departmentSlug}`] = {
                    ...item.factors,
                    production: {
                        status: item.status,
                        dateStart: item.dateStart,
                        dateEnd: item.dateEnd,
                        departmentSlug: item.departmentSlug,
                    }
                }

                return acc
            }, new Map())

            return [...agreementLinesMap.values()]
        }
    }
}