import { ref, reactive, watch } from 'vue'
import { opportunitiesApi } from '@/api/opportunities'
import { requirementsApi } from '@/api/requirements'

export function useRequirements({ perform, detail }) {
    const reviewForm = ref(null),
        newReq = reactive({ source_document_id: '', source_page: 1, description: '' })

    watch(
        () => detail.value?.opportunity.id,
        () => {
            Object.assign(newReq, { source_document_id: '', source_page: 1, description: '' })
            reviewForm.value = null
        },
    )

    function review(req) {
        reviewForm.value = {
            ...req,
            evidence_document_id: req.evidence_document_id || '',
            evidence_page: req.evidence_page || 1,
            notes: req.notes || '',
        }
    }

    async function saveReview() {
        await perform(async () => {
            const r = reviewForm.value
            await requirementsApi.review(r.id, {
                status: r.status,
                evidence_document_id: r.evidence_document_id || null,
                evidence_page: r.evidence_page,
                notes: r.notes,
            })
            detail.value = await opportunitiesApi.show(detail.value.opportunity.id)
            reviewForm.value = null
        })
    }

    async function addRequirement() {
        await perform(async () => {
            await requirementsApi.create(detail.value.opportunity.id, { ...newReq })
            newReq.description = ''
            detail.value = await opportunitiesApi.show(detail.value.opportunity.id)
        })
    }

    return { review, saveReview, addRequirement, reviewForm, newReq }
}
