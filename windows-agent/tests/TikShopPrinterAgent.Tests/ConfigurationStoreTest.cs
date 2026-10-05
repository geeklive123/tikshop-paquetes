using System.Text;
using TikShopPrinterAgent.Core;
using TikShopPrinterAgent.Core.Models;

namespace TikShopPrinterAgent.Tests;

[TestClass]
public sealed class ConfigurationStoreTest
{
    [TestMethod]
    public async Task SaveAndLoadPreservesSettingsWithoutPlainTokenOnDisk()
    {
        using var temporary = new TemporaryDirectory();
        var path = Path.Combine(temporary.Path, "settings.json");
        var store = new ConfigurationStore(path, new FakeTokenProtector());
        var expected = new AppSettings("https://tikshop.test", "super-secret-token", "T-IM5003", 7, true);

        await store.SaveAsync(expected);
        var actual = await store.LoadAsync();
        var contents = await File.ReadAllTextAsync(path);

        Assert.AreEqual(expected, actual);
        Assert.IsFalse(contents.Contains(expected.AgentToken, StringComparison.Ordinal));
        StringAssert.Contains(contents, "agent_token_protected");
    }

    private sealed class FakeTokenProtector : ITokenProtector
    {
        public string Protect(string plainText) => Convert.ToBase64String(Encoding.UTF8.GetBytes(plainText));
        public string Unprotect(string protectedText) => Encoding.UTF8.GetString(Convert.FromBase64String(protectedText));
    }
}
