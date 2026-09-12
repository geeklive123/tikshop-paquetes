---
paths:
  - app/Actions/Packages/GeneratePickupQrCodeAction.php
---

# Packages

## Usar PNG Base64 para QR en tickets PDF
Las vistas web pueden seguir usando SVG, pero DomPDF debe recibir el QR de recojo como PNG generado en memoria y embebido mediante data:image/png;base64,. Reutilizar siempre la misma URL/token activo; no crear archivos QR ni tokens nuevos para renderizar el PDF.

## Forzar PNG de 8 bits para DomPDF
Para el ticket, PngWriter debe usar WRITER_OPTION_NUMBER_OF_COLORS => null y devolver getString() como PNG binario. El controlador construye data:image/png;base64,; evitar el PNG indexado de 1 bit predeterminado porque puede no mostrarse consistentemente en DomPDF/visores.
