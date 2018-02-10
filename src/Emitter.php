<?php
/**
 * @package   Atanvarno\Http\Util
 * @author    atanvarno69 <https://github.com/atanvarno69>
 * @copyright 2018 atanvarno.com
 * @license   https://opensource.org/licenses/MIT The MIT License
 */

namespace Atanvarno\Http\Util;

/** SPL use block. */
use RuntimeException;

/** PSR-7 use block. */
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Atanvarno\Http\Util\Emitter
 *
 * HTTP response emitter for a PHP SAPI environment.
 *
 * @api
 */
class Emitter
{
    /**
     * Emits a response for a PHP SAPI environment.
     *
     * Emits the status line and headers via the header() function, and the
     * body content via the output buffer.
     *
     * @param Response $response HTTP response to emit.
     *
     * @throws RuntimeException Headers already sent.
     * @throws RuntimeException Output has already been emitted.
     *
     * @return void
     */
    public function emit(Response $response): void
    {
        $this->assertNoPreviousOutput();
        $this->emitHeaders($response);
        $this->emitStatusLine($response);
        $this->emitBody($response);
    }

    private function emitBody(Response $response): void
    {
        echo $response->getBody();
    }

    private function assertNoPreviousOutput(): void
    {
        if (headers_sent()) {
            throw new RuntimeException(
                'Unable to emit response; headers already sent'
            );
        }
        if (ob_get_level() > 0 && ob_get_length() > 0) {
            throw new RuntimeException(
                'Output has been emitted previously; cannot emit response'
            );
        }
    }

    private function emitStatusLine(Response $response): void
    {
        $reasonPhrase = $response->getReasonPhrase();
        $statusCode   = $response->getStatusCode();
        header(sprintf(
            'HTTP/%s %d%s',
            $response->getProtocolVersion(),
            $statusCode,
            ($reasonPhrase ? ' ' . $reasonPhrase : '')
        ), true, $statusCode);
    }

    private function emitHeaders(Response $response): void
    {
        $statusCode = $response->getStatusCode();
        foreach ($response->getHeaders() as $header => $values) {
            $name  = $this->filterHeader($header);
            $first = $name === 'Set-Cookie' ? false : true;
            foreach ($values as $value) {
                header(sprintf(
                    '%s: %s',
                    $name,
                    $value
                ), $first, $statusCode);
                $first = false;
            }
        }
    }

    private function filterHeader(string $header): string
    {
        $filtered = str_replace('-', ' ', $header);
        $filtered = ucwords($filtered);
        return str_replace(' ', '-', $filtered);
    }
}
