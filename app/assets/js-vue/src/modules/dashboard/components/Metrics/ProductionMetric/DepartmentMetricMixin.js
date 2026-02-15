import { getUserDepartments } from "@/helpers";

export default {
    methods: {
        aggregateByDepartment(data) {
            return getUserDepartments().map((department) => ({
                name: department.name,
                slug: department.slug,
                value: data?.reduce((acc, item) => {
                    if (item.departmentSlug === department.slug) {
                        return acc + item.factors.factor
                    }
                    return acc
                }, 0)
            }))
        },
    }
}