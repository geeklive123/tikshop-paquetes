# TikShop Printer Agent

Agente Windows independiente para consumir los `PrintJob` de Tik Shop y enviarlos a una impresora instalada mediante su cola/driver de Windows. No requiere una copia de Laravel, PHP, Composer, Node, MySQL ni secretos del servidor.

## Requisitos de compilación

- Windows 10/11 x64.
- SDK .NET 8.
- Inno Setup 6, solo para generar el instalador.

Desde PowerShell:

```powershell
cd windows-agent
.\build.ps1
```

El ejecutable autocontenido queda en `artifacts\publish\win-x64\TikShopPrinterAgent.exe`. Para compilar las pruebas, publicar el `.exe` y generar `TikShopPrinterAgent-Setup.exe`:

```powershell
.\build.ps1 -Installer
```

## Configuración y seguridad

La configuración se guarda en `%LOCALAPPDATA%\TikShop\PrinterAgent\settings.json`. El token se protege con Windows DPAPI para el usuario actual; nunca se guarda en texto plano. Los logs están en la subcarpeta `logs` y filtran tokens Bearer, el token configurado y datos de imagen/QR.

El agente solo abre conexiones HTTPS salientes al servidor configurado. HTTP se admite exclusivamente para `localhost` durante desarrollo. No desactiva TLS ni acepta certificados inválidos.

## Impresión

`WindowsPrintDriver` enumera las impresoras con `PrinterSettings.InstalledPrinters`, valida la cola con Winspool y usa `PrintDocument` con papel personalizado de 80 mm. El ticket usa las imágenes PNG Base64 de logo y QR que ya vienen en el snapshot; no reconstruye tokens.

Las copias se envían con `PrinterSettings.Copies`, por lo que Windows/driver genera documentos físicos iguales sin duplicar el diseño lógico. El corte automático depende de que el driver del fabricante T-IM5003 esté configurado para cortar al final de cada documento/copia; el agente no inyecta comandos `GS V` en la cola Windows.

`LanEscPosPrintDriver` existe como alternativa futura y permanece desactivado. Para desarrollo sin impresora, iniciar con `--fake-printer` o definir `TIKSHOP_PRINTER_DRIVER=fake`.

## Recuperación

El agente primero reclama el job con `/processing` y solo imprime si el servidor confirma el claim. Si se pierde Internet después de imprimir, conserva localmente el reporte final y reintenta `/completed` sin volver a imprimir. Los jobs que otro agente ya reclamó no se imprimen.
