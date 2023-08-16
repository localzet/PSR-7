<?php

/**
 * @package     PSR-7 (Localzet Version)
 * @link        https://github.com/localzet/PSR-7
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2018-2023 Localzet Group
 * @license     https://www.gnu.org/licenses/agpl AGPL-3.0 license
 *
 *              This program is free software: you can redistribute it and/or modify
 *              it under the terms of the GNU Affero General Public License as
 *              published by the Free Software Foundation, either version 3 of the
 *              License, or (at your option) any later version.
 *
 *              This program is distributed in the hope that it will be useful,
 *              but WITHOUT ANY WARRANTY; without even the implied warranty of
 *              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *              GNU Affero General Public License for more details.
 *
 *              You should have received a copy of the GNU Affero General Public License
 *              along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace localzet\PSR7;

use InvalidArgumentException;
use localzet\PSR\Http\Message\StreamInterface;

/**
 * Converts Localzet streams into PHP stream resources.
 */
class StreamWrapper
{
    /** @var resource */
    public $context;

    /** @var StreamInterface */
    private StreamInterface $stream;

    /** @var string r, r+, or w */
    private string $mode;

    /**
     * Returns a resource representing the stream.
     *
     * @param StreamInterface $stream The stream to get a resource for
     *
     * @return resource
     * @throws InvalidArgumentException if stream is not readable or writable
     */
    public static function getResource(StreamInterface $stream)
    {
        self::register();

        if ($stream->isReadable()) {
            $mode = $stream->isWritable() ? 'r+' : 'r';
        } elseif ($stream->isWritable()) {
            $mode = 'w';
        } else {
            throw new InvalidArgumentException('The stream must be readable, writable, or both.');
        }

        return fopen('localzet://stream', $mode, null, self::createStreamContext($stream));
    }

    /**
     * Creates a stream context that can be used to open a stream as a php stream resource.
     *
     * @param StreamInterface $stream
     *
     * @return resource
     */
    public static function createStreamContext(StreamInterface $stream)
    {
        return stream_context_create([
            'localzet' => ['stream' => $stream]
        ]);
    }

    /**
     * Registers the stream wrapper if needed
     */
    public static function register(): void
    {
        if (!in_array('localzet', stream_get_wrappers())) {
            stream_wrapper_register('localzet', __CLASS__);
        }
    }

    public function stream_open($path, $mode, $options, &$opened_path): bool
    {
        $options = stream_context_get_options($this->context);

        if (!isset($options['localzet']['stream'])) {
            return false;
        }

        $this->mode = $mode;
        $this->stream = $options['localzet']['stream'];

        return true;
    }

    public function stream_read($count): string
    {
        return $this->stream->read($count);
    }

    public function stream_write($data): int
    {
        return $this->stream->write($data);
    }

    public function stream_tell(): int
    {
        return $this->stream->tell();
    }

    public function stream_eof(): bool
    {
        return $this->stream->eof();
    }

    public function stream_seek($offset, $whence): true
    {
        $this->stream->seek($offset, $whence);

        return true;
    }

    public function stream_cast($cast_as)
    {
        $stream = clone($this->stream);

        return $stream->detach();
    }

    public function stream_stat(): array
    {
        static $modeMap = [
            'r' => 33060,
            'rb' => 33060,
            'r+' => 33206,
            'w' => 33188,
            'wb' => 33188
        ];

        return [
            'dev' => 0,
            'ino' => 0,
            'mode' => $modeMap[$this->mode],
            'nlink' => 0,
            'uid' => 0,
            'gid' => 0,
            'rdev' => 0,
            'size' => $this->stream->getSize() ?: 0,
            'atime' => 0,
            'mtime' => 0,
            'ctime' => 0,
            'blksize' => 0,
            'blocks' => 0
        ];
    }

    public function url_stat($path, $flags): array
    {
        return [
            'dev' => 0,
            'ino' => 0,
            'mode' => 0,
            'nlink' => 0,
            'uid' => 0,
            'gid' => 0,
            'rdev' => 0,
            'size' => 0,
            'atime' => 0,
            'mtime' => 0,
            'ctime' => 0,
            'blksize' => 0,
            'blocks' => 0
        ];
    }
}
