import Swal from 'sweetalert2'

const ensureTop = () => {
  if (document.getElementById('fv-swal-z')) return
  const style = document.createElement('style')
  style.id = 'fv-swal-z'
  style.textContent = `
    .swal2-container{ z-index:20000 !important; }
    .swal2-popup{ border-radius:16px !important; }
  `
  document.head.appendChild(style)
}

export const swalToast = (title: string, icon: 'success'|'error'|'warning'|'info' = 'success') => {
  ensureTop()
  return Swal.fire({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2400,
    timerProgressBar: true,
    icon,
    title,
  })
}

export const swalOk = (title: string, text?: string) => {
  ensureTop()
  return Swal.fire({ icon: 'success', title, text, confirmButtonText: 'OK' })
}

export const swalErr = (title: string, text?: string) => {
  ensureTop()
  return Swal.fire({ icon: 'error', title, text, confirmButtonText: 'Entendido' })
}

export const swalConfirm = async (title: string, text?: string, confirmText = 'Sí, continuar') => {
  ensureTop()
  const res = await Swal.fire({
    icon: 'warning',
    title,
    text,
    showCancelButton: true,
    confirmButtonText: confirmText,
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
  })
  return res.isConfirmed
}

export const swalLoading = (title = 'Procesando...') => {
  ensureTop()
  Swal.fire({
    title,
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => Swal.showLoading(),
  })
}

export const swalClose = () => Swal.close()
