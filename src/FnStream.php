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

use BadMethodCallException;
use LogicException;
use Psr\Http\Message\StreamInterface;

/**
 * Compose stream implementations based on a hash of functions.
 *
 * Allows for easy testing and extension of a provided stream without needing
 * to create a concrete class for a simple extension point.
 */
class FnStream implements StreamInterface
{
    /** @var array */
    private $methods;

    /** @var array Methods that must be implemented in the given array */
    private static $slots = ['__toString', 'close', 'detach', 'rewind',
        'getSize', 'tell', 'eof', 'isSeekable', 'seek', 'isWritable', 'write',
        'isReadable', 'read', 'getContents', 'getMetadata'];

    /**
     * @param array $methods Hash of method name to a callable.
     */
    public function __construct(array $methods)
    {
        $this->methods = $methods;

        // Create the functions on the class
        foreach ($methods as $name => $fn) {
            $this->{'_fn_' . $name} = $fn;
        }
    }

    /**
     * Lazily determine which methods are not implemented.
     * @throws BadMethodCallException
     */
    public function __get($name)
    {
        throw new BadMethodCallException(str_replace('_fn_', '', $name)
            . '() is not implemented in the FnStream');
    }

    /**
     * The close method is called on the underlying stream only if possible.
     */
    public function __destruct()
    {
        if (isset($this->_fn_close)) {
            call_user_func($this->_fn_close);
        }
    }

    /**
     * An unserialize would allow the __destruct to run when the unserialized value goes out of scope.
     * @throws LogicException
     */
    public function __wakeup()
    {
        throw new LogicException('FnStream should never be unserialized');
    }

    /**
     * Adds custom functionality to an underlying stream by intercepting
     * specific method calls.
     *
     * @param StreamInterface $stream Stream to decorate
     * @param array $methods Hash of method name to a closure
     *
     * @return FnStream
     */
    public static function decorate(StreamInterface $stream, array $methods): FnStream
    {
        // If any of the required methods were not provided, then simply
        // proxy to the decorated stream.
        foreach (array_diff(self::$slots, array_keys($methods)) as $diff) {
            $methods[$diff] = [$stream, $diff];
        }

        return new self($methods);
    }

    public function __toString()
    {
        return call_user_func($this->_fn___toString);
    }

    public function close()
    {
        return call_user_func($this->_fn_close);
    }

    public function detach()
    {
        return call_user_func($this->_fn_detach);
    }

    public function getSize()
    {
        return call_user_func($this->_fn_getSize);
    }

    public function tell()
    {
        return call_user_func($this->_fn_tell);
    }

    public function eof()
    {
        return call_user_func($this->_fn_eof);
    }

    public function isSeekable()
    {
        return call_user_func($this->_fn_isSeekable);
    }

    public function rewind(): void
    {
        call_user_func($this->_fn_rewind);
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        call_user_func($this->_fn_seek, $offset, $whence);
    }

    public function isWritable()
    {
        return call_user_func($this->_fn_isWritable);
    }

    public function write($string)
    {
        return call_user_func($this->_fn_write, $string);
    }

    public function isReadable()
    {
        return call_user_func($this->_fn_isReadable);
    }

    public function read($length)
    {
        return call_user_func($this->_fn_read, $length);
    }

    public function getContents()
    {
        return call_user_func($this->_fn_getContents);
    }

    public function getMetadata($key = null)
    {
        return call_user_func($this->_fn_getMetadata, $key);
    }
}
