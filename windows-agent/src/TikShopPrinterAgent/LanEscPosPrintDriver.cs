using TikShopPrinterAgent.Core;
using TikShopPrinterAgent.Core.Models;

namespace TikShopPrinterAgent;

public sealed class LanEscPosPrintDriver : IPrintDriver
{
    public Task<PrintResult> PrintTestAsync(string printerName, CancellationToken cancellationToken = default) =>
        throw new NotSupportedException("El driver LAN ESC/POS está desactivado. Use la cola de impresión de Windows.");

    public Task<PrintResult> PrintAsync(PrintJob job, string printerName, int copies, CancellationToken cancellationToken = default) =>
        throw new NotSupportedException("El driver LAN ESC/POS está desactivado. Use la cola de impresión de Windows.");
}
