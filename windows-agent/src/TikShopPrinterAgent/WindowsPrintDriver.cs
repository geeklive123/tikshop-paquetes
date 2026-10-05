using System.Drawing;
using System.Drawing.Drawing2D;
using System.Drawing.Printing;
using System.Globalization;
using TikShopPrinterAgent.Core;
using TikShopPrinterAgent.Core.Models;

namespace TikShopPrinterAgent;

public sealed class WindowsPrintDriver : IPrintDriver
{
    public Task<PrintResult> PrintTestAsync(string printerName, CancellationToken cancellationToken = default)
    {
        cancellationToken.ThrowIfCancellationRequested();
        using var document = CreateDocument(printerName, 1, 430);
        document.DocumentName = "Tik Shop - Prueba de impresión";
        document.PrintPage += (_, args) => DrawTestPage(
            args.Graphics ?? throw new InvalidOperationException("Windows no proporcionó un contexto de impresión."),
            printerName);
        document.Print();
        return Task.FromResult(PrintResult.Success("Prueba enviada correctamente a la cola de Windows."));
    }

    public Task<PrintResult> PrintAsync(PrintJob job, string printerName, int copies, CancellationToken cancellationToken = default)
    {
        cancellationToken.ThrowIfCancellationRequested();
        copies = Math.Clamp(copies, 1, 2);
        using var document = CreateDocument(printerName, copies, CalculatePageHeight(job.Payload));
        document.DocumentName = $"Tik Shop - {job.Payload.TrackingCode}";
        document.PrintPage += (_, args) => DrawTicket(
            args.Graphics ?? throw new InvalidOperationException("Windows no proporcionó un contexto de impresión."),
            job.Payload);
        document.Print();
        return Task.FromResult(PrintResult.Success($"{copies} copia(s) enviada(s) a la cola de Windows '{printerName}'."));
    }

    private static PrintDocument CreateDocument(string printerName, int copies, int height)
    {
        var document = new PrintDocument
        {
            PrintController = new StandardPrintController(),
            OriginAtMargins = false,
        };
        document.PrinterSettings.PrinterName = printerName;

        if (!document.PrinterSettings.IsValid)
        {
            var exception = new InvalidPrinterException(document.PrinterSettings);
            document.Dispose();
            throw exception;
        }

        document.PrinterSettings.Copies = (short)copies;
        document.PrinterSettings.Collate = true;
        document.DefaultPageSettings.Margins = new Margins(0, 0, 0, 0);
        document.DefaultPageSettings.PaperSize = new PaperSize("Tik Shop 80 mm", 315, height);
        return document;
    }

    private static int CalculatePageHeight(TicketSnapshot snapshot)
    {
        var descriptionLines = Math.Max(1, (snapshot.Description?.Length ?? 0) / 38 + 1);
        return Math.Clamp(690 + descriptionLines * 18, 720, 880);
    }

    private static void DrawTestPage(Graphics graphics, string printerName)
    {
        ConfigureGraphics(graphics);
        using var title = new Font("Arial", 18, FontStyle.Bold);
        using var heading = new Font("Arial", 12, FontStyle.Bold);
        using var normal = new Font("Arial", 9);
        using var center = new StringFormat { Alignment = StringAlignment.Center };
        const float width = 291;

        graphics.DrawString("TIK SHOP", title, Brushes.Black, new RectangleF(12, 30, width, 30), center);
        graphics.DrawString("PRUEBA DE IMPRESION", heading, Brushes.Black, new RectangleF(12, 70, width, 24), center);
        graphics.DrawLine(Pens.Black, 12, 108, 303, 108);
        graphics.DrawString(DateTime.Now.ToString("dd/MM/yyyy HH:mm:ss"), normal, Brushes.Black, new RectangleF(12, 125, width, 20), center);
        graphics.DrawString(printerName, normal, Brushes.Black, new RectangleF(12, 155, width, 50), center);
        graphics.DrawString("Papel 80 mm - cola Windows", normal, Brushes.Black, new RectangleF(12, 220, width, 20), center);
    }

