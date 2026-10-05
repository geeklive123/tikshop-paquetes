namespace TikShopPrinterAgent.Core.Models;

public sealed record AgentStatus(
    bool IsRunning,
    bool ServerConnected,
    string ServerMessage,
    bool PrinterAvailable,
    string PrinterMessage,
    DateTimeOffset? LastCommunication,
    DateTimeOffset? LastPrint,
    string? LastPrintResult);
