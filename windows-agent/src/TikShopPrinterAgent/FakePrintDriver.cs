using TikShopPrinterAgent.Core;
using TikShopPrinterAgent.Core.Models;

namespace TikShopPrinterAgent;

public sealed class FakePrintDriver(IAgentLogger logger) : IPrintDriver
{
    public Task<PrintResult> PrintTestAsync(string printerName, CancellationToken cancellationToken = default)
    {
        logger.Info($"Prueba simulada en '{printerName}'.");
        return Task.FromResult(PrintResult.Success("Prueba simulada completada."));
    }

    public Task<PrintResult> PrintAsync(PrintJob job, string printerName, int copies, CancellationToken cancellationToken = default)
    {
        logger.Info($"Impresión simulada para job {job.Ulid}; copias: {copies}.");
        return Task.FromResult(PrintResult.Success($"Impresión simulada ({copies} copia(s))."));
    }
}
