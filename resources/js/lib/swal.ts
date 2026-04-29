import Swal from 'sweetalert2';

const ensureTop = () => {
    if (document.getElementById('fv-swal-z')) return;

    const style = document.createElement('style');
    style.id = 'fv-swal-z';
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
  `;
    document.head.appendChild(style);
};

const blurActiveElement = () => {
    const active = document.activeElement as HTMLElement | null;
    active?.blur?.();
};

export const swalToast = (
    title: string,
    icon: 'success' | 'error' | 'warning' | 'info' = 'success',
) => {
    ensureTop();
    blurActiveElement();

    return Swal.fire({
        target: document.body,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2400,
        timerProgressBar: true,
        icon,
        title,
    });
};

export const swalOk = (title: string, text?: string) => {
    ensureTop();
    blurActiveElement();

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
    });
};

export const swalErr = (title: string, text?: string) => {
    ensureTop();
    blurActiveElement();

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
    });
};

export const swalConfirm = async (
    title: string,
    text?: string,
    confirmText = 'Sí, continuar',
) => {
    ensureTop();
    blurActiveElement();

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
    });

    return res.isConfirmed;
};

export const swalLoading = (title = 'Procesando...') => {
    ensureTop();
    blurActiveElement();

    Swal.fire({
        target: document.body,
        title,
        allowOutsideClick: false,
        allowEscapeKey: false,
        heightAuto: false,
        returnFocus: false,
        didOpen: () => Swal.showLoading(),
    });
};

export const swalClose = () => Swal.close();

export const swalProgress = (
    title = 'Procesando...',
    text = 'Espera un momento',
) => {
    ensureTop();
    blurActiveElement();

    Swal.fire({
        target: document.body,
        title,
        html: `
      <div style="text-align:left;margin-top:12px;">
        <p style="margin:0 0 14px;color:#64748b;font-size:14px;line-height:1.5;">
          ${text}
        </p>

        <div style="height:12px;width:100%;overflow:hidden;border-radius:999px;background:#e5e7eb;">
          <div class="fv-swal-progress-bar"></div>
        </div>

        <p style="margin:12px 0 0;color:#94a3b8;font-size:12px;text-align:center;">
          No cierres esta ventana mientras termina el proceso.
        </p>
      </div>
    `,
        showConfirmButton: false,
        allowOutsideClick: false,
        allowEscapeKey: false,
        heightAuto: false,
        returnFocus: false,
        didOpen: () => {
            if (!document.getElementById('fv-swal-progress-style')) {
                const style = document.createElement('style');
                style.id = 'fv-swal-progress-style';
                style.textContent = `
          .fv-swal-progress-bar {
            height: 100%;
            width: 45%;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--primary), var(--primary-hover, var(--primary)));
            animation: fv-swal-progress 1.15s ease-in-out infinite;
          }

          @keyframes fv-swal-progress {
            0% {
              transform: translateX(-115%);
            }
            50% {
              transform: translateX(70%);
            }
            100% {
              transform: translateX(235%);
            }
          }
        `;
                document.head.appendChild(style);
            }
        },
    });
};
