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

echo "PSR-7 immutability regressions passed.\n";
