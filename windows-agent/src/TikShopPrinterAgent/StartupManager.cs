using Microsoft.Win32;

namespace TikShopPrinterAgent;

public static class StartupManager
{
    private const string RegistryPath = @"Software\Microsoft\Windows\CurrentVersion\Run";
    private const string ValueName = "TikShopPrinterAgent";

    public static void SetEnabled(bool enabled)
    {
        using var key = Registry.CurrentUser.OpenSubKey(RegistryPath, true)
            ?? Registry.CurrentUser.CreateSubKey(RegistryPath);

        if (enabled)
        {
            key.SetValue(ValueName, $"\"{Application.ExecutablePath}\" --autostart");
        }
        else
        {
            key.DeleteValue(ValueName, false);
        }
    }
}
