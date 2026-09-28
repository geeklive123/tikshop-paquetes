---
paths:
  - 'app/{Printing,Console/Commands,Http/Controllers/Api/V1,Http/Middleware}/**/*.php'
---

# Middleware

## Impresión local por polling, sin sockets en Laravel
Laravel solo encola PrintJob y expone polling autenticado y acotado por company_id/branch_id. En modo actual PRINT_DRIVER=mock genera evidencia HTML local; LanEscPosPrintDriver y WindowsUsbPrintDriver deben permanecer stubs sin sockets hasta integrar hardware. Los tokens de PrinterAgent se persisten únicamente como SHA-256 y el valor plano solo se muestra al crear o regenerar.
