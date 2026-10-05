using TikShopPrinterAgent.Core.Models;

namespace TikShopPrinterAgent.Core;

public interface ITokenProtector
{
    string Protect(string plainText);
    string Unprotect(string protectedText);
}

public interface IConfigurationStore
{
    Task<AppSettings> LoadAsync(CancellationToken cancellationToken = default);
    Task SaveAsync(AppSettings settings, CancellationToken cancellationToken = default);
}

public interface IAgentLogger
{
    void Info(string message);
    void Error(string message, Exception? exception = null);
}

public interface IPrinterCatalog
{
    IReadOnlyList<string> GetInstalledPrinters();
    PrinterAvailability Check(string printerName);
}

public sealed record PrinterAvailability(bool IsAvailable, string Message);

public interface IPrintDriver
{
    Task<PrintResult> PrintTestAsync(string printerName, CancellationToken cancellationToken = default);
    Task<PrintResult> PrintAsync(PrintJob job, string printerName, int copies, CancellationToken cancellationToken = default);
}

public sealed record PrintResult(bool Succeeded, string Message)
{
    public static PrintResult Success(string message) => new(true, message);
    public static PrintResult Failure(string message) => new(false, message);
}
