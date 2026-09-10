# Repositorio Inteligente de Documentos con IA

Proyecto académico — Desarrollo de Aplicaciones Empresariales, VI semestre (UTS).
Aplicación web en PHP + MySQL para gestionar un repositorio documental y aplicar
Inteligencia Artificial (clasificación, resumen, extracción de datos y consulta
en lenguaje natural sobre los documentos, con un enfoque RAG simplificado).

## 1. Requisitos

- XAMPP (Apache + PHP 8.1+ + MySQL) — https://www.apachefriends.org/
- Composer — https://getcomposer.org/
- Una clave de API de un modelo de lenguaje (Anthropic Claude u OpenAI)

## 2. Instalación paso a paso

1. **Copiar el proyecto** dentro de la carpeta `htdocs` de XAMPP:
   - Windows: `C:\xampp\htdocs\repositorio-inteligente`
   - Linux: `/opt/lampp/htdocs/repositorio-inteligente`

2. **Instalar dependencias PHP** (abrir una terminal en la carpeta del proyecto):
   ```
   composer install
   ```
   Esto descarga `smalot/pdfparser`, `phpoffice/phpword` y `vlucas/phpdotenv`.

3. **Configurar variables de entorno**:
   - Copiar `.env.example` a `.env`
   - Completar `DB_USER` / `DB_PASS` según tu instalación de MySQL (por defecto en XAMPP: usuario `root`, sin contraseña)
   - Completar `IA_API_KEY` con tu clave de Anthropic u OpenAI, y ajustar `IA_PROVEEDOR` / `IA_MODEL` según corresponda
   - `EMBEDDINGS_API_KEY` es opcional: si se deja vacío, el sistema usa un embedding local simplificado (ver sección 5) para que la búsqueda semántica funcione sin costo durante las pruebas

4. **Crear la base de datos**:
   - Abrir phpMyAdmin (`http://localhost/phpmyadmin`)
   - Crear una base de datos llamada `repositorio_inteligente` (cotejamiento `utf8mb4_general_ci`)
   - Ir a la pestaña "Importar" y seleccionar el archivo `database/schema.sql`
   - Esto crea todas las tablas y un usuario administrador de prueba

5. **Iniciar servicios** desde el panel de control de XAMPP: **Apache** y **MySQL**.

6. **Dar permisos de escritura** a la carpeta `public/uploads/` (para que PHP pueda guardar los archivos cargados).

7. **Acceder a la aplicación**:
   ```
   http://localhost/repositorio-inteligente/public/
   ```

## 3. Usuario de prueba

| Campo     | Valor            |
|-----------|------------------|
| Correo    | admin@demo.com   |
| Contraseña| Admin1234        |
| Rol       | Administrador    |

## 4. Flujo para probar el requisito central de IA

1. Inicia sesión y entra a **Repositorio**.
2. Selecciona una carpeta (o crea una nueva) y sube un PDF, DOCX o TXT.
3. El botón queda deshabilitado mientras el documento se procesa: en ese momento
   ocurre extracción → llamada a la IA → clasificación/resumen/metadatos →
   generación de embeddings → guardado en la base de datos (ver `procesamiento_log`).
4. Haz clic en **Ver** sobre el documento para revisar la categoría, el resumen
   y los metadatos extraídos.
5. Ve a **Preguntar a la IA** y haz una pregunta relacionada con el contenido
   del documento subido. La respuesta se genera solo a partir de tus documentos
   (RAG), citando el archivo fuente.
6. Ve a **Dashboard** para ver los indicadores agregados y, si iniciaste sesión
   como administrador, el registro global de errores y estados.

## 5. Notas técnicas importantes

- **Embeddings sin costo para pruebas**: si no configuras `EMBEDDINGS_API_KEY`,
  `ClienteIA::generarEmbedding()` usa un embedding local basado en hashing de
  palabras (`services/ClienteIA.php`). Esto permite demostrar el flujo completo
  de RAG (fragmentación, vectorización, similitud de coseno) sin depender de una
  API de pago. Para mejorar la calidad semántica, basta con configurar una clave
  de embeddings real (por ejemplo, de OpenAI) sin tocar el resto del código.
- **Procesamiento síncrono**: por simplicidad y alcance académico, el
  procesamiento IA ocurre en la misma petición HTTP de la carga del archivo
  (ver `DocumentoController::subir`). Para un entorno de producción real se
  recomendaría una cola de trabajos en segundo plano.
- **Seguridad**: contraseñas con `password_hash`/`password_verify`, consultas
  con PDO y *prepared statements*, validación de tipo MIME real de los archivos
  cargados, y credenciales fuera del control de versiones (`.env` en `.gitignore`).

## 6. Estructura del proyecto

Ver la sección 3.3 de la documentación de Desarrollo (`Repositorio_Inteligente_Analisis_Diseno.docx`)
para el detalle completo de la arquitectura de carpetas.
