---
paths:
  - 'app/{Actions,Models,Http/Requests}/**/*Package*.php'
---

# Requests

## Vendedor validado y contacto histórico en paquetes
Al crear un paquete con seller_id, resolver siempre un Seller activo de la misma company. Guardar seller_id y copiar sender_name desde business_name (o name si no existe negocio) y sender_phone desde Seller; nunca confiar en esos snapshots enviados por el navegador ni actualizarlos al editar el vendedor.
