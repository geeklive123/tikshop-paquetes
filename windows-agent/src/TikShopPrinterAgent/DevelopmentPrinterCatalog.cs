using TikShopPrinterAgent.Core;

namespace TikShopPrinterAgent;

public sealed class DevelopmentPrinterCatalog : IPrinterCatalog
{
    public const string PrinterName = "TikShop Fake Printer";

    public IReadOnlyList<string> GetInstalledPrinters() => [PrinterName];

    public PrinterAvailability Check(string printerName) =>
        string.Equals(printerName, PrinterName, StringComparison.Ordinal)
            ? new PrinterAvailability(true, "Disponible (simulado)")
            : new PrinterAvailability(false, "Seleccione la impresora simulada.");
}
