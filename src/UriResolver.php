<?php

/**
 * @package     PSR-7 (Localzet Version)
 * @link        https://github.com/localzet/PSR-7
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2018-2024 Localzet Group
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

use Psr\Http\Message\UriInterface;

/**
 * Resolves a URI reference in the context of a base URI and the opposite way.
 *
 * @author Ivan Zorin
 * @author Tobias Schultze
 *
 * @link https://tools.ietf.org/html/rfc3986#section-5
 */
final class UriResolver
{
    /**
     * Removes dot segments from a path and returns the new path.
     *
     * @param string $path
     *
     * @return string
     * @link http://tools.ietf.org/html/rfc3986#section-5.2.4
     */
    public static function removeDotSegments(string $path): string
    {
        if ($path === '' || $path === '/') {
            return $path;
        }

        $results = [];
        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            if ($segment === '..') {
                array_pop($results);
            } elseif ($segment !== '.') {
                $results[] = $segment;
            }
        }

        $newPath = implode('/', $results);

        if ($path[0] === '/' && (!isset($newPath[0]) || $newPath[0] !== '/')) {
            // Re-add the leading slash if necessary for cases like "/.."
            $newPath = '/' . $newPath;
        } elseif ($newPath !== '' && ($segment === '.' || $segment === '..')) {
            // Add the trailing slash if necessary
            // If newPath is not empty, then $segment must be set and is the last segment from the foreach
            $newPath .= '/';
        }

