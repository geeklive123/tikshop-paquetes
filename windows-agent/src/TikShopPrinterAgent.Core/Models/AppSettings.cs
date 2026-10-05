namespace TikShopPrinterAgent.Core.Models;

public sealed record AppSettings(
    string ServerUrl,
    string AgentToken,
    string WindowsPrinterName,
    int PollIntervalSeconds,
    bool StartWithWindows)
{
    public static AppSettings Default { get; } = new(
        "https://kenkystores28.sg-host.com",
        string.Empty,
        string.Empty,
        5,
        false);

    public bool IsValid =>
        Uri.TryCreate(ServerUrl, UriKind.Absolute, out var uri)
        && (uri.Scheme == Uri.UriSchemeHttps || uri.IsLoopback)
        && !string.IsNullOrWhiteSpace(AgentToken)
        && !string.IsNullOrWhiteSpace(WindowsPrinterName)
        && PollIntervalSeconds is >= 2 and <= 300;
}
