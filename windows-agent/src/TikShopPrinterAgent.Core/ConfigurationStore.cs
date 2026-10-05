using System.Text.Json;
using System.Text.Json.Serialization;
using TikShopPrinterAgent.Core.Models;

namespace TikShopPrinterAgent.Core;

public sealed class ConfigurationStore(string filePath, ITokenProtector tokenProtector) : IConfigurationStore
{
    private static readonly JsonSerializerOptions JsonOptions = new() { WriteIndented = true };

    public async Task<AppSettings> LoadAsync(CancellationToken cancellationToken = default)
    {
        if (!File.Exists(filePath))
        {
            return AppSettings.Default;
        }

        await using var stream = File.OpenRead(filePath);
        var stored = await JsonSerializer.DeserializeAsync<StoredSettings>(stream, JsonOptions, cancellationToken);

        if (stored is null)
        {
            return AppSettings.Default;
        }

        var token = string.IsNullOrWhiteSpace(stored.AgentTokenProtected)
            ? string.Empty
            : tokenProtector.Unprotect(stored.AgentTokenProtected);

        return new AppSettings(
            stored.ServerUrl ?? AppSettings.Default.ServerUrl,
            token,
            stored.WindowsPrinterName ?? string.Empty,
            Math.Clamp(stored.PollIntervalSeconds, 2, 300),
            stored.StartWithWindows);
    }

    public async Task SaveAsync(AppSettings settings, CancellationToken cancellationToken = default)
    {
        var directory = Path.GetDirectoryName(filePath)
            ?? throw new InvalidOperationException("La ruta de configuración no tiene directorio.");
        Directory.CreateDirectory(directory);

        var stored = new StoredSettings
        {
            ServerUrl = settings.ServerUrl.TrimEnd('/'),
            AgentTokenProtected = tokenProtector.Protect(settings.AgentToken),
            WindowsPrinterName = settings.WindowsPrinterName,
            PollIntervalSeconds = settings.PollIntervalSeconds,
            StartWithWindows = settings.StartWithWindows,
        };
        var temporaryPath = filePath + ".tmp";

        await using (var stream = File.Create(temporaryPath))
        {
            await JsonSerializer.SerializeAsync(stream, stored, JsonOptions, cancellationToken);
        }

        File.Move(temporaryPath, filePath, true);
    }

    private sealed class StoredSettings
    {
        [JsonPropertyName("server_url")]
        public string? ServerUrl { get; init; }

        [JsonPropertyName("agent_token_protected")]
        public string? AgentTokenProtected { get; init; }

        [JsonPropertyName("windows_printer_name")]
        public string? WindowsPrinterName { get; init; }

        [JsonPropertyName("poll_interval_seconds")]
        public int PollIntervalSeconds { get; init; } = 5;

        [JsonPropertyName("start_with_windows")]
        public bool StartWithWindows { get; init; }
    }
}
