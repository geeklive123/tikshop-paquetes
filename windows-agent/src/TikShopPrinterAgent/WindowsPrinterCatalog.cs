using System.Drawing.Printing;
using System.Runtime.InteropServices;
using TikShopPrinterAgent.Core;

namespace TikShopPrinterAgent;

public sealed class WindowsPrinterCatalog : IPrinterCatalog
{
    private const uint PrinterStatusError = 0x00000002;
    private const uint PrinterStatusPaperOut = 0x00000010;
    private const uint PrinterStatusOffline = 0x00000080;
    private const uint PrinterStatusOutputBinFull = 0x00000800;
    private const uint PrinterStatusNotAvailable = 0x00001000;
    private const uint PrinterStatusDoorOpen = 0x00400000;
    private const uint PrinterStatusUserIntervention = 0x00100000;
    private const uint BlockingStatuses = PrinterStatusError | PrinterStatusPaperOut | PrinterStatusOffline
        | PrinterStatusOutputBinFull | PrinterStatusNotAvailable | PrinterStatusDoorOpen | PrinterStatusUserIntervention;

    public IReadOnlyList<string> GetInstalledPrinters() =>
        PrinterSettings.InstalledPrinters.Cast<string>().OrderBy(name => name).ToArray();

    public PrinterAvailability Check(string printerName)
    {
        if (string.IsNullOrWhiteSpace(printerName) || !GetInstalledPrinters().Contains(printerName, StringComparer.OrdinalIgnoreCase))
        {
            return new PrinterAvailability(false, "La impresora configurada no está instalada en Windows.");
        }

        if (!OpenPrinter(printerName, out var printerHandle, IntPtr.Zero))
        {
            return new PrinterAvailability(false, "Windows no pudo abrir la cola de impresión.");
        }

        try
        {
            var statusInfo = new PrinterInfo6();
            var size = Marshal.SizeOf<PrinterInfo6>();
            var buffer = Marshal.AllocHGlobal(size);
            try
            {
                if (!GetPrinter(printerHandle, 6, buffer, size, out _))
                {
                    return new PrinterAvailability(false, "Windows no pudo consultar el estado de la cola.");
                }

                statusInfo = Marshal.PtrToStructure<PrinterInfo6>(buffer);
                return (statusInfo.Status & BlockingStatuses) == 0
                    ? new PrinterAvailability(true, "Disponible")
                    : new PrinterAvailability(false, $"La cola reporta un error (0x{statusInfo.Status:X8}).");
            }
            finally
            {
                Marshal.FreeHGlobal(buffer);
            }
        }
        finally
        {
            ClosePrinter(printerHandle);
        }
    }

    [StructLayout(LayoutKind.Sequential)]
    private struct PrinterInfo6
    {
        public uint Status;
    }

    [DllImport("winspool.drv", SetLastError = true, CharSet = CharSet.Unicode)]
    private static extern bool OpenPrinter(string printerName, out IntPtr printerHandle, IntPtr defaults);

    [DllImport("winspool.drv", SetLastError = true)]
    private static extern bool GetPrinter(IntPtr printerHandle, uint level, IntPtr printer, int bufferSize, out int needed);

    [DllImport("winspool.drv", SetLastError = true)]
    private static extern bool ClosePrinter(IntPtr printerHandle);
}
