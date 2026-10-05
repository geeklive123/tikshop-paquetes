using TikShopPrinterAgent.Core;

namespace TikShopPrinterAgent;

internal static class Program
{
    [STAThread]
    private static void Main(string[] args)
    {
        using var mutex = new Mutex(true, @"Local\TikShopPrinterAgent", out var ownsMutex);
        if (!ownsMutex)
        {
            MessageBox.Show("TikShop Printer Agent ya está abierto.", "TikShop Printer Agent", MessageBoxButtons.OK, MessageBoxIcon.Information);
            return;
        }

        ApplicationConfiguration.Initialize();
        var appDirectory = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "TikShop",
            "PrinterAgent");
        var tokenProtector = new WindowsTokenProtector();
        var configurationStore = new ConfigurationStore(Path.Combine(appDirectory, "settings.json"), tokenProtector);
        string? currentToken = null;
        var logger = new SafeFileLogger(Path.Combine(appDirectory, "logs"), () => currentToken);
        var fakeMode = args.Contains("--fake-printer", StringComparer.OrdinalIgnoreCase)
            || string.Equals(Environment.GetEnvironmentVariable("TIKSHOP_PRINTER_DRIVER"), "fake", StringComparison.OrdinalIgnoreCase);
        IPrinterCatalog printerCatalog = fakeMode ? new DevelopmentPrinterCatalog() : new WindowsPrinterCatalog();
        IPrintDriver printDriver = fakeMode ? new FakePrintDriver(logger) : new WindowsPrintDriver();
        var reportStore = new PendingReportStore(Path.Combine(appDirectory, "pending-reports.json"));
        var worker = new AgentWorker(
            settings => new PrintAgentApiClient(new HttpClient(), settings),
            printerCatalog,
            printDriver,
            reportStore,
            logger);
        var startMinimized = args.Contains("--autostart", StringComparer.OrdinalIgnoreCase);

        Application.Run(new MainForm(
            configurationStore,
            printerCatalog,
            printDriver,
            worker,
            logger,
            startMinimized,
            token => currentToken = token));
    }
}
