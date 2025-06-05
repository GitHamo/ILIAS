<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\FileDelivery\Delivery\ResponseBuilder;

use Psr\Http\Message\ResponseInterface;
use ILIAS\Filesystem\Stream\FileStream;
use ILIAS\HTTP\Response\ResponseHeader;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\RequestInterface;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class PHPResponseBuilder implements ResponseBuilder
{
    public function getName(): string
    {
        return 'php';
    }

    public function buildForStream(
        ServerRequestInterface $request,
        ResponseInterface $response,
        FileStream $stream,
    ): ResponseInterface {
        $response = $this->buildHeaders($response, $stream);
        $server_params = $request->getServerParams();
        if (isset($server_params['HTTP_RANGE']) && $this->supportPartial()) {
            return $this->deliverPartial($request, $response, $stream);
        }
        return $this->deliverFull($response, $stream);
    }

    protected function buildHeaders(
        ResponseInterface $response,
        FileStream $stream
    ): ResponseInterface {
        $uri = $stream->getMetadata('uri');
        if ($this->supportPartial()) {
            $response = $response->withHeader(ResponseHeader::ACCEPT_RANGES, 'bytes');
        }

        $response = $response->withHeader(ResponseHeader::CONTENT_LENGTH, $stream->getSize());
        try {
            $response = $response->withHeader(
                ResponseHeader::LAST_MODIFIED,
                date("D, j M Y H:i:s", filemtime($uri) ?: time()) . " GMT"
            );
        } catch (\Throwable) {
        }

        return $response->withHeader(ResponseHeader::ETAG, md5((string) $uri));
    }

    protected function deliverFull(
        ResponseInterface $response,
        FileStream $stream,
    ): ResponseInterface {
        $stream->rewind();
        return $response->withBody($stream);
    }

    protected function deliverPartial(
        RequestInterface|ServerRequestInterface $request,
        ResponseInterface $response,
        FileStream $stream,
    ): ResponseInterface {
        if (!$this->supportPartial()) {
            return $response;
        }

        $filesize = $stream->getSize();
        $range_header = $request->getHeaderLine('Range');
        $start = 0;
        $end = $filesize - 1;
        $status = 200;

        $headers = [
            ResponseHeader::ACCEPT_RANGES => 'bytes',
        ];

        if ($range_header && preg_match('/bytes=(\d+)-(\d*)/', $range_header, $matches)) {
            $start = (int) $matches[1];
            if (isset($matches[2]) && $matches[2] !== '') {
                $end = (int) $matches[2];
            }

            if ($start > $end || $end >= $filesize) {
                return $response
                    ->withStatus(416)
                    ->withHeader(ResponseHeader::CONTENT_RANGE, "bytes */$filesize");
            }

            $status = 206;
            $headers[ResponseHeader::CONTENT_RANGE] = "bytes $start-$end/$filesize";
        }

        $length = $end - $start + 1;
        $headers[ResponseHeader::CONTENT_LENGTH] = (string) $length;

        $handle = $stream->detach();
        if ($handle === false) {
            return $response->withStatus(500);
        }

        fseek($handle, $start);

        $buffer_size = 8192;
        while ($length > 0 && !feof($handle)) {
            $read_length = min($buffer_size, $length);
            $buffer = fread($handle, $read_length);
            if ($buffer === false || $buffer === '') {
                break;
            }
            $response->getBody()->write($buffer);
            $length -= strlen($buffer);
        }

        fclose($handle);

        foreach ($headers as $key => $value) {
            $response = $response->withHeader($key, $value);
        }

        return $response->withStatus($status);
    }

    public function supportPartial(): bool
    {
        return true;
    }

    public function supportStreaming(): bool
    {
        return true;
    }

    public function supportFileDeletion(): bool
    {
        return true;
    }

    public function supportsInlineDelivery(): bool
    {
        return true;
    }

    public function supportsAttachmentDelivery(): bool
    {
        return true;
    }
}
