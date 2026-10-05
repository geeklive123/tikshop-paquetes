using TikShopPrinterAgent.Core;
using TikShopPrinterAgent.Core.Models;

namespace TikShopPrinterAgent.Tests;

[TestClass]
public sealed class AgentWorkerTest
{
    [DataTestMethod]
    [DataRow(1)]
    [DataRow(2)]
    public async Task ClaimedJobPrintsConfiguredCopiesAndReportsCompleted(int copies)
    {
        using var temporary = new TemporaryDirectory();
        var api = new FakeApi { NextJob = Job(copies), ClaimedJob = Job(copies) with { Status = "processing" } };
        var driver = new RecordingPrintDriver(PrintResult.Success("printed"));
        var worker = Worker(api, driver, new AvailablePrinterCatalog(), temporary.Path);

        await worker.RunOnceAsync(Settings());

        Assert.AreEqual(1, driver.PrintCalls);
        Assert.AreEqual(copies, driver.LastCopies);
        Assert.AreEqual(1, api.ProcessingCalls);
        Assert.AreEqual(1, api.CompletedCalls);
        Assert.AreEqual(0, api.FailedCalls);
    }

    [TestMethod]
    public async Task PrintFailureReportsFailed()
    {
        using var temporary = new TemporaryDirectory();
        var api = new FakeApi { NextJob = Job(1), ClaimedJob = Job(1) with { Status = "processing" } };
        var driver = new RecordingPrintDriver(PrintResult.Failure("paper out"));
        var worker = Worker(api, driver, new AvailablePrinterCatalog(), temporary.Path);

        await worker.RunOnceAsync(Settings());

        Assert.AreEqual(0, api.CompletedCalls);
        Assert.AreEqual(1, api.FailedCalls);
        Assert.AreEqual("paper out", api.LastFailure);
    }

    [TestMethod]
    public async Task MissingPrinterDoesNotPrintAndReportsFailed()
    {
        using var temporary = new TemporaryDirectory();
        var api = new FakeApi { NextJob = Job(1), ClaimedJob = Job(1) with { Status = "processing" } };
        var driver = new RecordingPrintDriver(PrintResult.Success("printed"));
        var worker = Worker(api, driver, new MissingPrinterCatalog(), temporary.Path);

        await worker.RunOnceAsync(Settings());

        Assert.AreEqual(0, driver.PrintCalls);
        Assert.AreEqual(1, api.FailedCalls);
    }

    [TestMethod]
    public async Task ConnectionLossDoesNotClaimOrPrintAJob()
    {
        using var temporary = new TemporaryDirectory();
        var api = new FakeApi { HeartbeatException = new HttpRequestException("offline"), NextJob = Job(1) };
        var driver = new RecordingPrintDriver(PrintResult.Success("printed"));
        var worker = Worker(api, driver, new AvailablePrinterCatalog(), temporary.Path);

        await worker.RunOnceAsync(Settings());

        Assert.AreEqual(0, api.ProcessingCalls);
        Assert.AreEqual(0, driver.PrintCalls);
    }

    [TestMethod]
    public async Task UnclaimedJobIsNotPrinted()
    {
        using var temporary = new TemporaryDirectory();
        var api = new FakeApi { NextJob = Job(1), ClaimedJob = null };
        var driver = new RecordingPrintDriver(PrintResult.Success("printed"));
        var worker = Worker(api, driver, new AvailablePrinterCatalog(), temporary.Path);

        await worker.RunOnceAsync(Settings());

        Assert.AreEqual(1, api.ProcessingCalls);
        Assert.AreEqual(0, driver.PrintCalls);
    }

    [TestMethod]
    public async Task ConcurrentPollingDoesNotDuplicateAJob()
    {
        using var temporary = new TemporaryDirectory();
        var api = new FakeApi { NextJob = Job(1), ClaimedJob = Job(1) with { Status = "processing" }, HeartbeatDelay = TimeSpan.FromMilliseconds(80) };
        var driver = new RecordingPrintDriver(PrintResult.Success("printed"));
        var worker = Worker(api, driver, new AvailablePrinterCatalog(), temporary.Path);

        await Task.WhenAll(worker.RunOnceAsync(Settings()), worker.RunOnceAsync(Settings()));

        Assert.AreEqual(1, driver.PrintCalls);
        Assert.AreEqual(1, api.ProcessingCalls);
    }

