using TikShopPrinterAgent.Core.Models;

namespace TikShopPrinterAgent.Core;

public sealed class AgentWorker(
    Func<AppSettings, IPrintAgentApi> apiFactory,
    IPrinterCatalog printerCatalog,
    IPrintDriver printDriver,
    IPendingReportStore pendingReportStore,
    IAgentLogger logger)
{
    private readonly SemaphoreSlim cycleLock = new(1, 1);
    private CancellationTokenSource? cancellation;
    private Task? workerTask;
    private IPrintAgentApi? activeApi;

    public event EventHandler<AgentStatus>? StatusChanged;

    public bool IsRunning => workerTask is { IsCompleted: false };

    public void Start(AppSettings settings)
    {
        if (IsRunning)
        {
            return;
        }

        cancellation = new CancellationTokenSource();
        activeApi = apiFactory(settings);
        workerTask = Task.Run(() => RunLoopAsync(settings, cancellation.Token), cancellation.Token);
        logger.Info("Agente iniciado.");
    }

    public async Task StopAsync()
    {
        if (cancellation is null)
        {
            return;
        }

        cancellation.Cancel();
        try
        {
            if (workerTask is not null)
            {
                await workerTask;
            }
        }
        catch (OperationCanceledException)
        {
        }
        finally
        {
            cancellation.Dispose();
            cancellation = null;
            workerTask = null;
            if (activeApi is IDisposable disposableApi)
            {
                disposableApi.Dispose();
            }
            activeApi = null;
            logger.Info("Agente detenido.");
            PublishStatus(false, false, "Detenido", false, "Sin verificar", null, null, null);
        }
    }

    public async Task RunOnceAsync(AppSettings settings, CancellationToken cancellationToken = default)
    {
        if (!await cycleLock.WaitAsync(0, cancellationToken))
        {
            return;
        }

        try
        {
            var api = activeApi ?? apiFactory(settings);
            await RetryPendingReportsAsync(api, cancellationToken);
            await api.HeartbeatAsync(cancellationToken);
            var communicationAt = DateTimeOffset.Now;
            var availability = printerCatalog.Check(settings.WindowsPrinterName);
            PublishStatus(IsRunning, true, "Conectado", availability.IsAvailable, availability.Message, communicationAt, null, null);
            var pendingJob = await api.GetNextJobAsync(cancellationToken);

            if (pendingJob is null)
            {
                return;
            }

            logger.Info($"Job recibido: {pendingJob.Ulid}.");
            var claimedJob = await api.MarkProcessingAsync(pendingJob.Ulid, cancellationToken);

            if (claimedJob is null)
            {
                logger.Info($"Job {pendingJob.Ulid} ya fue tomado por otro agente.");
                return;
            }

            if (!availability.IsAvailable)
            {
                await ReportFailureAsync(api, claimedJob.Ulid, availability.Message, cancellationToken);
                return;
            }

            var copies = claimedJob.Payload.Copies is 1 or 2 ? claimedJob.Payload.Copies : 1;
            logger.Info($"Impresión iniciada para job {claimedJob.Ulid}; copias: {copies}.");
            PrintResult result;

            try
            {
                result = await printDriver.PrintAsync(claimedJob, settings.WindowsPrinterName, copies, cancellationToken);
            }
            catch (Exception exception) when (exception is not OperationCanceledException)
            {
                result = PrintResult.Failure(exception.Message);
            }

            if (result.Succeeded)
            {
                var report = new PendingReport(claimedJob.Ulid, true, result.Message);
                await pendingReportStore.SaveAsync(report, cancellationToken);
                await api.MarkCompletedAsync(claimedJob.Ulid, result.Message, cancellationToken);
                await pendingReportStore.RemoveAsync(claimedJob.Ulid, cancellationToken);
                logger.Info($"Job completed: {claimedJob.Ulid}.");
            }
            else
            {
                await ReportFailureAsync(api, claimedJob.Ulid, result.Message, cancellationToken);
                logger.Error($"Job failed: {claimedJob.Ulid}. {result.Message}");
            }

            PublishStatus(IsRunning, true, "Conectado", true, availability.Message, DateTimeOffset.Now, DateTimeOffset.Now, result.Message);
        }
        catch (AgentUnauthorizedException exception)
        {
            logger.Error("Autenticación del agente rechazada.", exception);
            PublishStatus(IsRunning, false, "No autorizado (HTTP 401)", false, "Sin verificar", DateTimeOffset.Now, null, null);
        }
        catch (HttpRequestException exception)
        {
            logger.Error("No se pudo comunicar con Tik Shop.", exception);
            PublishStatus(IsRunning, false, "Desconectado", false, "Sin verificar", DateTimeOffset.Now, null, null);
        }
        catch (Exception exception) when (exception is not OperationCanceledException)
        {
            logger.Error("El ciclo del agente falló de forma controlada.", exception);
            PublishStatus(IsRunning, false, "Error", false, "No disponible", DateTimeOffset.Now, null, exception.Message);
        }
        finally
        {
            cycleLock.Release();
        }
    }

    private async Task RunLoopAsync(AppSettings settings, CancellationToken cancellationToken)
    {
        using var timer = new PeriodicTimer(TimeSpan.FromSeconds(settings.PollIntervalSeconds));
        do
        {
            await RunOnceAsync(settings, cancellationToken);
        }
        while (await timer.WaitForNextTickAsync(cancellationToken));
    }

    private async Task RetryPendingReportsAsync(IPrintAgentApi api, CancellationToken cancellationToken)
    {
        foreach (var report in await pendingReportStore.LoadAsync(cancellationToken))
        {
            if (report.Completed)
            {
                await api.MarkCompletedAsync(report.JobUlid, report.Message, cancellationToken);
            }
            else
            {
                await api.MarkFailedAsync(report.JobUlid, report.Message, cancellationToken);
            }

            await pendingReportStore.RemoveAsync(report.JobUlid, cancellationToken);
            logger.Info($"Reporte pendiente enviado para job {report.JobUlid}.");
        }
    }

    private async Task ReportFailureAsync(IPrintAgentApi api, string ulid, string message, CancellationToken cancellationToken)
    {
        var report = new PendingReport(ulid, false, message);
        await pendingReportStore.SaveAsync(report, cancellationToken);
        await api.MarkFailedAsync(ulid, message, cancellationToken);
        await pendingReportStore.RemoveAsync(ulid, cancellationToken);
    }

    private void PublishStatus(
        bool running,
        bool connected,
        string serverMessage,
        bool printerAvailable,
        string printerMessage,
        DateTimeOffset? lastCommunication,
        DateTimeOffset? lastPrint,
        string? lastPrintResult) =>
        StatusChanged?.Invoke(this, new AgentStatus(
            running,
            connected,
            serverMessage,
            printerAvailable,
            printerMessage,
            lastCommunication,
            lastPrint,
            lastPrintResult));
}
