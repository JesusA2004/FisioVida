export type PageMeta = {
    from?: number | null
    to?: number | null
    total?: number | null
    prev?: string | null
    next?: string | null
  }
  
  export type SelectOption = {
    value: string | number
    label: string
    disabled?: boolean
  }
  
  export type CrudColumn<Row> = {
    label: string
    class?: string
    value: (row: Row) => any
    mobileValue?: (row: Row) => any
    hideOnMobile?: boolean
  }
  
  export type CrudField<Form> = {
    key: keyof Form | string
    label: string
    type: 'text' | 'textarea' | 'date' | 'datetime' | 'number' | 'toggle' | 'select' | 'file'
    placeholder?: string
    hint?: string
    accept?: string
    options?: SelectOption[]
    span?: 1 | 2
    readonly?: boolean
    // opcional si quieres cambiar texto de toggle (si no, dice “Activo”)
    toggleLabel?: string
  }
  