    private static void DrawTicket(Graphics graphics, TicketSnapshot ticket)
    {
        ConfigureGraphics(graphics);
        const float left = 12;
        const float width = 291;
        var y = 8f;
        using var brandFont = new Font("Arial", 17, FontStyle.Bold);
        using var headingFont = new Font("Arial", 11, FontStyle.Bold);
        using var normalFont = new Font("Arial", 8.5f);
        using var boldFont = new Font("Arial", 8.5f, FontStyle.Bold);
        using var smallFont = new Font("Arial", 7.2f);
        using var codeFont = new Font("Consolas", 15, FontStyle.Bold);
        using var center = new StringFormat { Alignment = StringAlignment.Center };

        using var logo = DecodeDataUri(ticket.LogoDataUri);
        if (logo is not null)
        {
            var logoHeight = Math.Min(74f, 150f * logo.Height / logo.Width);
            graphics.DrawImage(logo, new RectangleF((315 - 150) / 2f, y, 150, logoHeight));
            y += logoHeight + 4;
        }
        else
        {
            graphics.DrawString("TIK SHOP", brandFont, Brushes.Black, new RectangleF(left, y, width, 26), center);
            y += 30;
        }

        DrawCentered(graphics, ticket.BranchName, boldFont, ref y, 18);
        DrawCentered(graphics, ticket.BranchAddress, smallFont, ref y, 30);
        DrawCentered(graphics, ticket.TrackingCode, codeFont, ref y, 27);
        if (ticket.ReceivedAt is not null)
        {
            DrawCentered(graphics, ticket.ReceivedAt.Value.LocalDateTime.ToString("dd/MM/yyyy HH:mm"), smallFont, ref y, 16);
        }

        y += 4;
        graphics.DrawRectangle(new Pen(Color.Black, 1.5f), left, y, width, 70);
        DrawCentered(graphics, "UBICACION", smallFont, ref y, 16, 4);
        DrawCentered(graphics, ticket.StorageCode, codeFont, ref y, 27);
        DrawCentered(graphics, ticket.CategoryName, boldFont, ref y, 18);
        y += 8;

        DrawRow(graphics, "Remitente", ticket.SenderName, normalFont, boldFont, ref y);
        DrawRow(graphics, "Destinatario", ticket.RecipientName, normalFont, boldFont, ref y);
        DrawRow(graphics, "Celular", ticket.RecipientPhone, normalFont, boldFont, ref y);
        DrawRow(graphics, "Estado", ticket.StatusLabel ?? ticket.Status, normalFont, boldFont, ref y);
        DrawRow(graphics, "Almacenaje", FormatPrice(ticket.StoragePrice), normalFont, boldFont, ref y);

        graphics.DrawLine(Pens.Black, left, y + 3, left + width, y + 3);
        y += 10;
        graphics.DrawString("Descripcion", smallFont, Brushes.Black, left, y);
        y += 14;
        var description = string.IsNullOrWhiteSpace(ticket.Description) ? "Sin descripcion" : ticket.Description;
        var descriptionHeight = graphics.MeasureString(description, normalFont, (int)width).Height;
        graphics.DrawString(description, normalFont, Brushes.Black, new RectangleF(left, y, width, descriptionHeight + 4));
        y += descriptionHeight + 10;

        using var qr = DecodeDataUri(ticket.QrDataUri);
        if (qr is not null)
        {
            const float qrSize = 170;
            graphics.InterpolationMode = InterpolationMode.NearestNeighbor;
            graphics.PixelOffsetMode = PixelOffsetMode.Half;
            graphics.DrawImage(qr, new RectangleF((315 - qrSize) / 2f, y, qrSize, qrSize));
            y += qrSize + 5;
        }

        DrawCentered(graphics, "Presenta este QR al recoger el paquete.", boldFont, ref y, 18);
        DrawCentered(graphics, "El operador verificara los datos antes de confirmar la entrega.", smallFont, ref y, 28);
        DrawCentered(graphics, "El QR deja de funcionar despues de la entrega.", smallFont, ref y, 22);
    }

    private static void ConfigureGraphics(Graphics graphics)
    {
        graphics.PageUnit = GraphicsUnit.Display;
        graphics.TextRenderingHint = System.Drawing.Text.TextRenderingHint.SingleBitPerPixelGridFit;
        graphics.SmoothingMode = SmoothingMode.HighQuality;
    }

    private static void DrawCentered(Graphics graphics, string? text, Font font, ref float y, float height, float paddingTop = 0)
    {
        if (string.IsNullOrWhiteSpace(text))
        {
            return;
        }

        y += paddingTop;
        using var format = new StringFormat { Alignment = StringAlignment.Center };
        graphics.DrawString(text, font, Brushes.Black, new RectangleF(12, y, 291, height), format);
        y += height;
    }

    private static void DrawRow(Graphics graphics, string label, string? value, Font labelFont, Font valueFont, ref float y)
    {
        graphics.DrawString(label, labelFont, Brushes.Black, new RectangleF(12, y, 105, 25));
        using var right = new StringFormat { Alignment = StringAlignment.Far };
        graphics.DrawString(value ?? "-", valueFont, Brushes.Black, new RectangleF(112, y, 191, 25), right);
        y += 24;
    }

    private static string FormatPrice(string? value) =>
        decimal.TryParse(value, NumberStyles.Number, CultureInfo.InvariantCulture, out var amount)
            ? $"Bs {amount:0.00}"
            : value ?? "-";

    private static Image? DecodeDataUri(string? dataUri)
    {
        if (string.IsNullOrWhiteSpace(dataUri))
        {
            return null;
        }

        var separator = dataUri.IndexOf(',');
        if (separator < 0 || !dataUri[..separator].Contains(";base64", StringComparison.OrdinalIgnoreCase))
        {
            return null;
        }

        var bytes = Convert.FromBase64String(dataUri[(separator + 1)..]);
        using var stream = new MemoryStream(bytes);
        using var source = Image.FromStream(stream);
        return new Bitmap(source);
    }
}
