using System.Text.Json.Serialization;

namespace TikShopPrinterAgent.Core.Models;

public sealed record PrintJob
{
    [JsonPropertyName("ulid")]
    public required string Ulid { get; init; }

    [JsonPropertyName("type")]
    public required string Type { get; init; }

    [JsonPropertyName("status")]
    public required string Status { get; init; }

    [JsonPropertyName("attempts")]
    public int Attempts { get; init; }

    [JsonPropertyName("payload")]
    public required TicketSnapshot Payload { get; init; }

    [JsonPropertyName("printer")]
    public PrinterSnapshot? Printer { get; init; }
}

public sealed record TicketSnapshot
{
    [JsonPropertyName("tracking_code")]
    public required string TrackingCode { get; init; }

    [JsonPropertyName("branch_name")]
    public string? BranchName { get; init; }

    [JsonPropertyName("branch_address")]
    public string? BranchAddress { get; init; }

    [JsonPropertyName("storage_code")]
    public string? StorageCode { get; init; }

    [JsonPropertyName("category_name")]
    public string? CategoryName { get; init; }

    [JsonPropertyName("sender_name")]
    public string? SenderName { get; init; }

    [JsonPropertyName("recipient_name")]
    public string? RecipientName { get; init; }

    [JsonPropertyName("recipient_phone")]
    public string? RecipientPhone { get; init; }

    [JsonPropertyName("status")]
    public string? Status { get; init; }

    [JsonPropertyName("status_label")]
    public string? StatusLabel { get; init; }

    [JsonPropertyName("description")]
    public string? Description { get; init; }

    [JsonPropertyName("storage_price")]
    public string? StoragePrice { get; init; }

    [JsonPropertyName("received_at")]
    public DateTimeOffset? ReceivedAt { get; init; }

    [JsonPropertyName("qr_data_uri")]
    public string? QrDataUri { get; init; }

    [JsonPropertyName("logo_data_uri")]
    public string? LogoDataUri { get; init; }

    [JsonPropertyName("copies")]
    public int Copies { get; init; } = 1;
}

public sealed record PrinterSnapshot
{
    [JsonPropertyName("name")]
    public string? Name { get; init; }

    [JsonPropertyName("paper_width")]
    public int PaperWidth { get; init; } = 80;
}