        return $newPath;
    }

    /**
     * Converts the relative URI into a new URI that is resolved against the base URI.
     *
     * @param UriInterface $base Base URI
     * @param UriInterface $rel Relative URI
     *
     * @return UriInterface|Uri
     * @link http://tools.ietf.org/html/rfc3986#section-5.2
     */
    public static function resolve(UriInterface $base, UriInterface $rel): UriInterface|Uri
    {
        if ((string)$rel === '') {
            // we can simply return the same base URI instance for this same-document reference
            return $base;
        }

        if ($rel->getScheme() != '') {
            return $rel->withPath(self::removeDotSegments($rel->getPath()));
        }

        if ($rel->getAuthority() != '') {
            $targetAuthority = $rel->getAuthority();
            $targetPath = self::removeDotSegments($rel->getPath());
            $targetQuery = $rel->getQuery();
        } else {
            $targetAuthority = $base->getAuthority();
            if ($rel->getPath() === '') {
                $targetPath = $base->getPath();
                $targetQuery = $rel->getQuery() != '' ? $rel->getQuery() : $base->getQuery();
            } else {
                if ($rel->getPath()[0] === '/') {
                    $targetPath = $rel->getPath();
                } else {
                    if ($targetAuthority != '' && $base->getPath() === '') {
                        $targetPath = '/' . $rel->getPath();
                    } else {
                        $lastSlashPos = strrpos($base->getPath(), '/');
                        if ($lastSlashPos === false) {
                            $targetPath = $rel->getPath();
                        } else {
                            $targetPath = substr($base->getPath(), 0, $lastSlashPos + 1) . $rel->getPath();
                        }
                    }
                }
                $targetPath = self::removeDotSegments($targetPath);
                $targetQuery = $rel->getQuery();
            }
        }

        return new Uri(Uri::composeComponents(
            $base->getScheme(),
            $targetAuthority,
            $targetPath,
            $targetQuery,
            $rel->getFragment()
        ));
    }

    /**
     * Returns the target URI as a relative reference from the base URI.
     *
     * This method is the counterpart to resolve():
     *
     *    (string) $target === (string) UriResolver::resolve($base, UriResolver::relativize($base, $target))
     *
     * One use-case is to use the current request URI as base URI and then generate relative links in your documents
     * to reduce the document size or offer self-contained downloadable document archives.
     *
     *    $base = new Uri('http://example.com/a/b/');
     *    echo UriResolver::relativize($base, new Uri('http://example.com/a/b/c'));  // prints 'c'.
     *    echo UriResolver::relativize($base, new Uri('http://example.com/a/x/y'));  // prints '../x/y'.
     *    echo UriResolver::relativize($base, new Uri('http://example.com/a/b/?q')); // prints '?q'.
     *    echo UriResolver::relativize($base, new Uri('http://example.org/a/b/'));   // prints '//example.org/a/b/'.
     *
     * This method also accepts a target that is already relative and will try to relativize it further. Only a
     * relative-path reference will be returned as-is.
     *
     *    echo UriResolver::relativize($base, new Uri('/a/b/c'));  // prints 'c' as well
     *
     * @param UriInterface $base Base URI
     * @param UriInterface $target Target URI
     *
     * @return UriInterface The relative URI reference
     */
    public static function relativize(UriInterface $base, UriInterface $target): UriInterface
    {
        if ($target->getScheme() !== '' &&
            ($base->getScheme() !== $target->getScheme() || $target->getAuthority() === '' && $base->getAuthority() !== '')
        ) {
            return $target;
        }

        if (Uri::isRelativePathReference($target)) {
            // As the target is already highly relative we return it as-is. It would be possible to resolve
            // the target with `$target = self::resolve($base, $target);` and then try make it more relative
            // by removing a duplicate query. But let's not do that automatically.
            return $target;
        }

        if ($target->getAuthority() !== '' && $base->getAuthority() !== $target->getAuthority()) {
            return $target->withScheme('');
        }

        // We must remove the path before removing the authority because if the path starts with two slashes, the URI
        // would turn invalid. And we also cannot set a relative path before removing the authority, as that is also
        // invalid.
        $emptyPathUri = $target->withScheme('')->withPath('')->withUserInfo('')->withPort(null)->withHost('');

        if ($base->getPath() !== $target->getPath()) {
            return $emptyPathUri->withPath(self::getRelativePath($base, $target));
        }

        if ($base->getQuery() === $target->getQuery()) {
            // Only the target fragment is left. And it must be returned even if base and target fragment are the same.
            return $emptyPathUri->withQuery('');
        }

        // If the base URI has a query but the target has none, we cannot return an empty path reference as it would
        // inherit the base query component when resolving.
        if ($target->getQuery() === '') {
            $segments = explode('/', $target->getPath());
            $lastSegment = end($segments);

            return $emptyPathUri->withPath($lastSegment === '' ? './' : $lastSegment);
        }

        return $emptyPathUri;
    }

    private static function getRelativePath(UriInterface $base, UriInterface $target): string
    {
        $sourceSegments = explode('/', $base->getPath());
        $targetSegments = explode('/', $target->getPath());
        array_pop($sourceSegments);
        $targetLastSegment = array_pop($targetSegments);
        foreach ($sourceSegments as $i => $segment) {
            if (isset($targetSegments[$i]) && $segment === $targetSegments[$i]) {
                unset($sourceSegments[$i], $targetSegments[$i]);
            } else {
                break;
            }
        }
        $targetSegments[] = $targetLastSegment;
        $relativePath = str_repeat('../', count($sourceSegments)) . implode('/', $targetSegments);

        // A reference to am empty last segment or an empty first sub-segment must be prefixed with "./".
        // This also applies to a segment with a colon character (e.g., "file:colon") that cannot be used
        // as the first segment of a relative-path reference, as it would be mistaken for a scheme name.
        if ('' === $relativePath || str_contains(explode('/', $relativePath, 2)[0], ':')) {
            $relativePath = "./$relativePath";
        } elseif ('/' === $relativePath[0]) {
            if ($base->getAuthority() != '' && $base->getPath() === '') {
                // In this case an extra slash is added by resolve() automatically. So we must not add one here.
                $relativePath = ".$relativePath";
            } else {
                $relativePath = "./$relativePath";
            }
        }

        return $relativePath;
    }

    private function __construct()
    {
        // cannot be instantiated
    }
}
