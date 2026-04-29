import Swal from 'sweetalert2'

const ensureTop = () => {
  if (document.getElementById('fv-swal-z')) return

  const style = document.createElement('style')
  style.id = 'fv-swal-z'
  style.textContent = `
    .swal2-container {
      z-index: 999999 !important;
      pointer-events: auto !important;
    }

    .swal2-backdrop-show {
      z-index: 999999 !important;
    }

    .swal2-popup {
      border-radius: 24px !important;
      pointer-events: auto !important;
    }

    .swal2-confirm,
    .swal2-cancel,
    .swal2-deny {
      pointer-events: auto !important;
    }
  `
  document.head.appendChild(style)
}

const blurActiveElement = () => {
  const active = document.activeElement as HTMLElement | null
  active?.blur?.()
}

export const swalToast = (
  title: string,
  icon: 'success' | 'error' | 'warning' | 'info' = 'success',
) => {
  ensureTop()
  blurActiveElement()

  return Swal.fire({
    target: document.body,
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
  blurActiveElement()

  return Swal.fire({
    target: document.body,
    icon: 'success',
    title,
    text,
    confirmButtonText: 'Entendido',
    heightAuto: false,
    allowOutsideClick: true,
    allowEscapeKey: true,
    returnFocus: false,
    focusConfirm: true,
  })
}

export const swalErr = (title: string, text?: string) => {
  ensureTop()
  blurActiveElement()

  return Swal.fire({
    target: document.body,
    icon: 'error',
    title,
    text,
    confirmButtonText: 'Entendido',
    heightAuto: false,
    allowOutsideClick: true,
    allowEscapeKey: true,
    returnFocus: false,
    focusConfirm: true,
  })
}

export const swalConfirm = async (
  title: string,
  text?: string,
  confirmText = 'Sí, continuar',
) => {
  ensureTop()
  blurActiveElement()

  const res = await Swal.fire({
    target: document.body,
    icon: 'warning',
    title,
    text,
    showCancelButton: true,
    confirmButtonText: confirmText,
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
    heightAuto: false,
    allowOutsideClick: true,
    allowEscapeKey: true,
    returnFocus: false,
    focusConfirm: false,
    focusCancel: true,
  })

  return res.isConfirmed
}

export const swalLoading = (title = 'Procesando...') => {
  ensureTop()
  blurActiveElement()

  Swal.fire({
    target: document.body,
    title,
    allowOutsideClick: false,
    allowEscapeKey: false,
    heightAuto: false,
    returnFocus: false,
    didOpen: () => Swal.showLoading(),
  })
}

export const swalClose = () => Swal.close()