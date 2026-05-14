import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

export type ReportFilters = {
    start_date: string
    end_date: string
    appointment_status?: string
    payment_status?: string
    activity_status?: string
    therapist_user_id?: number | null
    patient_persona_id?: number | null
}

export const useReporteCrud = (filters: ReportFilters) => {
    const page = usePage()
    const loading = ref(false)
    const dateError = ref<string | null>(null)

    const form = ref<ReportFilters>({
        start_date: filters.start_date,
        end_date: filters.end_date,
        appointment_status: filters.appointment_status ?? '',
        payment_status: filters.payment_status ?? '',
        activity_status: filters.activity_status ?? '',
        therapist_user_id: filters.therapist_user_id ?? null,
        patient_persona_id: filters.patient_persona_id ?? null,
    })

    const enabledModules = computed<Record<string, boolean>>(() => ((page.props as any).enabledModules ?? {}) as Record<string, boolean>)
    const moduleEnabled = computed(() => enabledModules.value.reportes !== false)

    const applyFilters = () => {
        if (form.value.start_date && form.value.end_date && form.value.end_date < form.value.start_date) {
            dateError.value = 'La fecha fin debe ser mayor o igual a la fecha inicio.'
            return
        }
        dateError.value = null
        loading.value = true
        router.get(
            '/reportes',
            {
                start_date: form.value.start_date,
                end_date: form.value.end_date,
                appointment_status: form.value.appointment_status || '',
                payment_status: form.value.payment_status || '',
                activity_status: form.value.activity_status || '',
                therapist_user_id: form.value.therapist_user_id || '',
                patient_persona_id: form.value.patient_persona_id || '',
            },
            {
                preserveState: true,
                replace: true,
                preserveScroll: true,
                onFinish: () => { loading.value = false },
            },
        )
    }

    const resetFilters = () => {
        form.value.appointment_status = ''
        form.value.payment_status = ''
        form.value.activity_status = ''
        form.value.therapist_user_id = null
        form.value.patient_persona_id = null
        dateError.value = null
        applyFilters()
    }

    return {
        form,
        loading,
        dateError,
        moduleEnabled,
        applyFilters,
        resetFilters,
    }
}
