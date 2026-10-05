using System.Net;
using System.Net.Http.Headers;
using System.Net.Http.Json;
using System.Text.Json;
using System.Text.Json.Serialization;
using TikShopPrinterAgent.Core.Models;

namespace TikShopPrinterAgent.Core;

public interface IPrintAgentApi
{
    Task HeartbeatAsync(CancellationToken cancellationToken);
    Task<PrintJob?> GetNextJobAsync(CancellationToken cancellationToken);
    Task<PrintJob?> MarkProcessingAsync(string ulid, CancellationToken cancellationToken);
    Task MarkCompletedAsync(string ulid, string resultMessage, CancellationToken cancellationToken);
    Task MarkFailedAsync(string ulid, string errorMessage, CancellationToken cancellationToken);
}

public sealed class PrintAgentApiClient : IPrintAgentApi, IDisposable
{
    private static readonly JsonSerializerOptions JsonOptions = new(JsonSerializerDefaults.Web);
    private readonly HttpClient httpClient;

    public PrintAgentApiClient(HttpClient httpClient, AppSettings settings)
    {
        this.httpClient = httpClient;
        this.httpClient.BaseAddress = new Uri(settings.ServerUrl.TrimEnd('/') + "/");
        this.httpClient.Timeout = TimeSpan.FromSeconds(15);
        this.httpClient.DefaultRequestHeaders.Authorization = new AuthenticationHeaderValue("Bearer", settings.AgentToken);
        this.httpClient.DefaultRequestHeaders.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));
    }

    public async Task HeartbeatAsync(CancellationToken cancellationToken)
    {
        using var response = await httpClient.PostAsync("api/v1/print-agent/heartbeat", null, cancellationToken);
        await EnsureSuccessAsync(response, cancellationToken);
    }

    public async Task<PrintJob?> GetNextJobAsync(CancellationToken cancellationToken)
    {
        using var response = await httpClient.GetAsync("api/v1/print-agent/jobs/next", cancellationToken);

        if (response.StatusCode == HttpStatusCode.NoContent)
        {
            return null;
        }

        await EnsureSuccessAsync(response, cancellationToken);
        var resource = await response.Content.ReadFromJsonAsync<ApiResource<PrintJob>>(JsonOptions, cancellationToken);

        return resource?.Data;
    }

    public async Task<PrintJob?> MarkProcessingAsync(string ulid, CancellationToken cancellationToken)
    {
        using var response = await httpClient.PostAsync($"api/v1/print-agent/jobs/{Uri.EscapeDataString(ulid)}/processing", null, cancellationToken);

        if (response.StatusCode == HttpStatusCode.Conflict || response.StatusCode == HttpStatusCode.NotFound)
        {
            return null;
        }

        await EnsureSuccessAsync(response, cancellationToken);
        var resource = await response.Content.ReadFromJsonAsync<ApiResource<PrintJob>>(JsonOptions, cancellationToken);

        return resource?.Data;
    }

    public async Task MarkCompletedAsync(string ulid, string resultMessage, CancellationToken cancellationToken)
    {
        using var response = await httpClient.PostAsJsonAsync(
            $"api/v1/print-agent/jobs/{Uri.EscapeDataString(ulid)}/completed",
            new { result_message = resultMessage },
            JsonOptions,
            cancellationToken);
        await EnsureSuccessAsync(response, cancellationToken);
    }

    public async Task MarkFailedAsync(string ulid, string errorMessage, CancellationToken cancellationToken)
    {
        using var response = await httpClient.PostAsJsonAsync(
            $"api/v1/print-agent/jobs/{Uri.EscapeDataString(ulid)}/failed",
            new { error_message = errorMessage },
            JsonOptions,
            cancellationToken);
        await EnsureSuccessAsync(response, cancellationToken);
    }

    private static async Task EnsureSuccessAsync(HttpResponseMessage response, CancellationToken cancellationToken)
    {
        if (response.StatusCode == HttpStatusCode.Unauthorized)
        {
            throw new AgentUnauthorizedException("El servidor rechazó el token del agente (HTTP 401).");
        }

        if (response.IsSuccessStatusCode)
        {
            return;
        }

        var content = await response.Content.ReadAsStringAsync(cancellationToken);
        throw new HttpRequestException(
            $"Tik Shop respondió HTTP {(int)response.StatusCode}. {content}",
            null,
            response.StatusCode);
    }

    public void Dispose() => httpClient.Dispose();

    private sealed record ApiResource<T>([property: JsonPropertyName("data")] T Data);
}

public sealed class AgentUnauthorizedException(string message) : Exception(message);
