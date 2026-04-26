# PromptVault (v2.1.0) 🚀

PromptVault es una plataforma web avanzada diseñada para la gestión, prueba y difusión de prompts de Inteligencia Artificial. La aplicación emplea una arquitectura **MVC (Modelo-Vista-Controller)** personalizada en PHP, enfocada en ofrecer un entorno seguro, escalable y con una experiencia de usuario fluida.

## 📋 Características Principales

* **Galería de Prompts**: Sistema centralizado para visualizar y gestionar prompts con soporte para metadatos detallados.
* **Probador Integrado (Tester)**: Herramienta para ejecutar y validar prompts utilizando la API de Anthropic.
* **Gestión de Usuarios**: Sistema de perfiles con avatares personalizables y control de sesiones seguras.
* **Interacción Social**: Funcionalidades nativas para que la comunidad deje comentarios y valore los prompts mediante likes.
* **Seguridad de Grado de Producción**: 
    * **Protección contra Fuerza Bruta**: Bloqueo automático de acceso tras 5 intentos fallidos durante 15 minutos.
    * **Integridad de Datos**: Implementación de tokens CSRF en formularios críticos.
    * **Control de Abuso**: Rate limiting configurado para la creación de prompts (máx. 10/hora) y comentarios (máx. 30/hora).

## 🛠️ Stack Tecnológico

* **Backend**: PHP 8.x con estructura MVC propia.
* **Base de Datos**: MariaDB / MySQL.
* **Frontend**: JavaScript Vanilla (arquitectura SPA parcial), CSS3 y HTML5.
* **Servidor**: Configuración optimizada mediante Apache y archivos `.htaccess`.

## 🚧 Estado del Desarrollo y Hoja de Ruta

El proyecto se encuentra en una fase activa de evolución. Los siguientes puntos representan las prioridades actuales de desarrollo:

* **Seguridad Crítica**: Implementación del flujo de recuperación de contraseñas y activación de verificación de cuentas por email.
* **Nuevas Funcionalidades**: Finalización del sistema de "Colecciones" y del historial de actividad en los perfiles públicos.
* **Optimización SEO**: Migración de la sección "Explorar" a una ruta PHP independiente para mejorar la indexación en buscadores.
* **Infraestructura**: Configuración de HSTS (HTTP Strict Transport Security) y refinamiento del sistema de logs de errores por categoría.

---
**Versión**: 2.1.0  
**Licencia**: MIT  
**Autor**: Rubén Blasco Armengod
