using System.Text.Json;

namespace TikShopPrinterAgent.Core;

public sealed record PendingReport(string JobUlid, bool Completed, string Message);

public interface IPendingReportStore
{
    Task<IReadOnlyList<PendingReport>> LoadAsync(CancellationToken cancellationToken);
    Task SaveAsync(PendingReport report, CancellationToken cancellationToken);
    Task RemoveAsync(string jobUlid, CancellationToken cancellationToken);
}

public sealed class PendingReportStore(string filePath) : IPendingReportStore
{
    private static readonly JsonSerializerOptions JsonOptions = new() { WriteIndented = true };
    private readonly SemaphoreSlim sync = new(1, 1);

    public async Task<IReadOnlyList<PendingReport>> LoadAsync(CancellationToken cancellationToken)
    {
        await sync.WaitAsync(cancellationToken);
        try
        {
            return await LoadUnsafeAsync(cancellationToken);
        }
        finally
        {
            sync.Release();
        }
    }

    public async Task SaveAsync(PendingReport report, CancellationToken cancellationToken)
    {
        await sync.WaitAsync(cancellationToken);
        try
        {
            var reports = (await LoadUnsafeAsync(cancellationToken)).Where(item => item.JobUlid != report.JobUlid).ToList();
            reports.Add(report);
            await WriteUnsafeAsync(reports, cancellationToken);
        }
        finally
        {
            sync.Release();
        }
    }

    public async Task RemoveAsync(string jobUlid, CancellationToken cancellationToken)
    {
        await sync.WaitAsync(cancellationToken);
        try
        {
            var reports = (await LoadUnsafeAsync(cancellationToken)).Where(item => item.JobUlid != jobUlid).ToList();
            await WriteUnsafeAsync(reports, cancellationToken);
        }
        finally
        {
            sync.Release();
        }
    }

    private async Task<List<PendingReport>> LoadUnsafeAsync(CancellationToken cancellationToken)
    {
        if (!File.Exists(filePath))
        {
            return [];
        }

        await using var stream = File.OpenRead(filePath);
        return await JsonSerializer.DeserializeAsync<List<PendingReport>>(stream, JsonOptions, cancellationToken) ?? [];
    }

    private async Task WriteUnsafeAsync(List<PendingReport> reports, CancellationToken cancellationToken)
    {
        Directory.CreateDirectory(Path.GetDirectoryName(filePath)!);
        await using var stream = File.Create(filePath);
        await JsonSerializer.SerializeAsync(stream, reports, JsonOptions, cancellationToken);
    }
}