    [TestMethod]
    public async Task CompletedReportIsRetriedWithoutReprintingAfterConnectionFailure()
    {
        using var temporary = new TemporaryDirectory();
        var api = new FakeApi
        {
            NextJob = Job(1),
            ClaimedJob = Job(1) with { Status = "processing" },
            CompletedException = new HttpRequestException("offline after print"),
        };
        var driver = new RecordingPrintDriver(PrintResult.Success("printed"));
        var worker = Worker(api, driver, new AvailablePrinterCatalog(), temporary.Path);

        await worker.RunOnceAsync(Settings());
        api.CompletedException = null;
        api.NextJob = null;
        await worker.RunOnceAsync(Settings());

        Assert.AreEqual(1, driver.PrintCalls);
        Assert.AreEqual(2, api.CompletedCalls);
    }

    [TestMethod]
    public async Task TestPrintUsesSelectedPrinter()
    {
        var driver = new RecordingPrintDriver(PrintResult.Success("test ok"));

        var result = await driver.PrintTestAsync("T-IM5003");

        Assert.IsTrue(result.Succeeded);
        Assert.AreEqual("T-IM5003", driver.LastTestPrinter);
    }

    private static AgentWorker Worker(FakeApi api, IPrintDriver driver, IPrinterCatalog catalog, string path) => new(
        _ => api,
        catalog,
        driver,
        new PendingReportStore(Path.Combine(path, "pending.json")),
        new NullLogger());

    private static AppSettings Settings() => new("https://tikshop.test", "token", "T-IM5003", 5, false);

    private static PrintJob Job(int copies) => new()
    {
        Ulid = "01JOB",
        Type = "package_ticket",
        Status = "pending",
        Payload = new TicketSnapshot { TrackingCode = "TIK-1", Copies = copies },
    };

    private sealed class FakeApi : IPrintAgentApi
    {
        public PrintJob? NextJob { get; set; }
        public PrintJob? ClaimedJob { get; set; }
        public Exception? HeartbeatException { get; set; }
        public Exception? CompletedException { get; set; }
        public TimeSpan HeartbeatDelay { get; set; }
        public int ProcessingCalls { get; private set; }
        public int CompletedCalls { get; private set; }
        public int FailedCalls { get; private set; }
        public string? LastFailure { get; private set; }

        public async Task HeartbeatAsync(CancellationToken cancellationToken)
        {
            if (HeartbeatDelay > TimeSpan.Zero)
            {
                await Task.Delay(HeartbeatDelay, cancellationToken);
            }

            if (HeartbeatException is not null)
            {
                throw HeartbeatException;
            }
        }

        public Task<PrintJob?> GetNextJobAsync(CancellationToken cancellationToken) => Task.FromResult(NextJob);

        public Task<PrintJob?> MarkProcessingAsync(string ulid, CancellationToken cancellationToken)
        {
            ProcessingCalls++;
            return Task.FromResult(ClaimedJob);
        }

        public Task MarkCompletedAsync(string ulid, string resultMessage, CancellationToken cancellationToken)
        {
            CompletedCalls++;
            if (CompletedException is not null)
            {
                throw CompletedException;
            }

            return Task.CompletedTask;
        }

        public Task MarkFailedAsync(string ulid, string errorMessage, CancellationToken cancellationToken)
        {
            FailedCalls++;
            LastFailure = errorMessage;
            return Task.CompletedTask;
        }
    }

    private sealed class RecordingPrintDriver(PrintResult result) : IPrintDriver
    {
        public int PrintCalls { get; private set; }
        public int LastCopies { get; private set; }
        public string? LastTestPrinter { get; private set; }

        public Task<PrintResult> PrintTestAsync(string printerName, CancellationToken cancellationToken = default)
        {
            LastTestPrinter = printerName;
            return Task.FromResult(result);
        }

        public Task<PrintResult> PrintAsync(PrintJob job, string printerName, int copies, CancellationToken cancellationToken = default)
        {
            PrintCalls++;
            LastCopies = copies;
            return Task.FromResult(result);
        }
    }

    private sealed class AvailablePrinterCatalog : IPrinterCatalog
    {
        public IReadOnlyList<string> GetInstalledPrinters() => ["T-IM5003"];
        public PrinterAvailability Check(string printerName) => new(true, "Available");
    }

    private sealed class MissingPrinterCatalog : IPrinterCatalog
    {
        public IReadOnlyList<string> GetInstalledPrinters() => [];
        public PrinterAvailability Check(string printerName) => new(false, "Printer not installed");
    }

    private sealed class NullLogger : IAgentLogger
    {
        public void Info(string message) { }
        public void Error(string message, Exception? exception = null) { }
    }
}
