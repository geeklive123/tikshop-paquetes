using System.Text.RegularExpressions;

namespace TikShopPrinterAgent.Core;

public sealed partial class SafeFileLogger(string logDirectory, Func<string?> currentToken) : IAgentLogger
{
    private readonly object sync = new();

    public void Info(string message) => Write("INFO", message, null);

    public void Error(string message, Exception? exception = null) => Write("ERROR", message, exception);

    private void Write(string level, string message, Exception? exception)
    {
        Directory.CreateDirectory(logDirectory);
        var safeMessage = Sanitize(message);
        var safeException = exception is null ? string.Empty : $" | {Sanitize(exception.Message)}";
        var line = $"{DateTimeOffset.Now:O} [{level}] {safeMessage}{safeException}{Environment.NewLine}";

        lock (sync)
        {
            File.AppendAllText(Path.Combine(logDirectory, $"agent-{DateTime.Now:yyyy-MM-dd}.log"), line);
        }
    }

    internal string Sanitize(string value)
    {
        var sanitized = BearerRegex().Replace(value, "Bearer [REDACTED]");
        sanitized = DataUriRegex().Replace(sanitized, "data:image/[REDACTED]");
        sanitized = TokenQueryRegex().Replace(sanitized, "$1[REDACTED]");
        var token = currentToken();

        return string.IsNullOrEmpty(token)
            ? sanitized
            : sanitized.Replace(token, "[REDACTED]", StringComparison.Ordinal);
    }

    [GeneratedRegex(@"Bearer\s+[^\s]+", RegexOptions.IgnoreCase)]
    private static partial Regex BearerRegex();

    [GeneratedRegex(@"data:image/[^\s]+", RegexOptions.IgnoreCase)]
    private static partial Regex DataUriRegex();

    [GeneratedRegex(@"([?&](?:token|qr)=)[^&\s]+", RegexOptions.IgnoreCase)]
    private static partial Regex TokenQueryRegex();
}
