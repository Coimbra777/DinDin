/**
 * POST clássico (multipart/form-urlencoded) com campos garantidos.
 * Evita o problema do VTextField não repassar `name` ao <input> nativo.
 */
export function submitNativePost(action, fields) {
  const form = document.createElement('form')
  form.method = 'POST'
  form.action = action
  form.setAttribute('accept-charset', 'UTF-8')
  form.style.cssText = 'position:absolute;left:-9999px;opacity:0;pointer-events:none'

  Object.entries(fields).forEach(([name, value]) => {
    if (value === undefined || value === null) return
    const input = document.createElement('input')
    input.type = 'hidden'
    input.name = name
    input.value = String(value)
    form.appendChild(input)
  })

  document.body.appendChild(form)
  form.submit()
}
