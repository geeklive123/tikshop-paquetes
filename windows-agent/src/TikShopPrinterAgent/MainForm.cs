using TikShopPrinterAgent.Core;
using TikShopPrinterAgent.Core.Models;

namespace TikShopPrinterAgent;

public sealed class MainForm : Form
{
    private readonly IConfigurationStore configurationStore;
    private readonly IPrinterCatalog printerCatalog;
    private readonly IPrintDriver printDriver;
    private readonly AgentWorker worker;
    private readonly IAgentLogger logger;
    private readonly bool startMinimized;
    private readonly Action<string> tokenChanged;
    private readonly TextBox serverTextBox = new();
    private readonly TextBox tokenTextBox = new();
    private readonly ComboBox printerComboBox = new();
    private readonly NumericUpDown pollInterval = new();
    private readonly CheckBox startWithWindows = new();
    private readonly Label serverStatus = new();
    private readonly Label printerStatus = new();
    private readonly Label lastCommunication = new();
    private readonly Label lastPrint = new();
    private readonly Button startButton = new();
    private readonly Button stopButton = new();
    private readonly NotifyIcon trayIcon = new();

    public MainForm(
        IConfigurationStore configurationStore,
        IPrinterCatalog printerCatalog,
        IPrintDriver printDriver,
        AgentWorker worker,
        IAgentLogger logger,
        bool startMinimized,
        Action<string> tokenChanged)
    {
        this.configurationStore = configurationStore;
        this.printerCatalog = printerCatalog;
        this.printDriver = printDriver;
        this.worker = worker;
        this.logger = logger;
        this.startMinimized = startMinimized;
        this.tokenChanged = tokenChanged;
        worker.StatusChanged += WorkerOnStatusChanged;

        Text = "TikShop Printer Agent";
        StartPosition = FormStartPosition.CenterScreen;
        MinimumSize = new Size(680, 610);
        Size = new Size(720, 650);
        Font = new Font("Segoe UI", 9F);
        Icon = SystemIcons.Application;
        BuildInterface();

        trayIcon.Icon = SystemIcons.Application;
        trayIcon.Text = "TikShop Printer Agent";
        trayIcon.Visible = true;
        trayIcon.DoubleClick += (_, _) => RestoreFromTray();
        Resize += (_, _) =>
        {
            if (WindowState == FormWindowState.Minimized)
            {
                ShowInTaskbar = false;
            }
        };
    }

    protected override async void OnLoad(EventArgs eventArgs)
    {
        base.OnLoad(eventArgs);
        await LoadSettingsAsync();

        if (startMinimized)
        {
            WindowState = FormWindowState.Minimized;
            ShowInTaskbar = false;
            var settings = ReadSettings();
            if (settings.IsValid)
            {
                worker.Start(settings);
                UpdateButtons();
            }
        }
    }

    protected override void OnFormClosing(FormClosingEventArgs eventArgs)
    {
        worker.StopAsync().GetAwaiter().GetResult();
        trayIcon.Visible = false;
        trayIcon.Dispose();
        base.OnFormClosing(eventArgs);
    }

