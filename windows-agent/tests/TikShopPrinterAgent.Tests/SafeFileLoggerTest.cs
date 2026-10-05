using TikShopPrinterAgent.Core;

namespace TikShopPrinterAgent.Tests;

[TestClass]
public sealed class SafeFileLoggerTest
{
    [TestMethod]
    public void LogRedactsAgentTokenBearerHeaderAndQrData()
    {
        using var temporary = new TemporaryDirectory();
        const string token = "plain-agent-token";
        var logger = new SafeFileLogger(temporary.Path, () => token);

        logger.Error($"Authorization: Bearer {token} token={token} data:image/png;base64,QRSECRET");
        var contents = File.ReadAllText(Directory.GetFiles(temporary.Path).Single());

        Assert.IsFalse(contents.Contains(token, StringComparison.Ordinal));
        Assert.IsFalse(contents.Contains("QRSECRET", StringComparison.Ordinal));
        StringAssert.Contains(contents, "[REDACTED]");
    }
}
