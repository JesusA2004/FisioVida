// resources/js/lib/fvSwal.ts
import Swal from 'sweetalert2'

export async function confirmDanger(opts: {
    title: string
    text?: string
    confirmText?: string
}) {
    const r = await Swal.fire({
        title: opts.title,
        text: opts.text ?? 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: opts.confirmText ?? 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
    })
    return r.isConfirmed
}