    private void BuildInterface()
    {
        var root = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            Padding = new Padding(24),
            ColumnCount = 2,
            RowCount = 12,
            AutoScroll = true,
        };
        root.ColumnStyles.Add(new ColumnStyle(SizeType.Absolute, 160));
        root.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100));

        var title = new Label
        {
            Text = "TikShop Printer Agent",
            Font = new Font("Segoe UI", 18, FontStyle.Bold),
            AutoSize = true,
            Margin = new Padding(0, 0, 0, 18),
        };
        root.Controls.Add(title, 0, 0);
        root.SetColumnSpan(title, 2);

        serverTextBox.Dock = DockStyle.Fill;
        tokenTextBox.Dock = DockStyle.Fill;
        tokenTextBox.UseSystemPasswordChar = true;
        printerComboBox.Dock = DockStyle.Fill;
        printerComboBox.DropDownStyle = ComboBoxStyle.DropDownList;
        pollInterval.Minimum = 2;
        pollInterval.Maximum = 300;
        pollInterval.Value = 5;
        pollInterval.Width = 90;
        startWithWindows.Text = "Iniciar con Windows";
        startWithWindows.AutoSize = true;

        AddRow(root, 1, "Servidor:", serverTextBox);
        AddRow(root, 2, "Token:", tokenTextBox);
        AddRow(root, 3, "Impresora:", printerComboBox);
        AddRow(root, 4, "Intervalo (segundos):", pollInterval);
        AddRow(root, 5, string.Empty, startWithWindows);

        serverStatus.Text = "Desconectado";
        printerStatus.Text = "No disponible";
        lastCommunication.Text = "Nunca";
        lastPrint.Text = "Nunca";
        AddRow(root, 6, "Estado servidor:", serverStatus);
        AddRow(root, 7, "Estado impresora:", printerStatus);
        AddRow(root, 8, "Última comunicación:", lastCommunication);
        AddRow(root, 9, "Última impresión:", lastPrint);

        var buttons = new FlowLayoutPanel
        {
            Dock = DockStyle.Fill,
            AutoSize = true,
            FlowDirection = FlowDirection.LeftToRight,
            WrapContents = true,
            Padding = new Padding(0, 16, 0, 0),
        };
        buttons.Controls.Add(CreateButton("Guardar", SaveButtonOnClick));
        buttons.Controls.Add(CreateButton("Probar conexión con Tik Shop", TestConnectionButtonOnClick));
        buttons.Controls.Add(CreateButton("Imprimir prueba", TestPrintButtonOnClick));
        startButton.Text = "Iniciar agente";
        startButton.AutoSize = true;
        startButton.Click += StartButtonOnClick;
        stopButton.Text = "Detener agente";
        stopButton.AutoSize = true;
        stopButton.Enabled = false;
        stopButton.Click += StopButtonOnClick;
        buttons.Controls.Add(startButton);
        buttons.Controls.Add(stopButton);
        root.Controls.Add(buttons, 0, 10);
        root.SetColumnSpan(buttons, 2);

        var note = new Label
        {
            Text = "La impresión usa la cola y el driver instalados en Windows. El corte automático depende de la configuración del driver T-IM5003.",
            ForeColor = Color.DimGray,
            AutoSize = true,
            MaximumSize = new Size(620, 0),
            Margin = new Padding(0, 18, 0, 0),
        };
        root.Controls.Add(note, 0, 11);
        root.SetColumnSpan(note, 2);
        Controls.Add(root);
    }

    private static void AddRow(TableLayoutPanel panel, int row, string label, Control control)
    {
        panel.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        var caption = new Label { Text = label, AutoSize = true, Anchor = AnchorStyles.Left, Margin = new Padding(0, 8, 8, 10) };
        control.Margin = new Padding(0, 4, 0, 10);
        panel.Controls.Add(caption, 0, row);
        panel.Controls.Add(control, 1, row);
    }

    private static Button CreateButton(string text, EventHandler handler)
    {
        var button = new Button { Text = text, AutoSize = true, Margin = new Padding(0, 0, 8, 8) };
        button.Click += handler;
        return button;
    }

    private async Task LoadSettingsAsync()
    {
        try
        {
            var settings = await configurationStore.LoadAsync();
            serverTextBox.Text = settings.ServerUrl;
            tokenTextBox.Text = settings.AgentToken;
            tokenChanged(settings.AgentToken);
            pollInterval.Value = settings.PollIntervalSeconds;
            startWithWindows.Checked = settings.StartWithWindows;
            RefreshPrinters(settings.WindowsPrinterName);
            UpdatePrinterStatus();
        }
        catch (Exception exception)
        {
            logger.Error("No se pudo cargar la configuración.", exception);
            ShowError(exception.Message);
        }
    }

    private void RefreshPrinters(string selectedPrinter)
    {
        printerComboBox.Items.Clear();
        foreach (var printer in printerCatalog.GetInstalledPrinters())
        {
            printerComboBox.Items.Add(printer);
        }

        if (!string.IsNullOrWhiteSpace(selectedPrinter) && printerComboBox.Items.Contains(selectedPrinter))
        {
            printerComboBox.SelectedItem = selectedPrinter;
        }
        else if (printerComboBox.Items.Count > 0)
        {
            printerComboBox.SelectedIndex = 0;
        }
    }

    private AppSettings ReadSettings() => new(
        serverTextBox.Text.Trim().TrimEnd('/'),
        tokenTextBox.Text.Trim(),
        printerComboBox.SelectedItem?.ToString() ?? string.Empty,
        decimal.ToInt32(pollInterval.Value),
        startWithWindows.Checked);

    private async void SaveButtonOnClick(object? sender, EventArgs eventArgs)
    {
        try
        {
            var settings = ReadSettings();
            ValidateSettings(settings, requireComplete: false);
            await configurationStore.SaveAsync(settings);
            StartupManager.SetEnabled(settings.StartWithWindows);
            tokenChanged(settings.AgentToken);
            MessageBox.Show("Configuración guardada.", Text, MessageBoxButtons.OK, MessageBoxIcon.Information);
        }
        catch (Exception exception)
        {
            logger.Error("No se pudo guardar la configuración.", exception);
            ShowError(exception.Message);
        }
    }

    private async void TestConnectionButtonOnClick(object? sender, EventArgs eventArgs)
    {
        try
        {
            var settings = ReadSettings();
            ValidateSettings(settings, requireComplete: true);
            using var httpClient = new HttpClient();
            await new PrintAgentApiClient(httpClient, settings).HeartbeatAsync(CancellationToken.None);
            serverStatus.Text = "Conectado";
            lastCommunication.Text = DateTime.Now.ToString("dd/MM/yyyy HH:mm:ss");
            MessageBox.Show("Conexión con Tik Shop correcta.", Text, MessageBoxButtons.OK, MessageBoxIcon.Information);
        }
        catch (AgentUnauthorizedException)
        {
            serverStatus.Text = "No autorizado (HTTP 401)";
            ShowError("Tik Shop rechazó el token. Verifique que el agente esté activo y copie nuevamente el token.");
        }
        catch (Exception exception)
        {
            serverStatus.Text = "Desconectado";
            logger.Error("Falló la prueba de conexión.", exception);
            ShowError(exception.Message);
        }
    }

    private async void TestPrintButtonOnClick(object? sender, EventArgs eventArgs)
    {
        try
        {
            var printerName = printerComboBox.SelectedItem?.ToString() ?? string.Empty;
            var availability = printerCatalog.Check(printerName);
            if (!availability.IsAvailable)
            {
                throw new InvalidOperationException(availability.Message);
            }

            var result = await Task.Run(() => printDriver.PrintTestAsync(printerName));
            lastPrint.Text = $"{DateTime.Now:dd/MM/yyyy HH:mm:ss} - {result.Message}";
            printerStatus.Text = result.Succeeded ? "Disponible" : "No disponible";

            if (!result.Succeeded)
            {
                throw new InvalidOperationException(result.Message);
            }

            MessageBox.Show(result.Message, Text, MessageBoxButtons.OK, MessageBoxIcon.Information);
        }
        catch (Exception exception)
        {
            logger.Error("Falló la impresión de prueba.", exception);
            lastPrint.Text = $"{DateTime.Now:dd/MM/yyyy HH:mm:ss} - Error: {exception.Message}";
            ShowError(exception.Message);
        }
    }

    private void StartButtonOnClick(object? sender, EventArgs eventArgs)
    {
        try
        {
            var settings = ReadSettings();
            ValidateSettings(settings, requireComplete: true);
            worker.Start(settings);
            UpdateButtons();
        }
        catch (Exception exception)
        {
            ShowError(exception.Message);
        }
    }

    private async void StopButtonOnClick(object? sender, EventArgs eventArgs)
    {
        await worker.StopAsync();
        UpdateButtons();
    }

    private void WorkerOnStatusChanged(object? sender, AgentStatus status)
    {
        if (InvokeRequired)
        {
            BeginInvoke(() => WorkerOnStatusChanged(sender, status));
            return;
        }

        serverStatus.Text = status.ServerMessage;
        printerStatus.Text = status.PrinterMessage;
        if (status.LastCommunication is not null)
        {
            lastCommunication.Text = status.LastCommunication.Value.LocalDateTime.ToString("dd/MM/yyyy HH:mm:ss");
        }

        if (status.LastPrint is not null)
        {
            lastPrint.Text = $"{status.LastPrint.Value.LocalDateTime:dd/MM/yyyy HH:mm:ss} - {status.LastPrintResult}";
        }

        UpdateButtons();
    }

    private void UpdatePrinterStatus()
    {
        var availability = printerCatalog.Check(printerComboBox.SelectedItem?.ToString() ?? string.Empty);
        printerStatus.Text = availability.Message;
    }

    private void UpdateButtons()
    {
        startButton.Enabled = !worker.IsRunning;
        stopButton.Enabled = worker.IsRunning;
    }

    private static void ValidateSettings(AppSettings settings, bool requireComplete)
    {
        if (!Uri.TryCreate(settings.ServerUrl, UriKind.Absolute, out var uri)
            || (uri.Scheme != Uri.UriSchemeHttps && !uri.IsLoopback))
        {
            throw new InvalidOperationException("Use una URL HTTPS válida. HTTP solo se permite para localhost.");
        }

        if (requireComplete && string.IsNullOrWhiteSpace(settings.AgentToken))
        {
            throw new InvalidOperationException("Ingrese el token del agente.");
        }

        if (requireComplete && string.IsNullOrWhiteSpace(settings.WindowsPrinterName))
        {
            throw new InvalidOperationException("Seleccione una impresora instalada en Windows.");
        }
    }

    private void RestoreFromTray()
    {
        ShowInTaskbar = true;
        WindowState = FormWindowState.Normal;
        Activate();
    }

    private void ShowError(string message) =>
        MessageBox.Show(message, Text, MessageBoxButtons.OK, MessageBoxIcon.Error);
}
