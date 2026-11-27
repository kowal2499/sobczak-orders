// The orders list hands over the legacy shape (Product, Agreement.Customer),
// the read-model list the flat camelCase one; both describe the same line
function pick(...values) {
    return values.find(v => v !== undefined && v !== null && v !== '') ?? null
}

export function agreementLineSummary(line) {
    const agreement = line.Agreement || line.agreement || {}
    const product = line.Product || line.product || {}
    const customer = agreement.Customer || line.customer || {}

    return {
        agreementLineId: pick(line.id, line.agreementLineId),
        orderNumber: pick(agreement.orderNumber, line.orderNumber),
        internalNumber: pick(line.internalNumber),
        customerName: pick(customer.name, line.customerName),
        productName: pick(product.name, line.productName),
        status: pick(line.status),
        confirmedDate: pick(line.confirmedDate),
        createDate: pick(agreement.createDate, line.agreementCreateDate),
        issuedBy: pick(agreement.user && agreement.user.userFullName, line.userName),
        description: pick(product.description, line.description),
    }
}
