import { getUserDepartments } from "@/helpers";

export default {
    methods: {
        aggregateByDepartment(data) {
            return getUserDepartments().map((department) => ({
                name: department.name,
                slug: department.slug,
                value: data?.reduce((acc, item) => {
                    // poza oknem (onTime === false) premia nie jest naliczana;
                    // rekordy poza zakresem raportu (inRange === false) nie mają współczynnika
                    if (item.departmentSlug === department.slug && item.onTime !== false && item.inRange !== false) {
                        return acc + item.factors.factor
                    }
                    return acc
                }, 0)
            }))
        },
    }
}