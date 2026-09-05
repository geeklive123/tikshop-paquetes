# Tik Shop Paquetes - Arquitectura Inicial

## Objetivo del MVP

La primera versión del sistema se enfocará únicamente en la recepción y entrega de paquetes mediante códigos QR.

## Flujo de recepción

1. El operador inicia sesión.
2. Registra los datos de la persona que deja el paquete.
3. Registra los datos de la persona que recogerá el paquete.
4. Registra información básica del paquete.
5. El sistema genera un código único de seguimiento.
6. El sistema genera un QR seguro.
7. El paquete queda disponible para ser recogido.

## Flujo de entrega

1. El destinatario presenta el QR.
2. El operador escanea el QR.
3. El sistema valida el token.
4. El sistema muestra la información del paquete.
5. El operador revisa los datos.
6. El operador confirma manualmente la entrega.
7. El paquete cambia al estado entregado.
8. El QR queda inutilizado.

## Estados iniciales del paquete

- received
- ready_for_pickup
- delivered
- cancelled

## Entidades principales

- Company
- Branch
- User
- Package
- PackagePickupToken
- PackageEvent

## Sucursales

Actualmente Tik Shop trabaja con una sola sucursal.

La arquitectura debe permitir tener varias sucursales en el futuro.

En esta primera versión:

- se utilizará una sucursal principal por defecto
- el operador no tendrá que seleccionar sucursal
- la interfaz se mantendrá simple

## Roles

### owner

Acceso completo al sistema.

### admin

Podrá gestionar:

- usuarios
- paquetes
- entregas
- reportes básicos

### operator

Podrá:

- registrar paquetes
- buscar paquetes
- escanear QR
- confirmar entregas

## Seguridad del QR

El QR no debe contener IDs incrementales de base de datos.

Debe utilizarse un token aleatorio seguro.

El token debe ser validado antes de mostrar el paquete.

Una vez entregado el paquete, el token ya no puede utilizarse nuevamente.

Escanear el QR no debe confirmar automáticamente la entrega.

## Auditoría

Las acciones importantes deben quedar registradas.

Ejemplos:

- package_created
- qr_generated
- qr_scanned
- package_delivered
- package_cancelled

## Arquitectura de código

La lógica debe separarse así:

Controller
-> Form Request
-> Action
-> Modelo / Base de datos

Los Controllers deben mantenerse pequeños.

La lógica principal del negocio debe vivir en Actions.

## Futuro

La arquitectura debe permitir agregar posteriormente:

- múltiples sucursales
- pagos
- servicio de delivery
- seguimiento de paquetes
- notificaciones
- WhatsApp
- reportes avanzados
- clientes
- ubicaciones físicas de almacenamiento
- casilleros