using System.Net;
using System.Text;
using TikShopPrinterAgent.Core;
using TikShopPrinterAgent.Core.Models;

namespace TikShopPrinterAgent.Tests;

[TestClass]
public sealed class PrintAgentApiClientTest
{
    [TestMethod]
    public async Task HeartbeatUsesBearerAuthentication()
    {
        var handler = new StubHttpHandler(request =>
        {
            Assert.AreEqual("Bearer", request.Headers.Authorization?.Scheme);
            Assert.AreEqual("agent-token", request.Headers.Authorization?.Parameter);
            Assert.AreEqual("/api/v1/print-agent/heartbeat", request.RequestUri?.AbsolutePath);
            return Json(HttpStatusCode.OK, "{\"status\":\"ok\"}");
        });
        var client = CreateClient(handler);

        await client.HeartbeatAsync(CancellationToken.None);

        Assert.AreEqual(1, handler.RequestCount);
    }

    [TestMethod]
    public async Task PollingReturnsNullForNoContentAndDeserializesJob()
    {
        var responses = new Queue<HttpResponseMessage>([
            new HttpResponseMessage(HttpStatusCode.NoContent),
            Json(HttpStatusCode.OK, """{"data":{"ulid":"01JOB","type":"package_ticket","status":"pending","attempts":0,"payload":{"tracking_code":"TIK-1","copies":2}}}"""),
        ]);
        var client = CreateClient(new StubHttpHandler(_ => responses.Dequeue()));

        var empty = await client.GetNextJobAsync(CancellationToken.None);
        var job = await client.GetNextJobAsync(CancellationToken.None);

        Assert.IsNull(empty);
        Assert.IsNotNull(job);
        Assert.AreEqual("01JOB", job.Ulid);
        Assert.AreEqual(2, job.Payload.Copies);
    }

    [TestMethod]
    public async Task UnauthorizedResponseRaisesSpecificException()
    {
        var client = CreateClient(new StubHttpHandler(_ => new HttpResponseMessage(HttpStatusCode.Unauthorized)));

        await Assert.ThrowsExceptionAsync<AgentUnauthorizedException>(
            () => client.HeartbeatAsync(CancellationToken.None));
    }

    private static PrintAgentApiClient CreateClient(HttpMessageHandler handler) =>
        new(new HttpClient(handler), new AppSettings("https://tikshop.test", "agent-token", "Printer", 5, false));

    private static HttpResponseMessage Json(HttpStatusCode status, string json) => new(status)
    {
        Content = new StringContent(json, Encoding.UTF8, "application/json"),
    };
}

internal sealed class StubHttpHandler(Func<HttpRequestMessage, HttpResponseMessage> responseFactory) : HttpMessageHandler
{
    public int RequestCount { get; private set; }

    protected override Task<HttpResponseMessage> SendAsync(HttpRequestMessage request, CancellationToken cancellationToken)
    {
        RequestCount++;
        return Task.FromResult(responseFactory(request));
    }
}
