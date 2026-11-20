<?php

/**
 * @package     Localzet PSR-7
 * @link        https://github.com/localzet/PSR-7
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright © 2016-2026 Localzet Group. All rights reserved.
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License v3.0
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use localzet\PSR7\Request;
use localzet\PSR7\Response;
use localzet\PSR7\Uri;

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message);
    }
}

function assertThrowsType(callable $operation, string $expectedType, string $message): void
{
    set_error_handler(static fn (): bool => true);
    try {
        $operation();
    } catch (Throwable $exception) {
        restore_error_handler();
        if ($exception instanceof $expectedType) {
            return;
        }
        throw new RuntimeException($message . ': got ' . $exception::class, 0, $exception);
    }
    restore_error_handler();
    throw new RuntimeException($message . ': no exception was thrown');
}

$request = new Request('get', 'https://example.test/original', ['X-Test' => 'old'], 'body');
$changed = $request
    ->withMethod('post')
    ->withProtocolVersion('2.0')
    ->withHeader('X-Test', 'new')
    ->withAddedHeader('X-Extra', 'added')
    ->withRequestTarget('/changed')
    ->withUri(new Uri('https://changed.test/path'));

assertSameValue('GET', $request->getMethod(), 'withMethod changed the original request.');
assertSameValue('1.1', $request->getProtocolVersion(), 'withProtocolVersion changed the original request.');
assertSameValue(['old'], $request->getHeader('X-Test'), 'withHeader changed the original request.');
assertSameValue([], $request->getHeader('X-Extra'), 'withAddedHeader changed the original request.');
assertSameValue('/original', $request->getRequestTarget(), 'withRequestTarget changed the original request.');
assertSameValue('example.test', $request->getUri()->getHost(), 'withUri changed the original request.');
assertSameValue('POST', $changed->getMethod(), 'withMethod did not update the returned request.');
assertSameValue('2.0', $changed->getProtocolVersion(), 'withProtocolVersion did not update the returned request.');
assertSameValue(['new'], $changed->getHeader('X-Test'), 'withHeader did not update the returned request.');

$withHeaders = $request->withHeaders(['X-Test' => 'extra', 'X-New' => 'value']);
assertSameValue(['old'], $request->getHeader('X-Test'), 'withHeaders changed the original request.');
assertSameValue(['old', 'extra'], $withHeaders->getHeader('X-Test'), 'withHeaders did not update the returned request.');

$withoutHeader = $request->withoutHeader('X-Test');
assertSameValue(['old'], $request->getHeader('X-Test'), 'withoutHeader changed the original request.');
assertSameValue([], $withoutHeader->getHeader('X-Test'), 'withoutHeader did not update the returned request.');

$response = new Response(200, ['X-Test' => 'old']);
$updatedResponse = $response->withStatus(201)->withHeader('X-Test', 'new')->withBody(\localzet\PSR7\stream_for('new body'));
assertSameValue(200, $response->getStatusCode(), 'withStatus changed the original response.');
assertSameValue(['old'], $response->getHeader('X-Test'), 'withHeader changed the original response.');
assertSameValue('', (string)$response->getBody(), 'withBody changed the original response.');
assertSameValue(201, $updatedResponse->getStatusCode(), 'withStatus did not update the returned response.');
assertSameValue('new body', (string)$updatedResponse->getBody(), 'withBody did not update the returned response.');

$uri = new Uri('HTTPS://user:pass@EXAMPLE.TEST:443/a path?q=two words#part one');
assertSameValue('https', $uri->getScheme(), 'URI scheme must be normalized to lowercase.');
assertSameValue('example.test', $uri->getHost(), 'URI host must be normalized to lowercase.');
assertSameValue(null, $uri->getPort(), 'The default HTTPS port must be omitted.');
assertSameValue(
    'https://user:pass@example.test/a%20path?q=two%20words#part%20one',
    (string)$uri,
    'URI components must be encoded and composed consistently.'
);

$changedUri = $uri
    ->withScheme('http')
    ->withUserInfo('reader', 'token')
    ->withHost('API.EXAMPLE.TEST')
    ->withPort(8080)
    ->withPath('/new path')
    ->withQuery('filter=active now')
    ->withFragment('details section');
assertSameValue(
    'https://user:pass@example.test/a%20path?q=two%20words#part%20one',
    (string)$uri,
    'with* URI methods must leave the original instance unchanged.'
);
assertSameValue('http', $changedUri->getScheme(), 'withScheme did not update the returned URI.');
assertSameValue('reader:token', $changedUri->getUserInfo(), 'withUserInfo did not update the returned URI.');
assertSameValue('api.example.test', $changedUri->getHost(), 'withHost did not normalize the host.');
assertSameValue(8080, $changedUri->getPort(), 'withPort did not update the returned URI.');
assertSameValue('/new%20path', $changedUri->getPath(), 'withPath did not encode spaces.');
assertSameValue('filter=active%20now', $changedUri->getQuery(), 'withQuery did not encode spaces.');
assertSameValue('details%20section', $changedUri->getFragment(), 'withFragment did not encode spaces.');

$normalizedUri = \localzet\PSR7\UriNormalizer::normalize(new Uri('HTTP://EXAMPLE.TEST:80/a/../b/%7Ereader'));
assertSameValue(
    'http://example.test/b/~reader',
    (string)$normalizedUri,
    'Default URI normalization must remove default ports, decode unreserved characters, and remove dot segments.'
);
assertSameValue(
    true,
    \localzet\PSR7\UriNormalizer::isEquivalent(new Uri('http://EXAMPLE.TEST:80'), new Uri('http://example.test/')),
    'URIs that differ only by case, default port, or empty HTTP path must be equivalent.'
);

$resolvedUri = \localzet\PSR7\UriResolver::resolve(
    new Uri('https://example.test/a/b/c?q=old'),
    new Uri('../images/icon.png')
);
assertSameValue(
    'https://example.test/a/images/icon.png',
    (string)$resolvedUri,
    'Relative URI resolution must merge paths and remove dot segments.'
);

$stream = \localzet\PSR7\stream_for('hello', ['metadata' => ['source' => 'regression']]);
assertSameValue(true, $stream->isReadable(), 'A read/write stream must report readable.');
assertSameValue(true, $stream->isWritable(), 'A read/write stream must report writable.');
assertSameValue(true, $stream->isSeekable(), 'A temporary stream must report seekable.');
assertSameValue(5, $stream->getSize(), 'Stream size must match its contents.');
assertSameValue('regression', $stream->getMetadata('source'), 'Custom stream metadata must be available.');
assertSameValue('he', $stream->read(2), 'read must advance the stream by the requested length.');
assertSameValue(2, $stream->tell(), 'tell must return the current stream position.');
assertSameValue('', $stream->read(0), 'A zero-length read must return an empty string.');
$stream->seek(0, SEEK_END);
assertSameValue(1, $stream->write('!'), 'write must return the number of bytes written.');
assertSameValue(6, $stream->getSize(), 'Stream size must be refreshed after a write.');
$stream->rewind();
assertSameValue('hello!', $stream->getContents(), 'getContents must read from the current position.');
assertSameValue(true, $stream->eof(), 'eof must report the end after reading all contents.');
assertThrowsType(
    static fn () => $stream->read(-1),
    RuntimeException::class,
    'A negative read length must be rejected.'
);
$stream->close();

$writeOnlyResource = fopen('php://output', 'w');
$writeOnlyStream = new \localzet\PSR7\Stream($writeOnlyResource);
assertSameValue(false, $writeOnlyStream->isReadable(), 'A write-only stream must report not readable.');
assertThrowsType(
    static fn () => $writeOnlyStream->read(1),
    RuntimeException::class,
    'Reading from a write-only stream must fail.'
);
$readOnlyResource = fopen('php://input', 'r');
$readOnlyStream = new \localzet\PSR7\Stream($readOnlyResource);
assertSameValue(false, $readOnlyStream->isWritable(), 'A read-only stream must report not writable.');
assertThrowsType(
    static fn () => $readOnlyStream->write('x'),
    RuntimeException::class,
    'Writing to a read-only stream must fail.'
);
$writeOnlyStream->close();
$readOnlyStream->close();

$socketPair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
if ($socketPair === false) {
    throw new RuntimeException('Could not create a local stream pair for seekability coverage.');
}
$nonSeekableStream = new \localzet\PSR7\Stream($socketPair[0]);
assertSameValue(false, $nonSeekableStream->isSeekable(), 'A socket stream must report not seekable.');
assertThrowsType(
    static fn () => $nonSeekableStream->seek(0),
    RuntimeException::class,
    'Seeking a non-seekable stream must fail.'
);
$nonSeekableStream->close();
fclose($socketPair[1]);

$detachedStream = \localzet\PSR7\stream_for('body');
$detachedResource = $detachedStream->detach();
assertSameValue(true, is_resource($detachedResource), 'detach must return the underlying resource.');
assertSameValue(null, $detachedStream->getSize(), 'A detached stream must not report a size.');
assertSameValue([], $detachedStream->getMetadata(), 'A detached stream must not report metadata.');
assertSameValue(null, $detachedStream->getMetadata('uri'), 'A detached stream must not report keyed metadata.');
assertThrowsType(
    static fn () => $detachedStream->read(1),
    RuntimeException::class,
    'Reading a detached stream must fail.'
);
fclose($detachedResource);

echo "PSR-7 immutability regressions passed.\n";
