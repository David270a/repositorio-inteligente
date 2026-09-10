<?php
// services/OrquestadorIA.php
// Coordina el flujo completo: extracción -> IA (clasificar/resumir/extraer) ->
// generación de embeddings -> almacenamiento de resultados (RF-05 a RF-08).

class OrquestadorIA
{
    private const CATEGORIAS = ['Contrato', 'Factura', 'Informe', 'Correspondencia'];

    private const CAMPOS_POR_CATEGORIA = [
        'Contrato' => ['partes', 'fecha_firma', 'vigencia'],
        'Factura' => ['numero_factura', 'monto', 'fecha', 'proveedor'],
        'Informe' => ['autor', 'fecha', 'conclusion_principal'],
        'Correspondencia' => ['remitente', 'destinatario', 'fecha'],
    ];

    private ClienteIA $clienteIA;

    public function __construct(?ClienteIA $clienteIA = null)
    {
        $this->clienteIA = $clienteIA ?? new ClienteIA();
    }

    /**
     * Ejecuta el flujo completo de procesamiento para un documento ya cargado.
     * Nunca lanza excepción hacia afuera: cualquier error queda registrado
     * en procesamiento_log con estado 'error' (RF-12).
     */
    public function procesar(array $documento): void
    {
        $id = (int) $documento['id_documento'];

        try {
            ProcesamientoLog::registrar($id, 'procesando');

            $rutaAbsoluta = __DIR__ . '/../public/' . $documento['ruta_almacenamiento'];
            $texto = ExtractorTexto::extraer($rutaAbsoluta, $documento['tipo']);

            if (trim($texto) === '') {
                throw new Exception('No se pudo extraer texto del documento (¿está escaneado como imagen?).');
            }

            $resultado = $this->clasificarResumirYExtraer($texto);

            Documento::actualizarResultadosIA($id, $texto, $resultado['categoria'], $resultado['resumen']);

            MetadatoExtraido::eliminarPorDocumento($id);
            foreach ($resultado['metadatos'] as $campo => $valor) {
                MetadatoExtraido::crear($id, (string) $campo, (string) $valor);
            }

            $this->generarYGuardarEmbeddings($id, $texto);

            ProcesamientoLog::registrar($id, 'completado');
        } catch (Throwable $e) {
            ProcesamientoLog::registrar($id, 'error', $e->getMessage());
        }
    }

    /** Arma el prompt estructurado y parsea la respuesta JSON del modelo (RF-06, RF-07, RF-08). */
    private function clasificarResumirYExtraer(string $texto): array
    {
        $categorias = implode(', ', self::CATEGORIAS);
        $camposEjemplo = json_encode(self::CAMPOS_POR_CATEGORIA, JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
Eres un asistente que analiza documentos empresariales. Responde ÚNICAMENTE con un
objeto JSON válido, sin texto adicional, sin marcadores de código, con esta forma exacta:

{"categoria": "una de estas opciones: {$categorias}",
 "resumen": "resumen del documento en máximo 150 palabras",
 "metadatos": {"campo1": "valor1", "campo2": "valor2"}}

Los campos de "metadatos" a extraer dependen de la categoría detectada, por ejemplo: {$camposEjemplo}.
Si no encuentras un dato, usa el valor "no encontrado".

Documento a analizar:
---
{$texto}
---
PROMPT;

        $textoRespuesta = $this->clienteIA->generarTexto($prompt);
        $json = $this->extraerJson($textoRespuesta);

        if (!isset($json['categoria'], $json['resumen'], $json['metadatos'])) {
            throw new Exception('La respuesta de la IA no tiene el formato JSON esperado: ' . $textoRespuesta);
        }

        if (!in_array($json['categoria'], self::CATEGORIAS, true)) {
            $json['categoria'] = 'Informe'; // categoría de respaldo si el modelo devuelve algo fuera de lo cerrado
        }

        return $json;
    }

    /** Fragmenta el texto y genera+guarda un embedding por fragmento (para RAG). */
    private function generarYGuardarEmbeddings(int $idDocumento, string $texto): void
    {
        Embedding::eliminarPorDocumento($idDocumento);
        $fragmentos = ExtractorTexto::fragmentar($texto, 800);

        foreach ($fragmentos as $fragmento) {
            if (trim($fragmento) === '') {
                continue;
            }
            $vector = $this->clienteIA->generarEmbedding($fragmento);
            Embedding::crear($idDocumento, $fragmento, $vector);
        }
    }

    /** Extrae el primer bloque JSON válido de la respuesta del modelo (tolerante a texto extra). */
    private function extraerJson(string $texto): array
    {
        $texto = trim($texto);
        $texto = preg_replace('/^```json|```$/m', '', $texto);

        $inicio = strpos($texto, '{');
        $fin = strrpos($texto, '}');

        if ($inicio === false || $fin === false) {
            throw new Exception('No se encontró un JSON en la respuesta de la IA.');
        }

        $fragmentoJson = substr($texto, $inicio, $fin - $inicio + 1);
        $datos = json_decode($fragmentoJson, true);

        if (!is_array($datos)) {
            throw new Exception('El JSON devuelto por la IA no se pudo interpretar.');
        }

        return $datos;
    }
}
