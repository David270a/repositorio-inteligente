<?php
// services/ClienteIA.php
// Encapsula las llamadas HTTPS (cURL) a la API externa de Inteligencia Artificial.
// Soporta Anthropic (Claude) u OpenAI como proveedor de generación de texto,
// según la variable de entorno IA_PROVEEDOR.

class ClienteIA
{
    /**
     * Envía un prompt al modelo de lenguaje y devuelve el texto de la respuesta.
     * @throws Exception si la llamada falla o la respuesta no es válida
     */
    public function generarTexto(string $prompt): string
    {
        $proveedor = $_ENV['IA_PROVEEDOR'] ?? 'anthropic';

        return $proveedor === 'openai'
            ? $this->llamarOpenAI($prompt)
            : $this->llamarAnthropic($prompt);
    }

    private function llamarAnthropic(string $prompt): string
    {
        // IA_ENDPOINT_ANTHROPIC permite sobrescribir la URL (usado en pruebas con un mock local; ver Plan de Pruebas 04).
        $url = !empty($_ENV['IA_ENDPOINT_ANTHROPIC']) ? $_ENV['IA_ENDPOINT_ANTHROPIC'] : 'https://api.anthropic.com/v1/messages';
        $respuesta = $this->post($url, [
            'x-api-key: ' . $_ENV['IA_API_KEY'],
            'anthropic-version: 2023-06-01',
            'Content-Type: application/json',
        ], [
            'model' => $_ENV['IA_MODEL'],
            'max_tokens' => 1024,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ]);

        if (!isset($respuesta['content'][0]['text'])) {
            throw new Exception('Respuesta inesperada de la API de Anthropic: ' . json_encode($respuesta));
        }

        return $respuesta['content'][0]['text'];
    }

    private function llamarOpenAI(string $prompt): string
    {
        // IA_ENDPOINT_OPENAI permite apuntar a un servicio compatible con la API de OpenAI,
        // por ejemplo Groq (https://api.groq.com/openai/v1/chat/completions), que ofrece
        // una capa gratuita. Ver README para instrucciones.
        $url = !empty($_ENV['IA_ENDPOINT_OPENAI']) ? $_ENV['IA_ENDPOINT_OPENAI'] : 'https://api.openai.com/v1/chat/completions';
        $respuesta = $this->post($url, [
            'Authorization: Bearer ' . $_ENV['IA_API_KEY'],
            'Content-Type: application/json',
        ], [
            'model' => $_ENV['IA_MODEL'],
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ]);

        if (!isset($respuesta['choices'][0]['message']['content'])) {
            throw new Exception('Respuesta inesperada de la API de OpenAI: ' . json_encode($respuesta));
        }

        return $respuesta['choices'][0]['message']['content'];
    }

    /**
     * Genera el embedding (vector numérico) de un texto.
     * Si no hay EMBEDDINGS_API_KEY configurada, usa un embedding local
     * simplificado (hashing) para que el flujo de RAG funcione sin costo
     * durante el desarrollo y las pruebas académicas.
     */
    public function generarEmbedding(string $texto): array
    {
        if (!empty($_ENV['EMBEDDINGS_API_KEY'])) {
            $respuesta = $this->post('https://api.openai.com/v1/embeddings', [
                'Authorization: Bearer ' . $_ENV['EMBEDDINGS_API_KEY'],
                'Content-Type: application/json',
            ], [
                'model' => $_ENV['EMBEDDINGS_MODEL'],
                'input' => $texto,
            ]);

            if (!isset($respuesta['data'][0]['embedding'])) {
                throw new Exception('Respuesta inesperada del servicio de embeddings: ' . json_encode($respuesta));
            }

            return $respuesta['data'][0]['embedding'];
        }

        return $this->embeddingLocalSimplificado($texto);
    }

    /**
     * Embedding local simplificado basado en hashing de palabras.
     * No es semánticamente tan preciso como un modelo entrenado, pero permite
     * demostrar el flujo completo de RAG (fragmentación, vectorización,
     * similitud de coseno) sin depender de una API de pago.
     */
    private function embeddingLocalSimplificado(string $texto, int $dimensiones = 128): array
    {
        $vector = array_fill(0, $dimensiones, 0.0);
        $palabras = preg_split('/\W+/u', mb_strtolower($texto), -1, PREG_SPLIT_NO_EMPTY);

        foreach ($palabras as $palabra) {
            $indice = crc32($palabra) % $dimensiones;
            $vector[$indice] += 1.0;
        }

        $norma = sqrt(array_sum(array_map(fn($v) => $v * $v, $vector)));
        if ($norma > 0) {
            $vector = array_map(fn($v) => $v / $norma, $vector);
        }

        return $vector;
    }

    /**
     * @throws Exception si hay un error de conexión con la API externa
     */
    private function post(string $url, array $headers, array $cuerpo): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($cuerpo),
            CURLOPT_TIMEOUT => 60,
        ]);

        $respuesta = curl_exec($ch);
        $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($respuesta === false) {
            throw new Exception('Error de conexión con la API de IA: ' . $error);
        }

        $datos = json_decode($respuesta, true);

        if ($codigoHttp >= 400) {
            throw new Exception("La API de IA respondió con error HTTP {$codigoHttp}: " . json_encode($datos));
        }

        return $datos ?? [];
    }
}
