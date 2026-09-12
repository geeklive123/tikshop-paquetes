---
paths:
  - 'app/{Actions,Models,Http/Controllers}/**/*PickupToken*.php'
---

# Controllers

## Preservar token recuperable cifrado para tickets
El hash SHA-256 sigue siendo la fuente de validación del QR. Conservar `token_encrypted` con cast `encrypted` y oculto para poder volver a renderizar el QR activo en tickets PDF; nunca almacenar ni comparar el token en texto plano.
