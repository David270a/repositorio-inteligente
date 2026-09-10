<?php
// services/BuscadorSemantico.php
// Implementa el lado de "recuperación" de RAG: dado un embedding de pregunta,
// encuentra los fragmentos de documentos más similares (RF-10).

class BuscadorSemantico
{
    private ClienteIA $clienteIA;

    public function __construct(?ClienteIA $clienteIA = null)
    {
        $this->clienteIA = $clienteIA ?? new ClienteIA();
    }

    /**
     * Responde una pregunta en lenguaje natural usando los documentos del usuario como contexto (RAG).
     * Si $idDocumento se indica, la búsqueda de contexto se restringe a ese documento únicamente.
     * Devuelve ['respuesta' => string, 'fuentes' => array de nombres de archivo]
     */
    public function responderPregunta(string $pregunta, int $idUsuario, int $topN = 5, ?int $idDocumento = null): array
    {
        $fragmentosDisponibles = Embedding::obtenerPorUsuario($idUsuario, $idDocumento);

        if (empty($fragmentosDisponibles)) {
            return [
                'respuesta' => $idDocumento !== null
                    ? 'Ese documento aún no tiene contenido procesado para responder preguntas sobre él.'
                    : 'Aún no hay documentos procesados en tu repositorio para responder esta pregunta.',
                'fuentes' => [],
            ];
        }

        $embeddingPregunta = $this->clienteIA->generarEmbedding($pregunta);
        $relevantes = $this->fragmentosMasRelevantes($embeddingPregunta, $fragmentosDisponibles, $topN);

        if (empty($relevantes) || $relevantes[0]['score'] < 0.05) {
            return [
                'respuesta' => 'No encontré información relevante en el repositorio para responder esa pregunta.',
                'fuentes' => [],
            ];
        }

        $contexto = '';
        $fuentes = [];
        foreach ($relevantes as $fragmento) {
            $contexto .= "[Fuente: {$fragmento['nombre_archivo']}]\n{$fragmento['fragmento_texto']}\n\n";
            $fuentes[$fragmento['nombre_archivo']] = true;
        }

        $prompt = <<<PROMPT
Responde la pregunta del usuario basándote ÚNICAMENTE en los siguientes fragmentos de documentos.
Si la información no está en los fragmentos, dilo explícitamente en vez de inventar una respuesta.
Cita el nombre del documento fuente cuando sea relevante.

Fragmentos de contexto:
{$contexto}

Pregunta del usuario: {$pregunta}
PROMPT;

        $respuesta = $this->clienteIA->generarTexto($prompt);

        return [
            'respuesta' => $respuesta,
            'fuentes' => array_keys($fuentes),
        ];
    }

    /** @return array Los $topN fragmentos más similares, cada uno con su 'score' de similitud */
    /**
     * Chat libre: responde sin restringirse al contenido de ningún documento
     * (modo conversación general con la IA, sin RAG).
     */
    public function responderLibre(string $pregunta): array
    {
        $prompt = <<<PROMPT
Eres un asistente conversacional útil dentro de un repositorio inteligente de documentos.
El usuario te está haciendo una pregunta general, no necesariamente relacionada con sus documentos.
Responde de forma clara y directa.

Pregunta del usuario: {$pregunta}
PROMPT;

        $respuesta = $this->clienteIA->generarTexto($prompt);

        return [
            'respuesta' => $respuesta,
            'fuentes' => [],
        ];
    }

    private function fragmentosMasRelevantes(array $embeddingPregunta, array $fragmentos, int $topN): array
    {
        foreach ($fragmentos as &$fragmento) {
            $vector = json_decode($fragmento['vector'], true) ?? [];
            $fragmento['score'] = $this->similitudCoseno($embeddingPregunta, $vector);
        }
        unset($fragmento);

        usort($fragmentos, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($fragmentos, 0, $topN);
    }

    private function similitudCoseno(array $a, array $b): float
    {
        $longitud = min(count($a), count($b));
        if ($longitud === 0) {
            return 0.0;
        }

        $producto = 0.0;
        $normaA = 0.0;
        $normaB = 0.0;

        for ($i = 0; $i < $longitud; $i++) {
            $producto += $a[$i] * $b[$i];
            $normaA += $a[$i] ** 2;
            $normaB += $b[$i] ** 2;
        }

        $denominador = sqrt($normaA) * sqrt($normaB);
        return $denominador > 0 ? $producto / $denominador : 0.0;
    }
}
