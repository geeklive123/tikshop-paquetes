<?php

namespace App\Enums;

enum PackageEventType: string
{
    case PackageCreated = 'package_created';
    case QrGenerated = 'qr_generated';
    case QrRegenerated = 'qr_regenerated';
    case QrScanned = 'qr_scanned';
    case PackageDelivered = 'package_delivered';
    case PackageUpdated = 'package_updated';
    case PackageCancelled = 'package_cancelled';
}
