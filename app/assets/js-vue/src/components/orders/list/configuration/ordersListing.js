import moment from "moment";
import Column from "@/components/base/BaseListing/model/Column";
import Criterion, { TYPE_DATE_RANGE, TYPE_TEXT } from "@/components/base/BaseListing/model/Criterion";
import i18n from "@/../i18n";
import helpers from "@/helpers";
import OrderNumberCell from "../cells/OrderNumberCell";
import OrderUserCell from "../cells/OrderUserCell";
import OrderProductCell from "../cells/OrderProductCell";
import OrderStatusCell from "../cells/OrderStatusCell";
import ProductionStatusCell from "../cells/ProductionStatusCell";

/**
 * Lista zamówień czyta ten sam read model co harmonogram produkcji, ale przez
 * /agreement-line/rm/orders, który przepuszcza wiersz przez LegacyAgreementLineMapper.
 * Stąd zagnieżdżone ścieżki (`Agreement.Customer`) i daty w formacie `Y-m-d H:i:s`.
 */

export const LISTING_ORDERS_ID = 'listing.orders'

export const COLUMN_ORDERS_NUMBER = 'orders_number'
export const COLUMN_ORDERS_RECEIVE_DATE = 'orders_receive_date'
export const COLUMN_ORDERS_DELIVERY_DATE = 'orders_delivery_date'
export const COLUMN_ORDERS_ISSUED_BY = 'orders_issued_by'
export const COLUMN_ORDERS_CUSTOMER = 'orders_customer'
export const COLUMN_ORDERS_PRODUCT = 'orders_product'
export const COLUMN_ORDERS_STATUS = 'orders_status'
export const COLUMN_ORDERS_PRODUCTION_STATUS = 'orders_production_status'

export const CRITERION_SEARCH = 'q'
export const CRITERION_DATE_START = 'dateStart'
export const CRITERION_DATE_DELIVERY = 'dateDelivery'

const formatDate = value => value ? moment(value).format('YYYY-MM-DD') : ''

export const criteriaFactory = () => ([
  new Criterion({
    id: CRITERION_SEARCH,
    label: i18n.t('search'),
    type: TYPE_TEXT,
    defaultValue: '',
  }),
  new Criterion({
    id: CRITERION_DATE_START,
    label: i18n.t('receiveDate'),
    type: TYPE_DATE_RANGE,
    defaultValue: { start: null, end: null },
  }),
  new Criterion({
    id: CRITERION_DATE_DELIVERY,
    label: i18n.t('deliveryDate'),
    type: TYPE_DATE_RANGE,
    defaultValue: { start: null, end: null },
  }),
])

export const columnsFactory = () => ([
  new Column({
    id: COLUMN_ORDERS_NUMBER,
    label: i18n.t('id'),
    apiSortKey: 'id',
    displayComponent: OrderNumberCell,
  }),
  new Column({
    id: COLUMN_ORDERS_RECEIVE_DATE,
    label: i18n.t('receiveDate'),
    apiPath: 'Agreement.createDate',
    apiSortKey: 'dateReceive',
    formatter: formatDate,
    tdClass: 'text-nowrap',
  }),
  new Column({
    id: COLUMN_ORDERS_DELIVERY_DATE,
    label: i18n.t('deliveryDate'),
    apiPath: 'confirmedDate',
    apiSortKey: 'dateConfirmed',
    formatter: formatDate,
    tdClass: 'text-nowrap',
  }),
  new Column({
    id: COLUMN_ORDERS_ISSUED_BY,
    label: i18n.t('orders.issuedBy'),
    apiPath: 'Agreement.user.userFullName',
    apiSortKey: 'user',
    displayComponent: OrderUserCell,
  }),
  new Column({
    id: COLUMN_ORDERS_CUSTOMER,
    label: i18n.t('customer'),
    apiPath: 'Agreement.Customer',
    apiSortKey: 'customer',
    formatter: customer => helpers.customerName(customer),
  }),
  new Column({
    id: COLUMN_ORDERS_PRODUCT,
    label: i18n.t('product'),
    apiPath: 'Product.name',
    apiSortKey: 'product',
    displayComponent: OrderProductCell,
  }),
  new Column({
    id: COLUMN_ORDERS_STATUS,
    label: i18n.t('orderStatus'),
    apiPath: 'status',
    displayComponent: OrderStatusCell,
  }),
  new Column({
    id: COLUMN_ORDERS_PRODUCTION_STATUS,
    label: i18n.t('productionStatus'),
    apiPath: 'productions',
    displayComponent: ProductionStatusCell,
  }),
])
