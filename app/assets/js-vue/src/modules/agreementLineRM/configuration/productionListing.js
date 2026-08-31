import moment from "moment";
import Column from "@/components/base/BaseListing/model/Column";
import i18n from "@/../i18n";
import { DEPARTMENTS } from "@/helpers";
import Roles from "@/definitions/userRoles";
import IdCell from "../components/cells/IdCell";
import AttachmentsCell from "../components/cells/AttachmentsCell";
import TasksCell from "../components/cells/TasksCell";
import UserCell from "../components/cells/UserCell";
import ProductCell from "../components/cells/ProductCell";
import DepartmentCell from "../components/cells/DepartmentCell";

export const LISTING_PRODUCTION_ID = 'listing.production'
export const COLUMN_PRODUCTION_ID = 'production_id'
export const COLUMN_PRODUCTION_ATTACHMENTS = 'production_attachments'
export const COLUMN_PRODUCTION_TASKS = 'production_tasks'
export const COLUMN_PRODUCTION_CONFIRMED_DATE = 'production_confirmed_date'
export const COLUMN_PRODUCTION_ISSUED_BY = 'production_issued_by'
export const COLUMN_PRODUCTION_CUSTOMER = 'production_customer'
export const COLUMN_PRODUCTION_PRODUCT = 'production_product'
export const COLUMN_PRODUCTION_FACTOR = 'production_factor'
export const COLUMN_PRODUCTION_DEPARTMENTS_MAP = DEPARTMENTS.reduce((acc, dpt) => {
  acc[dpt.slug] = `production_${dpt.slug}`
  return acc
}, {})

export const columnsFactory = (user) => ([
  new Column({
    id: COLUMN_PRODUCTION_ID,
    label: i18n.t('ID'),
    apiPath: 'agreementLineId',
    apiSortKey: 'id',
    displayComponent: IdCell,
  }),
  new Column({
    id: COLUMN_PRODUCTION_ATTACHMENTS,
    label: i18n.t('attachments'),
    apiPath: 'attachments',
    displayComponent: AttachmentsCell,
  }),
  user.can('task.orphans:read') && new Column({
    id: COLUMN_PRODUCTION_TASKS,
    label: i18n.t('tasks'),
    apiPath: 'tasks',
    displayComponent: TasksCell,
  }),
  user.can('production.show.production_date') && new Column({
    id: COLUMN_PRODUCTION_CONFIRMED_DATE,
    label: i18n.t('orders.date'),
    apiPath: 'confirmedDate',
    apiSortKey: 'dateConfirmed',
    formatter: value => value ? moment(value).format('YYYY-MM-DD') : '',
    tdClass: 'text-nowrap',
  }),
  new Column({
    id: COLUMN_PRODUCTION_ISSUED_BY,
    label: i18n.t('orders.issuedBy'),
    apiPath: 'user.name',
    apiSortKey: 'user',
    displayComponent: UserCell,
  }),
  new Column({
    id: COLUMN_PRODUCTION_CUSTOMER,
    label: i18n.t('customer'),
    apiPath: 'customerName',
    apiSortKey: 'customer',
  }),
  new Column({
    id: COLUMN_PRODUCTION_PRODUCT,
    label: i18n.t('product'),
    apiPath: 'productName',
    apiSortKey: 'product',
    displayComponent: ProductCell,
  }),
  user.can(Roles.CAN_PRODUCTION) && new Column({
    id: COLUMN_PRODUCTION_FACTOR,
    label: i18n.t('orders.fctr'),
    apiPath: 'factor',
    apiSortKey: 'factor',
    thClass: 'text-center',
    tdClass: 'text-center',
  }),
  ...DEPARTMENTS.map((dpt, index) => user.can(dpt.grant) &&
      new Column({
        id: COLUMN_PRODUCTION_DEPARTMENTS_MAP[dpt.slug],
        label: i18n.t(`_${dpt.slug}`),
        apiPath: record => (record.productions || []).find(prod => prod.departmentSlug === dpt.slug) || null,
        headerItems: [
          { label: i18n.t(`_${dpt.slug}`), sortKey: null },
          { label: i18n.t('agreement_line_list.startProductionForm.startDate'), sortKey: `${dpt.slug}DateStart` },
          { label: i18n.t('agreement_line_list.startProductionForm.endDate'), sortKey: `${dpt.slug}DateEnd` },
        ],
        displayComponent: DepartmentCell,
        thClass: `text-center background-color-primary-light-${index % 2 ? 90 : 80}`,
        tdClass: 'prod',
      })
  )
]).filter(Boolean)
